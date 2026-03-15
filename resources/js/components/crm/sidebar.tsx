import { Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import type { CrmNavigation, CrmNavItem } from '@/types/crm';

type Props = {
    navigation: CrmNavigation;
    sidebarExpanded: boolean;
    sidebarOpen: boolean;
    onToggleExpand: () => void;
    onCloseMobile: () => void;
};

export function CrmSidebar({
    navigation,
    sidebarExpanded,
    sidebarOpen,
    onToggleExpand,
    onCloseMobile,
}: Props) {
    const initialOpen = useMemo(() => {
        const map = new Map<number, boolean>();
        navigation.links.forEach((link, index) => {
            if (link.expanded) {
                map.set(index, true);
            }
        });
        return map;
    }, [navigation.links]);

    const [submenuOpen, setSubmenuOpen] = useState<Map<number, boolean>>(
        initialOpen,
    );
    const [flyoutIndex, setFlyoutIndex] = useState<number | null>(null);

    const toggleSubmenu = (index: number) => {
        setSubmenuOpen((prev) => {
            const next = new Map(prev);
            next.set(index, !next.get(index));
            return next;
        });
    };

    const renderLink = (link: CrmNavItem, index: number) => {
        const active = link.active;
        const hasChildren = Boolean(link.children?.length);
        const isOpen = submenuOpen.get(index) ?? false;

        if (hasChildren) {
            return (
                <div
                    className="relative"
                    onMouseEnter={() =>
                        !sidebarExpanded ? setFlyoutIndex(index) : undefined
                    }
                    onMouseLeave={() =>
                        !sidebarExpanded ? setFlyoutIndex(null) : undefined
                    }
                >
                    <button
                        type="button"
                        className={`crm-nav-item group justify-between py-2 ${active ? 'crm-nav-item-active' : 'crm-nav-item-inactive'}`}
                        onClick={() =>
                            sidebarExpanded
                                ? toggleSubmenu(index)
                                : setFlyoutIndex(
                                      flyoutIndex === index ? null : index,
                                  )
                        }
                    >
                        <span className="flex items-center gap-3">
                            <i
                                className={`${link.icon ?? ''} crm-nav-icon text-base`}
                            ></i>
                            <span
                                className={`truncate whitespace-nowrap ${sidebarExpanded ? '' : 'hidden'}`}
                            >
                                {link.label}
                            </span>
                        </span>
                        <i
                            className={`fa-solid fa-chevron-down crm-nav-icon crm-nav-icon-sm text-xs crm-nav-chevron ${sidebarExpanded ? '' : 'hidden'} ${isOpen ? 'is-open' : ''}`}
                        ></i>
                    </button>

                    <div
                        className={`crm-nav-submenu origin-left mt-1 ${sidebarExpanded && isOpen ? '' : 'hidden'}`}
                    >
                        {link.children?.map((child) => {
                            const badgeValue = child.badge ?? 0;
                            return (
                                <Link
                                    key={child.href}
                                    href={child.href}
                                    className={`crm-nav-subitem ${child.active ? 'crm-nav-item-active' : 'crm-nav-item-inactive'}`}
                                >
                                    <span className="truncate">{child.label}</span>
                                    {child.badgeKey && (
                                        <span
                                            data-nav-badge-key={child.badgeKey}
                                            className={`ml-2 inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-red-500 px-1.5 text-[9px] font-semibold leading-none text-white ${badgeValue > 0 ? '' : 'hidden'}`}
                                        >
                                            {badgeValue > 99 ? '99+' : badgeValue}
                                        </span>
                                    )}
                                </Link>
                            );
                        })}
                    </div>

                    <div
                        className={`crm-nav-flyout origin-top absolute left-full top-0 ml-2 w-56 rounded bg-[#233b6d] shadow-lg ${!sidebarExpanded && flyoutIndex === index ? '' : 'hidden'}`}
                    >
                        <p className="mb-2 text-sm font-semibold text-white">
                            {link.label}
                        </p>
                        <div className="space-y-1">
                            {link.children?.map((child) => {
                                const badgeValue = child.badge ?? 0;
                                return (
                                    <Link
                                        key={child.href}
                                        href={child.href}
                                        className={`crm-nav-subitem ${child.active ? 'crm-nav-item-active' : 'crm-nav-item-inactive'}`}
                                    >
                                        <span className="truncate">
                                            {child.label}
                                        </span>
                                        {child.badgeKey && (
                                            <span
                                                data-nav-badge-key={child.badgeKey}
                                                className={`ml-2 inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-red-500 px-1.5 text-[11px] font-semibold leading-none text-white ${badgeValue > 0 ? '' : 'hidden'}`}
                                            >
                                                {badgeValue > 99
                                                    ? '99+'
                                                    : badgeValue}
                                            </span>
                                        )}
                                    </Link>
                                );
                            })}
                        </div>
                    </div>
                </div>
            );
        }

        return (
            <Link
                href={link.href ?? '#'}
                className={`crm-nav-item group py-3 ${active ? 'crm-nav-item-active' : 'crm-nav-item-inactive'}`}
            >
                <i
                    className={`${link.icon ?? ''} crm-nav-icon text-base`}
                ></i>
                <span
                    className={`truncate whitespace-nowrap ${sidebarExpanded ? '' : 'hidden'}`}
                >
                    {link.label}
                </span>
                {link.badgeKey && (
                    <span
                        data-nav-badge-key={link.badgeKey}
                        className={`ml-2 inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-red-500 px-1.5 text-[11px] font-semibold leading-none text-white ${(link.badge ?? 0) > 0 ? '' : 'hidden'}`}
                    >
                        {(link.badge ?? 0) > 99 ? '99+' : link.badge ?? 0}
                    </span>
                )}
            </Link>
        );
    };

    return (
        <>
            <aside className="crm-sidebar-desktop fixed inset-y-0 left-0 z-40 hidden border-r border-[#395085] bg-gradient-to-b from-[#233b6d] via-[#325baf] to-[#0c8dc8] text-slate-100 lg:flex">
                <div className="flex h-full w-full flex-col">
                    <div className="flex items-center justify-between border-b border-white/15 px-3 py-3">
                        <Link
                            href={navigation.homeUrl}
                            className="crm-sidebar-brand"
                        >
                            <span className="crm-nav-icon crm-sidebar-brand-icon text-xl">
                                <i className="fa-light fa-house-building"></i>
                            </span>
                            <span
                                className={`font-bold tracking-wide text-white truncate ${sidebarExpanded ? '' : 'hidden'}`}
                            >
                                CRM Property
                            </span>
                        </Link>
                        <button
                            type="button"
                            className="crm-sidebar-toggle-btn"
                            onClick={onToggleExpand}
                        >
                            <i
                                data-sidebar-toggle-expanded
                                className="fa-solid fa-angles-left"
                            ></i>
                            <i
                                data-sidebar-toggle-collapsed
                                className="fa-solid fa-bars"
                            ></i>
                        </button>
                    </div>

                    <nav className="flex-1 space-y-1 p-2 scrollbar-hide transition-all">
                        {navigation.links.map((link, index) => (
                            <div key={`${link.label}-${index}`}>
                                {renderLink(link, index)}
                            </div>
                        ))}
                    </nav>
                </div>
            </aside>

            <div
                className={`fixed inset-0 z-50 lg:hidden ${sidebarOpen ? '' : 'hidden'}`}
                role="dialog"
                aria-modal="true"
            >
                <div
                    className="absolute inset-0 bg-slate-900/40"
                    onClick={onCloseMobile}
                ></div>
                <aside className="absolute inset-y-0 left-0 flex w-[18rem] max-w-[85vw] flex-col border-r border-[#395085] bg-gradient-to-b from-[#233b6d] via-[#325baf] to-[#0c8dc8] text-slate-100 shadow-xl">
                    <div className="flex h-16 items-center justify-between border-b border-white/15 px-4">
                        <Link
                            href={navigation.homeUrl}
                            className="crm-sidebar-brand"
                        >
                            <i className="fa-solid fa-chart-line crm-nav-icon crm-sidebar-brand-icon"></i>
                            <span className="truncate text-sm font-semibold tracking-wide text-white">
                                Mini CRM
                            </span>
                        </Link>
                        <button
                            type="button"
                            className="crm-sidebar-toggle-btn"
                            onClick={onCloseMobile}
                        >
                            <i className="fa-solid fa-xmark crm-nav-icon"></i>
                        </button>
                    </div>

                    <nav className="flex-1 space-y-1 overflow-y-auto p-2 scrollbar-hide">
                        {navigation.links.map((link, index) => {
                            const hasChildren = Boolean(
                                link.children?.length,
                            );
                            if (hasChildren) {
                                const isOpen =
                                    submenuOpen.get(index) ?? false;
                                return (
                                    <div key={`${link.label}-${index}`}>
                                        <button
                                            type="button"
                                            className={`crm-nav-item group justify-between ${link.active ? 'crm-nav-item-active' : 'crm-nav-item-inactive'}`}
                                            onClick={() => toggleSubmenu(index)}
                                        >
                                            <span className="flex items-center gap-3">
                                                <i
                                                    className={`${link.icon ?? ''} crm-nav-icon text-base`}
                                                ></i>
                                                <span className="truncate whitespace-nowrap">
                                                    {link.label}
                                                </span>
                                            </span>
                                            <i
                                                className={`fa-solid fa-chevron-down crm-nav-icon crm-nav-icon-sm text-xs crm-nav-chevron ${isOpen ? 'is-open' : ''}`}
                                            ></i>
                                        </button>
                                        <div
                                            className={`crm-nav-submenu ${isOpen ? '' : 'hidden'}`}
                                        >
                                            {link.children?.map((child) => {
                                                const badgeValue = child.badge ?? 0;
                                                return (
                                                    <Link
                                                        key={child.href}
                                                        href={child.href}
                                                        className={`crm-nav-subitem ${child.active ? 'crm-nav-item-active' : 'crm-nav-item-inactive'}`}
                                                        onClick={onCloseMobile}
                                                    >
                                                        <span className="truncate">
                                                            {child.label}
                                                        </span>
                                                        {child.badgeKey && (
                                                            <span
                                                                data-nav-badge-key={
                                                                    child.badgeKey
                                                                }
                                                                className={`ml-2 inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-red-500 px-1.5 text-[11px] font-semibold leading-none text-white ${badgeValue > 0 ? '' : 'hidden'}`}
                                                            >
                                                                {badgeValue > 99
                                                                    ? '99+'
                                                                    : badgeValue}
                                                            </span>
                                                        )}
                                                    </Link>
                                                );
                                            })}
                                        </div>
                                    </div>
                                );
                            }

                            return (
                                <Link
                                    key={`${link.label}-${index}`}
                                    href={link.href ?? '#'}
                                    className={`crm-nav-item group py-3 ${link.active ? 'crm-nav-item-active' : 'crm-nav-item-inactive'}`}
                                    onClick={onCloseMobile}
                                >
                                    <i
                                        className={`${link.icon ?? ''} crm-nav-icon text-base`}
                                    ></i>
                                    <span className="truncate whitespace-nowrap">
                                        {link.label}
                                    </span>
                                    {link.badgeKey && (
                                        <span
                                            data-nav-badge-key={link.badgeKey}
                                            className={`ml-2 inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-red-500 px-1.5 text-[11px] font-semibold leading-none text-white ${(link.badge ?? 0) > 0 ? '' : 'hidden'}`}
                                        >
                                            {(link.badge ?? 0) > 99
                                                ? '99+'
                                                : link.badge ?? 0}
                                        </span>
                                    )}
                                </Link>
                            );
                        })}
                    </nav>
                </aside>
            </div>
        </>
    );
}
