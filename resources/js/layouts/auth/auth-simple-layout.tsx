import AppLogoIcon from '@/components/app-logo-icon';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="flex min-h-screen flex-col items-center gap-6 bg-gray-100 p-3 pt-[100px]">
            <div className="w-full max-w-lg">
                <div className="flex flex-col">
                    <div className="flex flex-col items-center gap-4">
                        {(title || description) && (
                            <div className="text-center">
                                <div className="h-18 w-18 m-auto flex items-center justify-center rounded-md">
                                    <AppLogoIcon className="size-18 fill-current text-red-400/80" />
                                </div>
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
