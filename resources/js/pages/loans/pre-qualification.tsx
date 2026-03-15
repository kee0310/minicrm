
import { Head, useForm } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';
import { CrmFilterSearch } from '@/components/crm/filter-search';
import { CrmFilterTabs } from '@/components/crm/filter-tabs';
import { LoanDetailModal } from '@/components/crm/loan-detail-modal';
import { CrmPagination, type PaginationLink } from '@/components/crm/pagination';
import { useCrmFilters } from '@/hooks/use-crm-filters';
import CrmLayout from '@/layouts/crm-layout';

type Recommendation = {
    bank?: string | null;
    approval_probability?: number | null;
    loan_margin?: number | null;
};

type PreQualRow = {
    id: number;
    deal_code?: string | null;
    project_name?: string | null;
    client_name?: string | null;
    loan_officer_name?: string | null;
    risk_grade?: string | null;
    risk_class?: string | null;
    recommendations: Recommendation[];
    pre_qualification_date?: string | null;
    has_record?: boolean;
};

type PaginatedDeals = {
    data: PreQualRow[];
    links: PaginationLink[];
};

type PreQualificationProps = {
    deals: PaginatedDeals;
    bankOptions: string[];
    canManageLoanRecords: boolean;
};

type PreQualFormData = {
    pre_qualification_date: string;
    recommended_bank_1: string;
    recommended_bank_2: string;
    recommended_bank_3: string;
    approval_probability_1: string;
    approval_probability_2: string;
    approval_probability_3: string;
    loan_margin_1: string;
    loan_margin_2: string;
    loan_margin_3: string;
};

const formatRecommendation = (rec?: Recommendation | null) => {
    if (!rec || (!rec.bank && rec.approval_probability == null && rec.loan_margin == null)) {
        return null;
    }
    return (
        <div className="grid">
            {rec.bank ? <b>{rec.bank}</b> : '-'}
            {rec.loan_margin != null && (
                <em className="text-xs">Loan Margin: {rec.loan_margin}%</em>
            )}
            {rec.approval_probability != null && (
                <em className="text-xs">
                    Approval Probability: {rec.approval_probability}%
                </em>
            )}
        </div>
    );
};

