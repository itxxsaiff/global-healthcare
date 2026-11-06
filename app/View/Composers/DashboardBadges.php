<?php

namespace App\View\Composers;

use App\Enums\ArticleStatus;
use App\Enums\BookingStatus;
use App\Enums\ModerationStatus;
use App\Enums\TicketStatus;
use App\Enums\VerificationStatus;
use App\Models\Article;
use App\Models\Booking;
use App\Models\PractitionerProfile;
use App\Models\Question;
use App\Models\Review;
use App\Models\Tag;
use App\Models\Ticket;
use Illuminate\View\View;

/** Counts shown as badges in the dashboard sidebar. */
class DashboardBadges
{
    public function compose(View $view): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $badges = match (true) {
            $user->isAdmin() => [
                'pendingVerifications' => PractitionerProfile::query()->where('verification_status', VerificationStatus::Pending)->count(),
                'pendingReviews' => Review::query()->where('status', ModerationStatus::Pending)->count(),
                'pendingQuestions' => Question::query()->where('status', ModerationStatus::Pending)->count(),
                'pendingArticles' => Article::query()->where('status', ArticleStatus::Pending)->count(),
                'pendingTags' => Tag::query()->where('status', ModerationStatus::Pending)->count(),
                'openTickets' => Ticket::query()->where('status', TicketStatus::Open)->count(),
            ],
            $user->isPractitioner() => [
                'pendingRequests' => $user->practitionerProfile
                    ? Booking::query()->where('practitioner_profile_id', $user->practitionerProfile->id)->where('status', BookingStatus::Requested)->count()
                    : 0,
            ],
            default => [],
        };

        $view->with('dashboardBadges', $badges);
    }
}
