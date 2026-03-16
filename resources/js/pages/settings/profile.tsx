import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import DeleteUser from '@/components/delete-user';
import CrmLayout from '@/layouts/crm-layout';

export default function Profile({
    mustVerifyEmail,
    status,
}: {
    mustVerifyEmail: boolean;
    status?: string | null;
}) {
    const page = usePage<{
        auth: {
            user: {
                name: string;
                email: string;
                email_verified_at?: string | null;
            } | null;
        };
    }>();

    const user = page.props.auth?.user;
    const [verificationSent, setVerificationSent] = useState(false);

    const profileForm = useForm({
        name: user?.name ?? '',
        email: user?.email ?? '',
    });

    const passwordForm = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const updateProfile = (event: React.FormEvent) => {
        event.preventDefault();
        profileForm.patch('/settings/profile', {
            preserveScroll: true,
        });
    };

    const updatePassword = (event: React.FormEvent) => {
        event.preventDefault();
        passwordForm.put('/settings/password', {
            preserveScroll: true,
            errorBag: 'updatePassword',
            onSuccess: () => passwordForm.reset(),
        });
    };

    const goBack = () => {
        if (window.history.length > 1) {
            window.history.back();
        } else {
            router.visit('/dashboard');
        }
    };

    const resendVerification = () => {
        router.post(
            '/email/verification-notification',
            {},
            {
                preserveScroll: true,
                onSuccess: () => setVerificationSent(true),
            },
        );
    };

    return (
        <>
            <Head title="Profile Settings" />
            <button
                type="button"
                onClick={goBack}
                className="ml-10 mt-5 inline-flex items-center gap-2 border border-slate-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-[0.08em] text-slate-700 shadow-sm transition hover:bg-blue-500 hover:text-white"
            >
                <span aria-hidden="true">←</span>
                Back
            </button>

            <div className="crm-content mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div className="flex flex-col gap-6 lg:flex-row">
                    <main className="flex-1">
                        <section className="crm-card">
                            <div className="crm-card-body">
                                <header>
                                    <h2 className="text-xl font-semibold text-slate-900">
                                        Profile information
                                    </h2>
                                    <p className="mt-1 text-sm text-slate-500">
                                        Manage your name, email and verification
                                        status.
                                    </p>
                                </header>

                                <form
                                    onSubmit={updateProfile}
                                    className="mt-6 space-y-5"
                                >
                                    <div className="grid gap-4 md:grid-cols-2">
                                        <label className="space-y-1">
                                            <span className="text-sm font-medium text-slate-700">
                                                Name
                                            </span>
                                            <input
                                                className="w-full rounded-lg border border-slate-300 p-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                                                value={profileForm.data.name}
                                                onChange={(event) =>
                                                    profileForm.setData(
                                                        'name',
                                                        event.target.value,
                                                    )
                                                }
                                                name="name"
                                                required
                                            />
                                            {profileForm.errors.name && (
                                                <p className="text-xs text-rose-600">
                                                    {profileForm.errors.name}
                                                </p>
                                            )}
                                        </label>

                                        <label className="space-y-1">
                                            <span className="text-sm font-medium text-slate-700">
                                                Email
                                            </span>
                                            <input
                                                type="email"
                                                className="w-full rounded-lg border border-slate-300 p-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                                                value={profileForm.data.email}
                                                onChange={(event) =>
                                                    profileForm.setData(
                                                        'email',
                                                        event.target.value,
                                                    )
                                                }
                                                name="email"
                                                required
                                            />
                                            {profileForm.errors.email && (
                                                <p className="text-xs text-rose-600">
                                                    {profileForm.errors.email}
                                                </p>
                                            )}
                                        </label>
                                    </div>

                                    {mustVerifyEmail &&
                                        user?.email_verified_at == null && (
                                            <div className="rounded-lg border border-amber-100 bg-amber-50 p-3 text-sm text-amber-700">
                                                <p>
                                                    Your email is not verified.
                                                </p>
                                                <button
                                                    type="button"
                                                    className="mt-2 inline-flex rounded-lg bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-900 hover:bg-amber-200"
                                                    onClick={resendVerification}
                                                >
                                                    Resend verification email
                                                </button>
                                                {status ===
                                                    'verification-link-sent' ||
                                                verificationSent ? (
                                                    <p className="mt-2 text-xs font-semibold text-emerald-600">
                                                        Verification email sent.
                                                    </p>
                                                ) : null}
                                            </div>
                                        )}

                                    <div className="flex items-center gap-3">
                                        <button
                                            type="submit"
                                            disabled={profileForm.processing}
                                            className="crm-btn-primary"
                                        >
                                            Save profile
                                        </button>
                                        {profileForm.recentlySuccessful && (
                                            <span className="text-sm text-emerald-600">
                                                Saved.
                                            </span>
                                        )}
                                    </div>
                                </form>
                            </div>
                        </section>

                        <section className="crm-card">
                            <div className="crm-card-body">
                                <header>
                                    <h2 className="text-xl font-semibold text-slate-900">
                                        Update password
                                    </h2>
                                    <p className="mt-1 text-sm text-slate-500">
                                        Change your account password for better
                                        security.
                                    </p>
                                </header>

                                <form
                                    onSubmit={updatePassword}
                                    className="mt-6 space-y-5"
                                >
                                    <label className="block space-y-1">
                                        <span className="text-sm font-medium text-slate-700">
                                            Current password
                                        </span>
                                        <input
                                            type="password"
                                            className="w-full rounded-lg border border-slate-300 p-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                                            value={
                                                passwordForm.data
                                                    .current_password
                                            }
                                            onChange={(event) =>
                                                passwordForm.setData(
                                                    'current_password',
                                                    event.target.value,
                                                )
                                            }
                                            name="current_password"
                                        />
                                        {passwordForm.errors
                                            .current_password && (
                                            <p className="text-xs text-rose-600">
                                                {
                                                    passwordForm.errors
                                                        .current_password
                                                }
                                            </p>
                                        )}
                                    </label>

                                    <label className="block space-y-1">
                                        <span className="text-sm font-medium text-slate-700">
                                            New password
                                        </span>
                                        <input
                                            type="password"
                                            className="w-full rounded-lg border border-slate-300 p-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                                            value={passwordForm.data.password}
                                            onChange={(event) =>
                                                passwordForm.setData(
                                                    'password',
                                                    event.target.value,
                                                )
                                            }
                                            name="password"
                                        />
                                        {passwordForm.errors.password && (
                                            <p className="text-xs text-rose-600">
                                                {passwordForm.errors.password}
                                            </p>
                                        )}
                                    </label>

                                    <label className="block space-y-1">
                                        <span className="text-sm font-medium text-slate-700">
                                            Confirm password
                                        </span>
                                        <input
                                            type="password"
                                            className="w-full rounded-lg border border-slate-300 p-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                                            value={
                                                passwordForm.data
                                                    .password_confirmation
                                            }
                                            onChange={(event) =>
                                                passwordForm.setData(
                                                    'password_confirmation',
                                                    event.target.value,
                                                )
                                            }
                                            name="password_confirmation"
                                        />
                                        {passwordForm.errors
                                            .password_confirmation && (
                                            <p className="text-xs text-rose-600">
                                                {
                                                    passwordForm.errors
                                                        .password_confirmation
                                                }
                                            </p>
                                        )}
                                    </label>

                                    <div className="flex items-center gap-3">
                                        <button
                                            type="submit"
                                            disabled={passwordForm.processing}
                                            className="crm-btn-primary"
                                        >
                                            Change password
                                        </button>
                                        {passwordForm.recentlySuccessful && (
                                            <span className="text-sm text-emerald-600">
                                                Saved.
                                            </span>
                                        )}
                                    </div>
                                </form>
                            </div>
                        </section>

                        <section className="rounded-xl border border-red-300 bg-red-50 shadow-sm">
                            <div className="crm-card-body p-6">
                                <header className="flex items-center gap-2">
                                    <h2 className="text-xl font-semibold text-red-700">
                                        Danger zone
                                    </h2>
                                </header>

                                <p className="mt-2 text-sm text-slate-700">
                                    Delete your account and all associated data.
                                    This action cannot be undone.
                                </p>

                                <div className="mt-5">
                                    <DeleteUser />
                                </div>
                            </div>
                        </section>
                    </main>
                </div>
            </div>
        </>
    );
}

Profile.layout = (page: React.ReactNode) => (
    <CrmLayout
        header={
            <h2 className="text-2xl font-semibold text-slate-900">
                Profile Settings
            </h2>
        }
        headerSubtitle="Manage your account settings and security"
    >
        {page}
    </CrmLayout>
);
