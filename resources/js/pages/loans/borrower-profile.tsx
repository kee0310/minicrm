
import { Head, useForm } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';
import { CrmFilterSearch } from '@/components/crm/filter-search';
import { CrmFilterTabs } from '@/components/crm/filter-tabs';
import { LoanDetailModal } from '@/components/crm/loan-detail-modal';
import { CrmPagination, type PaginationLink } from '@/components/crm/pagination';
import { useCrmFilters } from '@/hooks/use-crm-filters';
import CrmLayout from '@/layouts/crm-layout';

type LoanOfficer = {
    id: number;
    name: string;
};

type BorrowerRow = {
    id: number;
    deal_code?: string | null;
    project_name?: string | null;
    client_name?: string | null;
    loan_officer_name?: string | null;
    loan_officer_id?: number | null;
    risk_grade?: string | null;
    risk_class?: string | null;
    existing_loans?: number | null;
    monthly_commitments?: number | null;
    credit_card_limits?: number | null;
    credit_card_utilization?: number | null;
    ccris?: string | null;
    ctos?: string | null;
    has_record?: boolean;
};

type PaginatedDeals = {
    data: BorrowerRow[];
    links: PaginationLink[];
};

type BorrowerProps = {
    deals: PaginatedDeals;
    canManageLoanRecords: boolean;
    loanOfficers: LoanOfficer[];
    currentLoanOfficerId?: number | null;
};

type BorrowerFormData = {
    assign_to: string;
    existing_loans: string;
    monthly_commitments: string;
    credit_card_limits: string;
    credit_card_utilization: string;
    ccris: string;
    ctos: string;
};

