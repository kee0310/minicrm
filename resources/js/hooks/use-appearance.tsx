import { useCallback, useMemo, useSyncExternalStore } from 'react';

export type ResolvedAppearance = 'light';
export type Appearance = 'light';

export type UseAppearanceReturn = {
    readonly appearance: Appearance;
    readonly resolvedAppearance: ResolvedAppearance;
    readonly updateAppearance: (mode: Appearance) => void;
};

const listeners = new Set<() => void>();
let currentAppearance: Appearance = 'light';

const applyTheme = (): void => {
    if (typeof document === 'undefined') return;

    document.documentElement.classList.remove('dark');
    document.documentElement.style.colorScheme = 'light';
};

const subscribe = (callback: () => void) => {
    listeners.add(callback);

    return () => listeners.delete(callback);
};

const notify = (): void => listeners.forEach((listener) => listener());

export function initializeTheme(): void {
    if (typeof window === 'undefined') return;

    currentAppearance = 'light';
    applyTheme();

    localStorage.setItem('appearance', 'light');
}

export function useAppearance(): UseAppearanceReturn {
    const appearance: Appearance = useSyncExternalStore(
        subscribe,
        () => currentAppearance,
        () => 'light',
    );

    const resolvedAppearance: ResolvedAppearance = useMemo(() => 'light', []);

    const updateAppearance = useCallback((mode: Appearance): void => {
        // No-op in light-only mode except keep state consistent.
        currentAppearance = 'light';

        localStorage.setItem('appearance', 'light');

        applyTheme();
        notify();
    }, []);

    return { appearance, resolvedAppearance, updateAppearance } as const;
}
