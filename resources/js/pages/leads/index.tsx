
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useMemo, useState, type ReactNode } from 'react';
import { CrmFilterSearch } from '@/components/crm/filter-search';
import { CrmFilterTabs } from '@/components/crm/filter-tabs';
import { CrmPagination } from '@/components/crm/pagination';
import { useCrmFilters } from '@/hooks/use-crm-filters';
import CrmLayout from '@/layouts/crm-layout';

type Salesperson = {
    id: number;
    name: string;
};

type LeadRow = {
    id: number;
    name: string;
    email: string;
    phone: string;
    source: string;
    salesperson_id: number | null;
    leader_id: number | null;
    salesperson_name?: string | null;
    leader_name?: string | null;
    status?: string | null;
    status_badge?: string | null;
    age?: number | null;
    ic_passport?: string | null;
    occupation?: string | null;
    company?: string | null;
    working_years?: number | null;
    monthly_income?: number | null;
    fixed_income?: number | null;
    created_at?: string | null;
};

type PaginatedLeads = {
    data: LeadRow[];
    links: { url: string | null; label: string; active: boolean }[];
};

type LeadsProps = {
    leads: PaginatedLeads;
    statuses: string[];
    salespersons: Salesperson[];
    summary: Record<string, number>;
    canEditSalesperson: boolean;
};

type LeadFormData = {
    id: number | null;
    name: string;
    email: string;
    phone: string;
    source: string;
    salesperson_id: string;
    status: string;
    age: string;
    ic_passport: string;
    occupation: string;
    company: string;
    monthly_income: string;
    working_years: string;
    fixed_income: string;
};

const LEAD_SOURCES = [
    'Facebook',
    'Friend Referral',
    'Exhibition/Fair',
    'Company Assigned',
    'Old Client Referral',
];