export default function BorrowerProfile({
    deals,
    canManageLoanRecords,
    loanOfficers,
    currentLoanOfficerId,
}: BorrowerProps) {
    const {
        searchTerm,
        setSearchTerm,
        statusFilter: completionFilter,
        submitSearch,
        resetSearch,
        applyStatus,
    } = useCrmFilters({
        baseUrl: '/loans/borrower-profile',
        statusKey: 'completion',
    });

    const [detailDealId, setDetailDealId] = useState<number | null>(null);
    const [editOpen, setEditOpen] = useState(false);
    const [selectedDeal, setSelectedDeal] = useState<BorrowerRow | null>(null);

    const form = useForm<BorrowerFormData>({
        assign_to: '',
        existing_loans: '',
        monthly_commitments: '',
        credit_card_limits: '',
        credit_card_utilization: '',
        ccris: '',
        ctos: '',
    });

    const openEdit = (deal: BorrowerRow) => {
        setSelectedDeal(deal);
        form.setData({
            assign_to: String(deal.loan_officer_id ?? currentLoanOfficerId ?? ''),
            existing_loans: deal.existing_loans != null ? String(deal.existing_loans) : '',
            monthly_commitments:
                deal.monthly_commitments != null
                    ? String(deal.monthly_commitments)
                    : '',
            credit_card_limits:
                deal.credit_card_limits != null
                    ? String(deal.credit_card_limits)
                    : '',
            credit_card_utilization:
                deal.credit_card_utilization != null
                    ? String(deal.credit_card_utilization)
                    : '',
            ccris: deal.ccris ?? '',
            ctos: deal.ctos ?? '',
        });
        setEditOpen(true);
    };

    const closeEdit = () => {
        setEditOpen(false);
        setSelectedDeal(null);
    };

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        if (!selectedDeal) {
            return;
        }
        form.put(`/loans/borrower-profile/${selectedDeal.id}`, {
            preserveScroll: true,
            onSuccess: () => closeEdit(),
        });
    };

    const rows = deals?.data ?? [];
    const sortStart = canManageLoanRecords ? 1 : 0;

    return (
        <>
            <Head title="Loans / Borrower Profile" />

            <div className="space-y-6">
                <div className="crm-card">
                    <div className="crm-card-body">
                        <div className="crm-filter-block">
                            <CrmFilterSearch
                                value={searchTerm}
                                onChange={setSearchTerm}
                                onSubmit={submitSearch}
                                onClear={resetSearch}
                                placeholder="Search project or client..."
                                className="crm-filter-search-row"
                            />
                            <CrmFilterTabs
                                items={[
                                    {
                                        label: 'All',
                                        value: '',
                                        active: completionFilter === '',
                                        variant: 'all',
                                        onClick: () => {
                                            applyStatus('');
                                        },
                                    },
                                    {
                                        label: 'New',
                                        value: 'new',
                                        active: completionFilter === 'new',
                                        variant: 'stage',
                                        onClick: () => {
                                            applyStatus('new');
                                        },
                                    },
                                    {
                                        label: 'Completed',
                                        value: 'completed',
                                        active: completionFilter === 'completed',
                                        variant: 'stage',
                                        onClick: () => {
                                            applyStatus('completed');
                                        },
                                    },
                                ]}
                            />
                        </div>

                        <div className="crm-table-wrap">
                            <table className="crm-table crm-table-center" data-sortable-table="true">
                                <thead>
                                    <tr>
                                        {canManageLoanRecords && <th></th>}
                                        <th data-sort-index={sortStart} className="w-[15%]">
                                            <span className="crm-sort-btn">
                                                Project <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index={sortStart + 1} className="col-left">
                                            <span className="crm-sort-btn">
                                                Client <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index={sortStart + 2}>
                                            <span className="crm-sort-btn">
                                                Loan Officer <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index={sortStart + 3}>
                                            <span className="crm-sort-btn">
                                                Risk <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index={sortStart + 4} data-sort-type="number">
                                            <span className="crm-sort-btn">
                                                Existing Loans <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index={sortStart + 5} data-sort-type="number">
                                            <span className="crm-sort-btn">
                                                Monthly Commitments{' '}
                                                <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index={sortStart + 6} data-sort-type="number">
                                            <span className="crm-sort-btn">
                                                Credit Card Limits{' '}
                                                <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index={sortStart + 7} data-sort-type="number">
                                            <span className="crm-sort-btn">
                                                Card Utilization (%) <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index={sortStart + 8}>
                                            <span className="crm-sort-btn">
                                                CCRIS <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index={sortStart + 9}>
                                            <span className="crm-sort-btn">
                                                CTOS <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200">
                                    {rows.length ? (
                                        rows.map((deal) => (
                                            <tr key={deal.id}>
                                                {canManageLoanRecords && (
                                                    <td>
                                                        <button
                                                            type="button"
                                                            className="crm-action-btn"
                                                            onClick={() => openEdit(deal)}
                                                        >
                                                            <i
                                                                className={`fa-solid ${
                                                                    deal.has_record
                                                                        ? 'fa-pen-to-square'
                                                                        : 'fa-plus'
                                                                }`}
                                                            ></i>
                                                        </button>
                                                    </td>
                                                )}
                                                <td
                                                    className="col-left"
                                                    data-sort-value={
                                                        deal.deal_code?.toLowerCase() ?? ''
                                                    }
                                                >
                                                    <button
                                                        type="button"
                                                        className="truncate text-indigo-600 hover:underline"
                                                        onClick={() => setDetailDealId(deal.id)}
                                                    >
                                                        {deal.deal_code ?? '-'}:
                                                    </button>
                                                    <br />
                                                    {deal.project_name ?? '-'}
                                                </td>
                                                <td
                                                    className="col-left"
                                                    data-sort-value={
                                                        deal.client_name?.toLowerCase() ?? ''
                                                    }
                                                >
                                                    {deal.client_name ?? '-'}
                                                </td>
                                                <td
                                                    data-sort-value={
                                                        deal.loan_officer_name?.toLowerCase() ?? ''
                                                    }
                                                >
                                                    {deal.loan_officer_name ?? '-'}
                                                </td>
                                                <td
                                                    data-sort-value={
                                                        deal.risk_grade?.toLowerCase() ?? ''
                                                    }
                                                >
                                                    <span
                                                        className={`inline-flex items-center px-2.5 py-1 rounded-full font-semibold ${
                                                            deal.risk_class ?? ''
                                                        }`}
                                                    >
                                                        {deal.risk_grade ?? '-'}
                                                    </span>
                                                </td>
                                                <td data-sort-value={deal.existing_loans ?? 0}>
                                                    {deal.existing_loans ?? '-'}
                                                </td>
                                                <td data-sort-value={deal.monthly_commitments ?? 0}>
                                                    {deal.monthly_commitments ?? '-'}
                                                </td>
                                                <td data-sort-value={deal.credit_card_limits ?? 0}>
                                                    {deal.credit_card_limits ?? '-'}
                                                </td>
                                                <td
                                                    data-sort-value={deal.credit_card_utilization ?? 0}
                                                >
                                                    {deal.credit_card_utilization != null
                                                        ? `${deal.credit_card_utilization}%`
                                                        : '-'}
                                                </td>
                                                <td
                                                    data-sort-value={
                                                        deal.ccris?.toLowerCase() ?? ''
                                                    }
                                                >
                                                    {deal.ccris ?? '-'}
                                                </td>
                                                <td
                                                    data-sort-value={
                                                        deal.ctos?.toLowerCase() ?? ''
                                                    }
                                                >
                                                    {deal.ctos ?? '-'}
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td
                                                colSpan={canManageLoanRecords ? 11 : 10}
                                                className="crm-table-empty"
                                            >
                                                No deals found.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        <div className="mt-4">
                            <CrmPagination links={deals?.links ?? []} />
                        </div>
                    </div>
                </div>
            </div>

            {editOpen && selectedDeal && canManageLoanRecords && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
                    onClick={(event) => {
                        if (event.currentTarget === event.target) {
                            closeEdit();
                        }
                    }}
                >
                    <div className="crm-modal-panel">
                        <div className="crm-modal-header">
                            <h4 className="crm-modal-title">
                                {selectedDeal.has_record
                                    ? 'Edit Financial Profile'
                                    : 'Add Financial Profile'}
                            </h4>
                            <button
                                type="button"
                                className="text-gray-500 hover:text-gray-700"
                                onClick={closeEdit}
                            >
                                X
                            </button>
                        </div>

                        <form onSubmit={submit} data-preserve-list-state>
                            <div className="crm-modal-grid">
                                <div className="md:col-span-2">
                                    <label className="crm-form-label">Assign To</label>
                                    <select
                                        name="assign_to"
                                        value={form.data.assign_to}
                                        onChange={(event) =>
                                            form.setData('assign_to', event.target.value)
                                        }
                                        className="crm-form-select"
                                    >
                                        {loanOfficers.map((officer) => (
                                            <option key={officer.id} value={officer.id}>
                                                {officer.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="crm-form-label">Existing Loans</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        name="existing_loans"
                                        value={form.data.existing_loans}
                                        onChange={(event) =>
                                            form.setData('existing_loans', event.target.value)
                                        }
                                        className="crm-form-input"
                                    />
                                </div>
                                <div>
                                    <label className="crm-form-label">Monthly Commitments</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        name="monthly_commitments"
                                        value={form.data.monthly_commitments}
                                        onChange={(event) =>
                                            form.setData(
                                                'monthly_commitments',
                                                event.target.value,
                                            )
                                        }
                                        className="crm-form-input"
                                    />
                                </div>
                                <div>
                                    <label className="crm-form-label">Credit Card Limits</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        name="credit_card_limits"
                                        value={form.data.credit_card_limits}
                                        onChange={(event) =>
                                            form.setData(
                                                'credit_card_limits',
                                                event.target.value,
                                            )
                                        }
                                        className="crm-form-input"
                                    />
                                </div>
                                <div>
                                    <label className="crm-form-label">
                                        Credit Card Utilization (%)
                                    </label>
                                    <input
                                        type="number"
                                        name="credit_card_utilization"
                                        value={form.data.credit_card_utilization}
                                        onChange={(event) =>
                                            form.setData(
                                                'credit_card_utilization',
                                                event.target.value,
                                            )
                                        }
                                        className="crm-form-input"
                                    />
                                </div>
                                <div>
                                    <label className="crm-form-label">CCRIS</label>
                                    <input
                                        type="text"
                                        name="ccris"
                                        value={form.data.ccris}
                                        onChange={(event) =>
                                            form.setData('ccris', event.target.value)
                                        }
                                        className="crm-form-input"
                                    />
                                </div>
                                <div>
                                    <label className="crm-form-label">CTOS</label>
                                    <input
                                        type="text"
                                        name="ctos"
                                        value={form.data.ctos}
                                        onChange={(event) =>
                                            form.setData('ctos', event.target.value)
                                        }
                                        className="crm-form-input"
                                    />
                                </div>
                            </div>

                            <div className="crm-modal-footer">
                                <button
                                    type="button"
                                    onClick={closeEdit}
                                    className="crm-btn-secondary"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    className="crm-btn-primary"
                                    disabled={form.processing}
                                >
                                    Save
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            <LoanDetailModal
                open={detailDealId != null}
                dealId={detailDealId}
                onClose={() => setDetailDealId(null)}
            />
        </>
    );
}

BorrowerProfile.layout = (page: ReactNode) => (
    <CrmLayout
        header={
            <div className="flex items-center justify-between gap-3">
                <div>
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Loans / Borrower Profile
                    </h2>
                </div>
            </div>
        }
    >
        {page}
    </CrmLayout>
);
