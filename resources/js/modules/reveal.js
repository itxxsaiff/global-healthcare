// Scroll reveal for the public site: sections fade up as they enter the viewport,
// items that arrive together (a row of cards) are staggered, and hero numbers count up.
//
// Opt-in per element with data-reveal="up|fade|zoom|left|right" (+ optional data-reveal-delay ms),
// and a list of common blocks below is revealed automatically so new pages get it for free.
// Uses the individual `translate`/`scale` properties so it never fights an element's own
// `transform` (hover lifts, floating animations). Without JS, or with reduced motion,
// everything is simply visible.

const AUTO = [
    '.section-head',
    '.spec-grid > *',
    '.plans-banner',
    '.how-card',
    '.symptom-band',
    '.qa-item',
    '.cta-band',
    '.profile-hero',
    '.results-head',
    '.filter-panel',
    '.directory-head .container > *',
    '.row.g-4 > [class*="col"]',
    '.row.g-5 > [class*="col"]',
    '.card-soft',
    '.practitioner-card',
    '.site-footer .row > *',
];

const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const scope = document.querySelector('[data-reveal-scope]');

if (!scope || reduced) document.documentElement.classList.remove('has-reveal');

if (scope && !reduced && 'IntersectionObserver' in window) {
    window.__reveal = true;
    document.documentElement.classList.add('has-reveal');

    const mark = (el) => {
        if (el.classList.contains('reveal')) return;
        if (el.closest('.hero-dark') && !el.hasAttribute('data-reveal')) return;
        // Never nest reveals: a parent that animates already carries its children.
        if (el.parentElement?.closest('.reveal') || el.querySelector('.reveal')) {
            el.removeAttribute('data-reveal');
            return;
        }
        el.classList.add('reveal', `reveal-${el.dataset.reveal || 'up'}`);
    };

    scope.querySelectorAll('[data-reveal]').forEach(mark);
    scope.querySelectorAll(AUTO.join(',')).forEach(mark);

    const finish = (el) => {
        // Hand the element back to its own styles (hover transitions etc.).
        el.classList.remove('reveal', 'is-visible', 'reveal-up', 'reveal-fade', 'reveal-zoom', 'reveal-left', 'reveal-right');
        el.style.removeProperty('--reveal-delay');
        el.removeAttribute('data-reveal');
    };

    const show = (el, delay) => {
        el.style.setProperty('--reveal-delay', `${delay}ms`);
        el.classList.add('is-visible');
        el.querySelectorAll('[data-count]').forEach(countUp);
        if (el.matches('[data-count]')) countUp(el);
        setTimeout(() => finish(el), delay + 1000);
    };

    const io = new IntersectionObserver((entries) => {
        let i = 0;
        entries
            .filter((e) => e.isIntersecting)
            .sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top || a.boundingClientRect.left - b.boundingClientRect.left)
            .forEach((e) => {
                const own = Number(e.target.dataset.revealDelay);
                show(e.target, Number.isFinite(own) && own > 0 ? own : Math.min(i++, 6) * 90);
                io.unobserve(e.target);
            });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0 });

    scope.querySelectorAll('.reveal').forEach((el) => io.observe(el));

    // A jump (End key, anchor link) can skip elements entirely; show anything already above us.
    let ticking = false;
    window.addEventListener('scroll', () => {
        if (ticking) return;
        ticking = true;
        requestAnimationFrame(() => {
            ticking = false;
            scope.querySelectorAll('.reveal:not(.is-visible)').forEach((el) => {
                if (el.getBoundingClientRect().bottom < 0) { io.unobserve(el); show(el, 0); }
            });
        });
    }, { passive: true });

    // Safety net: never leave anything hidden (e.g. printing, odd layouts).
    window.addEventListener('beforeprint', () => scope.querySelectorAll('.reveal').forEach(finish));
}

// Counts a number up from 0. Markup keeps the real value, so it reads fine without JS.
function countUp(el) {
    if (el.dataset.counted) return;
    el.dataset.counted = '1';
    const target = Number(el.dataset.count);
    if (!Number.isFinite(target) || reduced) return;
    const suffix = el.dataset.suffix || '';
    const duration = 1400;
    const start = performance.now();
    const tick = (now) => {
        const p = Math.min((now - start) / duration, 1);
        const eased = 1 - Math.pow(1 - p, 3);
        el.textContent = Math.round(target * eased).toLocaleString() + suffix;
        if (p < 1) requestAnimationFrame(tick);
    };
    el.textContent = '0' + suffix;
    requestAnimationFrame(tick);
}