export default function Leads(props: LeadsProps) {
    const page = usePage();
    const authUser = page.props.auth?.user as { id: number } | undefined;
    const currentUserId = authUser?.id ?? 0;

    const {
        searchTerm,
        setSearchTerm,
        statusFilter,
        submitSearch,
        resetSearch,
        applyStatus,
    } = useCrmFilters({
        baseUrl: '/leads',
    });

    const defaultSalespersonId = useMemo(() => {
        const hasCurrent = props.salespersons.some(
            (user) => Number(user.id) === Number(currentUserId),
        );
        if (hasCurrent) {
            return String(currentUserId);
        }
        return String(props.salespersons[0]?.id ?? '');
    }, [props.salespersons, currentUserId]);

    const emptyForm = (): LeadFormData => ({
        id: null,
        name: '',
        email: '',
        phone: '',
        source: LEAD_SOURCES[0] ?? '',
        salesperson_id: defaultSalespersonId,
        status: props.statuses[0] ?? 'New',
        age: '',
        ic_passport: '',
        occupation: '',
        company: '',
        monthly_income: '',
        working_years: '',
        fixed_income: '',
    });

    const [modalOpen, setModalOpen] = useState(false);
    const [mode, setMode] = useState<'create' | 'edit'>('create');

    const form = useForm<LeadFormData>(emptyForm());

    const showDealFields = form.data.status === 'Deal';

    const openCreate = () => {
        setMode('create');
        form.setData(emptyForm());
        setModalOpen(true);
    };

    const openEdit = (lead: LeadRow) => {
        setMode('edit');
        form.setData({
            id: lead.id,
            name: lead.name ?? '',
            email: lead.email ?? '',
            phone: lead.phone ?? '',
            source: lead.source ?? '',
            salesperson_id: String(lead.salesperson_id ?? ''),
            status: lead.status ?? 'New',
            age: lead.age ? String(lead.age) : '',
            ic_passport: lead.ic_passport ?? '',
            occupation: lead.occupation ?? '',
            company: lead.company ?? '',
            monthly_income: lead.monthly_income ? String(lead.monthly_income) : '',
            working_years: lead.working_years ? String(lead.working_years) : '',
            fixed_income: lead.fixed_income ? String(lead.fixed_income) : '',
        });
        setModalOpen(true);
    };

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        if (mode === 'edit' && form.data.id) {
            form.put(`/leads/${form.data.id}`, {
                preserveScroll: true,
                onSuccess: () => setModalOpen(false),
            });
            return;
        }
        form.post('/leads', {
            preserveScroll: true,
            onSuccess: () => setModalOpen(false),
        });
    };

    const handleDelete = (lead: LeadRow) => {
        if (!window.confirm(`Confirm to delete lead ${lead.name}?`)) {
            return;
        }
        router.delete(`/leads/${lead.id}`, {
            preserveScroll: true,
        });
    };

    const rows = props.leads?.data ?? [];

    return (
        <>
            <Head title="Leads" />

            <div className="space-y-6">
                <section className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                    <article className="crm-kpi">
                        <p className="crm-kpi-label">Total Leads</p>
                        <p className="mt-2 text-2xl font-semibold text-slate-900">
                            {(props.summary?.total ?? 0).toLocaleString('en-US')}
                        </p>
                    </article>
                    {props.statuses.map((status) => {
                        const key = status.toLowerCase().replaceAll(' ', '_');
                        const value = Number(props.summary?.[key] ?? 0);
                        const isLost = status.toLowerCase() === 'lost';
                        const isDeal = status.toLowerCase() === 'deal';
                        return (
                            <article key={status} className="crm-kpi">
                                <p className="crm-kpi-label">{status}</p>
                                <p
                                    className={`mt-2 text-2xl font-semibold ${
                                        isLost
                                            ? 'text-red-600'
                                            : isDeal
                                              ? 'text-green-600'
                                              : 'text-slate-900'
                                    }`}
                                >
                                    {value.toLocaleString('en-US')}
                                </p>
                            </article>
                        );
                    })}
                </section>

                <div className="crm-card">
                    <div className="crm-card-body text-gray-900">
                        <div className="flex items-center justify-between mb-4">
                            <h3 className="text-lg font-medium">List of leads</h3>
                        </div>

                        <div className="crm-filter-block">
                            <div className="crm-filter-toolbar">
                                <CrmFilterSearch
                                    value={searchTerm}
                                    onChange={setSearchTerm}
                                    onSubmit={submitSearch}
                                    onClear={resetSearch}
                                    placeholder="Search name, email or phone..."
                                    className="w-full"
                                />
                                <button
                                    className="crm-create-btn sm:w-full"
                                    type="button"
                                    onClick={openCreate}
                                >
                                    Create Lead
                                </button>
                            </div>
                            <CrmFilterTabs
                                items={[
                                    {
                                        label: 'All',
                                        value: '',
                                        active: statusFilter === '',
                                        variant: 'all',
                                        onClick: () => {
                                            applyStatus('');
                                        },
                                    },
                                    ...props.statuses.map((status) => ({
                                        label: status,
                                        value: status,
                                        active: statusFilter === status,
                                        variant: 'stage' as const,
                                        onClick: () => {
                                            applyStatus(status);
                                        },
                                    })),
                                ]}
                            />
                        </div>

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
                                                Phone <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index="4">
                                            <span className="crm-sort-btn">
                                                Source <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index="5">
                                            <span className="crm-sort-btn">
                                                Salesperson <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index="6">
                                            <span className="crm-sort-btn">
                                                Leader <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index="7">
                                            <span className="crm-sort-btn">
                                                Status <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index="8" data-sort-type="date">
                                            <span className="crm-sort-btn">
                                                Created Date <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody className="whitespace-nowrap">
                                    {rows.length > 0 ? (
                                        rows.map((lead) => (
                                            <tr key={lead.id}>
                                                <td>
                                                    <button
                                                        type="button"
                                                        className="crm-action-btn"
                                                        onClick={() => openEdit(lead)}
                                                    >
                                                        <i className="fa-solid fa-pen-to-square"></i>
                                                    </button>
                                                </td>
                                                <td
                                                    data-sort-value={lead.name?.toLowerCase()}
                                                    className="text-sm text-gray-900"
                                                >
                                                    {lead.name}
                                                </td>
                                                <td data-sort-value={lead.email?.toLowerCase()}>
                                                    {lead.email}
                                                </td>
                                                <td data-sort-value={lead.phone?.toLowerCase()}>
                                                    {lead.phone}
                                                </td>
                                                <td data-sort-value={lead.source?.toLowerCase()}>
                                                    {lead.source}
                                                </td>
                                                <td data-sort-value={lead.salesperson_name?.toLowerCase() ?? ''}>
                                                    {lead.salesperson_name ?? '-'}
                                                </td>
                                                <td data-sort-value={lead.leader_name?.toLowerCase() ?? ''}>
                                                    {lead.leader_name ?? '-'}
                                                </td>
                                                <td data-sort-value={lead.status?.toLowerCase() ?? ''}>
                                                    <span className={lead.status_badge ?? ''}>
                                                        {lead.status}
                                                    </span>
                                                </td>
                                                <td data-sort-value={lead.created_at ?? ''} className="crm-table-date">
                                                    {lead.created_at ?? '-'}
                                                </td>
                                                <td>
                                                    <button
                                                        type="button"
                                                        className="crm-action-btn-danger"
                                                        onClick={() => handleDelete(lead)}
                                                    >
                                                        <i className="fa-solid fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td colSpan={10} className="crm-table-empty">
                                                No leads found.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        <div className="mt-4">
                            <CrmPagination links={props.leads?.links ?? []} />
                        </div>
                    </div>
                </div>
            </div>
            {modalOpen && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
                    onClick={(event) => {
                        if (event.currentTarget === event.target) {
                            setModalOpen(false);
                        }
                    }}
                >
                    <div className="crm-modal-panel">
                        <div className="crm-modal-header">
                            <h4 className="crm-modal-title">
                                {mode === 'edit' ? 'Edit Lead' : 'Create Lead'}
                            </h4>
                            <button
                                type="button"
                                className="text-gray-500 hover:text-gray-700"
                                onClick={() => setModalOpen(false)}
                            >
                                X
                            </button>
                        </div>
                        <form onSubmit={submit} data-preserve-list-state>
                            <div className="crm-modal-grid">
                                <div>
                                    <label className="crm-form-label" htmlFor="lead_salesperson_id">
                                        Salesperson
                                    </label>
                                    <select
                                        id="lead_salesperson_id"
                                        className="crm-form-select"
                                        value={form.data.salesperson_id}
                                        onChange={(event) =>
                                            form.setData('salesperson_id', event.target.value)
                                        }
                                        disabled={!props.canEditSalesperson}
                                        required
                                    >
                                        <option value="">Select a user</option>
                                        {props.salespersons.map((salesperson) => (
                                            <option key={salesperson.id} value={salesperson.id}>
                                                {salesperson.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="crm-form-label" htmlFor="lead_source">
                                        Source
                                    </label>
                                    <select
                                        id="lead_source"
                                        className="crm-form-select"
                                        value={form.data.source}
                                        onChange={(event) =>
                                            form.setData('source', event.target.value)
                                        }
                                        required
                                    >
                                        {LEAD_SOURCES.map((source) => (
                                            <option key={source} value={source}>
                                                {source}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="crm-form-label" htmlFor="lead_name">
                                        Name
                                    </label>
                                    <input
                                        id="lead_name"
                                        className="crm-form-text"
                                        type="text"
                                        value={form.data.name}
                                        onChange={(event) =>
                                            form.setData('name', event.target.value)
                                        }
                                        required
                                    />
                                </div>
                                <div>
                                    <label className="crm-form-label" htmlFor="lead_email">
                                        Email
                                    </label>
                                    <input
                                        id="lead_email"
                                        className="crm-form-text"
                                        type="email"
                                        value={form.data.email}
                                        onChange={(event) =>
                                            form.setData('email', event.target.value)
                                        }
                                        required
                                    />
                                </div>
                                <div>
                                    <label className="crm-form-label" htmlFor="lead_phone">
                                        Phone
                                    </label>
                                    <input
                                        id="lead_phone"
                                        className="crm-form-text"
                                        type="text"
                                        value={form.data.phone}
                                        onChange={(event) =>
                                            form.setData('phone', event.target.value)
                                        }
                                        required
                                    />
                                </div>
                                <div>
                                    <label className="crm-form-label" htmlFor="lead_status">
                                        Status
                                    </label>
                                    <select
                                        id="lead_status"
                                        className="crm-form-select"
                                        value={form.data.status}
                                        onChange={(event) =>
                                            form.setData('status', event.target.value)
                                        }
                                        disabled={mode === 'edit' && form.data.status === 'Deal'}
                                        required
                                    >
                                        {props.statuses.map((status) => (
                                            <option key={status} value={status}>
                                                {status}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            </div>

                            {showDealFields && (
                                <div className="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div>
                                        <label className="crm-form-label" htmlFor="lead_age">
                                            Age
                                        </label>
                                        <input
                                            id="lead_age"
                                            className="crm-form-text"
                                            type="number"
                                            min={18}
                                            max={120}
                                            value={form.data.age}
                                            onChange={(event) =>
                                                form.setData('age', event.target.value)
                                            }
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label className="crm-form-label" htmlFor="lead_ic_passport">
                                            IC/Passport
                                        </label>
                                        <input
                                            id="lead_ic_passport"
                                            className="crm-form-text"
                                            type="text"
                                            value={form.data.ic_passport}
                                            onChange={(event) =>
                                                form.setData('ic_passport', event.target.value)
                                            }
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label className="crm-form-label" htmlFor="lead_occupation">
                                            Occupation
                                        </label>
                                        <input
                                            id="lead_occupation"
                                            className="crm-form-text"
                                            type="text"
                                            value={form.data.occupation}
                                            onChange={(event) =>
                                                form.setData('occupation', event.target.value)
                                            }
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label className="crm-form-label" htmlFor="lead_company">
                                            Company
                                        </label>
                                        <input
                                            id="lead_company"
                                            className="crm-form-text"
                                            type="text"
                                            value={form.data.company}
                                            onChange={(event) =>
                                                form.setData('company', event.target.value)
                                            }
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label className="crm-form-label" htmlFor="lead_monthly_income">
                                            Monthly Income
                                        </label>
                                        <input
                                            id="lead_monthly_income"
                                            className="crm-form-text"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={form.data.monthly_income}
                                            onChange={(event) =>
                                                form.setData('monthly_income', event.target.value)
                                            }
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label className="crm-form-label" htmlFor="lead_working_years">
                                            Working Years
                                        </label>
                                        <input
                                            id="lead_working_years"
                                            className="crm-form-text"
                                            type="number"
                                            min="0"
                                            value={form.data.working_years}
                                            onChange={(event) =>
                                                form.setData('working_years', event.target.value)
                                            }
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label className="crm-form-label" htmlFor="lead_fixed_income">
                                            Fixed Income
                                        </label>
                                        <input
                                            id="lead_fixed_income"
                                            className="crm-form-text"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={form.data.fixed_income}
                                            onChange={(event) =>
                                                form.setData('fixed_income', event.target.value)
                                            }
                                            required
                                        />
                                    </div>
                                </div>
                            )}

                            <div className="crm-modal-footer">
                                <button
                                    type="button"
                                    className="crm-btn-secondary"
                                    onClick={() => setModalOpen(false)}
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    className="crm-btn-primary"
                                    disabled={form.processing}
                                >
                                    {mode === 'edit' ? 'Save' : 'Create'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </>
    );
}

Leads.layout = (page: ReactNode) => (
    <CrmLayout
        header={
            <div className="flex items-center justify-between gap-3">
                <div>
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Leads
                    </h2>
                </div>
            </div>
        }
    >
        {page}
    </CrmLayout>
);
