import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import type { CrmFlash } from '@/types/crm';

type FlashItem = {
    key: keyof CrmFlash;
    title: string;
    icon: string;
    classes: string;
};

const FLASH_ITEMS: FlashItem[] = [
    {
        key: 'success',
        icon: 'fa-circle-check',
        title: 'Success',
        classes: 'border-emerald-200 bg-emerald-50 text-emerald-800',
    },
    {
        key: 'warning',
        icon: 'fa-triangle-exclamation',
        title: 'Warning',
        classes: 'border-amber-200 bg-amber-50 text-amber-900',
    },
    {
        key: 'error',
        icon: 'fa-circle-xmark',
        title: 'Error',
        classes: 'border-rose-200 bg-rose-50 text-rose-900',
    },
    {
        key: 'deleted',
        icon: 'fa-trash-can',
        title: 'Deleted',
        classes: 'border-rose-200 bg-rose-50 text-rose-900',
    },
];

const FLASH_ITEMS_BY_KEY = new Map<string, FlashItem>(
    FLASH_ITEMS.map((item) => [item.key, item]),
);

type FlashAlertProps = {
    title: string;
    message: string;
    icon: string;
    classes: string;
    autoDismiss?: boolean;
    dismissMs?: number;
};

function FlashAlert({
    title,
    message,
    icon,
    classes,
    autoDismiss = true,
    dismissMs = 3200,
}: FlashAlertProps) {
    const [show, setShow] = useState(true);

    useEffect(() => {
        if (!autoDismiss) {
            return;
        }
        const timer = window.setTimeout(() => setShow(false), dismissMs);
        return () => window.clearTimeout(timer);
    }, [autoDismiss, dismissMs]);

    if (!show) {
        return null;
    }

    return (
        <div
            className={`pointer-events-auto flex items-start justify-between gap-3 rounded-xl border px-4 py-3 shadow-sm ${classes}`}
        >
            <div className="flex items-start gap-3">
                <i className={`fa-solid ${icon} mt-0.5`}></i>
                <div>
                    <p className="text-sm font-semibold leading-5">{title}</p>
                    <p className="text-sm leading-5">{message}</p>
                </div>
            </div>
            <button
                type="button"
                onClick={() => setShow(false)}
                className="text-current/70 hover:text-current"
                aria-label="Dismiss"
            >
                <i className="fa-solid fa-xmark"></i>
            </button>
        </div>
    );
}

export function FlashMessages() {
    const page = usePage();
    const flash = (page.props.crm?.flash ?? {}) as CrmFlash;
    const errors = page.props.errors as Record<string, string> | undefined;

    const queryFlash = (() => {
        if (typeof window === 'undefined') {
            return null;
        }
        const url = new URL(window.location.href);
        const message = url.searchParams.get('flash_message') ?? '';
        if (!message) {
            return null;
        }
        const type = (url.searchParams.get('flash_type') ?? 'success') as keyof CrmFlash;
        const item = FLASH_ITEMS_BY_KEY.get(type) ?? FLASH_ITEMS[0];
        return { message, item };
    })();

    useEffect(() => {
        if (!queryFlash) {
            return;
        }
        const url = new URL(window.location.href);
        url.searchParams.delete('flash_message');
        url.searchParams.delete('flash_type');
        window.history.replaceState({}, '', url.toString());
    }, [queryFlash]);

    const hasSessionFlash = FLASH_ITEMS.some(
        (item) => Boolean(flash[item.key]),
    );

    const validationMessage = errors
        ? Object.values(errors)[0]
        : undefined;

    return (
        <div className="space-y-2">
            {validationMessage && (
                <FlashAlert
                    title="Validation Error"
                    message={validationMessage}
                    icon="fa-circle-xmark"
                    classes="border-rose-200 bg-rose-50 text-rose-900"
                    dismissMs={4200}
                />
            )}
            {!hasSessionFlash && queryFlash && (
                <FlashAlert
                    title={queryFlash.item.title}
                    message={queryFlash.message}
                    icon={queryFlash.item.icon}
                    classes={queryFlash.item.classes}
                    autoDismiss
                    dismissMs={3200}
                />
            )}
            {FLASH_ITEMS.map((item) => {
                const message = flash[item.key];
                if (!message) {
                    return null;
                }
                const isDeleteMessage =
                    typeof message === 'string' &&
                    message.toLowerCase().includes('deleted');
                return (
                    <FlashAlert
                        key={item.key}
                        title={item.title}
                        message={String(message)}
                        icon={item.icon}
                        classes={item.classes}
                        autoDismiss={!isDeleteMessage}
                        dismissMs={3200}
                    />
                );
            })}
        </div>
    );
}
