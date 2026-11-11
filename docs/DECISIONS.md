# Decisions log

Where the client document was silent or ambiguous, we chose a sensible default instead of blocking development.
Every value below is **configurable** (`config/platform.php`, the admin Settings screen, or the Plans screen), so the client can change them later without code changes.

| # | Topic | Client document says | What we built | Where to change it |
|---|---|---|---|---|
| 1 | Platform | WordPress + Elementor | Laravel 13 / PHP 8.4 / MySQL, agreed with client | n/a |
| 2 | Plan 2 six-month & yearly price | "discounted packages", no amount | PKR 27,000 / 6 months (10% off), PKR 50,000 / year (~17% off) | Admin → Plans |
| 3 | Plan 2 not renewed | not specified | Moves automatically to Plan 1 (free + 20%), profile stays listed | `platform.pk_paid_expiry_fallback` |
| 4 | International plan lapses | "auto-hide profile" | Hidden after a 3-day grace period | `platform.membership_grace_days` |
| 5 | "Direct dealing" (paid plans) | not defined | Patients still book a slot via the calendar; payment is arranged directly with the practitioner (no platform payment, 0% commission) | n/a |
| 6 | Paying Plan 1 practitioners their 80% | not specified | Admin records manual payouts (bank transfer etc.) against each practitioner's balance in the Commission Wallet | Admin → Wallet |
| 7 | Time to accept a request | not specified | 24 hours, then the request expires | `platform.request_response_hours` |
| 8 | Time to pay after acceptance | not specified | 12 hours (and always before the session starts) | `platform.payment_window_hours` |
| 9 | Does a pending request hold the slot? | not specified | Yes; max 3 open requests per patient to prevent slot-blocking | `platform.max_open_requests_per_patient` |
| 10 | How the session happens | not specified | Practitioner adds a meeting link/instructions (Zoom, Meet, WhatsApp call…) when confirming; shown to the patient in their dashboard. No in-house video | n/a |
| 11 | Cancellation & refunds | Refund policy page required | Patient cancels ≥ 24h before → full refund; later → no refund; practitioner cancels / no-show → full refund. Refunds are flagged for admin to process | `platform.free_cancellation_hours` |
| 12 | Completing a session | not specified | Auto-completed 2 hours after the end time; practitioner can mark it complete earlier | `platform.auto_complete_after_hours` |
| 13 | SMS alerts | "Email/SMS" | Email + in-dashboard notifications now; SMS can be added later as another notification channel | n/a |
| 14 | "Sexologists" listed twice | in #6 (included) and as #14 | Kept both, exactly as written (14 specialties) | Admin → Specialties |
| 15 | Document size limit | "3 MB to 5 MB", error text says "3MB" | 3 MB, using the client's exact error message | `platform.document_max_kb` |
| 16 | Blog image size | 100–200 KB max | Accept uploads up to 2 MB, auto-compress the stored image to WebP ≤ 200 KB at 1200×630 | `platform.article_image_*` |
| 17 | WhatsApp on free (Plan 1) profiles | "optional for all practitioners" | Plan 1 profiles show the **platform's** WhatsApp (protects the 20% commission); paid plans show the practitioner's own | `platform.platform_whatsapp` |
| 18 | City in URLs (`/dermatologists/lahore`) | example only | City field + city landing pages | n/a |
| 19 | Fee filter across PKR & USD | not specified | The fee range applies within one currency, picked in the filter | n/a |
| 20 | Ask a Doctor | anonymous questions, moderation | Patients log in to ask (shown anonymously). Questions are moderated; answers by verified practitioners publish immediately, and admin can hide them | `platform.answers_require_approval` |
| 21 | Re-verification after edits | not specified | Changing name, licence number or documents sends the profile back to "pending"; other edits go live immediately | n/a |
| 22 | Pricing market | "GeoIP switcher" | Decided by the practitioner's verified practice country, not the visitor's IP (IP isn't reliable for billing) | n/a |
| 23 | Sub-specialties & symptom list | examples only | A starting list of 67 sub-specialties and 34 symptoms, editable by admin | Admin → Specialties & symptoms |
| 24 | Brand name | Allbari, domain allbari.com (2026-11-11) | Was working name "Global Healthcare" | `.env` → `APP_NAME` |
| 25 | Admin login | "Admin login" | Same login page; admin accounts are created only by command, and 2FA is required in production | `php artisan app:create-admin` |

Still needs the client (can't be decided by us):

- Merchant accounts for the Pakistani gateway(s) and a Stripe-eligible entity (Stripe doesn't onboard Pakistan-registered businesses).
- Legal review of the Terms, Privacy Policy and Refund Policy drafts, and the cross-border telemedicine licensing position.
- Final brand name, logo and domain; hosting with cron + queue worker support.
