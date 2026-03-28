import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { PaginationLinks } from '@/components/pagination-links';
import { PaymentRecordSummaryCards } from '@/components/payment-records/payment-record-summary-cards';
import { PaymentStatusBadge } from '@/components/payment-records/payment-status-badge';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import {
    type ActiveFilter,
    type AdminPaymentDashboardSummary,
    type AdminPaymentRecordFilterOptions,
    type AdminPaymentRecordFilters,
    type AdminPaymentRecordPagination,
    type BreadcrumbItem,
    type SharedData,
} from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { CalendarRange, Eye, FileSpreadsheet, Printer, ReceiptText, RotateCcw, Search } from 'lucide-react';
import { type FormEvent, useMemo, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Payment Records',
        href: '/admin/payment-records',
    },
];

const currencyFormatter = new Intl.NumberFormat('en-NG', {
    style: 'currency',
    currency: 'NGN',
    minimumFractionDigits: 2,
});

const defaultFilters: AdminPaymentRecordFilters = {
    search: '',
    payment_type_id: '',
    payment_status: '',
    department: '',
    faculty: '',
    graduation_session: '',
    date_from: '',
    date_to: '',
    sort: 'newest',
};

interface PaymentRecordIndexProps {
    summary: AdminPaymentDashboardSummary;
    paymentRecords: AdminPaymentRecordPagination;
    filters: AdminPaymentRecordFilters;
    filterOptions: AdminPaymentRecordFilterOptions;
    activeFilters: ActiveFilter[];
}

function cleanFilters(filters: AdminPaymentRecordFilters) {
    return Object.fromEntries(
        Object.entries(filters).filter(([key, value]) => value !== '' && !(key === 'sort' && value === 'newest')),
    );
}

