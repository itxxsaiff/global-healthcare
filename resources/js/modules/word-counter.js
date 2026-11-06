// Live word counter: <textarea data-word-limit="200" data-word-min="0"> + <span data-word-count-for="id">
const countWords = (text) => {
    const plain = text.replace(/<[^>]*>/g, ' ').replace(/&nbsp;/g, ' ').trim();
    return plain ? plain.split(/\s+/).length : 0;
};

window.countWords = countWords;

const bind = (field) => {
    const out = document.querySelector(`[data-word-count-for="${field.id}"]`);
    if (!out) return;
    const max = parseInt(field.dataset.wordLimit || '0', 10);
    const min = parseInt(field.dataset.wordMin || '0', 10);
    const render = () => {
        const n = countWords(field.value);
        out.textContent = max ? `${n} / ${max} words` : `${n} words`;
        out.classList.toggle('is-over', (max && n > max) || (min && n > 0 && n < min));
        out.classList.toggle('is-ok', (!max || n <= max) && (!min || n >= min) && n > 0);
    };
    field.addEventListener('input', render);
    render();
};

document.querySelectorAll('[data-word-limit], [data-word-min]').forEach(bind);
