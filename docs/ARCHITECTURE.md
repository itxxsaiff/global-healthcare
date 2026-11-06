# Global Healthcare — Architecture & Delivery Plan

> Working name: **Global Healthcare** (set in `.env` → `APP_NAME`; change any time).
> Source of truth for features: [`docs/client-requirements.docx`](client-requirements.docx).
> Stack decision (agreed with client): Laravel instead of WordPress + Elementor.

Legend used throughout:

- **[DOC]** stated in the client document
- **[PROPOSED]** our technical implementation choice
- **[CONFIRM]** open business decision, needs the client's answer (collected in §12 and `CLIENT_QUESTIONS.md`)

---

## Status (2026-11-06)

All modules in §11 are implemented and covered by feature tests (see README). Items that depend on the client:
- live merchant credentials (JazzCash sandbox → production, Stripe on an eligible entity; PayFast / EasyPaisa / Safepay drivers can be added behind the same interface);
- legal review of the draft legal pages;
- final brand, domain and hosting.

The open questions in §12 were answered with the defaults recorded in `docs/DECISIONS.md`.

## 0. Current setup (done)

| Item | Value |
|---|---|
| Framework | Laravel 13.40 |
| PHP | 8.4.16 (GD with WebP support, intl, bcmath, sodium) |
| Database | MySQL 9.5 → database `global_healthcare` (utf8mb4_unicode_ci) |
| Frontend | Blade + Bootstrap 5.3 (SCSS) + Alpine.js via Vite. Tailwind removed |
| Queue / cache / session | `database` drivers locally. Redis recommended in production |
| Git | initialised, nothing committed yet |

---

## 1. Platform understanding

A directory and booking platform for **non-emergency online outpatient consultations**, connecting patients in Pakistan, the Gulf, the UK and North America with verified practitioners.

The platform does five jobs:

