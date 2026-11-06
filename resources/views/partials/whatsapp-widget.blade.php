@php $wa = preg_replace('/\D+/', '', (string) ($number ?? \App\Models\Setting::get('platform_whatsapp'))); @endphp
@if ($wa)
<a href="https://wa.me/{{ $wa }}?text={{ urlencode($message ?? 'Hi, I have a question about booking a consultation.') }}"
   class="btn btn-whatsapp rounded-pill shadow position-fixed d-inline-flex align-items-center gap-2"
   style="right: 1.1rem; bottom: 1.1rem; z-index: 1040; padding: .7rem 1.1rem"
   target="_blank" rel="noopener" aria-label="Ask us on WhatsApp">
    <x-icon name="whatsapp" size="lg" />
    <span class="d-none d-sm-inline">Questions? WhatsApp us</span>
</a>
@endif
