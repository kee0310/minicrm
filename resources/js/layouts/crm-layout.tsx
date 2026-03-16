import { usePage } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { FlashMessages } from '@/components/crm/flash-messages';
import { CrmHeader } from '@/components/crm/header';
import { CrmSidebar } from '@/components/crm/sidebar';
import {
    applySidebarAttributes,
    initSidebarBootAttributes,
    persistSidebarState,
} from '@/modules/sidebar';
import type { User } from '@/types';
import type { CrmShared } from '@/types/crm';

type Props = {
    children: React.ReactNode;
    header?: React.ReactNode;
    headerSubtitle?: string;
};

type PageProps = {
    auth?: {
        user?: User | null;
    };
    crm?: CrmShared;
};

export default function CrmLayout({ children, header, headerSubtitle }: Props) {
    const page = usePage<PageProps>();
    const crm = (page.props.crm ?? {}) as CrmShared;
    const user = (page.props.auth?.user ?? {
        name: 'User',
        email: '',
    }) as User;

    const [sidebarExpanded, setSidebarExpanded] = useState<boolean>(() => {
        if (typeof window === 'undefined') {
            return true;
        }
        return initSidebarBootAttributes();
    });
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const transitionTimer = useRef<number | null>(null);
    const ready = typeof window !== 'undefined';

    useEffect(() => {
        if (!crm?.urls) {
            return;
        }
        document.body.dataset.crmLoanDetailDealUrl = crm.urls.loanDetailDeal;
        document.body.dataset.crmLoanDetailLoanUrl = crm.urls.loanDetailLoan;
        document.body.dataset.crmNotificationsCountUrl =
            crm.urls.notificationsCount;
    }, [crm?.urls]);

    const toggleSidebarExpanded = () => {
        const nextExpanded = !sidebarExpanded;
        setSidebarExpanded(nextExpanded);
        persistSidebarState(nextExpanded);
        applySidebarAttributes(nextExpanded, {
            transitioning: true,
            direction: nextExpanded ? 'expand' : 'collapse',
        });

        if (transitionTimer.current) {
            window.clearTimeout(transitionTimer.current);
        }
        transitionTimer.current = window.setTimeout(() => {
            applySidebarAttributes(nextExpanded, {
                transitioning: false,
                direction: 'idle',
            });
        }, 320);
    };

    const shellClass = useMemo(() => {
        return `crm-shell lg:flex ${ready ? 'crm-ready' : ''}`;
    }, [ready]);

    return (
        <>
            <div className="crm-page-loader" aria-hidden="true">
                <div className="crm-page-loader-spinner"></div>
            </div>
            <div
                className={shellClass}
                style={{
                    // @ts-expect-error CSS variable
                    '--crm-sidebar-width': sidebarExpanded ? '14rem' : '4rem',
                }}
            >
                {crm?.navigation && (
                    <CrmSidebar
                        navigation={crm.navigation}
                        sidebarExpanded={sidebarExpanded}
                        sidebarOpen={sidebarOpen}
                        onToggleExpand={toggleSidebarExpanded}
                        onCloseMobile={() => setSidebarOpen(false)}
                    />
                )}

                <div className="pointer-events-none fixed left-1/2 top-[80px] z-[100] w-full max-w-2xl -translate-x-1/2 px-4">
                    <FlashMessages />
                </div>

                <div className="crm-main-with-sidebar min-w-0 flex-1">
                    <CrmHeader
                        title={header}
                        subtitle={headerSubtitle}
                        onToggleMobile={() => setSidebarOpen((prev) => !prev)}
                        crm={crm}
                        user={user}
                    />
                    <main className="crm-content">{children}</main>
                    <footer className="pointer-events-none mx-8 mb-4 text-end text-[10px] text-slate-500 opacity-50">
                        © 2026 Chua Yun Kee. All rights reserved.
                    </footer>
                </div>
            </div>
        </>
    );
}
