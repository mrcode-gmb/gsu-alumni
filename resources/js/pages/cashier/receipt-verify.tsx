import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { PaymentStatusBadge } from '@/components/payment-records/payment-status-badge';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type PaymentRequestStatus, type SharedData } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { CheckCircle2, Loader2, Search } from 'lucide-react';
import { useState } from 'react';

type ReceiptVerification = {
    public_reference: string;
    receipt_number: string | null;
    member_name: string;
    matric_number: string;
    payment_type: string;
    payment_amount: string;
    paid_at: string | null;
    payment_reference: string | null;
    status: string;
    payment_status: PaymentRequestStatus;
    can_recheck: boolean;
};

interface ReceiptVerifyProps {
    verification: ReceiptVerification[] | null;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Verify Payment', href: '/cashier/receipts/verify' },
];

const currencyFormatter = new Intl.NumberFormat('en-NG', {
    style: 'currency',
    currency: 'NGN',
    minimumFractionDigits: 2,
});

export default function CashierReceiptVerify({ verification }: ReceiptVerifyProps) {
    const { errors, flash } = usePage<SharedData>().props;
    const { data, setData, post, processing } = useForm({
        matric_number: '',
    });
    const [verifyingId, setVerifyingId] = useState<string | null>(null);

    const submit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        post(route('cashier.receipts.verify.submit'));
    };

    const handleRecheck = (publicReference: string) => {
        setVerifyingId(publicReference);
        router.post(
            route('cashier.payment-records.verify', publicReference),
            {},
            {
                preserveScroll: true,
                onFinish: () => setVerifyingId(null),
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Verify Payment" />

            <div className="space-y-6 p-4">
                <Heading
                    title="Verify payment"
                    description="Cashiers can check all member payment records by matric number, including successful, pending, failed, and abandoned payments."
                />

                <Card>
                    <CardHeader>
                        <CardTitle>Find member payment</CardTitle>
                        <CardDescription>Enter the matric number to check every payment record for this member.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form className="space-y-5" onSubmit={submit}>
                            <div className="grid gap-2">
                                <Label htmlFor="matric_number">Matric number</Label>
                                <Input
                                    id="matric_number"
                                    value={data.matric_number}
                                    onChange={(event) => setData('matric_number', event.target.value)}
                                    placeholder="GSU/19/1234"
                                    disabled={processing}
                                />
                                <InputError message={errors?.matric_number} />
                            </div>

                            <Button type="submit" disabled={processing}>
                                <Search />
                                {processing ? 'Checking...' : 'Verify payment'}
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <Alert>
                    <AlertTitle>All payment statuses are included</AlertTitle>
                    <AlertDescription>
                        This search returns successful, pending, failed, and abandoned payment records for the entered matric number.
                    </AlertDescription>
                </Alert>

                {flash.error && (
                    <Alert variant="destructive">
                        <AlertTitle>Verification failed</AlertTitle>
                        <AlertDescription>{flash.error}</AlertDescription>
                    </Alert>
                )}

                {verification && (
                    <Card className="border-emerald-200 bg-emerald-50/70">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-emerald-900">
                                <CheckCircle2 className="size-5" />
                                Payment requests
                            </CardTitle>
                            <CardDescription>
                                {verification.length} payment record(s) found for this member across all payment statuses.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {verification.map((receipt, index) => (
                                <div key={`${receipt.receipt_number}-${index}`} className="rounded-xl border border-emerald-200 bg-white p-4 shadow-sm">
                                    <div className="flex flex-wrap items-center justify-between gap-3">
                                        <div>
                                            <p className="text-xs font-semibold tracking-[0.16em] text-emerald-700 uppercase">Receipt number</p>
                                            <p className="mt-1 font-semibold text-emerald-950">{receipt.receipt_number ?? 'Not issued'}</p>
                                        </div>
                                        <PaymentStatusBadge status={receipt.payment_status} label={receipt.status} />
                                    </div>

                                    <Separator className="my-4" />

                                    <div className="grid gap-4 md:grid-cols-2">
                                        <div>
                                            <p className="text-xs font-semibold tracking-[0.16em] text-emerald-700 uppercase">Member name</p>
                                            <p className="mt-1 font-semibold text-emerald-950">{receipt.member_name}</p>
                                        </div>
                                        <div>
                                            <p className="text-xs font-semibold tracking-[0.16em] text-emerald-700 uppercase">Matric number</p>
                                            <p className="mt-1 font-semibold text-emerald-950">{receipt.matric_number}</p>
                                        </div>
                                        <div>
                                            <p className="text-xs font-semibold tracking-[0.16em] text-emerald-700 uppercase">Payment type</p>
                                            <p className="mt-1 font-semibold text-emerald-950">{receipt.payment_type}</p>
                                        </div>
                                        <div>
                                            <p className="text-xs font-semibold tracking-[0.16em] text-emerald-700 uppercase">Payment amount</p>
                                            <p className="mt-1 font-semibold text-emerald-950">
                                                {currencyFormatter.format(Number(receipt.payment_amount))}
                                            </p>
                                        </div>
                                        <div>
                                            <p className="text-xs font-semibold tracking-[0.16em] text-emerald-700 uppercase">Payment reference</p>
                                            <p className="mt-1 font-semibold text-emerald-950">{receipt.payment_reference ?? 'Not recorded'}</p>
                                        </div>
                                        <div>
                                            <p className="text-xs font-semibold tracking-[0.16em] text-emerald-700 uppercase">Paid at</p>
                                            <p className="mt-1 font-semibold text-emerald-950">
                                                {receipt.paid_at ? new Date(receipt.paid_at).toLocaleString() : 'Not recorded'}
                                            </p>
                                        </div>
                                    </div>

                                    {receipt.can_recheck && (
                                        <div className="mt-4">
                                            <Button
                                                size="sm"
                                                onClick={() => handleRecheck(receipt.public_reference)}
                                                disabled={verifyingId === receipt.public_reference}
                                            >
                                                {verifyingId === receipt.public_reference ? (
                                                    <>
                                                        <Loader2 className="animate-spin" />
                                                        Rechecking...
                                                    </>
                                                ) : (
                                                    'Recheck status'
                                                )}
                                            </Button>
                                        </div>
                                    )}
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
