// Article editor: Quill (snow) + live word counter against the client's ranges.
import Quill from 'quill';
import 'quill/dist/quill.snow.css';

const holder = document.getElementById('editor');
if (holder) {
    const input = document.getElementById('body');
    const counter = document.getElementById('word-counter');
    const typeSelect = document.querySelectorAll('input[name="type"]');
    const ranges = JSON.parse(holder.dataset.ranges);

    const quill = new Quill(holder, {
        theme: 'snow',
        placeholder: 'Start with the question your patients ask most…',
        modules: {
            toolbar: [[{ header: [2, 3, false] }], ['bold', 'italic', 'underline'], [{ list: 'ordered' }, { list: 'bullet' }], ['blockquote', 'link'], ['clean']],
        },
    });
    quill.clipboard.dangerouslyPasteHTML(input.value || '');

    const currentType = () => document.querySelector('input[name="type"]:checked')?.value || 'standard';
    const render = () => {
        const text = quill.getText().trim();
        const words = text ? text.split(/\s+/).length : 0;
        const [min, max] = ranges[currentType()];
        counter.textContent = `${words.toLocaleString()} words · aim for ${min.toLocaleString()}–${max.toLocaleString()}`;
        counter.classList.toggle('is-over', words > max);
        counter.classList.toggle('is-ok', words >= min && words <= max);
        input.value = quill.root.innerHTML === '<p><br></p>' ? '' : quill.root.innerHTML;
    };
    quill.on('text-change', render);
    typeSelect.forEach((r) => r.addEventListener('change', render));
    render();
}
