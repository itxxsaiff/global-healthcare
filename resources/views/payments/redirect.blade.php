<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
    <title>Redirecting to secure payment…</title>
    <style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#F5F9F8;color:#0F2A2A;font:16px/1.6 system-ui,sans-serif}main{text-align:center;padding:24px}button{background:#0E8474;color:#fff;border:0;border-radius:10px;padding:.7rem 1.3rem;font-weight:600;font-size:1rem}</style>
</head>
<body>
<main>
    <p>Taking you to the secure payment page…</p>
    {{-- Signed fields generated server-side; the browser just carries them to the gateway. --}}
    <form id="gateway-form" method="POST" action="{{ $checkout->formAction }}">
        @foreach ($checkout->fields as $name => $value)
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach
        <noscript><button>Continue to payment</button></noscript>
    </form>
</main>
<script>document.getElementById('gateway-form').submit();</script>
</body>
</html>
