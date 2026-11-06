<?php

namespace App\Services\Search;

use App\Models\Condition;
use App\Models\PractitionerProfile;
use App\Models\Specialty;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Directory search. All filters are optional and combine with AND.
 * Within a multi-select (languages, tags) any match qualifies.
 */
class PractitionerSearch
{
    public const PER_PAGE = 12;

    public const SORTS = [
        'recommended' => 'Recommended',
        'rating' => 'Highest rated',
        'fee_low' => 'Fee: low to high',
        'fee_high' => 'Fee: high to low',
        'experience' => 'Most experienced',
    ];

    /** @param array<string, mixed> $f normalised filters (see normalise()) */
    public function query(array $f): Builder
    {
        $query = PractitionerProfile::query()
            ->public()
            ->with(['user:id,name', 'primarySpecialty:id,name,slug,practitioner_label', 'city:id,name', 'country:id,name,iso2', 'languages:id,name,code', 'conditions:id,name,slug']);

        if ($f['specialty']) {
            $ids = Specialty::query()->where('id', $f['specialty']->id)->orWhere('parent_id', $f['specialty']->id)->pluck('id');
            $query->where(fn (Builder $q) => $q
                ->where('primary_specialty_id', $f['specialty']->id)
                ->orWhereHas('subSpecialties', fn ($s) => $s->whereIn('specialties.id', $ids)));
        }

        if ($f['sub']) {
            $query->whereHas('subSpecialties', fn ($s) => $s->where('specialties.id', $f['sub']->id));
        }

        if ($f['condition']) {
            // Practitioners tagged with the symptom, or whose specialty treats it.
            $specialtyIds = $f['condition']->specialties()->pluck('specialties.id');
            $query->where(fn (Builder $q) => $q
                ->whereHas('conditions', fn ($c) => $c->where('conditions.id', $f['condition']->id))
                ->orWhereIn('primary_specialty_id', $specialtyIds));
        }

        if ($f['gender']) {
            $query->where('gender', $f['gender']);
        }

        if ($f['languages']) {
            $query->whereHas('languages', fn ($l) => $l->whereIn('code', $f['languages']));
        }

        if ($f['tags']) {
            $query->whereHas('approvedTags', fn ($t) => $t->whereIn('tags.slug', $f['tags']));
        }

        if ($f['country']) {
            $query->where('country_id', $f['country']->id);
        }

        if ($f['city']) {
            $query->where('city_id', $f['city']->id);
        }

        if ($f['currency']) {
            $query->where('fee_currency', $f['currency']);
            if ($f['fee_min'] !== null) {
                $query->where('fee_minor', '>=', $f['fee_min'] * 100);
            }
            if ($f['fee_max'] !== null) {
                $query->where('fee_minor', '<=', $f['fee_max'] * 100);
            }
        }

        if ($f['q'] !== '') {
            $this->applyText($query, $f['q']);
        }

        return match ($f['sort']) {
            'rating' => $query->orderByDesc('rating_avg')->orderByDesc('reviews_count'),
            'fee_low' => $query->orderBy('fee_currency')->orderBy('fee_minor'),
            'fee_high' => $query->orderBy('fee_currency')->orderByDesc('fee_minor'),
            'experience' => $query->orderByDesc('years_experience'),
            default => $query->orderByDesc('is_featured')->orderByDesc('rating_avg')->orderByDesc('reviews_count')->orderBy('id'),
        };
    }

    public function paginate(array $filters): LengthAwarePaginator
    {
        return $this->query($filters)->paginate(self::PER_PAGE)->withQueryString();
    }

    /** Free text: name, headline/bio, specialty name or symptom name. */
    private function applyText(Builder $query, string $text): void
    {
        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $text).'%';

        $specialtyIds = Specialty::query()->where('name', 'like', $like)->pluck('id');
        $conditionIds = Condition::query()->where('name', 'like', $like)->pluck('id');

