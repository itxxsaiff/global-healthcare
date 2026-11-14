import '@fontsource-variable/plus-jakarta-sans';
import '@fontsource-variable/figtree';

import { Collapse, Dropdown, Modal, Offcanvas, Toast, Tooltip } from 'bootstrap';
import Alpine from 'alpinejs';

import './modules/header';
import './modules/word-counter';
import './modules/search-suggest';
import './modules/intro-video';
import './modules/timezone';
import './modules/file-limit';
import './modules/pwa';
import './modules/reveal';

window.bootstrap = { Collapse, Dropdown, Modal, Offcanvas, Toast, Tooltip };
window.Alpine = Alpine;

document.addEventListener('alpine:init', () => {
    // Registered in the modules above.
});

Alpine.start();

document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => new Tooltip(el));
