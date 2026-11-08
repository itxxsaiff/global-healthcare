// Rich text editors (Quill "snow"): practitioner articles and admin legal pages.
// Non-technical users get a Word-like toolbar; the form still posts plain HTML,
// which the server sanitises (config/purify.php) before saving.
import Quill from 'quill';
import 'quill/dist/quill.snow.css';

const TOOLBAR = [
    [{ header: [2, 3, false] }],
    ['bold', 'italic', 'underline'],
    [{ list: 'ordered' }, { list: 'bullet' }],
    ['blockquote', 'link'],
    ['clean'],
];

/**
 * Quill 2 stores lists as <ol><li data-list="bullet">, which the sanitiser would turn
 * into numbered lists. getSemanticHTML() gives proper <ul>/<ol>.
 */
const htmlOf = (quill) => {
    if (quill.getText().trim() === '') return '';
    return quill.getSemanticHTML().replace(/&nbsp;/g, ' ');
};

function mount(holder, input, { placeholder, onChange } = {}) {
    const quill = new Quill(holder, { theme: 'snow', placeholder, modules: { toolbar: TOOLBAR } });
    quill.clipboard.dangerouslyPasteHTML(input.value || '');
    quill.history.clear();

    const sync = () => {
        input.value = htmlOf(quill);
        onChange?.(quill);
    };
    quill.on('text-change', sync);
    sync();

    return quill;
}

// ── Article editor (with word counter against the client's ranges) ──
const articleHolder = document.getElementById('editor');
if (articleHolder) {
    const counter = document.getElementById('word-counter');
    const ranges = JSON.parse(articleHolder.dataset.ranges);
    const currentType = () => document.querySelector('input[name="type"]:checked')?.value || 'standard';
    let quill;

    const render = () => {
        const text = quill.getText().trim();
        const words = text ? text.split(/\s+/).length : 0;
        const [min, max] = ranges[currentType()];
        counter.textContent = `${words.toLocaleString()} words · aim for ${min.toLocaleString()}–${max.toLocaleString()}`;
        counter.classList.toggle('is-over', words > max);
        counter.classList.toggle('is-ok', words >= min && words <= max);
    };

    quill = mount(articleHolder, document.getElementById('body'), {
        placeholder: 'Start with the question your patients ask most…',
        onChange: () => quill && render(),
    });
    document.querySelectorAll('input[name="type"]').forEach((r) => r.addEventListener('change', render));
    render();
}

// ── Generic editors: <div data-rich-editor="input-id" data-placeholder="…"> ──
document.querySelectorAll('[data-rich-editor]').forEach((holder) => {
    const input = document.getElementById(holder.dataset.richEditor);
    if (!input) return;
    const quill = mount(holder, input, { placeholder: holder.dataset.placeholder || 'Start writing…' });

    // Warn before leaving with unsaved changes.
    const form = input.form;
    let dirty = false;
    quill.on('text-change', (_d, _o, source) => { if (source === 'user') dirty = true; });
    form?.addEventListener('submit', () => { dirty = false; });
    window.addEventListener('beforeunload', (e) => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
});
