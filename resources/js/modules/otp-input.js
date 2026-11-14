// Email verification code screen: digits only, submit once all 6 are in,
// and count down the "Send a new code" button (the server enforces the wait too).
document.querySelectorAll('input[data-otp]').forEach((input) => {
    let submitted = false;
    input.addEventListener('input', () => {
        const digits = input.value.replace(/\D/g, '').slice(0, 6);
        if (input.value !== digits) input.value = digits;
        input.classList.remove('is-invalid');
        if (digits.length === 6 && !submitted) {
            submitted = true;
            input.form.requestSubmit();
        }
    });
});

document.querySelectorAll('[data-resend-wait]').forEach((button) => {
    let left = parseInt(button.dataset.resendWait, 10) || 0;
    if (left <= 0) return;
    const label = button.dataset.label;
    const timer = setInterval(() => {
        left -= 1;
        if (left <= 0) {
            clearInterval(timer);
            button.disabled = false;
            button.textContent = label;
            return;
        }
        button.innerHTML = `${label} <span class="text-muted">in ${left}s</span>`;
    }, 1000);
});
