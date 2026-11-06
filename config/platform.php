<?php

/*
|--------------------------------------------------------------------------
| Platform business rules
|--------------------------------------------------------------------------
| Defaults chosen where the client document was silent (see docs/DECISIONS.md).
| Any key can be overridden at runtime by admin via the `settings` table
| (App\Models\Setting::get()).
*/

return [

    // The 14 top-level specialties from the client document (URL slugs, e.g. /dermatologists).
    'specialty_slugs' => [
        'dermatologists', 'endocrinologists', 'gastroenterologists', 'psychiatrists', 'urologists',
        'clinical-psychologists-counselors', 'general-physicians', 'gynecologists-obstetricians',
        'pediatricians', 'neurologists', 'general-surgeons', 'dietitians-clinical-nutritionists',
        'sexologists', 'speech-occupational-therapists',
    ],

    // Booking windows
    'request_response_hours' => (int) env('BOOKING_RESPONSE_HOURS', 24),
    'payment_window_hours' => (int) env('BOOKING_PAYMENT_HOURS', 12),
    'min_notice_hours' => 3,              // earliest bookable slot from now
    'booking_horizon_days' => 30,         // how far ahead patients can book
    'max_open_requests_per_patient' => 3,
    'free_cancellation_hours' => 24,      // patient cancels ≥ 24h before → full refund
    'auto_complete_after_hours' => 2,     // confirmed → completed after ends_at + 2h

    // Memberships
    'membership_grace_days' => 3,
    'pk_paid_expiry_fallback' => 'pk_free', // Plan 2 not renewed → moved to Plan 1 (stays listed)
    'renewal_reminder_days' => [7, 3, 1],

    // Uploads (client document)
    'headshot_max_kb' => 2048,
    'document_max_kb' => 3072,
    'article_image_max_upload_kb' => 2048,  // stored output is compressed to ≤ 200 KB WebP
    'article_image_target_kb' => 200,

    // Contact
    'platform_whatsapp' => env('PLATFORM_WHATSAPP', '+923000000000'),
    'support_email' => env('SUPPORT_EMAIL', 'support@example.com'),
    'admin_alert_email' => env('ADMIN_ALERT_EMAIL', 'admin@example.com'),

    // Forum / blog
    'questions_per_page' => 12,
    'answers_require_approval' => false,

    // Payment gateways per market
    'gateways' => [
        'pk' => env('PK_GATEWAY', 'fake'),
        'intl' => env('INTL_GATEWAY', 'fake'),
    ],
];
