import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';
import CrmLayout from '@/layouts/crm-layout';

type ProfileProps = {
    mustVerifyEmail: boolean;
    status?: string | null;
};

type AuthUser = {
    name: string;
    email: string;
    email_verified_at?: string | null;
};

export default function Profile({ mustVerifyEmail }: ProfileProps) {
    const page = usePage();
    const user = page.props.auth?.user as AuthUser;
    const [verificationSent, setVerificationSent] = useState(false);
    const [deleteOpen, setDeleteOpen] = useState(false);

    const profileForm = useForm({
        name: user?.name ?? '',
        email: user?.email ?? '',
    });

    const passwordForm = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const deleteForm = useForm({
        password: '',
    });

    const updateProfile = (event: React.FormEvent) => {
        event.preventDefault();
        profileForm.patch('/profile', {
            preserveScroll: true,
        });
    };

    const updatePassword = (event: React.FormEvent) => {
        event.preventDefault();
        passwordForm.put('/password', {
            preserveScroll: true,
            errorBag: 'updatePassword',
            onSuccess: () => passwordForm.reset(),
        });
    };

    const sendVerification = () => {
        router.post('/email/verification-notification', {}, {
            preserveScroll: true,
            onSuccess: () => setVerificationSent(true),
        });
    };

    const confirmDelete = (event: React.FormEvent) => {
        event.preventDefault();
        deleteForm.delete('/profile', {
            preserveScroll: true,
            errorBag: 'userDeletion',
            onSuccess: () => {
                setDeleteOpen(false);
                deleteForm.reset();
            },
        });
    };

    return (
        <>
            <Head title="Profile" />

            <div className="py-6">
                <div className="crm-content max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    <div className="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                        <div className="max-w-xl">
                            <section>
                                <header>
                                    <h2 className="text-lg font-medium text-gray-900">
                                        Profile Information
                                    </h2>

                                    <p className="mt-1 text-sm text-gray-600">
                                        Update your account's profile information and email address.
                                    </p>
                                </header>

                                <form onSubmit={updateProfile} className="mt-6 space-y-6">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700" htmlFor="name">
                                            Name
                                        </label>
                                        <input
                                            id="name"
                                            name="name"
                                            type="text"
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                            value={profileForm.data.name}
                                            onChange={(event) =>
                                                profileForm.setData('name', event.target.value)
                                            }
                                            required
                                            autoComplete="name"
                                        />
                                        {profileForm.errors.name && (
                                            <p className="mt-2 text-sm text-red-600">
                                                {profileForm.errors.name}
                                            </p>
                                        )}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700" htmlFor="email">
                                            Email
                                        </label>
                                        <input
                                            id="email"
                                            name="email"
                                            type="email"
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                            value={profileForm.data.email}
                                            onChange={(event) =>
                                                profileForm.setData('email', event.target.value)
                                            }
                                            required
                                            autoComplete="username"
                                        />
                                        {profileForm.errors.email && (
                                            <p className="mt-2 text-sm text-red-600">
                                                {profileForm.errors.email}
                                            </p>
                                        )}

                                        {mustVerifyEmail && user?.email_verified_at == null && (
                                            <div>
                                                <p className="text-sm mt-2 text-gray-800">
                                                    Your email address is unverified.
                                                    <button
                                                        type="button"
                                                        onClick={sendVerification}
                                                        className="underline text-sm text-gray-600 hover:text-gray-900 rounded-md ml-2"
                                                    >
                                                        Click here to re-send the verification email.
                                                    </button>
                                                </p>

                                                {verificationSent && (
                                                    <p className="mt-2 font-medium text-sm text-green-600">
                                                        A new verification link has been sent to your email address.
                                                    </p>
                                                )}
                                            </div>
                                        )}
                                    </div>

                                    <div className="flex items-center gap-4">
                                        <button
                                            type="submit"
                                            className="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-xs font-semibold uppercase tracking-widest rounded-md"
                                            disabled={profileForm.processing}
                                        >
                                            Save
                                        </button>

                                        {profileForm.recentlySuccessful && (
                                            <p className="text-sm text-gray-600">Saved.</p>
                                        )}
                                    </div>
                                </form>
                            </section>
                        </div>
                    </div>

                    <div className="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                        <div className="max-w-xl">
                            <section>
                                <header>
                                    <h2 className="text-lg font-medium text-gray-900">
                                        Update Password
                                    </h2>

                                    <p className="mt-1 text-sm text-gray-600">
                                        Ensure your account is using a long, random password to stay secure.
                                    </p>
                                </header>

                                <form onSubmit={updatePassword} className="mt-6 space-y-6">
                                    <div>
                                        <label
                                            className="block text-sm font-medium text-gray-700"
                                            htmlFor="update_password_current_password"
                                        >
                                            Current Password
                                        </label>
                                        <input
                                            id="update_password_current_password"
                                            name="current_password"
                                            type="password"
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                            value={passwordForm.data.current_password}
                                            onChange={(event) =>
                                                passwordForm.setData(
                                                    'current_password',
                                                    event.target.value,
                                                )
                                            }
                                            autoComplete="current-password"
                                        />
                                        {passwordForm.errors.current_password && (
                                            <p className="mt-2 text-sm text-red-600">
                                                {passwordForm.errors.current_password}
                                            </p>
                                        )}
                                    </div>

                                    <div>
                                        <label
                                            className="block text-sm font-medium text-gray-700"
                                            htmlFor="update_password_password"
                                        >
                                            New Password
                                        </label>
                                        <input
                                            id="update_password_password"
                                            name="password"
                                            type="password"
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                            value={passwordForm.data.password}
                                            onChange={(event) =>
                                                passwordForm.setData('password', event.target.value)
                                            }
                                            autoComplete="new-password"
                                        />
                                        {passwordForm.errors.password && (
                                            <p className="mt-2 text-sm text-red-600">
                                                {passwordForm.errors.password}
                                            </p>
                                        )}
                                    </div>

                                    <div>
                                        <label
                                            className="block text-sm font-medium text-gray-700"
                                            htmlFor="update_password_password_confirmation"
                                        >
                                            Confirm Password
                                        </label>
                                        <input
                                            id="update_password_password_confirmation"
                                            name="password_confirmation"
                                            type="password"
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                            value={passwordForm.data.password_confirmation}
                                            onChange={(event) =>
                                                passwordForm.setData(
                                                    'password_confirmation',
                                                    event.target.value,
                                                )
                                            }
                                            autoComplete="new-password"
                                        />
                                        {passwordForm.errors.password_confirmation && (
                                            <p className="mt-2 text-sm text-red-600">
                                                {passwordForm.errors.password_confirmation}
                                            </p>
                                        )}
                                    </div>

                                    <div className="flex items-center gap-4">
                                        <button
                                            type="submit"
                                            className="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-xs font-semibold uppercase tracking-widest rounded-md"
                                            disabled={passwordForm.processing}
                                        >
                                            Save
                                        </button>

                                        {passwordForm.recentlySuccessful && (
                                            <p className="text-sm text-gray-600">Saved.</p>
                                        )}
                                    </div>
                                </form>
                            </section>
                        </div>
                    </div>

                    <div className="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                        <div className="max-w-xl">
                            <section className="space-y-6">
                                <header>
                                    <h2 className="text-lg font-medium text-gray-900">
                                        Delete Account
                                    </h2>

                                    <p className="mt-1 text-sm text-gray-600">
                                        Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.
                                    </p>
                                </header>

                                <button
                                    type="button"
                                    onClick={() => setDeleteOpen(true)}
                                    className="inline-flex items-center px-4 py-2 bg-red-600 text-white text-xs font-semibold uppercase tracking-widest rounded-md"
                                >
                                    Delete Account
                                </button>
                            </section>
                        </div>
                    </div>
                </div>
            </div>

            {deleteOpen && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
                    onClick={(event) => {
                        if (event.currentTarget === event.target) {
                            setDeleteOpen(false);
                        }
                    }}
                >
                    <div className="crm-modal-panel">
                        <div className="crm-modal-header">
                            <h4 className="crm-modal-title">Delete Account</h4>
                            <button
                                type="button"
                                className="text-gray-500 hover:text-gray-700"
                                onClick={() => setDeleteOpen(false)}
                            >
                                X
                            </button>
                        </div>
                        <form onSubmit={confirmDelete} className="space-y-6">
                            <div>
                                <p className="text-sm text-gray-600">
                                    Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.
                                </p>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700" htmlFor="delete_password">
                                    Password
                                </label>
                                <input
                                    id="delete_password"
                                    name="password"
                                    type="password"
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    value={deleteForm.data.password}
                                    onChange={(event) =>
                                        deleteForm.setData('password', event.target.value)
                                    }
                                    autoComplete="current-password"
                                />
                                {deleteForm.errors.password && (
                                    <p className="mt-2 text-sm text-red-600">
                                        {deleteForm.errors.password}
                                    </p>
                                )}
                            </div>
                            <div className="crm-modal-footer">
                                <button
                                    type="button"
                                    className="crm-btn-secondary"
                                    onClick={() => setDeleteOpen(false)}
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    className="crm-btn-primary"
                                    disabled={deleteForm.processing}
                                >
                                    Delete Account
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </>
    );
}

Profile.layout = (page: ReactNode) => (
    <CrmLayout
        header={<h1 className="text-2xl font-semibold">Profile</h1>}
        headerSubtitle="Manage your account settings and security"
    >
        {page}
    </CrmLayout>
);
