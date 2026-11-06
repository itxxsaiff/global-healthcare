// Alpine component for the smart search box (specialties + symptoms + doctors).
document.addEventListener('alpine:init', () => {
    window.Alpine.data('searchSuggest', (endpoint, initial = '') => ({
        q: initial,
        open: false,
        loading: false,
        groups: [],
        active: -1,
        timer: null,

        get flat() {
            return this.groups.flatMap((g) => g.items);
        },

        onInput() {
            clearTimeout(this.timer);
            if (this.q.trim().length < 2) {
                this.groups = [];
                this.open = false;
                return;
            }
            this.timer = setTimeout(() => this.fetch(), 180);
        },

        async fetch() {
            this.loading = true;
            try {
                const res = await fetch(`${endpoint}?q=${encodeURIComponent(this.q.trim())}`, { headers: { Accept: 'application/json' } });
                const data = await res.json();
                this.groups = data.groups || [];
                this.active = -1;
                this.open = this.flat.length > 0;
            } catch (e) {
                this.open = false;
            } finally {
                this.loading = false;
            }
        },

        move(step) {
            if (!this.open) return;
            const n = this.flat.length;
            this.active = (this.active + step + n) % n;
        },

        choose(event) {
            if (this.open && this.active >= 0) {
                event.preventDefault();
                window.location.href = this.flat[this.active].url;
            }
        },

        isActive(item) {
            return this.flat[this.active] === item;
        },
    }));
});