1. **Discovery.** Patients find practitioners by specialty, by symptom or disease (for patients who don't know which specialist they need), gender, spoken language, country or city, tags and fee. **[DOC]**
2. **Trust.** Every practitioner is manually verified from uploaded ID, degree and licence documents before going public. Reviews come only from patients with a completed, paid booking and are moderated. **[DOC]**
3. **Monetisation.** Three practitioner plans. **[DOC]**
   - PK Plan 1: free listing, 20% commission on bookings paid through the platform, LinkedIn hidden.
   - PK Plan 2: PKR 5,000 per month (plus 6-month and 1-year packages), 0% commission, direct dealing, LinkedIn shown.
   - International: $30 per month, $150 per 6 months or $280 per year through Stripe auto-debit, 0% commission, direct dealing, LinkedIn shown. The profile auto-hides if the subscription lapses.
4. **Booking.** A two-step flow for PK Plan 1: request → practitioner accepts → payment link → paid → confirmed → alerts to all three parties → commission recorded. **[DOC]**
5. **Organic growth.** An "Ask a Doctor" public Q&A, a practitioner blog with word-count and image rules, SEO URLs such as `/dermatologists/lahore` and `/symptom/acne`, schema markup, a PWA and PageSpeed 85+. **[DOC]**

Excluded by rule: emergency and acute care specialties (trauma, emergency medicine, ICU, acute cardiac). **[DOC]** We enforce this structurally: practitioners can only pick from the seeded list of allowed specialties.

---

## 2. Functional modules

| # | Module | Key contents |
|---|---|---|
| A | Auth & accounts | Patient and practitioner registration, single login with role redirect, email verification, password reset, admin 2FA, profiles |
| B | Practitioner onboarding & verification | Profile wizard, headshot (2 MB, auto-WebP), private documents (ID, degree, licence), languages, hierarchical specialties, custom tags (moderated), 200-word bio, LinkedIn, YouTube/Vimeo intro, optional WhatsApp, admin approve or reject |
| C | Directory & search | Home search bar, directory with multi-level filters, specialty, city and symptom landing pages, profile pages |
| D | Memberships | Plans per market, purchase and renewal, expiry, profile visibility rules |
| E | Availability & booking | Weekly availability and exceptions, time-zone-aware slots, booking state machine, accept or reject, payment window, cancellation, history |
| F | Payments | Gateway abstraction, PK gateway(s), Stripe, webhooks, idempotency, commission ledger, payouts record |
| G | Reviews | 1–5 stars, text, completed-and-paid booking gate, moderation, cached average |
| H | Ask a Doctor | Anonymous questions, categories and symptom tags, doctor answers, moderation, paginated SEO pages |
| I | Blog | Doctor and platform articles, standard or guide type, live word counter, featured image rules, WebP, approval, SEO meta |
| J | Support | Tickets (No-show, Technical, Payment dispute, Unprofessional behaviour), threaded replies, WhatsApp widget, email notifications |
| K | SEO / PWA / performance | Schema (MedicalBusiness, Physician, Article, FAQPage), sitemap, robots, meta, manifest, service worker, offline page, caching |
| L | Admin panel | Dashboard KPIs, verification queue, users, plans, bookings, commission wallet, payouts, moderation queues, tickets, legal pages, settings, audit log |
| M | Legal pages | Terms, Privacy (GDPR), Refund & Cancellation, admin-editable |

---

## 3. Laravel architecture [PROPOSED]

A **standard Laravel monolith** (no SPA, no microservices), organised by role on the HTTP side and by domain in the service layer.

- **Controllers** stay thin. They authorise, validate through a Form Request, call a Service and return a view or redirect.
- **Services** (`app/Services/*`) hold business rules: `BookingService`, `SlotService`, `MembershipService`, `VerificationService`, `CommissionService`, `ImageService`, `SearchService`.
- **Enums** (`app/Enums`) hold every status, using PHP backed enums. `BookingStatus` owns its allowed transitions.
- **Policies** cover per-record access: who can view a document, accept a booking, or review a booking.
- **Events → Listeners → Notifications.** For example, `BookingPaid` triggers mail plus database notifications to the patient, the practitioner and the admin.
- **Jobs + Scheduler** run image conversion, expiries, reminders and the sitemap rebuild.
- **Gateways** (`app/Payments`) sit behind one `PaymentGateway` interface, so providers are swappable (§9).

Packages, kept deliberately small:

| Package | Why |
|---|---|
| `laravel/fortify` | Headless auth backend (register, login, verify email, reset, 2FA) rendered with our own Blade + Bootstrap views. Laravel's current starter kits are React, Vue or Livewire, which the brief rules out |
| `intervention/image` v3 (GD driver) | Resize and convert to WebP |
| `stripe/stripe-php` | Stripe Checkout (subscription mode), Customer Portal, webhooks. We keep our own `memberships` table as the single source of truth instead of Cashier's tables |
| `stevebauman/purify` | HTML sanitising for article and answer bodies |
| `spatie/laravel-sitemap` | Sitemap generation |
| Front end: Bootstrap 5, Alpine.js, FullCalendar (practitioner dashboard only), a lightweight editor (Quill or Trix) | |

Not used: Spatie Permission (three fixed roles don't need it), Livewire, Inertia, React.

---

## 4. Database schema [PROPOSED]

Conventions:

- Every table has `id` (bigint) and timestamps.
- Money is stored as **integer minor units** (`*_minor`) plus a `currency` char(3).
- Rates are in **basis points** (2000 = 20%).
- All datetimes are stored in **UTC**.
- Foreign keys are constrained, with `restrictOnDelete` on financial records.
- Soft deletes are used on users, profiles, articles and questions.

### 4.1 Identity

**users**: `name`, `email` (unique), `password`, `role` enum(admin, practitioner, patient) indexed, `status` enum(active, suspended), `email_verified_at`, `timezone`, `last_login_at`, Fortify `two_factor_*` columns, soft deletes.

**patient_profiles**: `user_id` (FK, unique), `gender` enum(male, female, prefer_not_to_say), `date_of_birth` (gives age; **[CONFIRM]** the doc says "Age"), `phone`, `country_id` FK nullable.

### 4.2 Reference data (seeded)

**countries**: `iso2` (unique), `name`, `currency`, `is_target` (bool), `sort`. The targets are PK, AE, SA, GB, US, CA, QA, OM, KW, plus "Other". **[DOC]**

**cities**: `country_id` FK, `name`, `slug`, unique(`country_id`, `slug`). Needed for `/dermatologists/lahore`. **[DOC example]**

**specialties**: `parent_id` (self-FK, null for the 14 top-level ones), `name`, `slug` (unique), `plural_slug` (for `/dermatologists`), `description`, `icon`, `sort`, `is_active`.

The 14 top-level rows are seeded **exactly** from the doc:

1. Dermatologists
2. Endocrinologists
3. Gastroenterologists
4. Psychiatrists
5. Urologists
6. Clinical Psychologists & Counselors
7. General Physicians
8. Gynecologists / Obstetricians
9. Pediatricians
10. Neurologists
11. General Surgeons
12. Dietitians & Clinical Nutritionists
13. Sexologists
14. Speech & Occupational Therapists

Child rows are the sub-specialties. **[CONFIRM]** the full sub-specialty list; the doc only gives examples.

**conditions** (symptoms and diseases): `name`, `slug` (unique), `is_active`. The doc examples are seeded: Acne, Hair Loss, Diabetes, Anxiety, Depression, PCOS, Acid Reflux, Migraine, Erectile Dysfunction, Speech Delay, Fever, Weight Loss/Gain.

**condition_specialty** pivot maps each symptom to the specialties that treat it. This powers the "I know my symptom, not the specialist" search.

**languages**: `code`, `name`, `is_preset`. Seeded presets: Urdu, English, Arabic, French, German, Spanish, Hindi, Turkish. **[DOC]**

**tags** (custom certifications such as EMDR): `name`, `slug` (unique), `status` enum(pending, approved, rejected), `suggested_by` FK users.

### 4.3 Practitioners

**practitioner_profiles**

| Column | Notes |
|---|---|
| `user_id` | FK, unique |
| `slug` | unique, used in profile URLs |
| `title`, `gender` | `gender` enum(male, female, other) |
| `country_id`, `city_id`, `timezone` | |
| `market` | enum(pk, intl), derived from practice country and drives plan eligibility |
| `primary_specialty_id` | FK specialties |
| `qualifications` | text |
| `license_number`, `licensing_body` | e.g. PMDC |
| `years_experience` | |
| `fee_minor`, `fee_currency`, `session_minutes` | |
| `bio` | text, ≤ 200 words, validated server-side and counted live client-side |
| `linkedin_url`, `intro_video_url` | intro video limited to YouTube/Vimeo |
| `whatsapp_number` | optional |
| `other_languages` | free-text "Other" |
| `headshot_path` | public disk, WebP |
| `verification_status` | enum(draft, pending, approved, rejected, suspended) |
| `submitted_at`, `verified_at`, `verified_by`, `rejection_reason` | |
| `is_public` | bool, denormalised = approved AND active membership; recomputed only by `VisibilityService` |
| `rating_avg` decimal(3,2), `reviews_count` | cached from approved reviews |
| soft deletes | |

Indexes: (`is_public`, `primary_specialty_id`), (`is_public`, `country_id`, `city_id`), `gender`, `fee_minor`, and FULLTEXT on (`bio`, `qualifications`).

Pivots: **practitioner_specialty** (sub-specialties), **practitioner_condition**, **practitioner_language**, **practitioner_tag**, each with a composite primary key.

**practitioner_documents**: `practitioner_profile_id` FK, `type` enum(government_id, passport, degree, license), `path` (**private** disk, random filename), `original_name`, `mime`, `size_bytes`, `sha256`, `status` enum(pending, accepted, rejected), `reviewed_by`, `reviewed_at`, `note`.

### 4.4 Memberships

**plans**

| Column | Notes |
|---|---|
| `code` | unique |
| `name`, `market` | `market` enum(pk, intl) |
| `currency`, `price_minor` | |
| `interval` | enum(none, month, six_months, year) |
| `commission_bps` | 2000 or 0 |
| `shows_linkedin`, `direct_dealing` | bool |
| `stripe_price_id` | nullable |
| `is_active`, `sort` | |

Seeded plans:

| Code | Price | Commission | Notes |
|---|---|---|---|
| `pk_free` | PKR 0 | 20% | |
| `pk_pro_1m` | PKR 5,000 | 0% | |
| `pk_pro_6m` | **[CONFIRM]** | 0% | inactive until priced |
| `pk_pro_12m` | **[CONFIRM]** | 0% | inactive until priced |
| `intl_1m` | $30 | 0% | |
| `intl_6m` | $150 | 0% | |
| `intl_12m` | $280 | 0% | |

**memberships**: `practitioner_profile_id` FK, `plan_id` FK, `status` enum(pending_payment, active, past_due, cancelled, expired), `starts_at`, `ends_at` (null for the free plan), `auto_renew`, `gateway`, `gateway_subscription_id` (indexed), `cancelled_at`.

Index: (`status`, `ends_at`) for the expiry job. Only one current membership per practitioner, enforced by a service-level lock.

### 4.5 Availability & bookings

**availability_rules**: `practitioner_profile_id`, `weekday` (0–6), `start_time`, `end_time`, all in the practitioner's local time zone.

**availability_exceptions**: `practitioner_profile_id`, `date`, `is_unavailable`, optional `start_time` and `end_time` (for leave or extra hours).

**bookings**

| Column | Notes |
|---|---|
| `reference` | unique, public e.g. `GH-7K3P9Q`; internal ids are never exposed |
| `patient_id` | FK users |
| `practitioner_profile_id` | FK |
| `starts_at`, `ends_at` | UTC |
| `practitioner_timezone`, `patient_timezone` | snapshots |
| `status` | enum, see §8 |
| `flow` | enum(platform_payment, direct), set from the plan at booking time |
| `plan_id` | snapshot |
| `fee_minor`, `currency`, `commission_bps`, `commission_minor`, `practitioner_earning_minor` | **snapshotted** at creation, never recalculated |
| `patient_note` | **encrypted cast**, may contain health info |
| `meeting_details` | encrypted; how the session happens, **[CONFIRM]** |
| `responded_at`, `payment_due_at`, `confirmed_at`, `completed_at`, `cancelled_at` | |
| `cancelled_by`, `cancellation_reason` | |
| `active_slot` | **stored generated column** = `starts_at` while status ∈ {requested, awaiting_payment, confirmed}, else NULL |

Constraints:

- **UNIQUE(`practitioner_profile_id`, `active_slot`)**: the database itself refuses double-booking a slot, even under race conditions. MySQL allows many NULLs, so cancelled or expired rows don't block the slot.
- Index (`status`, `payment_due_at`) for expiry, and (`patient_id`, `status`).

**booking_status_history**: `booking_id`, `from_status`, `to_status`, `actor_id` (null for system), `note`, `created_at`. This is the full audit trail of the state machine.

### 4.6 Payments & money

**payments**

| Column | Notes |
|---|---|
| `uuid` | unique, sent to the gateway as our order id |
| `payable_type` / `payable_id` | morph: booking or membership |
| `user_id` | |
| `gateway` | |
| `gateway_reference` | |
| `amount_minor`, `currency` | |
| `status` | enum(pending, paid, failed, expired, refunded, partially_refunded) |
| `paid_at`, `expires_at` | |
| `meta` | json, sanitised, with no card data ever |

Constraints: UNIQUE(`gateway`, `gateway_reference`), plus an index on the payable morph.

**payment_events**: `gateway`, `event_id`, `type`, `payload` (json), `signature_valid`, `processed_at`, with UNIQUE(`gateway`, `event_id`). This gives webhook **idempotency**: a replayed event is a no-op.

**ledger_entries** (the "Admin Wallet"): `practitioner_profile_id`, `booking_id`, `payout_id` nullable, `type` enum(commission_earned, practitioner_payable, payout, refund_reversal), `amount_minor` (signed), `currency`, `created_at`.

Entries are append-only. Balances are sums. Each booking can produce only one `commission_earned` row, guaranteed by UNIQUE(`booking_id`, `type`).

**payouts**: `practitioner_profile_id`, `amount_minor`, `currency`, `method`, `reference`, `paid_at`, `created_by`. Admin records the transfers made to Plan 1 doctors. **[CONFIRM]** the payout process.

### 4.7 Engagement

**reviews**: `booking_id` (FK, **unique**: one review per booking), `patient_id`, `practitioner_profile_id`, `rating` tinyint with CHECK 1–5, `body`, `status` enum(pending, approved, rejected), `moderated_by`, `moderated_at`.

**questions**: `patient_id` (never shown publicly), `specialty_id`, `title`, `slug` (unique), `body`, `status` enum(pending, approved, rejected), `answers_count`, `published_at`, soft deletes. Pivot **condition_question**.

**answers**: `question_id`, `practitioner_profile_id`, `body` (purified), `status`, `published_at`.

**articles**: `author_id` FK users (practitioner or admin), `practitioner_profile_id` nullable, `type` enum(standard, guide), `title`, `slug` (unique), `excerpt`, `body` (purified HTML), `word_count`, `featured_image_path`, `meta_title`, `meta_description`, `status` enum(draft, pending, approved, rejected), `rejection_reason`, `published_at`, soft deletes. Pivot **article_specialty**.

### 4.8 Support, content, system

- **tickets**: `reference` (unique), `user_id`, `booking_id` nullable, `category` enum(no_show, technical, payment_dispute, unprofessional_behavior, other), `subject`, `status` enum(open, in_progress, resolved, closed), `priority`, `assigned_to`.
- **ticket_messages**: `ticket_id`, `user_id`, `body`, `attachment_path` (private), `is_internal_note`.
- **pages**: `slug` (unique), `title`, `body`, `meta_*`. Holds the legal pages.
- **settings**: `key` (unique), `value` (json). Examples: WhatsApp number, payment window hours, request response hours.
- **audit_logs**: `actor_id`, `action`, `subject_type` / `subject_id`, `ip`, `user_agent`, `meta`, `created_at`. Covers document views, verification decisions, refunds, role changes and payout records.
- Laravel defaults: `notifications` (dashboard alerts), `jobs`, `failed_jobs`, `cache`, `sessions`.

---

## 5. Authentication & authorisation [PROPOSED]

- **Fortify** with custom Blade views. Registration has two pages, `/register/patient` and `/register/practitioner`. There is **one login page**, which redirects by role to `/patient`, `/practitioner` or `/admin`.
- **Admins are never publicly registrable.** They are created with `php artisan app:create-admin`. **2FA is mandatory for admins.**
- Email verification is required before booking, posting questions or submitting a practitioner profile. **[DOC]** (email is mandatory)
- Route groups use middleware `auth`, `verified` and `role:practitioner|patient|admin`. Policies handle record-level checks, e.g. `BookingPolicy::accept`, which only lets the owning practitioner accept while the booking is `requested`.
- Rate limits cover login (Fortify), registration, booking requests, questions, ticket creation and webhooks. Throttles are separate per route.
- Suspended users are logged out by middleware. Session regeneration and secure, HTTP-only, SameSite=Lax cookies are on, and HTTPS is forced in production.

---

## 6. Practitioner verification workflow

```
Register (email verified)
  → Onboarding wizard (draft): personal & professional → specialties/symptoms/tags/languages
                               → bio, fee, links → headshot → documents
  → Submit → status = pending  (admin notified)                         [DOC Step 2]
  → Admin reviews documents via signed, short-lived private URLs (each view audit-logged)
       ├─ Reject (reason required) → email → practitioner edits → resubmit → pending
       └─ Approve → email "Your membership plans are unlocked"           [DOC Step 3]
  → Practitioner chooses plan
       ├─ PK Plan 1 → membership active immediately → is_public = true   [DOC]
       ├─ PK Plan 2 → pay → active → public                              [DOC + CONFIRM]
       └─ Intl plan → Stripe Checkout → webhook → active → public        [DOC]
```

File rules:

- **Headshot**: JPG, PNG or WebP, ≤ 2 MB, checked in the browser and on the server. Stored as a resized square WebP (e.g. 600×600) on the public disk. **[DOC]**
- **Documents**: PDF, JPG or PNG. They go to the private disk (`storage/app/private`), are never web-accessible, and are served only through a policy-checked controller. The size limit is configurable. **[CONFIRM]** 3 MB or 5 MB: the doc says "3 MB to 5 MB" but quotes a 3 MB error message, so we default to **3 MB** with that exact message.
- MIME is checked by content, not extension. Files get random names, and the original name is stored separately.

**[CONFIRM]** Which edits after approval send the profile back to `pending`? Our proposal: name, licence number and documents trigger re-verification; other fields go live immediately.

---

## 7. Subscription & commission architecture

**Market** is decided by the practitioner's declared and verified practice country (PK → `pk`, anything else → `intl`), **not** by the visitor's IP. GeoIP can't be trusted for billing, and the licence document confirms the country. **[PROPOSED; CONFIRM]** GeoIP is still used for display only, e.g. pre-selecting the patient's time zone and country filter.

**Visibility rule**, enforced in one place (`VisibilityService::refresh($profile)`):

```
is_public = verification_status == approved
         AND user.status == active
         AND has membership with status == active (or past_due within grace period)
```

The rule runs whenever verification, membership or user status changes, and on an hourly scheduled sweep as a safety net.

**Intl plans (Stripe auto-debit)** [DOC]:

- Stripe Checkout runs in subscription mode.
- Webhooks: `checkout.session.completed` and `invoice.paid` activate or extend the membership. `invoice.payment_failed` sets `past_due`. `customer.subscription.deleted` sets `expired`, and `is_public` becomes false.
- The Stripe Customer Portal lets practitioners update their card or cancel.

**PK Plan 2**: Pakistani gateways generally don't offer reliable card auto-debit, so we propose **manual renewal**. Renewal emails go out 7, 3 and 1 days before `ends_at`, and the practitioner pays a fresh invoice. **[CONFIRM]** On expiry, auto-hide (proposed) or fall back to Plan 1?

**Grace period** after failure or expiry: proposed 3 days, configurable. **[CONFIRM]**

**Commission** (Plan 1 only):

- At booking creation, snapshot `commission_bps` from the active plan. Snapshotting means later plan changes don't rewrite history.
- On payment confirmation, inside the same DB transaction:
  - `commission_minor = round(fee_minor × bps / 10000)`
  - `practitioner_earning_minor = fee_minor − commission_minor`
  - Write `commission_earned` and `practitioner_payable` ledger rows.
- The admin "Wallet" screen shows totals per currency and period, a per-practitioner payable balance, and a payout recorder.

**[CONFIRM]** "Direct dealing" for Plan 2 and Intl practitioners. Our reading: patients can still request and book slots through the platform calendar, but **no payment passes through the platform** (`flow = direct`). The practitioner arranges payment directly. The "direct booking buttons" also link to WhatsApp or contact details.

---

## 8. Booking state machine

```
                ┌──────────── reject ───────────► rejected
                │
 [patient] ──► requested ── no response in N hrs ─► expired_unanswered
                │
                └─ accept ─► awaiting_payment ── window passes ─► payment_expired
                   (payment link emailed)  │
                                           └─ payment verified ─► confirmed
                                                                   │
                         cancelled_by_patient / cancelled_by_practitioner (rules + refund)
                                                                   │
                                                  session time passes ─► completed
                                                  (or: no_show_reported via ticket)
```

For the **direct flow** (Plan 2 and Intl), accept moves the booking straight to `confirmed`, with no payment step.

Guarantees:

- `BookingStatus::canTransitionTo()` is the only allowed path. `BookingService` methods (`request`, `accept`, `reject`, `markPaid`, `cancel`, `complete`, `expire`) each run in `DB::transaction()` with `lockForUpdate()` on the booking row and write a `booking_status_history` row.
- **Double-booking prevention** works on two layers:
  1. the slot is validated against availability and existing bookings inside the transaction;
  2. the unique `active_slot` index is the final guard against races. A duplicate key error becomes a friendly "slot just taken" message.
- **Time zones**: availability is defined in the practitioner's zone, slots are generated and stored in UTC, and they are displayed in the patient's zone, detected in the browser with `Intl.DateTimeFormat().resolvedOptions().timeZone` and saved to the profile. DST is handled by Carbon.
- **Payment idempotency**: `markPaid` is a no-op if the booking is already `confirmed` and the payment is already `paid`. Webhook replays are blocked by the unique `payment_events(gateway, event_id)`. Status is always **re-verified server-side** with the gateway's inquiry API; the browser redirect is never trusted.
- **Late payment** (money arrives after `payment_expired`): if the slot is still free, re-confirm; otherwise flag for admin refund. Never silently lose money.
- Scheduler (every 5 min): expire unanswered requests and unpaid bookings, and mark past confirmed sessions as `completed` after `ends_at` + X hours. **[CONFIRM]** auto-complete vs practitioner marks complete.
- Notifications are queued: mail plus database for every transition. The three "payment received" alerts are **[DOC]**. SMS is **[CONFIRM]** (provider and cost).

**[CONFIRM]** list for booking:

- Request response window (proposed 24 h).
- Payment window after acceptance (proposed 12 h, and always before the slot).
- Does a pending request hold the slot? Proposed **yes**, with a limit of 3 open requests per patient to stop slot-blocking.
- Cancellation and refund cut-offs.
- How the consultation actually happens: a video link supplied by the doctor, WhatsApp call, or other. The platform does **not** build video calling unless requested.

---

## 9. Payment gateway architecture

```php
interface PaymentGateway {
    public function key(): string;                                   // 'payfast', 'jazzcash', 'stripe', 'fake'
    public function supports(Capability $c): bool;                   // OneTime, Recurring, Refund, StatusInquiry
    public function createCheckout(Payment $p): CheckoutResult;      // redirect URL or form fields
    public function verifyCallback(Request $r): GatewayEvent;        // signature/hash check → normalised event
    public function fetchStatus(Payment $p): PaymentStatus;          // server-side inquiry
    public function refund(Payment $p, int $amountMinor): RefundResult;
}
```

- `PaymentManager` (Laravel's `Manager` pattern) resolves drivers from `config/payments.php`. Which gateway serves which market and currency is configured there, not hard-coded.
- **`FakeGateway`** for local and dev, with buttons to simulate success or failure. We can build and test the whole booking flow before any merchant account exists.
- Each real driver lives in `app/Payments/Gateways/{PayFast,JazzCash,EasyPaisa,Safepay,Stripe}Gateway.php` and is implemented **only against the provider's official docs and sandbox**. Each one is a separate, self-contained task.
- Webhook routes are `/webhooks/{gateway}`: CSRF-exempt, signature-verified, logged to `payment_events`, and processed by a queued job.
- Secrets live in `.env` only. No card data ever touches our server; all gateways are hosted checkout or redirect. This keeps PCI scope minimal.

**Reality check [CONFIRM]**:

- **Stripe does not onboard Pakistan-based businesses.** The client needs a Stripe account under an entity in a supported country, e.g. UAE, UK or US. Otherwise international subscriptions need an alternative such as Paddle, 2Checkout or PayPal.
- Each PK gateway needs the client's own approved merchant account, and approval can take weeks. Recommendation: launch with **one** PK aggregator (PayFast or Safepay cover cards and wallets) and add JazzCash and EasyPaisa through the same interface later.
- Can patients abroad pay PKR fees to Plan 1 doctors with international cards on the chosen PK gateway? This depends on the merchant setup.

---

## 10. Folder structure [PROPOSED]

```
app/
  Enums/                 BookingStatus, VerificationStatus, MembershipStatus, Role, Gender, Market, TicketCategory…
  Models/                User, PatientProfile, PractitionerProfile, Specialty, Condition, Booking, Payment…
  Http/
    Controllers/
      Public/            Home, Directory, PractitionerProfile, Question, Article, Page, Sitemap
      Patient/           Dashboard, Booking, Review, Question, Ticket
      Practitioner/      Dashboard, Onboarding, Availability, Booking, Membership, Answer, Article, Ticket
      Admin/             Dashboard, Verification, Practitioner, Booking, Wallet, Payout, Plan, Review,
                         Question, Article, Tag, Ticket, Page, Setting, AuditLog
      Webhooks/          PaymentWebhookController
    Requests/            {Area}/{Action}Request.php
    Middleware/          EnsureRole, NoStoreForAuthenticated, EnsureProfileComplete
  Policies/
  Services/              Booking/, Membership/, Verification/, Search/, Media/, Commission/
  Payments/              Contracts/, Gateways/, PaymentManager.php, DTOs/
  Events/  Listeners/  Notifications/  Jobs/
  View/Components/       practitioner-card, rating-stars, filter-panel, slot-picker…
config/payments.php
database/migrations/  seeders/ (Countries, Languages, Specialties, Conditions, Plans, DemoSeeder)
resources/
  scss/                  _tokens, _base, components/, pages/
  js/                    app.js, modules/ (slot-picker, word-counter, filters, pwa)
  views/                 layouts/, components/, public/, patient/, practitioner/, admin/, auth/, emails/
routes/
  web.php (public)  patient.php  practitioner.php  admin.php  webhooks.php   ← wired in bootstrap/app.php
public/                  manifest.webmanifest, sw.js, offline.html, icons/
tests/Feature/           Booking, Payments, Verification, Membership, Reviews, Authorization
```

**SEO URL map** [DOC examples + PROPOSED]:

| URL | Page |
|---|---|
| `/` | Home |
| `/find-a-doctor` | Directory |
| `/{specialty}` | e.g. `/dermatologists`. The route is constrained to known specialty slugs so it can't swallow other routes |
| `/{specialty}/{city}` | e.g. `/dermatologists/lahore` |
| `/symptom/{condition}` | e.g. `/symptom/acne` |
| `/doctor/{slug}` | Practitioner profile |
| `/ask-a-doctor`, `/ask-a-doctor/{slug}` | Q&A |
| `/blog`, `/blog/{slug}` | Blog |
| `/terms`, `/privacy`, `/refund-policy` | Legal pages |

**Caching rule**:

- Public pages (home, directory, profiles, Q&A, blog) may be cached. They contain only public data.
- Every authenticated, dashboard, booking and document response gets `Cache-Control: private, no-store`.
- The service worker **never caches HTML for logged-in areas**. It caches only static assets plus an offline page.

---

## 11. Five-day roadmap

Each day ends with a review checkpoint. We move on after your approval.

| Day | Deliverable |
|---|---|
| **1 — Foundation & design system** | All migrations, enums and models with relationships. Seeders (14 specialties, conditions, languages, countries, plans). Fortify auth with patient and practitioner registration, role redirect and admin command with 2FA. Design tokens and base layouts (public + dashboard). Home page UI |
| **2 — Practitioners & directory** | Onboarding wizard, headshot WebP pipeline, private documents, admin verification queue, plan selection with Plan 1 live. Public directory with all filters, specialty, city and symptom pages, profile page, visibility service |
| **3 — Booking & payments core** | Availability editor (FullCalendar), time-zone slot picker, the full booking state machine, `PaymentManager` + `FakeGateway` + the first real PK gateway in sandbox (if credentials exist), webhooks, commission ledger, notifications, scheduler jobs. **Feature tests for booking and payment** |
| **4 — Subscriptions & community** | Stripe subscriptions + PK Plan 2 renewals, reviews with moderation and averages, Ask a Doctor, blog with editor, word counter and image rules, admin moderation queues |
| **5 — Support, SEO, PWA, hardening** | Tickets, WhatsApp widget, legal pages, admin wallet and payouts, schema + sitemap + robots + meta, PWA (manifest, SW, offline, install prompt, splash), performance pass, security pass (headers, rate limits, audit logs), test run, deployment guide |

**Honest constraints**:

- **Five days is tight but workable for the full application with a sandbox or fake payment flow.**
- *Live* payments depend on the client's merchant accounts, which we can't control.
- PageSpeed 85+ can only be measured once deployed on real hosting.
- Video tutorials, hosting purchase and the post-launch support period fall outside the build days.

**Tests to write** (Pest/PHPUnit):

- Every booking transition, both allowed and forbidden.
- Double-booking race (unique index).
- Webhook signature failure.
- Webhook replay idempotency.
- Commission maths and ledger uniqueness.
- Membership expiry hides profile.
- Review gate (only completed and paid bookings).
- Document access (only owner and admin).
- Role isolation on every route group.

---

## 12. Risks & client clarifications

### Conflicts and ambiguities in the document

1. **"Sexologists" appears twice**: inside #6 (Clinical Psychologists & Counselors) and as its own #14 entry. We keep both as written, since the doc says 14. Confirm whether that's intended.
2. **Document size limit**: "3 MB to 5 MB" vs the quoted "3MB" error. Defaulting to 3 MB.
3. **Article image limit of 100–200 KB** applied to the *upload* would reject most phone or camera images. Proposed: accept up to 2 MB, then auto-compress the *stored* image to WebP ≤ 200 KB at 1200×630.
4. **WhatsApp vs commission**: Plan 1 hides LinkedIn (presumably so patients don't bypass the 20%), but the doc makes WhatsApp optional "for all practitioners". Showing a Plan 1 doctor's WhatsApp lets patients bypass commission. Proposed: Plan 1 profiles show the **platform's** WhatsApp enquiry widget; Plan 2 and Intl show the doctor's own.
5. **City in URLs** (`/dermatologists/lahore`) but filters mention only country. We add a city field.
6. **"Direct dealing / direct booking buttons"** for paid plans is undefined (see §7).
7. **Fee range filter across currencies** (PKR vs USD). Proposed: the filter applies within the currency of the selected country.
8. **Ask a Doctor**: must patients log in to ask (shown anonymously)? Proposed yes, to limit spam. Are doctors' answers moderated, or only questions?

### Business decisions needed

- Plan 2 6-month and 1-year prices.
- Expiry behaviour and grace period.
- Booking windows.
- Cancellation and refund rules.
- Payout schedule and method for Plan 1 doctors.
- Session delivery method.
- SMS provider.
- Which PK gateway has an approved merchant account.
- Stripe-eligible entity.
- Final brand name, logo and domain.
- The full sub-specialty and symptom lists.

### Technical risks

- Gateway approval and timelines.
- Stripe eligibility.
- PageSpeed depends on hosting quality. Laravel needs a host with **cron + queue worker** support (VPS or managed Laravel hosting such as Forge or Ploi on DigitalOcean or Hetzner), not basic shared hosting.
- Email deliverability needs a transactional provider (SES, Postmark or Mailgun).

### Legal & compliance (for client/legal review: we implement security controls, not legal compliance)

- **Data protection**: EU/UK GDPR (UK and EU patients), Pakistan's draft Personal Data Protection Bill, UAE PDPL **and** UAE Federal Law No. 2 of 2019 (health data, with restrictions on storing data outside the UAE), Saudi PDPL, Qatar PDPPL, Canada PIPEDA, plus provincial health laws. In the USA, HIPAA may apply depending on how practitioners use the platform.
- **Cross-border telemedicine licensing**: a practitioner licensed in Pakistan consulting a patient located in the UK or US may not be permitted by the patient's jurisdiction. The client should decide whether consultations are restricted by practitioner licence vs patient location, and the Terms must address it.
- **Consent**: explicit consent capture at registration and booking, telehealth disclaimers ("not for emergencies"), and data-retention and deletion policy.
- **Medical advice liability** on Ask a Doctor and the blog needs disclaimers and moderation policy.
- **Backups**: daily encrypted DB backups plus private document storage backups, kept off-server, with a tested restore procedure.
