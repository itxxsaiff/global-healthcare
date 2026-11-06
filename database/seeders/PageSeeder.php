<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Starter legal pages. Admin edits them later. Only inserts if missing so
 * admin edits are never overwritten. These are DRAFTS for legal review.
 */
class PageSeeder extends Seeder
{
    public function run(): void
    {
        $app = config('app.name');

        $pages = [
            'terms' => ['Terms & Conditions', 'The rules for using '.$app.' as a patient or practitioner.', <<<HTML
<p class="lead">These terms explain how {$app} works and what we expect from patients and practitioners. Please read them before you book or list a practice.</p>
<h2>1. What we are</h2>
<p>{$app} is a directory and booking platform that helps patients find verified practitioners for <strong>non-emergency online consultations</strong>. We are not a hospital or clinic, and we do not provide medical advice ourselves.</p>
<h2>2. Not for emergencies</h2>
<p>If you have chest pain, difficulty breathing, severe bleeding, thoughts of harming yourself or any other emergency, contact your local emergency number or go to the nearest hospital immediately.</p>
<h2>3. Practitioners</h2>
<p>Every practitioner submits identity, qualification and licence documents, which our team checks before the profile is published. Practitioners remain fully responsible for the advice they give and for practising within the rules of their licensing body and the patient's location.</p>
<h2>4. Bookings and payments</h2>
<p>For practitioners on the Starter plan, a booking is a request until the practitioner accepts it and you complete payment within the time shown. For practitioners on paid plans, payment is arranged directly with the practitioner.</p>
<h2>5. Reviews and community content</h2>
<p>Only patients with a completed booking can leave a review. Reviews, questions and answers are moderated. Answers on the Ask a Doctor forum are general information, not a diagnosis.</p>
<h2>6. Accounts</h2>
<p>Keep your login details private. We may suspend accounts that misuse the platform, give false information or behave unprofessionally.</p>
<h2>7. Changes</h2>
<p>We may update these terms. We will tell you about important changes by email or on the site.</p>
HTML],
            'privacy' => ['Privacy Policy', 'How '.$app.' collects, uses and protects your personal and health information.', <<<HTML
<p class="lead">Your health information is sensitive. This policy explains what we collect, why, and the choices you have.</p>
<h2>What we collect</h2>
<ul>
<li><strong>Account details:</strong> name, email, phone, gender, age and time zone.</li>
<li><strong>Booking details:</strong> appointment times, the practitioner you booked and any note you choose to share.</li>
<li><strong>Practitioner verification documents:</strong> stored privately and visible only to our verification team.</li>
<li><strong>Payment records:</strong> handled by our payment partners. We never see or store your card number.</li>
</ul>
<h2>How we use it</h2>
<p>To run your bookings, send you confirmations and reminders, verify practitioners, prevent fraud and improve the service. We do not sell your data.</p>
<h2>Who can see it</h2>
<p>Your practitioner sees the booking details you share with them. Questions on Ask a Doctor are published anonymously.</p>
<h2>Your rights</h2>
<p>You can ask to access, correct, export or delete your data, and to withdraw consent, by contacting our support team. Where GDPR or similar laws apply, you may also complain to your local data protection authority.</p>
<h2>Security</h2>
<p>We use encrypted connections, encrypt sensitive booking notes at rest, restrict staff access and keep an audit trail of sensitive actions.</p>
<h2>Retention</h2>
<p>We keep records only as long as needed for the service and for legal, tax and dispute purposes.</p>
HTML],
            'refund-policy' => ['Refund & Cancellation Policy', 'When you can cancel a booking and how refunds work.', <<<HTML
<p class="lead">Plans change. Here is what happens when they do.</p>
<h2>If you cancel</h2>
<ul>
<li>More than 24 hours before your session: full refund.</li>
<li>Less than 24 hours before your session: the booking is not refundable, except in special circumstances reviewed by our team.</li>
</ul>
<h2>If your practitioner cancels or doesn't show up</h2>
<p>You receive a full refund. Report a no-show from your dashboard within 48 hours and our team will investigate.</p>
<h2>Unpaid requests</h2>
<p>If payment is not completed within the time shown on your booking, the request expires and the time slot is released. Nothing is charged.</p>
<h2>How refunds are paid</h2>
<p>Refunds go back to the original payment method. Depending on your bank or wallet, they can take 5–10 working days to appear.</p>
<h2>Practitioner subscriptions</h2>
<p>Subscriptions can be cancelled at any time and remain active until the end of the paid period. Partial periods are not refunded.</p>
HTML],
        ];

        foreach ($pages as $slug => [$title, $meta, $body]) {
            Page::query()->firstOrCreate(['slug' => $slug], [
                'title' => $title,
                'meta_description' => $meta,
                'body' => trim($body),
            ]);
        }
    }
}
