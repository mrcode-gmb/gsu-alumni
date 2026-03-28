import Heading from '@/components/heading';
import { PaymentRecordSummaryCards } from '@/components/payment-records/payment-record-summary-cards';
import { PaymentStatusBadge } from '@/components/payment-records/payment-status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type AdminPaymentDashboardSummary, type AdminRecentPaymentRecord, type BreadcrumbItem, type SharedData } from '@/types';
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
    recentPaymentRecords: AdminRecentPaymentRecord[];
}

export default function Dashboard({ adminSummary, recentPaymentRecords }: DashboardProps) {
    const { auth } = usePage<SharedData>().props;
    const isAdmin = auth.user.role !== 'student';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <Heading
                    title={isAdmin ? 'Admin dashboard' : 'Student dashboard'}
                    description={
                        isAdmin
                            ? 'Monitor payment activity, inspect verified records, and manage the core alumni payment workflow from here.'
                            : 'Public payment and receipt flows are already live. This student dashboard area can grow later with personal history and account tools.'
                    }
                />

                {isAdmin ? (
                    <div className="space-y-6">
                        {adminSummary && <PaymentRecordSummaryCards summary={adminSummary} />}

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
                                    <div className="flex flex-wrap gap-3">
                                        <Button asChild>
                                            <Link href={route('admin.payment-records.index')}>
                                                View payment records
                                                <ArrowRight />
                                            </Link>
                                        </Button>
                                        <Button variant="outline" asChild>
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
                                <Button variant="outline" asChild>
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
                                        <table className="w-full min-w-[760px] text-sm">
                                            <thead>
                                                <tr className="border-b text-left">
                                                    <th className="px-3 py-3 font-medium">Student</th>
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
                ) : (
                    <Card>
                        <CardHeader>
                            <CardTitle>Student self-service tools will expand here</CardTitle>
                            <CardDescription>
                                Students can already create payment requests, pay through the public portal, and reopen receipts. This signed-in dashboard can later show personal history and account support tools.
                            </CardDescription>
                        </CardHeader>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
