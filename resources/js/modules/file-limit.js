// Front-end half of the upload limits: <input type="file" data-max-kb="3072" data-max-message="...">
// The server enforces the same limits; this just saves people a slow failed upload.
document.querySelectorAll('input[type="file"][data-max-kb]').forEach((input) => {
    input.addEventListener('change', () => {
        const maxBytes = parseInt(input.dataset.maxKb, 10) * 1024;
        const feedback = input.parentElement.querySelector('.js-file-error');
        const tooBig = Array.from(input.files).some((f) => f.size > maxBytes);

        input.classList.toggle('is-invalid', tooBig);
        if (feedback) {
            feedback.textContent = tooBig ? input.dataset.maxMessage : '';
            feedback.classList.toggle('d-block', tooBig);
        }
        if (tooBig) input.value = '';

        // Live preview for image inputs.
        const preview = input.dataset.preview && document.querySelector(input.dataset.preview);
        if (preview && !tooBig && input.files[0]) {
            preview.src = URL.createObjectURL(input.files[0]);
            preview.closest('[hidden]')?.removeAttribute('hidden');
        }
    });
});
