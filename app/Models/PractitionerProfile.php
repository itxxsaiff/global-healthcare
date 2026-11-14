<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\Market;
use App\Enums\MembershipStatus;
use App\Enums\ModerationStatus;
use App\Enums\VerificationStatus;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class PractitionerProfile extends Model
{
    use HasFactory, SoftDeletes;

    /** Client doc: bio capped at 200 words. */
    public const BIO_MAX_WORDS = 200;

    protected $fillable = [
        'slug', 'title', 'gender', 'country_id', 'city_id', 'timezone', 'market',
        'primary_specialty_id', 'headline', 'qualifications', 'license_number', 'licensing_body',
        'years_experience', 'fee_minor', 'fee_currency', 'session_minutes', 'bio',
        'linkedin_url', 'intro_video_url', 'intro_video_path', 'intro_video_seconds', 'whatsapp_number', 'other_languages', 'headshot_path',
    ];

    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'market' => Market::class,
            'verification_status' => VerificationStatus::class,
            'submitted_at' => 'datetime',
            'verified_at' => 'datetime',
            'is_public' => 'boolean',
            'is_featured' => 'boolean',
            'rating_avg' => 'float',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function primarySpecialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class, 'primary_specialty_id');
    }

    public function subSpecialties(): BelongsToMany
    {
        return $this->belongsToMany(Specialty::class, 'practitioner_specialty');
    }

    public function conditions(): BelongsToMany
    {
        return $this->belongsToMany(Condition::class);
    }

    public function languages(): BelongsToMany
    {
        return $this->belongsToMany(Language::class)->orderBy('sort');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function approvedTags(): BelongsToMany
    {
        return $this->tags()->where('status', ModerationStatus::Approved);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(PractitionerDocument::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function activeMembership(): HasOne
    {
        return $this->hasOne(Membership::class)
            ->whereIn('status', [MembershipStatus::Active, MembershipStatus::PastDue])
            ->latestOfMany('starts_at');
    }

    public function availabilityRules(): HasMany
    {
        return $this->hasMany(AvailabilityRule::class)->orderBy('weekday')->orderBy('start_time');
    }

    public function availabilityExceptions(): HasMany
    {
        return $this->hasMany(AvailabilityException::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->reviews()->where('status', ModerationStatus::Approved)->latest();
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopePublic(Builder $query): void
    {
        $query->where('is_public', true);
    }

    // ── Presentation helpers ───────────────────────────────────────

    public function displayName(): string
    {
        return trim(($this->title ? $this->title.' ' : '').$this->user?->name);
    }

    public function headshotUrl(): ?string
    {
        return $this->headshot_path ? Storage::disk('public')->url($this->headshot_path) : null;
    }

    public function feeLabel(): string
    {
        return Money::format($this->fee_minor, $this->fee_currency);
    }

    public function locationLabel(): string
    {
        return collect([$this->city?->name, $this->country?->name])->filter()->implode(', ');
    }

    public function plan(): ?Plan
    {
        return $this->activeMembership?->plan;
    }

    /** LinkedIn shows only on paid plans (client doc). */
    public function showsLinkedin(): bool
    {
        return (bool) $this->plan()?->shows_linkedin && filled($this->linkedin_url);
    }

    /** Doctor's own WhatsApp only on paid plans; Plan 1 enquiries go to the platform number. */
    public function publicWhatsapp(): ?string
    {
        if ($this->plan()?->direct_dealing && filled($this->whatsapp_number)) {
            return $this->whatsapp_number;
        }

        return Setting::get('platform_whatsapp');
    }

    public function videoEmbedUrl(): ?string
    {
        return $this->introVideo()['embed'] ?? null;
    }

    /**
     * The optional intro video, ready for the profile page (a click-to-play facade, so
     * nothing heavy loads until the visitor asks for it).
     *
     * @return array{type: 'youtube'|'vimeo'|'file', src: string, embed: string|null, thumb: string|null, seconds: int|null, length: string|null}|null
     */
    public function introVideo(): ?array
    {
        if ($this->intro_video_path) {
            return [
                'type' => 'file',
                'src' => Storage::disk('public')->url($this->intro_video_path),
                'embed' => null,
                'thumb' => $this->headshotUrl(),
                'seconds' => $this->intro_video_seconds,
                'length' => $this->intro_video_seconds ? intdiv($this->intro_video_seconds, 60).':'.str_pad((string) ($this->intro_video_seconds % 60), 2, '0', STR_PAD_LEFT) : null,
            ];
        }

        $parsed = self::parseVideoUrl($this->intro_video_url);
        if (! $parsed) {
            return null;
        }

        return [
            'type' => $parsed['provider'],
            'src' => $this->intro_video_url,
            'embed' => $parsed['provider'] === 'youtube'
                ? 'https://www.youtube-nocookie.com/embed/'.$parsed['id']
                : 'https://player.vimeo.com/video/'.$parsed['id'],
            'thumb' => $parsed['provider'] === 'youtube' ? 'https://i.ytimg.com/vi/'.$parsed['id'].'/hqdefault.jpg' : $this->headshotUrl(),
            'seconds' => null,
            'length' => null,
        ];
    }

    public function hasIntroVideo(): bool
    {
        return $this->introVideo() !== null;
    }

    /** @return array{provider: 'youtube'|'vimeo', id: string}|null */
    public static function parseVideoUrl(?string $url): ?array
    {
        if (! $url) {
            return null;
        }
        if (preg_match('~^https://(?:(?:www|m)\.)?(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/)|youtu\.be/)([\w-]{11})~i', $url, $m)) {
            return ['provider' => 'youtube', 'id' => $m[1]];
        }
        if (preg_match('~^https://(?:www\.|player\.)?vimeo\.com/(?:video/)?(\d+)~i', $url, $m)) {
            return ['provider' => 'vimeo', 'id' => $m[1]];
        }

        return null;
    }

    public function hasDocument(string $type): bool
    {
        return $this->documents->contains(fn ($d) => $d->type->value === $type);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
