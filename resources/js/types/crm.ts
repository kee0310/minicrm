export type CrmNavChild = {
    label: string;
    href: string;
    active: boolean;
    badgeKey?: string | null;
    badge?: number;
};

export type CrmNavItem = {
    label: string;
    href: string | null;
    icon?: string | null;
    active: boolean;
    expanded: boolean;
    badgeKey?: string | null;
    badge?: number;
    children?: CrmNavChild[];
};

export type CrmNavigation = {
    homeUrl: string;
    links: CrmNavItem[];
};

export type CrmFlash = {
    success?: string | null;
    warning?: string | null;
    error?: string | null;
    deleted?: string | null;
};

export type CrmUrls = {
    loanDetailDeal: string;
    loanDetailLoan: string;
    notificationsCount: string;
};

export type CrmShared = {
    navigation: CrmNavigation;
    routeName?: string | null;
    urls: CrmUrls;
    flash: CrmFlash;
};
