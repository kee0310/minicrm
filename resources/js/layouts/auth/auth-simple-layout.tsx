import { Link } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="bg-background flex min-h-screen flex-col items-center justify-center gap-6 p-3">
            <div className="w-full max-w-lg">
                <div className="flex flex-col">
                    <div className="flex flex-col items-center gap-4">
                        <Link
                            href="/"
                            className="flex flex-col items-center font-medium"
                        >
                            <div className="h-21 w-21 flex items-center justify-center rounded-md">
                                <AppLogoIcon className="size-21 fill-current text-red-400/80" />
                            </div>
                            <span className="sr-only">{title}</span>
                        </Link>

                        {(title || description) && (
                            <div className="space-y-2 text-center">
                                {title && (
                                    <h1 className="text-xl font-medium">
                                        {title}
                                    </h1>
                                )}
                                {description && (
                                    <p className="text-muted-foreground text-center text-sm">
                                        {description}
                                    </p>
                                )}
                            </div>
                        )}
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
