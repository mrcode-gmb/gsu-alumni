import AppLogoIcon from '@/components/app-logo-icon';
import { PaymentRecordDocument } from '@/components/payment-records/payment-record-document';
import { Button } from '@/components/ui/button';
import { type AdminPaymentRecordDetail } from '@/types';
import { Head, router } from '@inertiajs/react';
import { ArrowLeft, Printer, ReceiptText } from 'lucide-react';

interface PaymentRecordPrintShowProps {
    paymentRecord: AdminPaymentRecordDetail;
}

export default function PaymentRecordPrintShow({ paymentRecord }: PaymentRecordPrintShowProps) {
    const printRecord = () => {
        window.print();
    };

    return (
        <>
            <Head title={`Print Payment Record ${paymentRecord.matric_number}`} />

            <div className="admin-print-shell min-h-screen bg-[linear-gradient(180deg,_#f8fafc_0%,_#eef2f7_100%)] py-6 sm:py-8">
                <div className="admin-print-screen-only mx-auto flex max-w-6xl flex-col gap-4 px-4 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
                    <div className="flex items-start gap-4">
                        <div className="flex size-14 shrink-0 items-center justify-center rounded-2xl border border-emerald-100 bg-white/90 p-2 shadow-sm">
                            <AppLogoIcon className="size-full" alt="GSU Alumni Association logo" />
                        </div>
                        <div>
                            <p className="text-sm font-semibold tracking-[0.22em] text-emerald-700 uppercase">GSU Alumni Payment Portal</p>
                            <h1 className="mt-2 text-2xl font-semibold tracking-tight text-slate-950">Print payment record</h1>
                            <p className="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                                This view is formatted for printing or saving to PDF as an internal admin record.
                            </p>
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-3">
                        <Button variant="outline" asChild>
                            <a href={route('admin.payment-records.show', paymentRecord.public_reference)}>
                                <ArrowLeft />
                                Back to record
                            </a>
                        </Button>

                        {paymentRecord.can_open_receipt && (
                            <Button
                                variant="outline"
                                onClick={() => router.post(route('admin.payment-records.receipt', paymentRecord.public_reference))}
                            >
                                <ReceiptText />
                                {paymentRecord.has_receipt ? 'Open receipt' : 'Issue receipt'}
                            </Button>
                        )}

                        <Button onClick={printRecord}>
                            <Printer />
                            Print / Download PDF
                        </Button>
                    </div>
                </div>

                <div className="mx-auto mt-6 max-w-6xl px-4 sm:px-6 lg:px-8">
                    <PaymentRecordDocument paymentRecord={paymentRecord} />
                </div>
            </div>
        </>
    );
}
