import './bootstrap';
import '../css/app.css';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

if (window.Echo) {
    const pathSegments = window.location.pathname.split('/').filter(Boolean);
    const pathRoot = pathSegments[0] ?? '';
    const liveResources = new Set(['users', 'leads', 'deals']);
    const isIndexPage = pathSegments.length === 1 && liveResources.has(pathRoot);

    window.Echo.channel('crm-updates').listen('.crm.data.changed', (event) => {
        if (!isIndexPage) {
            return;
        }

        if (event.resource === pathRoot) {
            window.location.reload();
        }
    });
}
