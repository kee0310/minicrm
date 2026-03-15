export const initNotificationPolling = (options = {}) => {
    const badgeNodes = Array.from(document.querySelectorAll('[data-nav-badge-key]'));
    if (badgeNodes.length === 0) {
        return;
    }

    const endpoint =
        options.endpoint || window.__crmNotificationsCountUrl || '/notifications/count';
    const intervalMs = Number.isFinite(options.intervalMs) ? options.intervalMs : 60000;

    let active = !document.hidden && document.hasFocus();
    let loading = false;

    const setBadgeCount = (key, count) => {
        const safeCount = Number.isFinite(count) ? Math.max(0, Math.floor(count)) : 0;
        const label = safeCount > 99 ? '99+' : String(safeCount);

        badgeNodes.forEach((node) => {
            if (node.getAttribute('data-nav-badge-key') !== key) {
                return;
            }

            if (safeCount > 0) {
                node.textContent = label;
                node.classList.remove('hidden');
            } else {
                node.textContent = '0';
                node.classList.add('hidden');
            }
        });
    };

    const loadNotifications = () => {
        if (loading) {
            return;
        }

        loading = true;

        fetch(endpoint, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
            credentials: 'same-origin',
        })
            .then((response) => (response.ok ? response.json() : {}))
            .then((payload) => {
                setBadgeCount('borrower_profile', Number(payload.borrower_profile ?? 0));
                setBadgeCount('bank_submission', Number(payload.bank_submission ?? payload.loan_submission ?? 0));
                setBadgeCount('legal_new', Number(payload.legal_new ?? payload.legal ?? 0));
            })
            .catch(() => {})
            .finally(() => {
                loading = false;
            });
    };

    window.addEventListener('blur', () => {
        active = false;
    });

    window.addEventListener('focus', () => {
        active = true;
        loadNotifications();
    });

    document.addEventListener('visibilitychange', () => {
        active = !document.hidden && document.hasFocus();
        if (active) {
            loadNotifications();
        }
    });

    loadNotifications();

    window.setInterval(() => {
        if (active) {
            loadNotifications();
        }
    }, intervalMs);
};
