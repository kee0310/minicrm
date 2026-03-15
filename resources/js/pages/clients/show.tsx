import { Head } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';
import { LoanDetailModal } from '@/components/crm/loan-detail-modal';
import CrmLayout from '@/layouts/crm-layout';

type ClientProfile = {
    id: number;
    name: string;
    email?: string | null;
    phone?: string | null;
    age?: number | null;
    ic_passport?: string | null;
    occupation?: string | null;
    company?: string | null;
    working_years?: number | null;
    monthly_income?: number | null;
    fixed_income?: number | null;
};

type DealRow = {
    id: number;
    deal_code?: string | null;
    project_name?: string | null;
    developer?: string | null;
    unit_number?: string | null;
    selling_price?: number | null;
    created_at?: string | null;
    salesperson_name?: string | null;
    leader_name?: string | null;
    loan_officer_name?: string | null;
    legal_officer_name?: string | null;
    pipeline?: {
        value?: string | null;
        badge?: string | null;
    };
};

type ClientShowProps = {
    client: ClientProfile;
    deals: DealRow[];
};

const currencyFormatter = new Intl.NumberFormat('ms-MY', {
    style: 'currency',
    currency: 'MYR',
});

export default function ClientShow({ client, deals }: ClientShowProps) {
    const [detailDealId, setDetailDealId] = useState<number | null>(null);

    const openDetail = (dealId: number) => {
        setDetailDealId(dealId);
    };

    const closeDetail = () => {
        setDetailDealId(null);
    };

    return (
        <>
            <Head title="Client Profile" />

            <div className="py-6">
                <button
                    type="button"
                    onClick={() => window.history.back()}
                    className="ml-4 inline-flex items-center gap-2 border border-slate-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-[0.08em] text-slate-700 shadow-sm transition hover:bg-blue-500 hover:text-white"
                >
                    <span aria-hidden="true">&larr;</span>
                    Back
                </button>

                <div className="grid grid-cols-3 gap-3">
                    <div className="crm-card crm-card-body grid-cols-1 h-min">
                        <div className="grid items-center justify-between gap-2">
                            <p className="text-xs font-semibold uppercase tracking-[0.08em] text-slate-500">
                                Client
                            </p>
                            <h3 className="mt-1 text-2xl font-semibold text-slate-900">
                                {client?.name ?? '-'}
                            </h3>
                        </div>

                        <div className="grid gap-2 mt-8 text-sm text-slate-700">
                            <div>
                                <span className="font-semibold">Lead ID:</span> {client?.id ?? '-'}
                            </div>
                            <div>
                                <span className="font-semibold">Email:</span> {client?.email ?? '-'}
                            </div>
                            <div>
                                <span className="font-semibold">Phone:</span> {client?.phone ?? '-'}
                            </div>
                            <div>
                                <span className="font-semibold">Age:</span> {client?.age ?? '-'}
                            </div>
                            <div>
                                <span className="font-semibold">IC/Passport:</span>{' '}
                                {client?.ic_passport ?? '-'}
                            </div>
                            <div>
                                <span className="font-semibold">Occupation:</span>{' '}
                                {client?.occupation ?? '-'}
                            </div>
                            <div>
                                <span className="font-semibold">Company:</span>{' '}
                                {client?.company ?? '-'}
                            </div>
                            <div>
                                <span className="font-semibold">Working Years:</span>{' '}
                                {client?.working_years ?? '-'}
                            </div>
                            <div>
                                <span className="font-semibold">Monthly Income:</span>{' '}
                                {client?.monthly_income
                                    ? `RM ${client.monthly_income.toLocaleString('en-US', {
                                          minimumFractionDigits: 2,
                                          maximumFractionDigits: 2,
                                      })}`
                                    : '-'}
                            </div>
                            <div>
                                <span className="font-semibold">Fixed Income:</span>{' '}
                                {client?.fixed_income
                                    ? `RM ${client.fixed_income.toLocaleString('en-US', {
                                          minimumFractionDigits: 2,
                                          maximumFractionDigits: 2,
                                      })}`
                                    : '-'}
                            </div>
                        </div>
                    </div>

                    <div className="crm-card crm-card-body col-span-2">
                        <div className="mb-4 flex items-center justify-between">
                            <h3 className="text-lg font-semibold text-slate-900">Deals</h3>
                            <span className="text-xs font-semibold uppercase tracking-[0.08em] text-slate-500">
                                {deals.length} {deals.length === 1 ? 'Record' : 'Records'}
                            </span>
                        </div>

                        {deals.length ? (
                            <div className="space-y-4">
                                {deals.map((deal) => (
                                    <div
                                        key={deal.id}
                                        className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
                                    >
                                        <div className="flex flex-wrap items-start justify-between gap-3">
                                            <div>
                                                <p className="text-xs font-semibold uppercase tracking-[0.08em] text-slate-500">
                                                    {deal.deal_code ?? '-'}
                                                </p>
                                                <p className="text-base font-semibold text-slate-900">
                                                    {deal.project_name ?? '-'}
                                                </p>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <span className={deal.pipeline?.badge ?? ''}>
                                                    {deal.pipeline?.value ?? '-'}
                                                </span>
                                                <button
                                                    type="button"
                                                    className="crm-btn-secondary text-xs text-blue-600"
                                                    onClick={() => openDetail(deal.id)}
                                                >
                                                    Details
                                                </button>
                                            </div>
                                        </div>

                                        <div className="mt-4 grid grid-cols-1 gap-4 text-sm text-slate-700 lg:grid-cols-2">
                                            <div className="space-y-2">
                                                <p>
                                                    <span className="font-semibold">Developer:</span>{' '}
                                                    {deal.developer ?? '-'}
                                                </p>
                                                <p>
                                                    <span className="font-semibold">Unit Number:</span>{' '}
                                                    {deal.unit_number ?? '-'}
                                                </p>
                                                <p>
                                                    <span className="font-semibold">Selling Price:</span>{' '}
                                                    {deal.selling_price != null
                                                        ? currencyFormatter.format(deal.selling_price)
                                                        : '-'}
                                                </p>
                                                <p>
                                                    <span className="font-semibold">Created:</span>{' '}
                                                    {deal.created_at ?? '-'}
                                                </p>
                                            </div>
                                            <div className="space-y-2">
                                                <p>
                                                    <span className="font-semibold">Salesperson:</span>{' '}
                                                    {deal.salesperson_name ?? '-'}
                                                </p>
                                                <p>
                                                    <span className="font-semibold">Leader:</span>{' '}
                                                    {deal.leader_name ?? '-'}
                                                </p>
                                                <p>
                                                    <span className="font-semibold">Loan Officer:</span>{' '}
                                                    {deal.loan_officer_name ?? '-'}
                                                </p>
                                                <p>
                                                    <span className="font-semibold">Legal Officer:</span>{' '}
                                                    {deal.legal_officer_name ?? '-'}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="crm-table-empty-inline">
                                No deals found for this client.
                            </div>
                        )}
                    </div>
                </div>
            </div>

            <LoanDetailModal open={detailDealId != null} dealId={detailDealId} onClose={closeDetail} />
        </>
    );
}

ClientShow.layout = (page: ReactNode) => (
    <CrmLayout
        header={
            <div className="flex items-center justify-between gap-3">
                <div>
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Client Profile
                    </h2>
                </div>
            </div>
        }
    >
        {page}
    </CrmLayout>
);
