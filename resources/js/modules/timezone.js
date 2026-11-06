// Detect the visitor's time zone and fill any [data-detect-timezone] input / cookie,
// so practitioner slots can be shown in the patient's local time.
const tz = Intl.DateTimeFormat().resolvedOptions().timeZone;

if (tz) {
    document.querySelectorAll('[data-detect-timezone]').forEach((el) => {
        if (!el.value) el.value = tz;
    });
    if (!document.cookie.includes('tz=')) {
        document.cookie = `tz=${encodeURIComponent(tz)}; path=/; max-age=31536000; SameSite=Lax`;
    }
}
