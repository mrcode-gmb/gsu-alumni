import AppLogoIcon from '@/components/app-logo-icon';
import { ReceiptDocument } from '@/components/student-receipts/receipt-document';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { type SharedData, type StudentReceipt } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { Download, Wallet } from 'lucide-react';

interface StudentReceiptShowProps {
    receipt: StudentReceipt;
    downloadUrl: string;
}

export default function StudentReceiptShow({ receipt, downloadUrl }: StudentReceiptShowProps) {
    const { flash } = usePage<SharedData>().props;

    return (
        <>
            <Head title={`Receipt ${receipt.receipt_number}`} />

            <div className="receipt-shell min-h-screen bg-[radial-gradient(circle_at_top,_rgba(16,185,129,0.12),_transparent_30%),linear-gradient(180deg,_#f7f8fa_0%,_#edf1f4_100%)] py-6 sm:py-8">
                <div className="receipt-screen-only mx-auto flex max-w-5xl flex-col gap-4 px-4 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
                    <div className="flex items-start gap-4">
                        <div className="flex size-14 shrink-0 items-center justify-center rounded-2xl border border-emerald-100 bg-white/90 p-2 shadow-sm">
                            <AppLogoIcon className="size-full" alt="GSU Alumni Association logo" />
                        </div>
                        <div>
                            <p className="text-sm font-semibold tracking-[0.22em] text-emerald-700 uppercase">GSU Alumni Payment Portal</p>
                            <h1 className="mt-2 text-2xl font-semibold tracking-tight text-slate-950">Official student receipt</h1>
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-3">
                        <Button variant="outline" asChild>
                            <Link href={route('home')}>
                                <Wallet />
                                New payment
                            </Link>
                        </Button>
                        <Button asChild>
                            <a href={downloadUrl}>
                                <Download />
                                Download receipt
                            </a>
                        </Button>
                    </div>
                </div>

                <div className="mx-auto mt-6 max-w-5xl space-y-4 px-4 sm:px-6 lg:px-8">
                    <Alert className="receipt-screen-only border-emerald-200 bg-emerald-50 text-emerald-950">
                        <AlertTitle>Before you leave</AlertTitle>
                        <AlertDescription>Please download this receipt before leaving the page.</AlertDescription>
                    </Alert>

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

                    <div className="receipt-screen-only pt-2">
                        <div className="rounded-3xl border border-emerald-200 bg-white/95 p-4 shadow-sm sm:p-5">
                            <Button size="lg" className="h-14 w-full text-base font-semibold sm:h-16 sm:text-lg" asChild>
                                <a href={downloadUrl}>
                                    <Download />
                                    Download receipt
                                </a>
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
