import { Head, Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { CrmFilterSearch } from '@/components/crm/filter-search';
import { CrmPagination } from '@/components/crm/pagination';
import { useCrmFilters } from '@/hooks/use-crm-filters';
import CrmLayout from '@/layouts/crm-layout';

type ClientRow = {
    id: number;
    name: string;
    email: string;
    phone: string;
    age?: number | null;
    occupation?: string | null;
    company?: string | null;
};

type PaginatedClients = {
    data: ClientRow[];
    links: { url: string | null; label: string; active: boolean }[];
};

type ClientsProps = {
    clients: PaginatedClients;
};

export default function Clients({ clients }: ClientsProps) {
    const { searchTerm, setSearchTerm, submitSearch, resetSearch } =
        useCrmFilters({
            baseUrl: '/clients',
        });

    const rows = clients?.data ?? [];

    return (
        <>
            <Head title="Clients" />

            <div className="space-y-6">
                <div className="crm-card">
                    <div className="crm-card-body text-gray-900">
                        <div className="mb-4 flex items-center justify-between">
                            <h3 className="text-lg font-medium">
                                List of clients
                            </h3>
                        </div>

                        <div className="crm-filter-block mb-6">
                            <div className="crm-filter-toolbar">
                                <CrmFilterSearch
                                    value={searchTerm}
                                    onChange={setSearchTerm}
                                    onSubmit={submitSearch}
                                    onClear={resetSearch}
                                    placeholder="Search name, email or phone..."
                                    className="w-full"
                                />
                            </div>
                        </div>

                        <div className="crm-table-wrap">
                            <table
                                className="crm-table"
                                data-sortable-table="true"
                            >
                                <thead>
                                    <tr>
                                        <th className="w-[50px]">
                                            <span className="crm-sort-btn pointer-events-none">
                                                No.
                                            </span>
                                        </th>
                                        <th data-sort-index="1">
                                            <span className="crm-sort-btn">
                                                Name{' '}
                                                <span
                                                    data-sort-indicator
                                                ></span>
                                            </span>
                                        </th>
                                        <th data-sort-index="2">
                                            <span className="crm-sort-btn">
                                                Email{' '}
                                                <span
                                                    data-sort-indicator
                                                ></span>
                                            </span>
                                        </th>
                                        <th data-sort-index="3">
                                            <span className="crm-sort-btn">
                                                Phone{' '}
                                                <span
                                                    data-sort-indicator
                                                ></span>
                                            </span>
                                        </th>
                                        <th
                                            data-sort-index="4"
                                            data-sort-type="number"
                                        >
                                            <span className="crm-sort-btn">
                                                Age{' '}
                                                <span
                                                    data-sort-indicator
                                                ></span>
                                            </span>
                                        </th>
                                        <th data-sort-index="5">
                                            <span className="crm-sort-btn">
                                                Occupation{' '}
                                                <span
                                                    data-sort-indicator
                                                ></span>
                                            </span>
                                        </th>
                                        <th data-sort-index="6">
                                            <span className="crm-sort-btn">
                                                Company{' '}
                                                <span
                                                    data-sort-indicator
                                                ></span>
                                            </span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="whitespace-nowrap">
                                    {rows.length ? (
                                        rows.map((client, index) => (
                                            <tr key={client.id}>
                                                <td>{index + 1}</td>
                                                <td
                                                    data-sort-value={client.name?.toLowerCase()}
                                                    className="text-gray-900"
                                                    style={{
                                                        textAlign: 'left',
                                                        paddingLeft: 20,
                                                    }}
                                                >
                                                    <Link
                                                        href={`/clients/${client.id}`}
                                                        className="text-indigo-600 hover:underline"
                                                    >
                                                        {client.name}
                                                    </Link>
                                                </td>
                                                <td
                                                    data-sort-value={client.email?.toLowerCase()}
                                                >
                                                    {client.email}
                                                </td>
                                                <td
                                                    data-sort-value={client.phone?.toLowerCase()}
                                                >
                                                    {client.phone}
                                                </td>
                                                <td
                                                    data-sort-value={
                                                        client.age ?? ''
                                                    }
                                                >
                                                    {client.age ?? '-'}
                                                </td>
                                                <td
                                                    data-sort-value={
                                                        client.occupation?.toLowerCase() ??
                                                        ''
                                                    }
                                                >
                                                    {client.occupation ?? '-'}
                                                </td>
                                                <td
                                                    data-sort-value={
                                                        client.company?.toLowerCase() ??
                                                        ''
                                                    }
                                                >
                                                    {client.company ?? '-'}
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td
                                                colSpan={7}
                                                className="crm-table-empty"
                                            >
                                                No clients found.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        <div className="mt-4">
                            <CrmPagination links={clients?.links ?? []} />
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

Clients.layout = (page: ReactNode) => (
    <CrmLayout
        header={
            <div className="flex items-center justify-between gap-3">
                <div>
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Clients
                    </h2>
                </div>
            </div>
        }
    >
        {page}
    </CrmLayout>
);
