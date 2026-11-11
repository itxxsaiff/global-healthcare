# Allbari

An international telehealth directory and booking platform. Patients find **verified** doctors, psychologists, dietitians and therapists for **non-emergency online consultations**. Practitioners list for free (20% commission) or on a flat subscription (0% commission).

Built with Laravel 13 · PHP 8.4 · MySQL · Blade + Bootstrap 5 + Alpine.js.

- Architecture & schema: [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md)
- Defaults chosen where the brief was silent: [`docs/DECISIONS.md`](docs/DECISIONS.md)
- Questions still open with the client: [`docs/CLIENT_QUESTIONS.md`](docs/CLIENT_QUESTIONS.md)

---

## What's included

| Area | Highlights |
|---|---|
| **Directory & search** | Search by specialty, sub-specialty, **symptom/disease**, gender, spoken language, country/city, fee range and approved custom tags. SEO landing pages such as `/dermatologists`, `/dermatologists/lahore` and `/symptom/acne`. Live suggestions as you type. |
| **Practitioner onboarding** | 5-step profile wizard. 200-word bio with a live counter. Headshot auto-converted to WebP. ID, degree and licence uploads go to **private storage** (3 MB limit with the client's exact error message). Custom tags go to an admin queue. |
| **Verification** | Admin queue to approve or request changes. Approval emails unlock plans. Re-verification is triggered when the licence, documents or name change. Every document view is audit-logged. |
| **Memberships** | PK Starter (free, 20% commission). PK Pro at PKR 5,000/month, plus 6- and 12-month options. International plans at $30/month, $150 per 6 months or $280/year via Stripe auto-debit. Profiles are hidden automatically when a plan lapses. |
| **Two-step booking** | Request → practitioner accepts → payment link → paid → confirmed → alerts to the practitioner, admin and patient → commission recorded. A time-zone-aware slot picker. Double-booking is blocked at database level. Unanswered and unpaid bookings expire. Refund rules. |
| **Payments** | One gateway interface with drivers for **Fake** (development), **JazzCash** and **Stripe**. Idempotent, signature-verified webhooks. Payments are re-verified server-side. |
| **Wallet** | Append-only commission ledger, per-practitioner balances, payout recording, refund reversals. |
| **Community** | Moderated reviews (completed + paid bookings only). Ask a Doctor anonymous Q&A. Practitioner blog with a word-count-enforced editor and auto-compressed images. |
| **Support** | Complaint tickets (No-show, Technical, Payment dispute, Unprofessional behaviour), WhatsApp enquiry widget, email and in-app notifications. |
| **SEO / PWA** | MedicalBusiness, Physician, FAQPage, QAPage and Article schema. Generated sitemap. Environment-aware `robots.txt`. Installable PWA with an offline page. The service worker **never caches personal pages**. |
| **Security** | Role-based areas, admin 2FA (enforced in production), rate limits, no-store headers on private pages, encrypted booking notes, sanitised HTML, security headers. |

---

## Local setup

Requirements: PHP 8.4 (gd with WebP, intl, bcmath), Composer 2, MySQL 8+, Node 20+.

```bash
git clone https://github.com/itxxsaiff/global-healthcare.git
cd global-healthcare
composer install
npm install && npm run build
cp .env.example .env && php artisan key:generate
# set DB_DATABASE / DB_USERNAME / DB_PASSWORD in .env, then:
php artisan migrate --seed          # reference data + demo data (local only)
php artisan storage:link
php artisan serve                   # http://localhost:8000
php artisan queue:work              # emails & notifications (separate terminal)
php artisan schedule:work           # expiries, reminders, sitemap (separate terminal)
```

Emails go to `storage/logs/laravel.log` locally (`MAIL_MAILER=log`).

### Demo accounts (local seed only, password `password`)

| Role | Email |
|---|---|
| Admin | `admin@allbari.test` |
| Patient | `patient@allbari.test` |
| Practitioner (Plan 1, PKR) | `sana.mirza@doctor.test` |
| Practitioner (Pro, PKR) | `hamza.qureshi@doctor.test` |
| Practitioner (International) | `layla.haddad@doctor.test` |

With `PK_GATEWAY=fake`, paying for a booking or plan opens a **Test payment** page with "succeed" and "fail" buttons. This gateway refuses to run in production.

### Tests

```bash
mysql -e "CREATE DATABASE global_healthcare_test"   # once
php artisan test
```

The suite covers registration and access control, the full booking state machine (including race conditions, late payments and refunds), payment idempotency and webhook signatures, verification, document privacy, memberships and expiry, reviews, Q&A, the blog and back-office actions.

---

## Useful commands

| Command | Purpose |
|---|---|
| `php artisan app:create-admin you@example.com --name="Your Name"` | Create an admin. Admins can't self-register. |
| `php artisan app:sitemap` | Regenerate `public/sitemap.xml`. Also runs daily. |
| `php artisan schedule:list` | See scheduled jobs: booking expiry every 5 min, auto-complete, membership expiry hourly, reminders daily, sitemap daily. |

---

## Deploying to production

Use a VPS or managed Laravel hosting (Forge, Ploi, Cloudways…) that can run **cron** and a **queue worker**. Shared hosting is not suitable.

1. Point the web root at `public/`, use HTTPS, PHP 8.4 and MySQL 8.
2. Set the `.env` values (see the "Production hardening" block in `.env.example`):
   - `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://…`
   - `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true`
   - a transactional mail provider (SES, Postmark, Mailgun)
   - `PK_GATEWAY` / `INTL_GATEWAY` plus the gateway keys
3. Deploy:
   ```bash
   composer install --no-dev --optimize-autoloader
   npm ci && npm run build
   php artisan migrate --force
   php artisan db:seed --class=ReferenceDataSeeder --force   # idempotent
   php artisan db:seed --class=PlanSeeder --force
   php artisan db:seed --class=PageSeeder --force
   php artisan storage:link
   php artisan optimize
   php artisan app:create-admin
   ```
4. Cron: `* * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1`
5. Queue worker (Supervisor/systemd): `php artisan queue:work --tries=3 --max-time=3600`
6. Backups: daily encrypted MySQL dumps **and** `storage/app/private` (verification documents), stored off-server. Test a restore.

### Payment gateway go-live checklist

- **JazzCash**: set `JAZZCASH_MERCHANT_ID`, `JAZZCASH_PASSWORD` and `JAZZCASH_INTEGRITY_SALT`. Run real sandbox transactions end-to-end, then switch the two URLs to production. The return/IPN URL is `https://your-domain/webhooks/payments/jazzcash`.
- **Stripe**: requires a Stripe account under a supported business entity (Stripe does not onboard Pakistan-registered businesses). Set the keys and add a webhook endpoint at `https://your-domain/webhooks/payments/stripe` for `checkout.session.completed`, `invoice.paid`, `invoice.payment_failed` and `customer.subscription.deleted`.
- **PayFast / EasyPaisa / Safepay**: add a class implementing `App\Payments\Contracts\PaymentGateway` and register it in `App\Payments\PaymentManager`. Nothing else in the booking or membership code changes.
- Refunds are processed in the gateway's merchant portal, then recorded in **Admin → Bookings** (this reverses the commission ledger).

---

## Project layout (short version)

```
app/Enums               every status, incl. the booking state machine (BookingStatus)
app/Services            Booking/, Membership/, Verification/, Commission/, Search/, Media/, Content/
app/Payments            gateway contract, PaymentManager, PaymentService, drivers
app/Http/Controllers    Public/, Patient/, Practitioner/, Admin/, Webhooks/
routes/                 web.php (public), patient.php, practitioner.php, admin.php, webhooks.php, console.php (scheduler)
resources/scss          design tokens + components (warm paper / forest / clay palette)
resources/views         layouts, components, public, patient, practitioner, admin
tests/Feature           end-to-end feature tests
```

## Not legal advice

Security controls are implemented, but legal compliance (GDPR/UK GDPR, UAE health-data law, Saudi PDPL, HIPAA where relevant, cross-border telemedicine licensing) must be reviewed by the client's legal advisers. The legal pages are **drafts**.
