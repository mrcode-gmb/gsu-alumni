import Heading from '@/components/heading';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type PaymentType, type PaymentTypeFilters, type PaymentTypeSummary, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { CirclePlus, PencilLine, Power, PowerOff, Search, Trash2 } from 'lucide-react';
import { type FormEvent, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Payment Types',
        href: '/admin/payment-types',
    },
];

const currencyFormatter = new Intl.NumberFormat('en-NG', {
    style: 'currency',
    currency: 'NGN',
    minimumFractionDigits: 2,
});

interface PaymentTypeIndexProps {
    paymentTypes: PaymentType[];
    filters: PaymentTypeFilters;
    summary: PaymentTypeSummary;
}

export default function PaymentTypeIndex({ paymentTypes, filters, summary }: PaymentTypeIndexProps) {
    const { flash } = usePage<SharedData>().props;
    const [search, setSearch] = useState(filters.search);
    const [paymentTypeToDelete, setPaymentTypeToDelete] = useState<PaymentType | null>(null);

    const submitSearch = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        const trimmedSearch = search.trim();

        router.get(
            route('admin.payment-types.index'),
            trimmedSearch ? { search: trimmedSearch } : {},
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    const clearSearch = () => {
        setSearch('');

        router.get(route('admin.payment-types.index'), {}, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const toggleStatus = (paymentType: PaymentType) => {
        router.patch(
            route('admin.payment-types.status.update', paymentType.id),
            {
                is_active: !paymentType.is_active,
            },
            {
                preserveScroll: true,
            },
        );
    };

    const deletePaymentType = () => {
        if (!paymentTypeToDelete) {
            return;
        }

        router.delete(route('admin.payment-types.destroy', paymentTypeToDelete.id), {
            preserveScroll: true,
            onFinish: () => setPaymentTypeToDelete(null),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Payment Types" />

            <div className="space-y-6 p-4">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <Heading
                        title="Payment type management"
                        description="Create and manage the official payment categories students will pay against later."
                    />
                    <Button asChild>
                        <Link href={route('admin.payment-types.create')}>
                            <CirclePlus />
                            Add payment type
                        </Link>
                    </Button>
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

                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader className="gap-0.5">
                            <CardDescription>Total payment types</CardDescription>
                            <CardTitle className="text-3xl">{summary.total}</CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="gap-0.5">
                            <CardDescription>Active</CardDescription>
                            <CardTitle className="text-3xl">{summary.active}</CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="gap-0.5">
                            <CardDescription>Inactive</CardDescription>
                            <CardTitle className="text-3xl">{summary.inactive}</CardTitle>
                        </CardHeader>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Search payment types</CardTitle>
                        <CardDescription>Find payment types quickly by name.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form className="flex flex-col gap-3 sm:flex-row" onSubmit={submitSearch}>
                            <div className="relative flex-1">
                                <Search className="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                                <Input
                                    value={search}
                                    onChange={(event) => setSearch(event.target.value)}
                                    placeholder="Search by payment name"
                                    className="pl-9"
                                />
                            </div>
                            <Button type="submit">Search</Button>
                            <Button type="button" variant="outline" onClick={clearSearch}>
                                Clear
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Payment types</CardTitle>
                        <CardDescription>
                            {paymentTypes.length === 1 ? '1 result found.' : `${paymentTypes.length} results found.`}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {paymentTypes.length === 0 ? (
                            <div className="text-muted-foreground rounded-lg border border-dashed px-6 py-10 text-center text-sm">
                                No payment types matched your search. Create one or adjust the search term.
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full min-w-[900px] text-sm">
                                    <thead>
                                        <tr className="border-b text-left">
                                            <th className="px-3 py-3 font-medium">Payment name</th>
                                            <th className="px-3 py-3 font-medium">Program types</th>
                                            <th className="px-3 py-3 font-medium">Amount</th>
                                            <th className="px-3 py-3 font-medium">Status</th>
                                            <th className="px-3 py-3 font-medium">Order</th>
                                            <th className="px-3 py-3 font-medium">Description</th>
                                            <th className="px-3 py-3 font-medium">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {paymentTypes.map((paymentType) => (
                                            <tr key={paymentType.id} className="border-b align-top last:border-b-0">
                                                <td className="px-3 py-4 font-medium">{paymentType.name}</td>
                                                <td className="px-3 py-4 text-slate-600">
                                                    {paymentType.program_types.length > 0
                                                        ? paymentType.program_types.join(', ')
                                                        : 'Not assigned'}
                                                </td>
                                                <td className="px-3 py-4">
                                                    {currencyFormatter.format(Number(paymentType.amount))}
                                                </td>
                                                <td className="px-3 py-4">
                                                    <Badge variant={paymentType.is_active ? 'default' : 'secondary'}>
                                                        {paymentType.is_active ? 'Active' : 'Inactive'}
                                                    </Badge>
                                                </td>
                                                <td className="px-3 py-4">
                                                    {paymentType.display_order ?? <span className="text-muted-foreground">Auto</span>}
                                                </td>
                                                <td className="text-muted-foreground max-w-sm px-3 py-4">
                                                    {paymentType.description || 'No description provided.'}
                                                </td>
                                                <td className="px-3 py-4">
                                                    <div className="flex flex-wrap gap-2">
                                                        <Button size="sm" variant="outline" asChild>
                                                            <Link href={route('admin.payment-types.edit', paymentType.id)}>
                                                                <PencilLine />
                                                                Edit
                                                            </Link>
                                                        </Button>
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            onClick={() => toggleStatus(paymentType)}
                                                        >
                                                            {paymentType.is_active ? <PowerOff /> : <Power />}
                                                            {paymentType.is_active ? 'Deactivate' : 'Activate'}
                                                        </Button>
                                                        <Button
                                                            size="sm"
                                                            variant="destructive"
                                                            disabled={!paymentType.can_delete}
                                                            onClick={() => setPaymentTypeToDelete(paymentType)}
                                                        >
                                                            <Trash2 />
                                                            Delete
                                                        </Button>
                                                    </div>
                                                    {!paymentType.can_delete && (
                                                        <p className="text-muted-foreground mt-2 text-xs">
                                                            Used payment types cannot be deleted.
                                                        </p>
                                                    )}
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

            <Dialog open={paymentTypeToDelete !== null} onOpenChange={(open) => !open && setPaymentTypeToDelete(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete payment type</DialogTitle>
                        <DialogDescription>
                            {paymentTypeToDelete
                                ? `Delete "${paymentTypeToDelete.name}" permanently? This should only be done for unused payment types.`
                                : 'Delete this payment type permanently.'}
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setPaymentTypeToDelete(null)}>
                            Cancel
                        </Button>
                        <Button variant="destructive" onClick={deletePaymentType}>
                            Delete payment type
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
