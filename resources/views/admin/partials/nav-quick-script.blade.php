{{-- Skróty menu panelu (Alpine navQuick): ulubione i ostatnio otwarte w localStorage. Używa ich menu boczne, górny pasek
     Metro w panelu i ten sam pasek na stronie publicznej (partials/site-admin-bar). --}}
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('navQuick', (storeKey, current) => ({
            pins: [], recents: [], current: current, status: '',
            init() {
                this.pins = this.read(storeKey + ':pins');
                this.recents = this.read(storeKey + ':recent');
                if (this.current && this.current.url) {
                    this.recents = [this.current].concat(this.recents.filter(r => r.url !== this.current.url)).slice(0, 8);
                    this.write(storeKey + ':recent', this.recents);
                }
            },
            read(key) {
                try {
                    const v = JSON.parse(localStorage.getItem(key) || '[]');
                    // Tylko adresy z tej witryny (wpis w localStorage nie może prowadzić gdzie indziej).
                    return Array.isArray(v) ? v.filter(e => e && e.label && typeof e.url === 'string' && (e.url.startsWith('/') || e.url.startsWith(location.origin + '/'))).slice(0, 12) : [];
                } catch (e) { return []; }
            },
            write(key, value) { try { localStorage.setItem(key, JSON.stringify(value)); } catch (e) {} },
            get isPinned() { return !! this.current && this.pins.some(p => p.url === this.current.url); },
            get visibleRecents() { return this.recents.filter(r => ! this.pins.some(p => p.url === r.url) && (! this.current || r.url !== this.current.url)).slice(0, 4); },
            togglePin() {
                if (! this.current) return;
                if (this.isPinned) { return this.unpin(this.current.url); }
                this.pins = this.pins.concat([this.current]).slice(-8);
                this.write(storeKey + ':pins', this.pins);
                this.status = 'Dodano do ulubionych: ' + this.current.label;
            },
            unpin(url) {
                const gone = this.pins.find(p => p.url === url);
                this.pins = this.pins.filter(p => p.url !== url);
                this.write(storeKey + ':pins', this.pins);
                this.status = 'Usunięto z ulubionych: ' + (gone ? gone.label : '');
            },
        }));
    });
</script>
