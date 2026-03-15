import { Head, router, useForm } from '@inertiajs/react';
import { useMemo, useState, type ReactNode } from 'react';
import { CrmFilterSearch } from '@/components/crm/filter-search';
import { CrmFilterTabs } from '@/components/crm/filter-tabs';
import { CrmPagination } from '@/components/crm/pagination';
import { useCrmFilters } from '@/hooks/use-crm-filters';
import CrmLayout from '@/layouts/crm-layout';

type UserRow = {
    id: number;
    name: string;
    email: string;
    roles: string[];
    leader_id?: number | null;
    leader_name?: string | null;
    created_at?: string | null;
};

type PaginatedUsers = {
    data: UserRow[];
    links: { url: string | null; label: string; active: boolean }[];
};

type Leader = {
    id: number;
    name: string;
};

type UsersProps = {
    users: PaginatedUsers;
    roles: string[];
    leaders: Leader[];
};

type UserFormData = {
    id: number | null;
    name: string;
    email: string;
    role: string;
    leader_id: string;
    password: string;
    password_confirmation: string;
};

const SALESPERSON_ROLE = 'Salesperson';

export default function Users({ users, roles, leaders }: UsersProps) {
    const {
        searchTerm,
        setSearchTerm,
        statusFilter: roleFilter,
        submitSearch,
        resetSearch,
        applyStatus,
    } = useCrmFilters({
        baseUrl: '/users',
        statusKey: 'role',
    });

    const [userFormOpen, setUserFormOpen] = useState(false);
    const [userFormMode, setUserFormMode] = useState<'create' | 'edit'>('create');
    const [showErrors, setShowErrors] = useState(false);

    const emptyForm = useMemo<UserFormData>(
        () => ({
            id: null,
            name: '',
            email: '',
            role: '',
            leader_id: '',
            password: '',
            password_confirmation: '',
        }),
        [],
    );

    const form = useForm<UserFormData>(emptyForm);

    const openCreate = () => {
        setUserFormMode('create');
        form.setData({ ...emptyForm });
        setShowErrors(false);
        setUserFormOpen(true);
    };

    const openEdit = (user: UserRow) => {
        setUserFormMode('edit');
        form.setData({
            ...emptyForm,
            id: user.id,
            name: user.name ?? '',
            email: user.email ?? '',
            role: user.roles?.[0] ?? '',
            leader_id: user.leader_id ? String(user.leader_id) : '',
        });
        setShowErrors(false);
        setUserFormOpen(true);
    };

    const closeForm = () => {
        setUserFormOpen(false);
        setShowErrors(false);
    };

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        setShowErrors(true);

        if (userFormMode === 'create') {
            if ((form.data.password || '').length < 8) {
                return;
            }
            if (form.data.password !== form.data.password_confirmation) {
                return;
            }
        }
        if (!form.data.role) {
            return;
        }
        if (form.data.role === SALESPERSON_ROLE && !form.data.leader_id) {
            return;
        }

        if (userFormMode === 'edit' && form.data.id) {
            form.transform((data) => ({
                name: data.name,
                email: data.email,
                role: data.role,
                leader_id: data.leader_id || null,
            }));
            form.put(`/users/${form.data.id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    closeForm();
                    form.transform((data) => data);
                },
            });
            return;
        }

        form.post('/users', {
            preserveScroll: true,
            onSuccess: () => {
                closeForm();
            },
        });
    };

    const handleDelete = (user: UserRow) => {
        if (!window.confirm(`Confirm to delete user ${user.name}?`)) {
            return;
        }
        router.delete(`/users/${user.id}`, {
            preserveScroll: true,
        });
    };

    const rows = users?.data ?? [];

    return (
        <>
            <Head title="Users" />

            <div className="space-y-6">
                <div className="crm-card">
                    <div className="crm-card-body text-gray-900">
                        <div className="flex items-center justify-between mb-4">
                            <h3 className="text-lg font-medium">List of users</h3>
                        </div>

                        <div className="crm-filter-block">
                            <div className="crm-filter-toolbar">
                                <CrmFilterSearch
                                    value={searchTerm}
                                    onChange={setSearchTerm}
                                    onSubmit={submitSearch}
                                    onClear={resetSearch}
                                    placeholder="Search name or email..."
                                    className="w-full"
                                />
                                <button
                                    type="button"
                                    onClick={openCreate}
                                    className="crm-create-btn"
                                >
                                    Create User
                                </button>
                            </div>
                            <CrmFilterTabs
                                items={[
                                    {
                                        label: 'All',
                                        value: '',
                                        active: roleFilter === '',
                                        variant: 'all',
                                        onClick: () => {
                                            applyStatus('');
                                        },
                                    },
                                    ...roles.map((role) => ({
                                        label: role,
                                        value: role,
                                        active: roleFilter === role,
                                        variant: 'stage' as const,
                                        onClick: () => {
                                            applyStatus(role);
                                        },
                                    })),
                                ]}
                            />
                        </div>

                        {rows.length ? (
                            <>
                                <div className="crm-table-wrap">
                                    <table className="crm-table" data-sortable-table="true">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th data-sort-index="1">
                                                    <span className="crm-sort-btn">
                                                        Name <span data-sort-indicator></span>
                                                    </span>
                                                </th>
                                                <th data-sort-index="2">
                                                    <span className="crm-sort-btn">
                                                        Email <span data-sort-indicator></span>
                                                    </span>
                                                </th>
                                                <th data-sort-index="3">
                                                    <span className="crm-sort-btn">
                                                        Role <span data-sort-indicator></span>
                                                    </span>
                                                </th>
                                                <th data-sort-index="4">
                                                    <span className="crm-sort-btn">
                                                        Leader <span data-sort-indicator></span>
                                                    </span>
                                                </th>
                                                <th data-sort-index="5" data-sort-type="date">
                                                    <span className="crm-sort-btn">
                                                        Created <span data-sort-indicator></span>
                                                    </span>
                                                </th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody className="whitespace-nowrap">
                                            {rows.map((user) => (
                                                <tr key={user.id}>
                                                    <td>
                                                        <button
                                                            type="button"
                                                            className="crm-action-btn"
                                                            onClick={() => openEdit(user)}
                                                        >
                                                            <i className="fa-solid fa-pen-to-square"></i>
                                                        </button>
                                                    </td>
                                                    <td
                                                        data-sort-value={user.name?.toLowerCase()}
                                                        className="text-gray-900"
                                                    >
                                                        {user.name}
                                                    </td>
                                                    <td data-sort-value={user.email?.toLowerCase()}>
                                                        {user.email}
                                                    </td>
                                                    <td
                                                        data-sort-value={
                                                            user.roles?.join(', ').toLowerCase() ?? ''
                                                        }
                                                    >
                                                        {user.roles?.join(', ') ?? '-'}
                                                    </td>
                                                    <td
                                                        data-sort-value={
                                                            user.leader_name?.toLowerCase() ?? ''
                                                        }
                                                    >
                                                        {user.leader_name ?? '-'}
                                                    </td>
                                                    <td
                                                        data-sort-value={user.created_at ?? ''}
                                                        className="crm-table-date"
                                                    >
                                                        {user.created_at ?? '-'}
                                                    </td>
                                                    <td>
                                                        <button
                                                            type="button"
                                                            className="crm-action-btn-danger"
                                                            onClick={() => handleDelete(user)}
                                                        >
                                                            <i className="fa-solid fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>

                                <div className="mt-4">
                                    <CrmPagination links={users?.links ?? []} />
                                </div>
                            </>
                        ) : (
                            <div className="crm-table-empty-inline">No users found.</div>
                        )}
                    </div>
                </div>
            </div>

            {userFormOpen && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
                    onClick={(event) => {
                        if (event.currentTarget === event.target) {
                            closeForm();
                        }
                    }}
                >
                    <div className="crm-modal-panel">
                        <div className="crm-modal-header">
                            <h4 className="crm-modal-title">
                                {userFormMode === 'edit' ? 'Edit User' : 'Create User'}
                            </h4>
                            <button
                                type="button"
                                className="text-gray-500 hover:text-gray-700"
                                onClick={closeForm}
                            >
                                X
                            </button>
                        </div>
                        <form onSubmit={submit} data-preserve-list-state>
                            <div className="crm-modal-grid">
                                <div>
                                    <label className="crm-form-label" htmlFor="user_name">
                                        Name
                                    </label>
                                    <input
                                        id="user_name"
                                        className="crm-form-text"
                                        type="text"
                                        name="name"
                                        value={form.data.name}
                                        onChange={(event) =>
                                            form.setData('name', event.target.value)
                                        }
                                        required
                                    />
                                </div>
                                <div>
                                    <label className="crm-form-label" htmlFor="user_email">
                                        Email
                                    </label>
                                    <input
                                        id="user_email"
                                        className="crm-form-text"
                                        type="email"
                                        name="email"
                                        value={form.data.email}
                                        onChange={(event) =>
                                            form.setData('email', event.target.value)
                                        }
                                        required
                                    />
                                </div>
                                {userFormMode === 'create' && (
                                    <div>
                                        <label className="crm-form-label" htmlFor="user_password">
                                            Password
                                        </label>
                                        <input
                                            id="user_password"
                                            className="crm-form-text"
                                            type="password"
                                            name="password"
                                            value={form.data.password}
                                            onChange={(event) =>
                                                form.setData('password', event.target.value)
                                            }
                                            required
                                        />
                                        {showErrors && (form.data.password || '').length < 8 && (
                                            <p className="mt-1 text-xs text-red-600">
                                                Password must be at least 8 characters.
                                            </p>
                                        )}
                                    </div>
                                )}
                                {userFormMode === 'create' && (
                                    <div>
                                        <label
                                            className="crm-form-label"
                                            htmlFor="user_password_confirmation"
                                        >
                                            Confirm Password
                                        </label>
                                        <input
                                            id="user_password_confirmation"
                                            className="crm-form-text"
                                            type="password"
                                            name="password_confirmation"
                                            value={form.data.password_confirmation}
                                            onChange={(event) =>
                                                form.setData(
                                                    'password_confirmation',
                                                    event.target.value,
                                                )
                                            }
                                            required
                                        />
                                        {showErrors &&
                                            form.data.password !==
                                                form.data.password_confirmation && (
                                                <p className="mt-1 text-xs text-red-600">
                                                    Password not match.
                                                </p>
                                            )}
                                    </div>
                                )}
                                <div>
                                    <label className="crm-form-label" htmlFor="user_role">
                                        Role
                                    </label>
                                    <select
                                        id="user_role"
                                        name="role"
                                        value={form.data.role}
                                        onChange={(event) =>
                                            form.setData('role', event.target.value)
                                        }
                                        className="crm-form-select"
                                        required
                                    >
                                        <option value="">Select a role</option>
                                        {roles.map((role) => (
                                            <option key={role} value={role}>
                                                {role}
                                            </option>
                                        ))}
                                    </select>
                                    {showErrors && !form.data.role && (
                                        <p className="mt-1 text-xs text-red-600">
                                            Please select role.
                                        </p>
                                    )}
                                </div>
                                {form.data.role === SALESPERSON_ROLE && (
                                    <div>
                                        <label className="crm-form-label" htmlFor="user_leader_id">
                                            Leader
                                        </label>
                                        <select
                                            id="user_leader_id"
                                            name="leader_id"
                                            value={form.data.leader_id}
                                            onChange={(event) =>
                                                form.setData('leader_id', event.target.value)
                                            }
                                            className="crm-form-select"
                                            required
                                        >
                                            <option value="">Select leader</option>
                                            {leaders.map((leader) => (
                                                <option key={leader.id} value={leader.id}>
                                                    {leader.name}
                                                </option>
                                            ))}
                                        </select>
                                        {showErrors && !form.data.leader_id && (
                                            <p className="mt-1 text-xs text-red-600">
                                                Please select a leader.
                                            </p>
                                        )}
                                    </div>
                                )}
                            </div>
                            <div className="crm-modal-footer">
                                <button
                                    type="button"
                                    onClick={closeForm}
                                    className="crm-btn-secondary"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    className="crm-btn-primary"
                                    disabled={form.processing}
                                >
                                    {userFormMode === 'edit' ? 'Save' : 'Create'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </>
    );
}

Users.layout = (page: ReactNode) => (
    <CrmLayout
        header={
            <div className="flex items-center justify-between gap-3">
                <div>
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Users
                    </h2>
                </div>
            </div>
        }
    >
        {page}
    </CrmLayout>
);
