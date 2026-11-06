<?php

namespace App\Http\Controllers\Practitioner;

use App\Enums\ArticleStatus;
use App\Enums\ArticleType;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Setting;
use App\Models\Specialty;
use App\Models\User;
use App\Notifications\ContentSubmitted;
use App\Rules\MaxWords;
use App\Services\Media\ImageService;
use App\Support\Slug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Stevebauman\Purify\Facades\Purify;

/**
 * Practitioner blog (client doc §7): standard 600–1,000 words, guides 1,200–1,500,
 * live word counter, featured image compressed to WebP ≤ 200 KB at 1200×630,
 * admin approval before publishing.
 */
class ArticleController extends Controller
{
    public function index(Request $request): View
    {
        return view('practitioner.articles.index', [
            'articles' => $request->user()->practitionerProfile->articles()->latest('updated_at')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return $this->form(new Article(['type' => ArticleType::Standard]));
    }

    public function edit(Request $request, Article $article): View
    {
        $this->authorizeOwner($request, $article);

        return $this->form($article);
    }

    public function store(Request $request, ImageService $images): RedirectResponse
    {
        $article = new Article;
        $article->forceFill([
            'author_id' => $request->user()->id,
            'practitioner_profile_id' => $request->user()->practitionerProfile->id,
            'status' => ArticleStatus::Draft,
        ]);

        return $this->save($request, $article, $images);
    }

    public function update(Request $request, Article $article, ImageService $images): RedirectResponse
    {
        $this->authorizeOwner($request, $article);
        abort_if($article->status === ArticleStatus::Pending, 422, 'This article is being reviewed.');

        return $this->save($request, $article, $images);
    }

    private function save(Request $request, Article $article, ImageService $images): RedirectResponse
    {
        $submitting = $request->input('action') === 'submit';
        $type = ArticleType::tryFrom((string) $request->input('type')) ?? ArticleType::Standard;
        [$min, $max] = $type->wordRange();
        $maxUploadKb = (int) Setting::get('article_image_max_upload_kb', 2048);

        $data = $request->validate([
            'type' => ['required', Rule::enum(ArticleType::class)],
            'title' => ['required', 'string', 'min:10', 'max:180'],
            'specialty_id' => ['nullable', Rule::exists('specialties', 'id')->whereNull('parent_id')],
            'excerpt' => ['nullable', 'string', 'max:300'],
            'body' => ['required', 'string', 'max:60000', ...($submitting ? [new MaxWords($max, $min)] : [])],
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:170'],
            'featured_image' => [Rule::requiredIf($submitting && ! $article->featured_image_path), 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.$maxUploadKb, 'dimensions:min_width=800,min_height=400'],
        ], [
            'featured_image.max' => 'Image is too large. Please upload one under '.round($maxUploadKb / 1024).'MB. We compress it to under 200KB automatically.',
            'featured_image.dimensions' => 'Please use an image at least 800×400 px (1200×630 recommended).',
            'featured_image.required' => 'Add a featured image before submitting.',
        ]);

        $body = Purify::clean($data['body']);
        $article->fill([
            'type' => $type,
            'title' => $data['title'],
            'specialty_id' => $data['specialty_id'] ?? null,
            'excerpt' => ($data['excerpt'] ?? null) ?: Str::limit(trim(strip_tags($body)), 220),
            'body' => $body,
            'word_count' => MaxWords::count($body),
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
        ]);
        if (! $article->slug || $article->status !== ArticleStatus::Approved) {
            $article->slug = Slug::unique(Article::class, $data['title'], ignoreId: $article->id);
        }
        if ($request->hasFile('featured_image')) {
            $article->featured_image_path = $images->storeArticleImage($request->file('featured_image'), (int) Setting::get('article_image_target_kb', 200), $article->featured_image_path);
        }

        if ($submitting) {
            $article->forceFill(['status' => ArticleStatus::Pending, 'submitted_at' => now(), 'rejection_reason' => null]);
        } elseif ($article->status === ArticleStatus::Approved) {
            // Editing a published article sends it back for a quick re-check.
            $article->forceFill(['status' => ArticleStatus::Pending, 'submitted_at' => now()]);
            $submitting = true;
        }
        $article->save();

        if ($submitting) {
            Notification::send(User::query()->where('role', Role::Admin)->get(), new ContentSubmitted('article', $article->title, route('admin.articles.show', $article)));
        }

        return redirect()->route('practitioner.articles.edit', $article)
            ->with('success', $submitting ? 'Submitted for review. We\'ll email you when it\'s published.' : 'Draft saved.');
    }

    private function form(Article $article): View
    {
        return view('practitioner.articles.form', [
            'article' => $article,
            'specialties' => Specialty::tree(),
            'types' => ArticleType::cases(),
        ]);
    }

    private function authorizeOwner(Request $request, Article $article): void
    {
        abort_unless($article->practitioner_profile_id === $request->user()->practitionerProfile?->id, 404);
    }
}
