import Heading from '@/components/heading';
import { PaymentRecordDocument } from '@/components/payment-records/payment-record-document';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type AdminPaymentRecordDetail, type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, FileSpreadsheet, Loader2, ReceiptText, ShieldCheck } from 'lucide-react';
import { useState } from 'react';

interface PaymentRecordShowProps {
    paymentRecord: AdminPaymentRecordDetail;
}

export default function PaymentRecordShow({ paymentRecord }: PaymentRecordShowProps) {
    const { flash } = usePage<SharedData>().props;
    const [rechecking, setRechecking] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Dashboard',
            href: '/dashboard',
        },
        {
            title: 'Payment Records',
            href: '/admin/payment-records',
        },
        {
            title: paymentRecord.matric_number,
            href: route('admin.payment-records.show', paymentRecord.public_reference),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Payment Record ${paymentRecord.matric_number}`} />

            <div className="space-y-6 p-4">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <Heading
                        title="Payment record details"
                        description="Review the stored student, payment, gateway, and receipt information for this single payment record."
                    />

                    <div className="flex flex-wrap gap-3">
                        <Button variant="outline" asChild>
                            <Link href={route('admin.payment-records.index')}>
                                <ArrowLeft />
                                Back to records
                            </Link>
                        </Button>

                        {paymentRecord.can_open_receipt && (
                            <Button variant="outline" asChild>
                                <Link href={route('admin.payment-records.receipt', paymentRecord.public_reference)} method="post" as="button">
                                    <ReceiptText />
                                    {paymentRecord.has_receipt ? 'Open receipt' : 'Issue receipt'}
                                </Link>
                            </Button>
                        )}

                        {paymentRecord.can_recheck && (
                            <Button
                                variant="outline"
                                onClick={() => {
                                    setRechecking(true);
                                    router.post(
                                        route('admin.payment-records.verify', paymentRecord.public_reference),
                                        {},
                                        {
                                            preserveScroll: true,
                                            onFinish: () => setRechecking(false),
                                        },
                                    );
                                }}
                                disabled={rechecking}
                            >
                                {rechecking ? (
                                    <>
                                        <Loader2 className="animate-spin" />
                                        Rechecking...
                                    </>
                                ) : (
                                    <>
                                        <ShieldCheck />
                                        Recheck status
                                    </>
                                )}
                            </Button>
                        )}

                        <Button asChild>
                            <a
                                href={route('admin.payment-records.print-single', paymentRecord.public_reference)}
                                target="_blank"
                                rel="noreferrer"
                            >
                                <FileSpreadsheet />
                                Print record
                            </a>
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

                <PaymentRecordDocument paymentRecord={paymentRecord} />
            </div>
        </AppLayout>
    );
}
