
import { usePage } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState, type ReactNode } from 'react';
import type { CrmShared } from '@/types/crm';

type LoanDetailModalProps = {
    open: boolean;
    dealId?: number | null;
    loanId?: string | number | null;
    onClose: () => void;
};

type PreQualificationRecommendation = {
    bank?: string | null;
    approval_probability?: number | null;
    loan_margin?: number | null;
};

type BankSubmissionItem = {
    loan_id?: string | number | null;
    bank_name?: string | null;
    banker_contact?: string | null;
    document_completeness_score?: number | null;
    approval_status?: string | null;
    expected_approval_date?: string | null;
    file_completeness_percentage?: number | null;
    submission_date?: string | null;
};

type ApprovalItem = {
    loan_id?: string | number | null;
    approved_bank?: string | null;
    applied_amount?: number | string | null;
    approved_amount?: number | string | null;
    interest_rate?: number | string | null;
    lock_in_period?: string | null;
};

type DisbursementItem = {
    loan_id?: string | number | null;
    first_disbursement_date?: string | null;
    full_disbursement_date?: string | null;
    spa_completion_date?: string | null;
    client_notification_date?: string | null;
};

type LoanDetailPayload = {
    deal_code?: string | null;
    project_name?: string | null;
    developer?: string | null;
    unit_number?: string | null;
    selling_price?: number | string | null;
    salesperson_name?: string | null;
    leader_name?: string | null;
    loan_officer_name?: string | null;
    legal_officer_name?: string | null;
    deal_status?: string | null;
    created_at?: string | null;
    client?: {
        name?: string | null;
        age?: number | string | null;
        occupation?: string | null;
        company?: string | null;
        working_years?: number | string | null;
        monthly_income?: number | string | null;
        fixed_income?: number | string | null;
        ic_passport?: string | null;
    } | null;
    borrower_profile?: {
        risk_grade?: string | null;
        existing_loans?: number | string | null;
        monthly_commitments?: number | string | null;
        credit_card_limits?: number | string | null;
        credit_card_utilization?: number | string | null;
        ccris?: string | null;
        ctos?: string | null;
    } | null;
    legal?: {
        status?: string | null;
        lawyer_firm?: string | null;
        spa_date?: string | null;
        loan_agreement_date?: string | null;
        completion_date?: string | null;
        stamp_duty?: boolean | null;
    } | null;
    pipeline_dates?: {
        lead_date?: string | null;
        viewing_date?: string | null;
        booking_date?: string | null;
        spa_signed_date?: string | null;
        loan_submitted_date?: string | null;
        loan_approved_date?: string | null;
        legal_processing_date?: string | null;
        completed_date?: string | null;
        commission_paid_date?: string | null;
    } | null;
    pre_qualification?: {
        date?: string | null;
        recommendations?: PreQualificationRecommendation[] | null;
    } | null;
    approval_analysis?: ApprovalItem[] | null;
    disbursements?: DisbursementItem[] | null;
    bank_submissions?: BankSubmissionItem[] | null;
};

const currencyFormatter = new Intl.NumberFormat('ms-MY', {
    style: 'currency',
    currency: 'MYR',
});

const formatCurrency = (value?: number | string | null) => {
    if (value === null || value === undefined || value === '') {
        return '-';
    }
    const numeric = Number(value);
    if (Number.isNaN(numeric)) {
        return '-';
    }
    return currencyFormatter.format(numeric);
};

const pipelineBadge = (status?: string | null) => {
    switch (status) {
        case 'New':
            return 'bg-gray-100 text-gray-800';
        case 'Viewing':
            return 'bg-blue-100 text-blue-800';
        case 'Booking':
            return 'bg-yellow-100 text-yellow-800';
        case 'SPA Signed':
            return 'bg-purple-100 text-purple-800';
        case 'Loan Submitted':
            return 'bg-orange-100 text-orange-800';
        case 'Loan Approved':
            return 'bg-green-100 text-green-800';
        case 'Legal Processing':
            return 'bg-indigo-100 text-indigo-800';
        case 'Completed':
            return 'bg-emerald-100 text-emerald-800';
        case 'Commission Paid':
            return 'bg-teal-100 text-teal-800';
        default:
            return 'bg-gray-100 text-gray-600';
    }
};

