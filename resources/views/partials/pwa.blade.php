<div id="install-banner" class="position-fixed start-0 end-0 bottom-0 p-3 d-md-none" style="z-index: 1050" hidden>
    <div class="card-soft shadow p-3 d-flex gap-3 align-items-center" data-no-reveal>
        <img src="/icons/icon-192.png" width="44" height="44" alt="" class="rounded-3">
        <div class="flex-grow-1 small">
            <strong class="d-block">Get the {{ config('app.name') }} app</strong>
            <span class="native-copy">Faster booking, right from your home screen.</span>
            <span class="ios-copy">Tap <strong>Share</strong> then <strong>Add to Home Screen</strong>.</span>
        </div>
        <button class="btn btn-primary btn-sm native-copy" data-install>Install</button>
        <button class="btn btn-sm btn-link text-muted p-1" data-dismiss aria-label="Not now"><x-icon name="x" /></button>
    </div>
</div>
<style>#install-banner[data-mode="ios"] .native-copy, #install-banner[data-mode="native"] .ios-copy { display: none !important; }</style>
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
    }
</script>