export default function PaymentRecordIndex({
    summary,
    paymentRecords,
    filters,
    filterOptions,
    activeFilters,
}: PaymentRecordIndexProps) {
    const { flash, errors } = usePage<SharedData>().props;
    const [form, setForm] = useState<AdminPaymentRecordFilters>(filters);

    const submitFilters = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        router.get(route('admin.payment-records.index'), cleanFilters(form), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const clearFilters = () => {
        setForm(defaultFilters);

        router.get(route('admin.payment-records.index'), {}, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const printUrl = useMemo(
        () => route('admin.payment-records.print', cleanFilters(form)),
        [form],
    );

    const noResults = paymentRecords.data.length === 0;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Payment Records" />

            <div className="space-y-6 p-4">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <Heading
                        title="Admin payment records"
                        description="Search, filter, inspect, print, and monitor the verified student payment records stored in the portal."
                    />

                    <div className="flex flex-wrap gap-3">
                        <Button variant="outline" asChild>
                            <a href={printUrl} target="_blank" rel="noreferrer">
                                <Printer />
                                Print filtered view
                            </a>
                        </Button>

                        <Button asChild>
                            <Link href={route('admin.payment-types.index')}>
                                <ReceiptText />
                                Payment types
                            </Link>
                        </Button>
                    </div>
                </div>

                {flash.success && (
                    <Alert>
                        <AlertTitle>Success</AlertTitle>
                        <AlertDescription>{flash.success}</AlertDescription>
                    </Alert>
                )}

                {flash.error && (
                    <Alert variant="destructive">
                        <AlertTitle>Action blocked</AlertTitle>
                        <AlertDescription>{flash.error}</AlertDescription>
                    </Alert>
                )}

                <PaymentRecordSummaryCards summary={summary} />

                <Card>
                    <CardHeader>
                        <CardTitle>Search and filters</CardTitle>
                        <CardDescription>
                            Search by student or payment reference, then narrow the records with payment and academic filters.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-5">
                        <form className="space-y-5" onSubmit={submitFilters}>
                            <div className="grid gap-5 lg:grid-cols-2">
                                <div className="grid gap-2 lg:col-span-2">
                                    <Label htmlFor="search">Search</Label>
                                    <div className="relative">
                                        <Search className="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                                        <Input
                                            id="search"
                                            value={form.search}
                                            onChange={(event) => setForm((current) => ({ ...current, search: event.target.value }))}
                                            placeholder="Name, matric number, email, payment reference, or receipt number"
                                            className="pl-9"
                                        />
                                    </div>
                                    <InputError message={errors?.search} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="payment_type_id">Payment type</Label>
                                    <Select value={form.payment_type_id || undefined} onValueChange={(value) => setForm((current) => ({ ...current, payment_type_id: value }))}>
                                        <SelectTrigger id="payment_type_id">
                                            <SelectValue placeholder="All payment types" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {filterOptions.paymentTypes.map((option) => (
                                                <SelectItem key={option.value} value={option.value}>
                                                    {option.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors?.payment_type_id} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="payment_status">Payment status</Label>
                                    <Select value={form.payment_status || undefined} onValueChange={(value) => setForm((current) => ({ ...current, payment_status: value }))}>
                                        <SelectTrigger id="payment_status">
                                            <SelectValue placeholder="All statuses" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {filterOptions.paymentStatuses.map((option) => (
                                                <SelectItem key={option.value} value={option.value}>
                                                    {option.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors?.payment_status} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="department">Department</Label>
                                    <Select value={form.department || undefined} onValueChange={(value) => setForm((current) => ({ ...current, department: value }))}>
                                        <SelectTrigger id="department">
                                            <SelectValue placeholder="All departments" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {filterOptions.departments.map((option) => (
                                                <SelectItem key={option.value} value={option.value}>
                                                    {option.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors?.department} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="faculty">Faculty</Label>
                                    <Select value={form.faculty || undefined} onValueChange={(value) => setForm((current) => ({ ...current, faculty: value }))}>
                                        <SelectTrigger id="faculty">
                                            <SelectValue placeholder="All faculties" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {filterOptions.faculties.map((option) => (
                                                <SelectItem key={option.value} value={option.value}>
                                                    {option.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors?.faculty} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="graduation_session">Graduation session</Label>
                                    <Select value={form.graduation_session || undefined} onValueChange={(value) => setForm((current) => ({ ...current, graduation_session: value }))}>
                                        <SelectTrigger id="graduation_session">
                                            <SelectValue placeholder="All sessions" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {filterOptions.graduationSessions.map((option) => (
                                                <SelectItem key={option.value} value={option.value}>
                                                    {option.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors?.graduation_session} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="sort">Sort by</Label>
                                    <Select value={form.sort || 'newest'} onValueChange={(value) => setForm((current) => ({ ...current, sort: value }))}>
                                        <SelectTrigger id="sort">
                                            <SelectValue placeholder="Newest first" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {filterOptions.sorts.map((option) => (
                                                <SelectItem key={option.value} value={option.value}>
                                                    {option.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors?.sort} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="date_from">Date from</Label>
                                    <Input
                                        id="date_from"
                                        type="date"
                                        value={form.date_from}
                                        onChange={(event) => setForm((current) => ({ ...current, date_from: event.target.value }))}
                                    />
                                    <InputError message={errors?.date_from} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="date_to">Date to</Label>
                                    <Input
                                        id="date_to"
                                        type="date"
                                        value={form.date_to}
                                        onChange={(event) => setForm((current) => ({ ...current, date_to: event.target.value }))}
                                    />
                                    <InputError message={errors?.date_to} />
                                </div>
                            </div>

                            <div className="flex flex-wrap gap-3">
                                <Button type="submit">
                                    <Search />
                                    Apply filters
                                </Button>
                                <Button type="button" variant="outline" onClick={clearFilters}>
                                    <RotateCcw />
                                    Clear filters
                                </Button>
                            </div>
                        </form>

                        {activeFilters.length > 0 && (
                            <div className="flex flex-wrap gap-2">
                                {activeFilters.map((filter) => (
                                    <Badge key={`${filter.label}-${filter.value}`} variant="secondary">
                                        {filter.label}: {filter.value}
                                    </Badge>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <CardTitle>Payment records</CardTitle>
                            <CardDescription>
                                {paymentRecords.meta.total === 1
                                    ? '1 record matches the current view.'
                                    : `${paymentRecords.meta.total} records match the current view.`}
                            </CardDescription>
                        </div>

                        <div className="flex items-center gap-2 text-sm text-slate-500">
                            <CalendarRange className="size-4" />
                            <span>
                                Showing {paymentRecords.meta.from ?? 0} - {paymentRecords.meta.to ?? 0}
                            </span>
                        </div>
                    </CardHeader>
                    <CardContent className="space-y-5">
                        {noResults ? (
                            <div className="text-muted-foreground rounded-lg border border-dashed px-6 py-10 text-center text-sm">
                                {activeFilters.length > 0
                                    ? 'No payment records matched the filters you applied. Adjust the search or clear the filters and try again.'
                                    : 'No payment records have been created yet.'}
                            </div>
                        ) : (
                            <>
                                <div className="overflow-x-auto">
                                    <table className="w-full min-w-[1240px] text-sm">
                                        <thead>
                                            <tr className="border-b text-left">
                                                <th className="px-3 py-3 font-medium">Student</th>
                                                <th className="px-3 py-3 font-medium">Department</th>
                                                <th className="px-3 py-3 font-medium">Faculty</th>
                                                <th className="px-3 py-3 font-medium">Payment type</th>
                                                <th className="px-3 py-3 font-medium">Amount</th>
                                                <th className="px-3 py-3 font-medium">Status</th>
                                                <th className="px-3 py-3 font-medium">Payment date</th>
                                                <th className="px-3 py-3 font-medium">Payment reference</th>
                                                <th className="px-3 py-3 font-medium">Receipt number</th>
                                                <th className="px-3 py-3 font-medium">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {paymentRecords.data.map((record) => (
                                                <tr key={record.public_reference} className="border-b align-top last:border-b-0">
                                                    <td className="px-3 py-4">
                                                        <p className="font-medium text-slate-900">{record.full_name}</p>
                                                        <p className="text-muted-foreground mt-1 text-xs">{record.matric_number}</p>
                                                        <p className="text-muted-foreground mt-1 text-xs">{record.email}</p>
                                                    </td>
                                                    <td className="px-3 py-4">{record.department}</td>
                                                    <td className="px-3 py-4">{record.faculty}</td>
                                                    <td className="px-3 py-4">{record.payment_type_name}</td>
                                                    <td className="px-3 py-4">{currencyFormatter.format(Number(record.amount))}</td>
                                                    <td className="px-3 py-4">
                                                        <PaymentStatusBadge status={record.payment_status} label={record.payment_status_label} />
                                                    </td>
                                                    <td className="px-3 py-4 text-slate-600">
                                                        {record.recorded_at ? new Date(record.recorded_at).toLocaleString() : 'Not recorded'}
                                                    </td>
                                                    <td className="px-3 py-4 font-mono text-xs">
                                                        {record.payment_reference ?? 'Not generated'}
                                                    </td>
                                                    <td className="px-3 py-4 font-mono text-xs">
                                                        {record.receipt_number ?? 'No receipt'}
                                                    </td>
                                                    <td className="px-3 py-4">
                                                        <div className="flex flex-wrap gap-2">
                                                            <Button size="sm" variant="outline" asChild>
                                                                <Link href={route('admin.payment-records.show', record.public_reference)}>
                                                                    <Eye />
                                                                    View
                                                                </Link>
                                                            </Button>

                                                            {record.can_open_receipt && (
                                                                <Button size="sm" variant="outline" asChild>
                                                                    <Link href={route('admin.payment-records.receipt', record.public_reference)} method="post" as="button">
                                                                        <ReceiptText />
                                                                        {record.has_receipt ? 'Receipt' : 'Issue receipt'}
                                                                    </Link>
                                                                </Button>
                                                            )}

                                                            <Button size="sm" variant="outline" asChild>
                                                                <a
                                                                    href={route('admin.payment-records.print-single', record.public_reference)}
                                                                    target="_blank"
                                                                    rel="noreferrer"
                                                                >
                                                                    <FileSpreadsheet />
                                                                    Print
                                                                </a>
                                                            </Button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>

                                <PaginationLinks links={paymentRecords.links} />
                            </>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
