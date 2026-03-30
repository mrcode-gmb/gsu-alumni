import Heading from '@/components/heading';
import { PaginationLinks } from '@/components/pagination-links';
import { PaymentRecordSummaryCards } from '@/components/payment-records/payment-record-summary-cards';
import { PaymentStatusBadge } from '@/components/payment-records/payment-status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type AdminPaymentDashboardSummary, type BreadcrumbItem, type PaginationLink, type PaymentRequestStatus } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowRight, Search } from 'lucide-react';
import { useMemo, useState } from 'react';

type CashierPaymentRecord = {
    public_reference: string;
    full_name: string;
    matric_number: string;
    payment_type_name: string;
    base_amount: string;
    payment_status: PaymentRequestStatus;
    payment_status_label: string;
    payment_reference: string | null;
    receipt_number: string | null;
    recorded_at: string | null;
    is_successful: boolean;
};

interface CashierPaymentRecordPagination {
    data: CashierPaymentRecord[];
    links: PaginationLink[];
    meta: {
        current_page: number;
        from: number | null;
        last_page: number;
        path: string;
        per_page: number;
        to: number | null;
        total: number;
    };
}

interface CashierPaymentRecordIndexProps {
    summary: AdminPaymentDashboardSummary;
    paymentRecords: CashierPaymentRecordPagination;
    filters: {
        search: string;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Payment Records', href: '/cashier/payment-records' },
];

const currencyFormatter = new Intl.NumberFormat('en-NG', {
    style: 'currency',
    currency: 'NGN',
    minimumFractionDigits: 2,
});

export default function CashierPaymentRecordsIndex({ summary, paymentRecords, filters }: CashierPaymentRecordIndexProps) {
    const [search, setSearch] = useState(filters.search ?? '');

    const handleSearch = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        router.get(
            route('cashier.payment-records.index'),
            { search: search.trim() },
            { preserveScroll: true, preserveState: true },
        );
    };

    const currentRange = useMemo(() => {
        if (!paymentRecords.meta.total || !paymentRecords.meta.from || !paymentRecords.meta.to) {
            return '0';
        }

        return `${paymentRecords.meta.from}-${paymentRecords.meta.to} of ${paymentRecords.meta.total}`;
    }, [paymentRecords.meta]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Cashier Payment Records" />
            <div className="space-y-6 p-4">
                <Heading
                    title="Payment records"
                    description="Review all member payments and confirm verified receipts when needed."
                />

                <PaymentRecordSummaryCards summary={summary} variant="cashier" />

                <Card>
                    <CardHeader className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <CardTitle>All transactions</CardTitle>
                            <CardDescription>Search by member name, matric number, payment reference, or receipt number.</CardDescription>
                        </div>
                        <div className="text-xs text-slate-500">Showing {currentRange}</div>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <form className="flex flex-col gap-3 sm:flex-row sm:items-end" onSubmit={handleSearch}>
                            <div className="grid gap-2">
                                <Label htmlFor="cashier-search">Search</Label>
                                <Input
                                    id="cashier-search"
                                    value={search}
                                    onChange={(event) => setSearch(event.target.value)}
                                    placeholder="Search member or receipt"
                                />
                            </div>
                            <Button type="submit" className="w-full sm:w-auto">
                                <Search />
                                Search
                            </Button>
                            {search && (
                                <Button
                                    type="button"
                                    variant="outline"
                                    className="w-full sm:w-auto"
                                    onClick={() => {
                                        setSearch('');
                                        router.get(route('cashier.payment-records.index'));
                                    }}
                                >
                                    Clear
                                </Button>
                            )}
                        </form>

                        {paymentRecords.data.length === 0 ? (
                            <div className="text-muted-foreground rounded-lg border border-dashed px-6 py-10 text-center text-sm">
                                No payment records matched this search.
                            </div>
                        ) : (
                            <div className="space-y-4">
                                <div className="space-y-4 md:hidden">
                                    {paymentRecords.data.map((record) => (
                                        <div key={record.public_reference} className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                            <div className="flex items-start justify-between gap-3">
                                                <div>
                                                    <p className="font-medium text-slate-900">{record.full_name}</p>
                                                    <p className="text-muted-foreground mt-1 text-xs">{record.matric_number}</p>
                                                </div>
                                                <PaymentStatusBadge status={record.payment_status} label={record.payment_status_label} />
                                            </div>

                                            <div className="mt-4 grid gap-2 text-sm text-slate-600">
                                                <p><span className="font-medium text-slate-800">Payment type:</span> {record.payment_type_name}</p>
                                                <p><span className="font-medium text-slate-800">Base amount:</span> {currencyFormatter.format(Number(record.base_amount))}</p>
                                                <p><span className="font-medium text-slate-800">Receipt:</span> {record.receipt_number ?? 'Not issued'}</p>
                                                <p><span className="font-medium text-slate-800">Date:</span> {record.recorded_at ? new Date(record.recorded_at).toLocaleString() : 'Not recorded'}</p>
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                <div className="hidden w-full overflow-x-auto md:block">
                                    <table className="w-full min-w-[760px] text-sm">
                                        <thead>
                                            <tr className="border-b text-left">
                                                <th className="px-3 py-3 font-medium">Member</th>
                                                <th className="px-3 py-3 font-medium">Payment type</th>
                                                <th className="px-3 py-3 font-medium">Base amount</th>
                                                <th className="px-3 py-3 font-medium">Status</th>
                                                <th className="px-3 py-3 font-medium">Receipt number</th>
                                                <th className="px-3 py-3 font-medium">Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {paymentRecords.data.map((record) => (
                                                <tr key={record.public_reference} className="border-b align-top last:border-b-0">
                                                    <td className="px-3 py-4">
                                                        <p className="font-medium text-slate-900">{record.full_name}</p>
                                                        <p className="text-muted-foreground mt-1 text-xs">{record.matric_number}</p>
                                                    </td>
                                                    <td className="px-3 py-4">{record.payment_type_name}</td>
                                                    <td className="px-3 py-4">{currencyFormatter.format(Number(record.base_amount))}</td>
                                                    <td className="px-3 py-4">
                                                        <PaymentStatusBadge status={record.payment_status} label={record.payment_status_label} />
                                                    </td>
                                                    <td className="px-3 py-4">{record.receipt_number ?? 'Not issued'}</td>
                                                    <td className="px-3 py-4 text-slate-600">
                                                        {record.recorded_at ? new Date(record.recorded_at).toLocaleString() : 'Not recorded'}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        )}

                        {paymentRecords.links.length > 0 && (
                            <div className="flex flex-wrap items-center justify-between gap-3">
                                <PaginationLinks links={paymentRecords.links} />
                                <Button variant="outline" asChild>
                                    <Link href={route('cashier.receipts.verify')}>
                                        Verify receipts
                                        <ArrowRight />
                                    </Link>
                                </Button>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
