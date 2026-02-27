import './bootstrap';
import '../css/app.css';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

const pathSegments = window.location.pathname.split('/').filter(Boolean);
const pathRoot = pathSegments[0] ?? '';
const liveResources = new Set(['users', 'leads', 'deals']);
const isIndexPage = pathSegments.length === 1 && liveResources.has(pathRoot);

if (isIndexPage) {
    setInterval(async () => {
        if (document.visibilityState !== 'visible') {
            return;
        }

        const currentContainer = document.getElementById('live-table-container');
        if (!currentContainer) {
            return;
        }

        try {
            const response = await fetch(window.location.href, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
                cache: 'no-store',
            });

            if (!response.ok) {
                return;
            }

            const html = await response.text();
            const parsedDoc = new DOMParser().parseFromString(html, 'text/html');
            const updatedContainer = parsedDoc.getElementById('live-table-container');

            if (!updatedContainer) {
                return;
            }

            if (currentContainer.innerHTML !== updatedContainer.innerHTML) {
                currentContainer.innerHTML = updatedContainer.innerHTML;
            }
        } catch {
            // Keep polling silent to avoid interrupting users on transient network errors.
        }
    }, 5000);
}