        $query->where(function (Builder $q) use ($like, $specialtyIds, $conditionIds) {
            $q->whereHas('user', fn ($u) => $u->where('name', 'like', $like))
                ->orWhere('headline', 'like', $like)
                ->orWhere('qualifications', 'like', $like);

            if ($specialtyIds->isNotEmpty()) {
                $q->orWhereIn('primary_specialty_id', $specialtyIds)
                    ->orWhereHas('subSpecialties', fn ($s) => $s->whereIn('specialties.id', $specialtyIds));
            }
            if ($conditionIds->isNotEmpty()) {
                $q->orWhereHas('conditions', fn ($c) => $c->whereIn('conditions.id', $conditionIds));
            }
        });
    }

    /** Turn raw request input into typed, validated filters. Unknown values are ignored, never errors. */
    public function normalise(array $input, array $fixed = []): array
    {
        $specialty = $fixed['specialty'] ?? (filled($input['specialty'] ?? null)
            ? Specialty::query()->whereNull('parent_id')->where('slug', $input['specialty'])->first() : null);

        $sub = filled($input['sub'] ?? null)
            ? Specialty::query()->whereNotNull('parent_id')->where('slug', $input['sub'])->first() : null;

        $condition = $fixed['condition'] ?? (filled($input['condition'] ?? null)
            ? Condition::query()->active()->where('slug', $input['condition'])->first() : null);

        $country = $fixed['country'] ?? (filled($input['country'] ?? null)
            ? \App\Models\Country::query()->where('iso2', strtoupper((string) $input['country']))->first() : null);

        $city = $fixed['city'] ?? (filled($input['city'] ?? null)
            ? \App\Models\City::query()->where('slug', $input['city'])->when($country, fn ($q) => $q->where('country_id', $country->id))->first() : null);

        $currency = strtoupper((string) ($input['currency'] ?? ''));
        if (! in_array($currency, ['PKR', 'USD'], true)) {
            $currency = $country ? ($country->isPakistan() ? 'PKR' : null) : null;
        }

        $toInt = fn ($v) => is_numeric($v) && $v >= 0 ? (int) $v : null;

        return [
            'q' => Str::limit(trim((string) ($input['q'] ?? '')), 80, ''),
            'specialty' => $specialty,
            'sub' => $sub,
            'condition' => $condition,
            'gender' => in_array($input['gender'] ?? null, ['male', 'female'], true) ? $input['gender'] : null,
            'languages' => array_values(array_filter((array) ($input['languages'] ?? []), fn ($c) => is_string($c) && preg_match('/^[a-z]{2,3}$/', $c))),
            'tags' => array_values(array_filter((array) ($input['tags'] ?? []), fn ($t) => is_string($t) && preg_match('/^[a-z0-9-]+$/', $t))),
            'country' => $country,
            'city' => $city,
            'currency' => $currency ?: null,
            'fee_min' => $currency ? $toInt($input['fee_min'] ?? null) : null,
            'fee_max' => $currency ? $toInt($input['fee_max'] ?? null) : null,
            'sort' => array_key_exists($input['sort'] ?? '', self::SORTS) ? $input['sort'] : 'recommended',
        ];
    }

    /** Suggestions for the home/search autocomplete. */
    public function suggest(string $q): array
    {
        $q = trim($q);
        if (mb_strlen($q) < 2) {
            return ['groups' => []];
        }
        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%';

        $specialties = Specialty::query()->where('is_active', true)->where('name', 'like', $like)->with('parent:id,slug')->limit(5)->get()
            ->map(fn ($s) => [
                'label' => $s->name,
                'hint' => $s->parent_id ? 'Sub-specialty' : 'Specialty',
                'url' => $s->parent_id ? route('directory', ['specialty' => $s->parent->slug, 'sub' => $s->slug]) : route('specialties.show', $s),
            ]);

        $conditions = Condition::query()->active()->where('name', 'like', $like)->limit(5)->get()
            ->map(fn ($c) => ['label' => $c->name, 'hint' => 'Symptom / condition', 'url' => route('conditions.show', $c)]);

        $doctors = PractitionerProfile::query()->public()
            ->whereHas('user', fn ($u) => $u->where('name', 'like', $like))
            ->with(['user:id,name', 'primarySpecialty:id,practitioner_label'])->limit(4)->get()
            ->map(fn ($p) => ['label' => $p->displayName(), 'hint' => $p->primarySpecialty?->practitioner_label, 'url' => route('practitioners.show', $p)]);

        return ['groups' => array_values(array_filter([
            $conditions->isNotEmpty() ? ['label' => 'Symptoms & conditions', 'items' => $conditions->values()] : null,
            $specialties->isNotEmpty() ? ['label' => 'Specialties', 'items' => $specialties->values()] : null,
            $doctors->isNotEmpty() ? ['label' => 'Practitioners', 'items' => $doctors->values()] : null,
        ]))];
    }
}
