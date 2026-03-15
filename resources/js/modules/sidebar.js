const SIDEBAR_STORAGE_KEY = 'crm.sidebar.expanded';
const SIDEBAR_WIDTH_EXPANDED = '14rem';
const SIDEBAR_WIDTH_COLLAPSED = '4rem';

const getStoredExpanded = () => {
    const saved = localStorage.getItem(SIDEBAR_STORAGE_KEY);
    if (saved === '0') {
        return false;
    }
    if (saved === '1') {
        return true;
    }
    return true;
};

const getExpandedFromDom = () => {
    const value = document.documentElement.getAttribute('data-sidebar-expanded');
    if (value === '0') {
        return false;
    }
    if (value === '1') {
        return true;
    }
    return null;
};

export const applySidebarAttributes = (expanded, options = {}) => {
    const transitioning = options.transitioning === true;
    const direction = options.direction || 'idle';

    document.documentElement.setAttribute('data-sidebar-expanded', expanded ? '1' : '0');
    document.documentElement.setAttribute('data-sidebar-transition', transitioning ? '1' : '0');
    document.documentElement.setAttribute('data-sidebar-direction', direction);
    document.documentElement.style.setProperty(
        '--crm-initial-sidebar-width',
        expanded ? SIDEBAR_WIDTH_EXPANDED : SIDEBAR_WIDTH_COLLAPSED,
    );
};

export const initSidebarBootAttributes = () => {
    const domExpanded = getExpandedFromDom();
    const expanded = domExpanded ?? getStoredExpanded();
    applySidebarAttributes(expanded, { transitioning: false, direction: 'idle' });
    return expanded;
};

export const persistSidebarState = (expanded) => {
    localStorage.setItem(SIDEBAR_STORAGE_KEY, expanded ? '1' : '0');
};

export const createSidebarComponent = () => ({
    sidebarOpen: false,
    sidebarExpanded: getExpandedFromDom() ?? getStoredExpanded(),
    sidebarTransitioning: false,
    sidebarTransitionTimer: null,
    initSidebarState() {
        const expanded = getStoredExpanded();
        this.sidebarExpanded = expanded;
        applySidebarAttributes(expanded, { transitioning: false, direction: 'idle' });
        this.$nextTick(() => this.$el.classList.add('crm-ready'));
    },
    toggleSidebar() {
        const nextExpanded = !this.sidebarExpanded;
        const direction = nextExpanded ? 'expand' : 'collapse';
        this.sidebarTransitioning = true;
        this.sidebarExpanded = nextExpanded;
        persistSidebarState(nextExpanded);
        applySidebarAttributes(nextExpanded, { transitioning: true, direction });

        if (this.sidebarTransitionTimer) {
            clearTimeout(this.sidebarTransitionTimer);
        }
        this.sidebarTransitionTimer = setTimeout(() => {
            applySidebarAttributes(this.sidebarExpanded, { transitioning: false, direction: 'idle' });
            this.sidebarTransitioning = false;
        }, 320);
    },
});
