<footer class="site-footer pt-5 mt-auto">
    <div class="container pb-4">
        <div class="row g-4 g-lg-5">
            <div class="col-lg-4">
                <x-logo />
                <p class="mt-3 mb-4" style="max-width: 22rem">Verified doctors, psychologists and therapists you can talk to from home, in the language you're most comfortable with.</p>
                <div class="emergency-note p-3 d-flex gap-2 small">
                    <x-icon name="alert" class="mt-1" />
                    <div><strong>Not for emergencies.</strong> If you or someone near you is in danger, call your local emergency number or go to the nearest hospital.</div>
                </div>
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <h6>Patients</h6>
                <ul class="list-unstyled d-grid gap-2">
                    <li><a href="{{ route('directory') }}">Find a doctor</a></li>
                    <li><a href="{{ route('questions.index') }}">Ask a doctor</a></li>
                    <li><a href="{{ route('articles.index') }}">Health articles</a></li>
                    <li><a href="{{ route('register.patient') }}">Create account</a></li>
                </ul>
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <h6>Practitioners</h6>
                <ul class="list-unstyled d-grid gap-2">
                    <li><a href="{{ route('for-practitioners') }}">Why join</a></li>
                    <li><a href="{{ route('for-practitioners') }}#plans">Plans & pricing</a></li>
                    <li><a href="{{ route('register.practitioner') }}">Apply to list</a></li>
                    <li><a href="{{ route('login') }}">Practitioner login</a></li>
                </ul>
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <h6>Popular</h6>
                <ul class="list-unstyled d-grid gap-2">
                    @foreach (\App\Models\Specialty::tree()->take(5) as $s)
                        <li><a href="{{ route('specialties.show', $s) }}">{{ $s->name }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <h6>Company</h6>
                <ul class="list-unstyled d-grid gap-2">
                    <li><a href="{{ route('pages.show', 'terms') }}">Terms & Conditions</a></li>
                    <li><a href="{{ route('pages.show', 'privacy') }}">Privacy Policy</a></li>
                    <li><a href="{{ route('pages.show', 'refund-policy') }}">Refunds & Cancellations</a></li>
                    <li><a href="mailto:{{ \App\Models\Setting::get('support_email') }}">Contact support</a></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="footer-note py-3">
        <div class="container d-flex flex-column flex-md-row justify-content-between gap-2">
            <span>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</span>
            <span>Pakistan · UAE · Saudi Arabia · UK · USA · Canada · Qatar · Oman · Kuwait</span>
        </div>
    </div>
</footer>
