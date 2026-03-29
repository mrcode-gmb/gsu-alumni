import AppLogoIcon from '@/components/app-logo-icon';
import { PaymentRecordSummaryCards } from '@/components/payment-records/payment-record-summary-cards';
import { PaymentStatusBadge } from '@/components/payment-records/payment-status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { type ActiveFilter, type AdminPaymentDashboardSummary, type AdminPaymentRecordFilters, type AdminPaymentRecordListItem } from '@/types';
import { Head } from '@inertiajs/react';
import { ArrowLeft, Printer } from 'lucide-react';

const currencyFormatter = new Intl.NumberFormat('en-NG', {
    style: 'currency',
    currency: 'NGN',
    minimumFractionDigits: 2,
});

interface PaymentRecordPrintIndexProps {
    summary: AdminPaymentDashboardSummary;
    paymentRecords: AdminPaymentRecordListItem[];
    filters: AdminPaymentRecordFilters;
    activeFilters: ActiveFilter[];
    printMeta: {
        total: number;
        truncated: boolean;
        limit: number;
    };
}

export default function PaymentRecordPrintIndex({
    summary,
    paymentRecords,
    activeFilters,
    printMeta,
}: PaymentRecordPrintIndexProps) {
    const printRecords = () => {
        window.print();
    };

    return (
        <>
            <Head title="Print Payment Records" />

            <div className="admin-print-shell min-h-screen bg-[linear-gradient(180deg,_#f8fafc_0%,_#eef2f7_100%)] py-6 sm:py-8">
                <div className="admin-print-screen-only mx-auto flex max-w-7xl flex-col gap-4 px-4 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
                    <div className="flex items-start gap-4">
                        <div className="flex size-14 shrink-0 items-center justify-center rounded-2xl border border-emerald-100 bg-white/90 p-2 shadow-sm">
                            <AppLogoIcon className="size-full" alt="GSU Alumni Association logo" />
                        </div>
                        <div>
                            <p className="text-sm font-semibold tracking-[0.22em] text-emerald-700 uppercase">GSU Alumni Payment Portal</p>
                            <h1 className="mt-2 text-2xl font-semibold tracking-tight text-slate-950">Print filtered payment records</h1>
                            <p className="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                                This view is optimized for printing the current filtered payment record list.
                            </p>
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-3">
                        <Button variant="outline" asChild>
                            <a href={route('admin.payment-records.index')}>
                                <ArrowLeft />
                                Back to records
                            </a>
                        </Button>

                        <Button onClick={printRecords}>
                            <Printer />
                            Print / Download PDF
                        </Button>
                    </div>
                </div>

                <div className="mx-auto mt-6 max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <section className="admin-print-document rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-[0_20px_70px_rgba(15,23,42,0.08)]">
                        <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <p className="text-sm font-semibold tracking-[0.22em] text-emerald-700 uppercase">Payment record print view</p>
                                <h2 className="mt-2 text-2xl font-semibold tracking-tight text-slate-950">Filtered admin payment records</h2>
                                <p className="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                                    Total matching records: {printMeta.total}. {printMeta.truncated ? `This print view is limited to the first ${printMeta.limit} records for readability.` : 'All matching records are included below.'}
                                </p>
                            </div>

                            {activeFilters.length > 0 && (
                                <div className="flex flex-wrap gap-2">
                                    {activeFilters.map((filter) => (
                                        <Badge key={`${filter.label}-${filter.value}`} variant="secondary">
                                            {filter.label}: {filter.value}
                                        </Badge>
                                    ))}
                                </div>
                            )}
                        </div>
                    </section>

                    <PaymentRecordSummaryCards summary={summary} />

                    <section className="admin-print-document overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-[0_20px_70px_rgba(15,23,42,0.08)]">
                        <div className="overflow-x-auto">
                            <table className="w-full min-w-[1180px] text-sm">
                                <thead>
                                    <tr className="border-b bg-slate-50 text-left">
                                        <th className="px-4 py-3 font-medium">Student</th>
                                        <th className="px-4 py-3 font-medium">Department</th>
                                        <th className="px-4 py-3 font-medium">Faculty</th>
                                        <th className="px-4 py-3 font-medium">Payment type</th>
                                        <th className="px-4 py-3 font-medium">Amount</th>
                                        <th className="px-4 py-3 font-medium">Status</th>
                                        <th className="px-4 py-3 font-medium">Payment date</th>
                                        <th className="px-4 py-3 font-medium">Payment reference</th>
                                        <th className="px-4 py-3 font-medium">Receipt number</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {paymentRecords.map((record) => (
                                        <tr key={record.public_reference} className="border-b align-top last:border-b-0">
                                            <td className="px-4 py-4">
                                                <p className="font-medium text-slate-900">{record.full_name}</p>
                                                <p className="text-muted-foreground mt-1 text-xs">{record.matric_number}</p>
                                                <p className="text-muted-foreground mt-1 text-xs">{record.email}</p>
                                            </td>
                                            <td className="px-4 py-4">{record.department}</td>
                                            <td className="px-4 py-4">{record.faculty}</td>
                                            <td className="px-4 py-4">{record.payment_type_name}</td>
                                            <td className="px-4 py-4">{currencyFormatter.format(Number(record.amount))}</td>
                                            <td className="px-4 py-4">
                                                <PaymentStatusBadge status={record.payment_status} label={record.payment_status_label} />
                                            </td>
                                            <td className="px-4 py-4 text-slate-600">
                                                {record.recorded_at ? new Date(record.recorded_at).toLocaleString() : 'Not recorded'}
                                            </td>
                                            <td className="px-4 py-4 font-mono text-xs">
                                                {record.payment_reference ?? 'Not generated'}
                                            </td>
                                            <td className="px-4 py-4 font-mono text-xs">
                                                {record.receipt_number ?? 'No receipt'}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </div>
        </>
    );
}
