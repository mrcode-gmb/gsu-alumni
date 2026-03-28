import AppLogoIcon from '@/components/app-logo-icon';
import { ReceiptDocument } from '@/components/student-receipts/receipt-document';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { type SharedData, type StudentReceipt } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { Download, Search, Wallet } from 'lucide-react';

interface StudentReceiptShowProps {
    receipt: StudentReceipt;
}

export default function StudentReceiptShow({ receipt }: StudentReceiptShowProps) {
    const { flash } = usePage<SharedData>().props;

    const printReceipt = () => {
        window.print();
    };

    return (
        <>
            <Head title={`Receipt ${receipt.receipt_number}`} />

            <div className="receipt-shell min-h-screen bg-[radial-gradient(circle_at_top,_rgba(16,185,129,0.16),_transparent_34%),linear-gradient(180deg,_#f8fafc_0%,_#eef2f7_100%)] py-6 sm:py-8">
                <div className="receipt-screen-only mx-auto flex max-w-5xl flex-col gap-4 px-4 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
                    <div className="flex items-start gap-4">
                        <div className="flex size-14 shrink-0 items-center justify-center rounded-2xl border border-emerald-100 bg-white/90 p-2 shadow-sm">
                            <AppLogoIcon className="size-full" alt="Gombe State University logo" />
                        </div>
                        <div>
                            <p className="text-sm font-semibold tracking-[0.22em] text-emerald-700 uppercase">GSU Alumni Payment Portal</p>
                            <h1 className="mt-2 text-2xl font-semibold tracking-tight text-slate-950">Receipt ready for print or reprint</h1>
                            <p className="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                                This page is optimized for printing. You can also use the print dialog to save the receipt as a PDF.
                            </p>
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-3">
                        <Button variant="outline" asChild>
                            <Link href={route('student-receipts.lookup')}>
                                <Search />
                                Find another receipt
                            </Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={route('home')}>
                                <Wallet />
                                New payment
                            </Link>
                        </Button>
                        <Button onClick={printReceipt}>
                            <Download />
                            Print / Download PDF
                        </Button>
                    </div>
                </div>

                <div className="mx-auto mt-6 max-w-5xl space-y-4 px-4 sm:px-6 lg:px-8">
                    {flash.success && (
                        <Alert className="receipt-screen-only">
                            <AlertTitle>Receipt update</AlertTitle>
                            <AlertDescription>{flash.success}</AlertDescription>
                        </Alert>
                    )}

                    {flash.error && (
                        <Alert variant="destructive" className="receipt-screen-only">
                            <AlertTitle>Receipt update</AlertTitle>
                            <AlertDescription>{flash.error}</AlertDescription>
                        </Alert>
                    )}

                    <ReceiptDocument receipt={receipt} />
                </div>
            </div>
        </>
    );
}
