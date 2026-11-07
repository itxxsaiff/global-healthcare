# Questions for the client

We've started building. These answers help us finish the platform correctly. Where you don't have a preference, we'll go with the suggestion shown.

## Payments & plans

1. **Payment gateway.** Which Pakistani gateway do you already have (or are applying for) a merchant account with: PayFast, Safepay, JazzCash or EasyPaisa? *Suggestion: launch with one, add others later.*
   **Update 2026-11-07:** merchant setup is in progress and **Safepay** is the likely choice. The Safepay driver will be built against its sandbox once the merchant account is approved. Until then the site runs on the Fake test gateway.
2. **Stripe.** Stripe doesn't accept Pakistan-registered businesses. Do you have a company or bank account in the UAE, UK, US or another Stripe country? If not, we'll suggest an alternative for USD subscriptions.
3. **Plan 2 prices.** What are the 6-month and 1-year prices for Plan 2 (PKR 5,000/month)?
4. **Plan 2 expiry.** When a Plan 2 doctor doesn't renew, should their profile be **hidden**, or **moved to Plan 1 (free + 20%)**?
5. **Grace period.** How many days of grace after a failed or expired payment before the profile hides? *Suggestion: 3 days.*
6. **"Direct dealing" (Plan 2 and international).** Do patients still book a time slot through our calendar, with payment arranged directly with the doctor? Or should these profiles only show contact and WhatsApp buttons?
7. **Paying Plan 1 doctors.** The platform collects the full fee and keeps 20%. How and how often should doctors receive their 80%? (e.g. weekly bank transfer by admin)

## Bookings

8. How many hours does a doctor have to accept a request? *Suggestion: 24 hours.*
9. After the doctor accepts, how long does the patient have to pay? *Suggestion: 12 hours, and always before the appointment.*
10. **Consultation method.** How does the actual consultation happen: the doctor sends a Zoom or Google Meet link, a WhatsApp call, or something else?
11. **Cancellations & refunds.** Up to how many hours before the session can a patient cancel for a full refund? What happens if the doctor cancels?
12. Should a session be marked "completed" automatically after its time passes, or by the doctor?
13. Do you want **SMS** alerts as well as email? (This needs an SMS provider and has a monthly cost.)

## Content & profiles

14. "Sexologists" appears both inside *Clinical Psychologists & Counselors* and as its own specialty. Should we keep both?
15. Document upload limit: **3 MB** or **5 MB**?
16. Blog images: the doc says a 100–200 KB maximum. Can we accept larger uploads (up to 2 MB) and compress them automatically to under 200 KB? This is much easier for doctors.
17. **WhatsApp on free (Plan 1) profiles.** If we show the doctor's own WhatsApp, patients can book outside the platform and skip the 20%. Should Plan 1 profiles use the **platform's** WhatsApp number instead?
18. Please share the full list of **sub-specialties** and **symptoms/diseases** per specialty (or approve the list we prepare).
19. **Ask a Doctor.** Should patients be logged in to ask a question (still shown anonymously)? Should doctors' answers also go through admin approval?
20. After a doctor is verified, which profile changes should require re-verification? *Suggestion: name, licence number and documents.*

## Business & legal

21. Final **brand name**, logo, colours (if any) and domain.
22. Can a doctor licensed in Pakistan consult patients located in the UK, USA, UAE and so on? Please check with a legal advisor. Telemedicine and health-data rules differ by country, and the Terms and Privacy Policy need to reflect your decision.
23. Hosting. Laravel needs a server that runs background jobs (a VPS or managed Laravel hosting), not basic shared hosting. Shall we recommend one?