export default function PreQualification({
    deals,
    bankOptions,
    canManageLoanRecords,
}: PreQualificationProps) {
    const {
        searchTerm,
        setSearchTerm,
        statusFilter: completionFilter,
        submitSearch,
        resetSearch,
        applyStatus,
    } = useCrmFilters({
        baseUrl: '/loans/pre-qualification',
        statusKey: 'completion',
    });

    const [detailDealId, setDetailDealId] = useState<number | null>(null);
    const [editOpen, setEditOpen] = useState(false);
    const [selectedDeal, setSelectedDeal] = useState<PreQualRow | null>(null);

    const form = useForm<PreQualFormData>({
        pre_qualification_date: '',
        recommended_bank_1: '',
        recommended_bank_2: '',
        recommended_bank_3: '',
        approval_probability_1: '',
        approval_probability_2: '',
        approval_probability_3: '',
        loan_margin_1: '',
        loan_margin_2: '',
        loan_margin_3: '',
    });

    const openEdit = (deal: PreQualRow) => {
        setSelectedDeal(deal);
        const recs = deal.recommendations ?? [];
        form.setData({
            pre_qualification_date:
                deal.pre_qualification_date ?? new Date().toISOString().slice(0, 10),
            recommended_bank_1: recs[0]?.bank ?? '',
            recommended_bank_2: recs[1]?.bank ?? '',
            recommended_bank_3: recs[2]?.bank ?? '',
            approval_probability_1:
                recs[0]?.approval_probability != null
                    ? String(recs[0]?.approval_probability)
                    : '',
            approval_probability_2:
                recs[1]?.approval_probability != null
                    ? String(recs[1]?.approval_probability)
                    : '',
            approval_probability_3:
                recs[2]?.approval_probability != null
                    ? String(recs[2]?.approval_probability)
                    : '',
            loan_margin_1:
                recs[0]?.loan_margin != null ? String(recs[0]?.loan_margin) : '',
            loan_margin_2:
                recs[1]?.loan_margin != null ? String(recs[1]?.loan_margin) : '',
            loan_margin_3:
                recs[2]?.loan_margin != null ? String(recs[2]?.loan_margin) : '',
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
        form.put(`/loans/pre-qualification/${selectedDeal.id}`, {
            preserveScroll: true,
            onSuccess: () => closeEdit(),
        });
    };

    const rows = deals?.data ?? [];
    const sortStart = canManageLoanRecords ? 1 : 0;

    return (
        <>
            <Head title="Loans / Pre-Qualification" />

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
                                        <th data-sort-index={sortStart}>
                                            <span className="crm-sort-btn">
                                                Project <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index={sortStart + 1}>
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
                                        <th data-sort-index={sortStart + 4} className="w-[15%]">
                                            <span className="crm-sort-btn whitespace-nowrap">
                                                Bank 1 <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index={sortStart + 5} className="w-[15%]">
                                            <span className="crm-sort-btn whitespace-nowrap">
                                                Bank 2 <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index={sortStart + 6} className="w-[15%]">
                                            <span className="crm-sort-btn whitespace-nowrap">
                                                Bank 3 <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index={sortStart + 7} data-sort-type="date">
                                            <span className="crm-sort-btn whitespace-nowrap">
                                                Qualificated at <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200">
                                    {rows.length ? (
                                        rows.map((deal) => {
                                            const recs = deal.recommendations ?? [];
                                            return (
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
                                                    {[0, 1, 2].map((index) => (
                                                        <td
                                                            key={`${deal.id}-rec-${index}`}
                                                            data-sort-value={
                                                                `${recs[index]?.bank ?? ''} ${
                                                                    recs[index]?.loan_margin ?? ''
                                                                } ${
                                                                    recs[index]?.approval_probability ?? ''
                                                                }`.toLowerCase()
                                                            }
                                                        >
                                                            {formatRecommendation(recs[index]) ?? '-'}
                                                        </td>
                                                    ))}
                                                    <td
                                                        data-sort-value={
                                                            deal.pre_qualification_date ?? ''
                                                        }
                                                        className="crm-table-date"
                                                    >
                                                        {deal.pre_qualification_date ?? '-'}
                                                    </td>
                                                </tr>
                                            );
                                        })
                                    ) : (
                                        <tr>
                                            <td
                                                colSpan={canManageLoanRecords ? 9 : 8}
                                                className="crm-table-empty"
                                            >
                                                No new deals found.
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
                                    ? 'Edit Pre-Qualification'
                                    : 'Add Pre-Qualification'}
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
                            <div className="space-y-4">
                                {[1, 2, 3].map((index) => (
                                    <div
                                        key={`rec-${index}`}
                                        className="rounded-md border border-gray-200 p-3"
                                    >
                                        <h5 className="mb-3 text-sm font-semibold text-gray-800">
                                            Recommendation {index}
                                        </h5>
                                        <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
                                            <div>
                                                <label className="crm-form-label">
                                                    Recommended Bank
                                                </label>
                                                <select
                                                    name={`recommended_bank_${index}`}
                                                    value={
                                                        form.data[
                                                            `recommended_bank_${index}` as keyof PreQualFormData
                                                        ] as string
                                                    }
                                                    onChange={(event) =>
                                                        form.setData(
                                                            `recommended_bank_${index}` as keyof PreQualFormData,
                                                            event.target.value,
                                                        )
                                                    }
                                                    className="crm-form-select"
                                                >
                                                    <option value="">-</option>
                                                    {bankOptions.map((bank) => (
                                                        <option key={bank} value={bank}>
                                                            {bank}
                                                        </option>
                                                    ))}
                                                </select>
                                            </div>
                                            <div>
                                                <label className="crm-form-label">
                                                    Approval Probability (%)
                                                </label>
                                                <input
                                                    type="number"
                                                    min={0}
                                                    max={100}
                                                    name={`approval_probability_${index}`}
                                                    value={
                                                        form.data[
                                                            `approval_probability_${index}` as keyof PreQualFormData
                                                        ] as string
                                                    }
                                                    onChange={(event) =>
                                                        form.setData(
                                                            `approval_probability_${index}` as keyof PreQualFormData,
                                                            event.target.value,
                                                        )
                                                    }
                                                    className="crm-form-input"
                                                />
                                            </div>
                                            <div>
                                                <label className="crm-form-label">
                                                    Loan Margin (%)
                                                </label>
                                                <select
                                                    name={`loan_margin_${index}`}
                                                    value={
                                                        form.data[
                                                            `loan_margin_${index}` as keyof PreQualFormData
                                                        ] as string
                                                    }
                                                    onChange={(event) =>
                                                        form.setData(
                                                            `loan_margin_${index}` as keyof PreQualFormData,
                                                            event.target.value,
                                                        )
                                                    }
                                                    className="crm-form-select"
                                                >
                                                    <option value="">-</option>
                                                    <option value="70">70%</option>
                                                    <option value="80">80%</option>
                                                    <option value="90">90%</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                ))}

                                <div>
                                    <label className="crm-form-label">Pre-Qualification Date</label>
                                    <input
                                        type="date"
                                        name="pre_qualification_date"
                                        value={form.data.pre_qualification_date}
                                        onChange={(event) =>
                                            form.setData(
                                                'pre_qualification_date',
                                                event.target.value,
                                            )
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

PreQualification.layout = (page: ReactNode) => (
    <CrmLayout
        header={
            <div className="flex items-center justify-between gap-3">
                <div>
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Loans / Pre-Qualification
                    </h2>
                </div>
            </div>
        }
    >
        {page}
    </CrmLayout>
);
