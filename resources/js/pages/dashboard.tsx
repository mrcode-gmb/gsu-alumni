import Heading from '@/components/heading';
import { PaymentRecordSummaryCards } from '@/components/payment-records/payment-record-summary-cards';
import { PaymentStatusBadge } from '@/components/payment-records/payment-status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import {
    type AdminPaymentDashboardSummary,
    type AdminProgramTypeSuccessfulTransaction,
    type AdminRecentPaymentRecord,
    type BreadcrumbItem,
    type SharedData,
} from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight, CreditCard, FileText, Printer, ShieldCheck } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const currencyFormatter = new Intl.NumberFormat('en-NG', {
    style: 'currency',
    currency: 'NGN',
    minimumFractionDigits: 2,
});

interface DashboardProps {
    adminSummary: AdminPaymentDashboardSummary | null;
    cashierSummary: AdminPaymentDashboardSummary | null;
    successfulTransactionsByProgramType: AdminProgramTypeSuccessfulTransaction[];
    recentPaymentRecords: AdminRecentPaymentRecord[];
}

export default function Dashboard({ adminSummary, cashierSummary, successfulTransactionsByProgramType, recentPaymentRecords }: DashboardProps) {
    const { auth } = usePage<SharedData>().props;
    const isAdmin = auth.user.role === 'alumni_admin' || auth.user.role === 'super_admin';
    const isCashier = auth.user.role === 'cashier';
    const maxSuccessfulTransactions = successfulTransactionsByProgramType.reduce(
        (max, item) => Math.max(max, item.successful_transactions),
        0,
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <Heading
                    title={isAdmin ? 'Admin dashboard' : isCashier ? 'Cashier dashboard' : 'Member dashboard'}
                    description={
                        isAdmin
                            ? 'Monitor payment activity, inspect verified records, and manage the core alumni payment workflow from here.'
                            : isCashier
                              ? 'Verify member receipts and confirm successful payments before certificates are issued.'
                              : 'Public payment and receipt flows are already live. This member dashboard area can grow later with personal history and account tools.'
                    }
                />

                {isAdmin ? (
                    <div className="space-y-6">
                        {adminSummary && <PaymentRecordSummaryCards summary={adminSummary} />}

                        <Card>
                            <CardHeader>
                                <CardTitle>Successful transactions by programme</CardTitle>
                                <CardDescription>
                                    A full count of verified successful transactions across all programme types, without using payment amounts.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                {successfulTransactionsByProgramType.length === 0 ? (
                                    <div className="text-muted-foreground rounded-lg border border-dashed px-6 py-10 text-center text-sm">
                                        No successful programme transactions have been recorded yet.
                                    </div>
                                ) : (
                                    <>
                                        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                                            {successfulTransactionsByProgramType.map((programme) => (
                                                <Card key={programme.name} className="border-slate-200 bg-slate-50/60">
                                                    <CardHeader className="gap-1">
                                                        <CardDescription className="text-xs tracking-[0.18em] uppercase">
                                                            Programme
                                                        </CardDescription>
                                                        <CardTitle className="text-lg leading-tight">
                                                            {programme.name}
                                                        </CardTitle>
                                                        <p className="text-2xl font-semibold text-slate-900">
                                                            {programme.successful_transactions}
                                                        </p>
                                                        <p className="text-xs text-slate-500">
                                                            successful transaction{programme.successful_transactions === 1 ? '' : 's'}
                                                        </p>
                                                    </CardHeader>
                                                </Card>
                                            ))}
                                        </div>

                                        <Card className="border-slate-200 bg-white">
                                            <CardHeader>
                                                <CardTitle>Programme performance chart</CardTitle>
                                                <CardDescription>
                                                    Quick visual comparison of successful transactions across all programme types.
                                                </CardDescription>
                                            </CardHeader>
                                            <CardContent>
                                                <div className="space-y-4">
                                                    {successfulTransactionsByProgramType.map((programme) => {
                                                        const width = maxSuccessfulTransactions > 0
                                                            ? Math.max((programme.successful_transactions / maxSuccessfulTransactions) * 100, 8)
                                                            : 0;

                                                        return (
                                                            <div key={programme.name} className="space-y-2">
                                                                <div className="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                                                    <p className="text-sm font-medium text-slate-800">{programme.name}</p>
                                                                    <p className="text-xs font-semibold tracking-[0.14em] text-slate-500 uppercase">
                                                                        {programme.successful_transactions} success{programme.successful_transactions === 1 ? '' : 'es'}
                                                                    </p>
                                                                </div>
                                                                <div className="h-3 overflow-hidden rounded-full bg-slate-100">
                                                                    <div
                                                                        className="h-full rounded-full bg-emerald-600 transition-all"
                                                                        style={{ width: `${width}%` }}
                                                                    />
                                                                </div>
                                                            </div>
                                                        );
                                                    })}
                                                </div>
                                            </CardContent>
                                        </Card>
                                    </>
                                )}
                            </CardContent>
                        </Card>

                        <div className="grid gap-4 lg:grid-cols-[1.35fr,1fr]">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <FileText className="size-5" />
                                        Payment records oversight
                                    </CardTitle>
                                    <CardDescription>
                                        Search every student payment request, inspect full record details, and reopen receipts from one admin workspace.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                    <p className="text-muted-foreground text-sm">
                                        Total collected so far: {adminSummary ? currencyFormatter.format(Number(adminSummary.total_amount_collected)) : currencyFormatter.format(0)}
                                    </p>
                                    <div className="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                                        <Button className="w-full sm:w-auto" asChild>
                                            <Link href={route('admin.payment-records.index')}>
                                                View payment records
                                                <ArrowRight />
                                            </Link>
                                        </Button>
                                        <Button className="w-full sm:w-auto" variant="outline" asChild>
                                            <Link href={route('admin.payment-types.index')}>
                                                Manage payment types
                                                <CreditCard />
                                            </Link>
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <ShieldCheck className="size-5" />
                                        Admin guide
                                    </CardTitle>
                                    <CardDescription>Focused on secure monitoring only.</CardDescription>
                                </CardHeader>
                                <CardContent className="text-muted-foreground space-y-3 text-sm leading-6">
                                    <p>Statuses come from stored backend verification results only.</p>
                                    <p>Receipts can be reopened from successful records without creating duplicates.</p>
                                    <p>Print views are available for single records and filtered record lists.</p>
                                </CardContent>
                            </Card>
                        </div>

                        <Card>
                            <CardHeader className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <CardTitle>Recent payment activity</CardTitle>
                                    <CardDescription>Latest payment requests and verification outcomes across the portal.</CardDescription>
                                </div>
                                <Button className="w-full sm:w-auto" variant="outline" asChild>
                                    <Link href={route('admin.payment-records.print')} target="_blank">
                                        <Printer />
                                        Print records
                                    </Link>
                                </Button>
                            </CardHeader>
                            <CardContent>
                                {recentPaymentRecords.length === 0 ? (
                                    <div className="text-muted-foreground rounded-lg border border-dashed px-6 py-10 text-center text-sm">
                                        No payment records have been created yet.
                                    </div>
                                ) : (
                                    <div className="overflow-x-auto">
                                        <div className="space-y-4 md:hidden">
                                            {recentPaymentRecords.map((record) => (
                                                <div key={record.public_reference} className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                                    <div className="flex flex-col gap-3">
                                                        <div className="flex items-start justify-between gap-3">
                                                            <div>
                                                                <p className="font-medium text-slate-900">{record.full_name}</p>
                                                                <p className="text-muted-foreground mt-1 text-xs">{record.matric_number}</p>
                                                            </div>
                                                            <PaymentStatusBadge status={record.payment_status} label={record.payment_status_label} />
                                                        </div>

                                                        <div className="grid gap-2 text-sm text-slate-600">
                                                            <p><span className="font-medium text-slate-800">Payment type:</span> {record.payment_type_name}</p>
                                                            <p><span className="font-medium text-slate-800">Amount:</span> {currencyFormatter.format(Number(record.amount))}</p>
                                                            <p><span className="font-medium text-slate-800">Date:</span> {record.recorded_at ? new Date(record.recorded_at).toLocaleString() : 'Not recorded'}</p>
                                                        </div>

                                                        <div className="grid gap-2 sm:grid-cols-2">
                                                            <Button size="sm" variant="outline" asChild>
                                                                <Link href={route('admin.payment-records.show', record.public_reference)}>
                                                                    View
                                                                </Link>
                                                            </Button>
                                                            {record.receipt_action_available && (
                                                                <Button size="sm" variant="outline" asChild>
                                                                    <Link href={route('admin.payment-records.receipt', record.public_reference)} method="post" as="button">
                                                                        Receipt
                                                                    </Link>
                                                                </Button>
                                                            )}
                                                        </div>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>

                                        <table className="hidden w-full min-w-[760px] text-sm md:table">
                                            <thead>
                                                <tr className="border-b text-left">
                                                    <th className="px-3 py-3 font-medium">Member</th>
                                                    <th className="px-3 py-3 font-medium">Payment type</th>
                                                    <th className="px-3 py-3 font-medium">Amount</th>
                                                    <th className="px-3 py-3 font-medium">Status</th>
                                                    <th className="px-3 py-3 font-medium">Date</th>
                                                    <th className="px-3 py-3 font-medium">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {recentPaymentRecords.map((record) => (
                                                    <tr key={record.public_reference} className="border-b align-top last:border-b-0">
                                                        <td className="px-3 py-4">
                                                            <p className="font-medium text-slate-900">{record.full_name}</p>
                                                            <p className="text-muted-foreground mt-1 text-xs">{record.matric_number}</p>
                                                        </td>
                                                        <td className="px-3 py-4">{record.payment_type_name}</td>
                                                        <td className="px-3 py-4">{currencyFormatter.format(Number(record.amount))}</td>
                                                        <td className="px-3 py-4">
                                                            <PaymentStatusBadge status={record.payment_status} label={record.payment_status_label} />
                                                        </td>
                                                        <td className="px-3 py-4 text-slate-600">
                                                            {record.recorded_at ? new Date(record.recorded_at).toLocaleString() : 'Not recorded'}
                                                        </td>
                                                        <td className="px-3 py-4">
                                                            <div className="flex flex-wrap gap-2">
                                                                <Button size="sm" variant="outline" asChild>
                                                                    <Link href={route('admin.payment-records.show', record.public_reference)}>
                                                                        View
                                                                    </Link>
                                                                </Button>
                                                                {record.receipt_action_available && (
                                                                    <Button size="sm" variant="outline" asChild>
                                                                        <Link href={route('admin.payment-records.receipt', record.public_reference)} method="post" as="button">
                                                                            Receipt
                                                                        </Link>
                                                                    </Button>
                                                                )}
                                                            </div>
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                ) : isCashier ? (
                    <div className="space-y-6">
                        <div className="grid gap-4 lg:grid-cols-[320px,1fr]">
                            <Card className="border-emerald-200 bg-emerald-50/70">
                                <CardHeader>
                                    <CardDescription className="text-xs tracking-[0.18em] uppercase">Total successful transactions</CardDescription>
                                    <CardTitle className="text-4xl leading-none text-emerald-900">
                                        {cashierSummary?.total_successful_payments ?? 0}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <p className="text-sm leading-6 text-emerald-900/80">
                                        This shows every successful verified transaction across all programme types, without using payment amounts.
                                    </p>
                                    <Button className="w-full sm:w-auto" asChild>
                                        <Link href={route('cashier.receipts.verify')}>
                                            Verify payment
                                            <ArrowRight />
                                        </Link>
                                    </Button>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle>Successful transactions by programme</CardTitle>
                                    <CardDescription>
                                        Count of successful transactions for Undergraduate, Diploma, and all other available programme types.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    {successfulTransactionsByProgramType.length === 0 ? (
                                        <div className="text-muted-foreground rounded-lg border border-dashed px-6 py-10 text-center text-sm">
                                            No successful programme transactions have been recorded yet.
                                        </div>
                                    ) : (
                                        <div className="space-y-5">
                                            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                                                {successfulTransactionsByProgramType.map((programme) => (
                                                    <Card key={programme.name} className="border-slate-200 bg-slate-50/60">
                                                        <CardHeader className="gap-1">
                                                            <CardDescription className="text-xs tracking-[0.18em] uppercase">
                                                                Programme
                                                            </CardDescription>
                                                            <CardTitle className="text-lg leading-tight">{programme.name}</CardTitle>
                                                            <p className="text-2xl font-semibold text-slate-900">
                                                                {programme.successful_transactions}
                                                            </p>
                                                        </CardHeader>
                                                    </Card>
                                                ))}
                                            </div>

                                            <Card className="border-slate-200 bg-white">
                                                <CardHeader>
                                                    <CardTitle>Programme bar chart</CardTitle>
                                                    <CardDescription>
                                                        A visual breakdown of successful transaction counts across all programme types.
                                                    </CardDescription>
                                                </CardHeader>
                                                <CardContent>
                                                    <div className="space-y-4">
                                                        {successfulTransactionsByProgramType.map((programme) => {
                                                            const width = maxSuccessfulTransactions > 0
                                                                ? Math.max((programme.successful_transactions / maxSuccessfulTransactions) * 100, 8)
                                                                : 0;

                                                            return (
                                                                <div key={programme.name} className="space-y-2">
                                                                    <div className="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                                                        <p className="text-sm font-medium text-slate-800">{programme.name}</p>
                                                                        <p className="text-xs font-semibold tracking-[0.14em] text-slate-500 uppercase">
                                                                            {programme.successful_transactions} success{programme.successful_transactions === 1 ? '' : 'es'}
                                                                        </p>
                                                                    </div>
                                                                    <div className="h-3 overflow-hidden rounded-full bg-slate-100">
                                                                        <div
                                                                            className="h-full rounded-full bg-emerald-600 transition-all"
                                                                            style={{ width: `${width}%` }}
                                                                        />
                                                                    </div>
                                                                </div>
                                                            );
                                                        })}
                                                    </div>
                                                </CardContent>
                                            </Card>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                ) : (
                    <Card>
                        <CardHeader>
                            <CardTitle>Member self-service tools will expand here</CardTitle>
                            <CardDescription>
                                Members can already create payment requests, pay through the public portal, and reopen receipts. This signed-in dashboard can later show personal history and account support tools.
                            </CardDescription>
                        </CardHeader>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