const renderCell = (value: unknown): ReactNode => {
    if (value === null || value === undefined || value === '') {
        return '-';
    }
    if (typeof value === 'boolean') {
        return value ? 'Yes' : 'No';
    }
    if (typeof value === 'object') {
        return JSON.stringify(value);
    }
    return value as ReactNode;
};

export function LoanDetailModal({
    open,
    dealId,
    loanId,
    onClose,
}: LoanDetailModalProps) {
    const page = usePage();
    const crm = (page.props.crm ?? {}) as CrmShared;
    const cacheRef = useRef<Record<string, LoanDetailPayload>>({});
    const [loading, setLoading] = useState(false);
    const [detail, setDetail] = useState<LoanDetailPayload | null>(null);
    const [error, setError] = useState<string | null>(null);

    const urlTemplates = useMemo(
        () => ({
            deal: crm?.urls?.loanDetailDeal ?? '/loans/detail/__DEAL__',
            loan: crm?.urls?.loanDetailLoan ?? '/loans/detail/by-loan/__LOAN__',
        }),
        [crm?.urls],
    );

    useEffect(() => {
        if (!open) {
            return;
        }
        const key = loanId ? `loan:${loanId}` : dealId ? `deal:${dealId}` : null;
        if (!key) {
            return;
        }

        if (cacheRef.current[key]) {
            setDetail(cacheRef.current[key]);
            setError(null);
            return;
        }

        const url = loanId
            ? urlTemplates.loan.replace('__LOAN__', encodeURIComponent(String(loanId)))
            : urlTemplates.deal.replace('__DEAL__', encodeURIComponent(String(dealId)));

        setLoading(true);
        setError(null);
        setDetail(null);

        fetch(url, {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then(async (response) => {
                if (!response.ok) {
                    throw new Error(`Failed to load detail (${response.status})`);
                }
                const payload = await response.json();
                const data = payload?.data ?? null;
                if (data) {
                    cacheRef.current[key] = data;
                }
                setDetail(data);
            })
            .catch((err) => {
                setError(err?.message ?? 'Failed to load detail');
            })
            .finally(() => {
                setLoading(false);
            });
    }, [open, dealId, loanId, urlTemplates.deal, urlTemplates.loan]);

    if (!open) {
        return null;
    }

    const preRecommendations =
        detail?.pre_qualification?.recommendations?.filter(
            (item) =>
                item?.bank || item?.approval_probability !== null || item?.loan_margin !== null,
        ) ?? [];

    const approvalRows = detail?.approval_analysis ?? [];
    const disbursementRows = detail?.disbursements ?? [];
    const bankSubmissions = detail?.bank_submissions ?? [];

    const summaryLeft = [
        ['Project', detail?.project_name],
        ['Developer', detail?.developer],
        ['Unit Number', detail?.unit_number],
        ['Selling Price', formatCurrency(detail?.selling_price)],
    ];

    const summaryRight = [
        ['Salesperson', detail?.salesperson_name],
        ['Leader', detail?.leader_name],
        ['Loan Officer', detail?.loan_officer_name],
        ['Legal Officer', detail?.legal_officer_name],
    ];

    const clientLeft = [
        ['Name', detail?.client?.name],
        ['Age', detail?.client?.age],
        ['Occupation', detail?.client?.occupation],
        ['Company', detail?.client?.company],
        ['Working Years', detail?.client?.working_years],
        ['Monthly Income', formatCurrency(detail?.client?.monthly_income)],
        ['Fixed Income', formatCurrency(detail?.client?.fixed_income)],
    ];

    const clientRight = [
        ['IC / Passport', detail?.client?.ic_passport],
        ['Existing Loans', formatCurrency(detail?.borrower_profile?.existing_loans)],
        ['Monthly Commitments', formatCurrency(detail?.borrower_profile?.monthly_commitments)],
        ['Credit Card Limits', formatCurrency(detail?.borrower_profile?.credit_card_limits)],
        [
            'Card Utilization',
            detail?.borrower_profile?.credit_card_utilization != null
                ? `${detail.borrower_profile.credit_card_utilization}%`
                : '-',
        ],
        ['CCRIS', detail?.borrower_profile?.ccris],
        ['CTOS', detail?.borrower_profile?.ctos],
    ];

    const legalRows = [
        ['Status', detail?.legal?.status],
        ['Lawyer Firm', detail?.legal?.lawyer_firm],
        ['SPA Date', detail?.legal?.spa_date],
        ['Loan Agreement Date', detail?.legal?.loan_agreement_date],
        ['Completion Date', detail?.legal?.completion_date],
        [
            'Stamp Duty',
            detail?.legal?.stamp_duty == null
                ? '-'
                : detail?.legal?.stamp_duty
                  ? 'Yes'
                  : 'No',
        ],
    ];

    const pipelineRows = [
        detail?.pipeline_dates?.lead_date
            ? `${detail.pipeline_dates.lead_date} - New Lead`
            : null,
        detail?.pipeline_dates?.viewing_date
            ? `${detail.pipeline_dates.viewing_date} - Viewing`
            : null,
        detail?.pipeline_dates?.booking_date
            ? `${detail.pipeline_dates.booking_date} - Booking`
            : null,
        detail?.pipeline_dates?.spa_signed_date
            ? `${detail.pipeline_dates.spa_signed_date} - SPA Signed`
            : null,
        detail?.pipeline_dates?.loan_submitted_date
            ? `${detail.pipeline_dates.loan_submitted_date} - Loan Submitted`
            : null,
        detail?.pipeline_dates?.loan_approved_date
            ? `${detail.pipeline_dates.loan_approved_date} - Loan Approved`
            : null,
        detail?.pipeline_dates?.legal_processing_date
            ? `${detail.pipeline_dates.legal_processing_date} - Legal Processing`
            : null,
        detail?.pipeline_dates?.completed_date
            ? `${detail.pipeline_dates.completed_date} - Completed`
            : null,
        detail?.pipeline_dates?.commission_paid_date
            ? `${detail.pipeline_dates.commission_paid_date} - Commission Paid`
            : null,
    ].filter(Boolean);

    return (
        <div
            className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-3"
            onClick={(event) => {
                if (event.target === event.currentTarget) {
                    onClose();
                }
            }}
        >
            <div className="w-full max-w-[680px] h-[90vh] overflow-y-auto border border-gray-300 bg-white p-5 shadow-2xl sm:p-7">
                {loading ? (
                    <div className="flex h-full items-center justify-center text-sm text-gray-600">
                        Loading deal detail...
                    </div>
                ) : error ? (
                    <div className="flex h-full items-center justify-center text-sm text-red-600">
                        {error}
                    </div>
                ) : (
                    <div className="space-y-4 text-xs text-gray-800">
                        <div className="mb-4 border-b border-gray-200 pb-3">
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <h4 className="text-xl font-bold text-gray-900">
                                        Deal Detail Report
                                    </h4>
                                    <p>
                                        <span>Deal Code:</span>{' '}
                                        <span>{detail?.deal_code ?? '-'}</span>
                                    </p>
                                </div>
                                <div className="grid gap-2 justify-items-end">
                                    <span
                                        className={`inline-flex items-center rounded-full mx-1 px-2 py-0.5 font-semibold max-w-min whitespace-nowrap ${pipelineBadge(
                                            detail?.deal_status,
                                        )}`}
                                    >
                                        {detail?.deal_status ?? '-'}
                                    </span>
                                    <p className="text-gray-500">
                                        <em>Created at:</em>{' '}
                                        <em>{detail?.created_at ?? '-'}</em>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <section className="rounded border border-gray-200 p-3">
                            <h5 className="mb-2 text-sm font-semibold text-gray-900">
                                Deal Summary
                            </h5>
                            <div className="grid grid-cols-1 gap-x-4 gap-y-1 sm:grid-cols-2">
                                <div className="space-y-1">
                                    {summaryLeft.map(([label, value]) => (
                                        <p key={label}>
                                            <span className="font-semibold">{label}:</span>{' '}
                                            <span>{renderCell(value)}</span>
                                        </p>
                                    ))}
                                </div>
                                <div className="space-y-1">
                                    {summaryRight.map(([label, value]) => (
                                        <p key={label}>
                                            <span className="font-semibold">{label}:</span>{' '}
                                            <span>{renderCell(value)}</span>
                                        </p>
                                    ))}
                                </div>
                            </div>
                        </section>

                        <section className="rounded border border-gray-200 p-3">
                            <div className="flex items-start justify-between gap-3">
                                <h5 className="mb-2 text-sm font-semibold text-gray-900">
                                    Client Detail
                                </h5>
                                <p>
                                    Risk Grade -{' '}
                                    <b>{detail?.borrower_profile?.risk_grade ?? '-'}</b>
                                </p>
                            </div>
                            <div className="grid grid-cols-1 gap-x-4 gap-y-1 sm:grid-cols-2">
                                <div className="space-y-1">
                                    {clientLeft.map(([label, value]) => (
                                        <p key={label}>
                                            <span className="font-semibold">{label}:</span>{' '}
                                            <span>{renderCell(value)}</span>
                                        </p>
                                    ))}
                                </div>
                                <div className="space-y-1">
                                    {clientRight.map(([label, value]) => (
                                        <p key={label}>
                                            <span className="font-semibold">{label}:</span>{' '}
                                            <span>{renderCell(value)}</span>
                                        </p>
                                    ))}
                                </div>
                            </div>
                        </section>

                        <section className="rounded border border-gray-200 p-3">
                            <div className="flex items-start justify-between gap-3">
                                <h5 className="mb-2 text-sm font-semibold text-gray-900">
                                    Pre-Qualification
                                </h5>
                                <em className="text-gray-600">
                                    Qualificated at:{' '}
                                    <span>{detail?.pre_qualification?.date ?? '-'}</span>
                                </em>
                            </div>
                            <div className="overflow-x-auto">
                                <table className="min-w-full border border-gray-200 text-center">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="border border-gray-200 px-2 py-1 w-[50%]">
                                                Bank
                                            </th>
                                            <th className="border border-gray-200 px-2 py-1 w-[25%]">
                                                Approval Probability
                                            </th>
                                            <th className="border border-gray-200 px-2 py-1 w-[25%]">
                                                Loan Margin
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {preRecommendations.length === 0 ? (
                                            <tr>
                                                <td
                                                    colSpan={3}
                                                    className="border border-gray-200 px-2 py-1 text-gray-500 text-center italic"
                                                >
                                                    No data
                                                </td>
                                            </tr>
                                        ) : (
                                            preRecommendations.map(
                                                (item, index) => (
                                                    <tr key={`${item?.bank ?? 'bank'}-${index}`}>
                                                        <td className="border border-gray-200 px-2 py-1">
                                                            {item?.bank ?? '-'}
                                                        </td>
                                                        <td className="border border-gray-200 px-2 py-1">
                                                            {item?.approval_probability != null
                                                                ? `${item.approval_probability}%`
                                                                : '-'}
                                                        </td>
                                                        <td className="border border-gray-200 px-2 py-1">
                                                            {item?.loan_margin != null
                                                                ? `${item.loan_margin}%`
                                                                : '-'}
                                                        </td>
                                                    </tr>
                                                ),
                                            )
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        <section className="rounded border border-gray-200 p-3">
                            <div className="flex items-start justify-between gap-3">
                                <h5 className="mb-2 text-sm font-semibold text-gray-900">
                                    Bank Submission Tracking
                                </h5>
                                <em className="text-gray-600">
                                    Approved at:{' '}
                                    <span>{detail?.pipeline_dates?.loan_approved_date ?? '-'}</span>
                                </em>
                            </div>
                            <div className="overflow-x-auto">
                                <table className="min-w-full border border-gray-200 text-center">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="border border-gray-200 px-2 py-1">
                                                Loan ID
                                            </th>
                                            <th className="border border-gray-200 px-2 py-1">
                                                Bank
                                            </th>
                                            <th className="border border-gray-200 px-2 py-1">
                                                Banker Contact
                                            </th>
                                            <th className="border border-gray-200 px-2 py-1">
                                                Doc Score
                                            </th>
                                            <th className="border border-gray-200 px-2 py-1">
                                                Status
                                            </th>
                                            <th className="border border-gray-200 px-2 py-1">
                                                Expected Approval
                                            </th>
                                            <th className="border border-gray-200 px-2 py-1">
                                                File Integrity
                                            </th>
                                            <th className="border border-gray-200 px-2 py-1">
                                                Submission Date
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {bankSubmissions.length === 0 ? (
                                            <tr>
                                                <td
                                                    colSpan={8}
                                                    className="border border-gray-200 px-2 py-1 text-gray-500 text-center italic"
                                                >
                                                    No data
                                                </td>
                                            </tr>
                                        ) : (
                                            bankSubmissions.map(
                                                (item, index) => (
                                                    <tr key={`${item?.loan_id ?? 'loan'}-${index}`}>
                                                        <td className="border border-gray-200 px-2 py-1">
                                                            {item?.loan_id ?? '-'}
                                                        </td>
                                                        <td className="border border-gray-200 px-2 py-1">
                                                            {item?.bank_name ?? '-'}
                                                        </td>
                                                        <td className="border border-gray-200 px-2 py-1">
                                                            {item?.banker_contact ?? '-'}
                                                        </td>
                                                        <td className="border border-gray-200 px-2 py-1">
                                                            {item?.document_completeness_score ?? '-'}
                                                        </td>
                                                        <td className="border border-gray-200 px-2 py-1">
                                                            {item?.approval_status ?? '-'}
                                                        </td>
                                                        <td className="border border-gray-200 px-2 py-1">
                                                            {item?.expected_approval_date ?? '-'}
                                                        </td>
                                                        <td className="border border-gray-200 px-2 py-1">
                                                            {item?.file_completeness_percentage ?? '-'}
                                                        </td>
                                                        <td className="border border-gray-200 px-2 py-1">
                                                            {item?.submission_date ?? '-'}
                                                        </td>
                                                    </tr>
                                                ),
                                            )
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </section>
                        <section className="rounded border border-gray-200 p-3">
                            <h5 className="mb-2 text-sm font-semibold text-gray-900">
                                Approval Analysis
                            </h5>
                            <div className="overflow-x-auto">
                                <table className="min-w-full border border-gray-200 text-center">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="border border-gray-200 px-2 py-1">
                                                Loan ID
                                            </th>
                                            <th className="border border-gray-200 px-2 py-1">
                                                Approved Bank
                                            </th>
                                            <th className="border border-gray-200 px-2 py-1">
                                                Applied Amount
                                            </th>
                                            <th className="border border-gray-200 px-2 py-1">
                                                Approved Amount
                                            </th>
                                            <th className="border border-gray-200 px-2 py-1">
                                                Interest Rate
                                            </th>
                                            <th className="border border-gray-200 px-2 py-1">
                                                Lock-in Period
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {approvalRows.length === 0 ? (
                                            <tr>
                                                <td
                                                    colSpan={6}
                                                    className="border border-gray-200 px-2 py-1 text-gray-500 text-center italic"
                                                >
                                                    No data
                                                </td>
                                            </tr>
                                        ) : (
                                            approvalRows.map(
                                                (item, index) => (
                                                    <tr key={`${item?.loan_id ?? 'loan'}-${index}`}>
                                                        <td className="border border-gray-200 px-2 py-1">
                                                            {item?.loan_id ?? '-'}
                                                        </td>
                                                        <td className="border border-gray-200 px-2 py-1">
                                                            {item?.approved_bank ?? '-'}
                                                        </td>
                                                        <td className="border border-gray-200 px-2 py-1">
                                                            {formatCurrency(item?.applied_amount)}
                                                        </td>
                                                        <td className="border border-gray-200 px-2 py-1">
                                                            {formatCurrency(item?.approved_amount)}
                                                        </td>
                                                        <td className="border border-gray-200 px-2 py-1">
                                                            {item?.interest_rate ?? '-'}
                                                        </td>
                                                        <td className="border border-gray-200 px-2 py-1">
                                                            {item?.lock_in_period ?? '-'}
                                                        </td>
                                                    </tr>
                                                ),
                                            )
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        <section className="rounded border border-gray-200 p-3">
                            <h5 className="mb-2 text-sm font-semibold text-gray-900">
                                Disbursement
                            </h5>
                            <div className="overflow-x-auto">
                                <table className="min-w-full border border-gray-200 text-center">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="border border-gray-200 px-2 py-1">
                                                Loan ID
                                            </th>
                                            <th className="border border-gray-200 px-2 py-1">
                                                First Disbursement
                                            </th>
                                            <th className="border border-gray-200 px-2 py-1">
                                                Full Disbursement
                                            </th>
                                            <th className="border border-gray-200 px-2 py-1">
                                                SPA Completion
                                            </th>
                                            <th className="border border-gray-200 px-2 py-1">
                                                Client Notification
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {disbursementRows.length === 0 ? (
                                            <tr>
                                                <td
                                                    colSpan={5}
                                                    className="border border-gray-200 px-2 py-1 text-gray-500 text-center italic"
                                                >
                                                    No data
                                                </td>
                                            </tr>
                                        ) : (
                                            disbursementRows.map(
                                                (item, index) => (
                                                    <tr key={`${item?.loan_id ?? 'loan'}-${index}`}>
                                                        <td className="border border-gray-200 px-2 py-1">
                                                            {item?.loan_id ?? '-'}
                                                        </td>
                                                        <td className="border border-gray-200 px-2 py-1">
                                                            {item?.first_disbursement_date ?? '-'}
                                                        </td>
                                                        <td className="border border-gray-200 px-2 py-1">
                                                            {item?.full_disbursement_date ?? '-'}
                                                        </td>
                                                        <td className="border border-gray-200 px-2 py-1">
                                                            {item?.spa_completion_date ?? '-'}
                                                        </td>
                                                        <td className="border border-gray-200 px-2 py-1">
                                                            {item?.client_notification_date ?? '-'}
                                                        </td>
                                                    </tr>
                                                ),
                                            )
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        <section className="rounded border border-gray-200 p-3">
                            <h5 className="mb-2 text-sm font-semibold text-gray-900">Legal</h5>
                            <div className="grid grid-cols-1 gap-x-4 gap-y-1 sm:grid-cols-2">
                                {legalRows.map(([label, value]) => (
                                    <p key={label}>
                                        <span className="font-semibold">{label}:</span>{' '}
                                        <span>{renderCell(value)}</span>
                                    </p>
                                ))}
                            </div>
                        </section>

                        <section className="rounded border border-gray-200 p-3">
                            <h5 className="mb-2 text-sm font-semibold text-gray-900">
                                Pipeline Dates
                            </h5>
                            <div className="grid grid-cols-1 gap-x-4 gap-y-1 sm:grid-cols-2">
                                {pipelineRows.length ? (
                                    pipelineRows.map((row) => <p key={row as string}>{row}</p>)
                                ) : (
                                    <p className="text-gray-500 italic">No pipeline dates.</p>
                                )}
                            </div>
                        </section>

                        <div className="flex justify-end">
                            <button
                                type="button"
                                className="rounded border border-gray-300 px-3 py-1 text-xs text-gray-700 hover:bg-gray-100"
                                onClick={onClose}
                            >
                                Close
                            </button>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
