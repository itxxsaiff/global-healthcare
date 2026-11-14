// Show/hide eye on every password field (login, register, reset, confirm, account settings),
// plus live hints: "at least 8 characters with letters and numbers" and "passwords match".
// Added automatically, so new password fields get it too. Opt out with data-no-toggle.

const meetsRule = (v) => v.length >= 8 && /[a-z]/i.test(v) && /\d/.test(v);

document.querySelectorAll('input[type="password"]:not([data-no-toggle])').forEach((input) => {
    const wrap = document.createElement('div');
    wrap.className = 'password-field';
    if (input.classList.contains('form-control-lg')) wrap.classList.add('is-lg');
    input.parentNode.insertBefore(wrap, input);
    wrap.appendChild(input);

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'toggle-password';
    btn.setAttribute('aria-pressed', 'false');
    btn.setAttribute('aria-controls', input.id || '');
    const render = (shown) => {
        btn.innerHTML = `<i class="${shown ? 'ri-eye-off-line' : 'ri-eye-line'}" aria-hidden="true"></i>`;
        btn.setAttribute('aria-label', shown ? 'Hide password' : 'Show password');
        btn.title = shown ? 'Hide password' : 'Show password';
        btn.setAttribute('aria-pressed', String(shown));
    };
    render(false);
    wrap.appendChild(btn);

    btn.addEventListener('click', () => {
        const show = input.type === 'password';
        const { selectionStart, selectionEnd } = input;
        input.type = show ? 'text' : 'password';
        render(show);
        input.focus({ preventScroll: true });
        try { input.setSelectionRange(selectionStart, selectionEnd); } catch { /* not supported for this type */ }
    });

    // Always submit as a password field, so browsers and password managers behave normally.
    input.form?.addEventListener('submit', () => {
        input.type = 'password';
        render(false);
    });
});

// Live requirement hint under new-password fields.
document.querySelectorAll('[data-password-hint]').forEach((hint) => {
    const input = hint.parentElement.querySelector('input[name="password"]');
    if (!input) return;
    const base = hint.textContent;
    const update = () => {
        const ok = meetsRule(input.value);
        hint.classList.toggle('is-ok', ok);
        hint.innerHTML = ok ? `<i class="ri-checkbox-circle-fill" aria-hidden="true"></i> ${base}` : base;
    };
    input.addEventListener('input', update);
    update();
});

// "Passwords match" under confirm fields.
document.querySelectorAll('input[data-confirms]').forEach((confirm) => {
    const original = confirm.form?.querySelector(`input[name="${confirm.dataset.confirms}"]`);
    const hint = confirm.closest('.password-field')?.parentElement.querySelector('[data-confirm-hint]');
    if (!original || !hint) return;
    const update = () => {
        if (!confirm.value) { hint.hidden = true; return; }
        const match = confirm.value === original.value;
        hint.hidden = false;
        hint.classList.toggle('is-ok', match);
        hint.classList.toggle('is-off', !match);
        hint.innerHTML = match
            ? '<i class="ri-checkbox-circle-fill" aria-hidden="true"></i> Passwords match'
            : "Passwords don't match yet";
    };
    confirm.addEventListener('input', update);
    original.addEventListener('input', update);
});
