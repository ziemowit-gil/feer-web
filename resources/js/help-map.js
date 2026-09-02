import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// Ikony Leaflet domyślnie ładują się przez URL względem CSS — pod Vite trzeba
// je jawnie zaimportować, inaczej znaczniki nie mają obrazka.
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

export function initHelpMap(el) {
    const points = JSON.parse(el.dataset.points || '[]');

    const map = L.map(el, { scrollWheelZoom: false }).setView([49.95, 20.0], 9);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19,
    }).addTo(map);

    const markersByCategory = {};

    points.forEach((point) => {
        const marker = L.marker([point.lat, point.lng]).addTo(map);
        marker.bindPopup(
            `<strong>${escapeHtml(point.name)}</strong><br>${escapeHtml(point.address || '')}` +
            (point.phone ? `<br><a href="tel:${escapeHtml(point.phone)}">${escapeHtml(point.phone)}</a>` : '') +
            (point.url ? `<br><a href="${escapeHtml(point.url)}" target="_blank" rel="noopener">Więcej informacji</a>` : '')
        );
        (markersByCategory[point.category] ||= []).push(marker);
    });

    document.querySelectorAll('[data-help-map-filter]').forEach((checkbox) => {
        checkbox.addEventListener('change', () => {
            const category = checkbox.dataset.helpMapFilter;
            const markers = markersByCategory[category] || [];
            markers.forEach((marker) => {
                if (checkbox.checked) {
                    marker.addTo(map);
                } else {
                    map.removeLayer(marker);
                }
            });
        });
    });

    document.querySelectorAll('[data-help-map-focus]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.helpMapFocus;
            const point = points.find((p) => String(p.id) === String(id));
            if (point) {
                map.setView([point.lat, point.lng], 16);
            }
        });
    });
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}
