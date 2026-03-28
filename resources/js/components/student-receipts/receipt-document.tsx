import AppLogoIcon from '@/components/app-logo-icon';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { type StudentReceipt } from '@/types';

const currencyFormatter = new Intl.NumberFormat('en-NG', {
    style: 'currency',
    currency: 'NGN',
    minimumFractionDigits: 2,
});

function DetailRow({ label, value }: { label: string; value: string }) {
    return (
        <div className="grid gap-1 py-3 sm:grid-cols-[180px,1fr] sm:gap-4">
            <dt className="text-xs font-semibold tracking-[0.16em] text-slate-500 uppercase">{label}</dt>
            <dd className="text-sm font-medium text-slate-900">{value}</dd>
        </div>
    );
}

function formatDateTime(value: string | null): string {
    if (!value) {
        return 'Not recorded';
    }

    return new Date(value).toLocaleString();
}

export function ReceiptDocument({ receipt }: { receipt: StudentReceipt }) {
    return (
        <article className="receipt-document overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_24px_80px_rgba(15,23,42,0.08)]">
            <div className="border-b border-slate-200 bg-[linear-gradient(135deg,_#ecfdf5_0%,_#ffffff_45%,_#f8fafc_100%)] px-6 py-6 sm:px-8">
                <div className="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                    <div className="space-y-3">
                        <div className="flex items-start gap-4">
                            <div className="flex size-16 shrink-0 items-center justify-center rounded-2xl border border-emerald-100 bg-white p-2 shadow-sm">
                                <AppLogoIcon className="size-full" alt="Gombe State University logo" />
                            </div>
                            <div>
                                <p className="text-sm font-semibold tracking-[0.26em] text-emerald-700 uppercase">Gombe State University</p>
                                <h1 className="text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl">Official Payment Receipt</h1>
                                <p className="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                                    GSU Alumni Payment Portal receipt for a successfully verified student payment.
                                </p>
                            </div>
                        </div>

                        <div className="flex flex-wrap items-center gap-3">
                            <Badge className="bg-emerald-600 text-white hover:bg-emerald-600">{receipt.payment_status_label}</Badge>
                            <span className="text-sm text-slate-600">Receipt number: {receipt.receipt_number}</span>
                        </div>
                    </div>

                    <div className="rounded-2xl border border-emerald-100 bg-white/90 px-4 py-3 text-sm shadow-sm">
                        <p className="text-xs font-semibold tracking-[0.16em] text-slate-500 uppercase">Issued at</p>
                        <p className="mt-1 font-semibold text-slate-950">{formatDateTime(receipt.issued_at)}</p>
                    </div>
                </div>
            </div>

            <div className="grid gap-8 px-6 py-6 sm:px-8 lg:grid-cols-[1.15fr,0.85fr]">
                <section>
                    <div className="rounded-3xl border border-slate-200 bg-slate-50/80 p-5">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <p className="text-xs font-semibold tracking-[0.18em] text-slate-500 uppercase">Student details</p>
                                <h2 className="mt-2 text-xl font-semibold text-slate-950">{receipt.full_name}</h2>
                            </div>

                            <div className="text-sm text-slate-600">
                                <span className="font-medium text-slate-800">Matric number:</span> {receipt.matric_number}
                            </div>
                        </div>

                        <div className="mt-5">
                            <dl>
                                <DetailRow label="Email address" value={receipt.email} />
                                <Separator />
                                <DetailRow label="Phone number" value={receipt.phone_number} />
                                <Separator />
                                <DetailRow label="Department" value={receipt.department} />
                                <Separator />
                                <DetailRow label="Faculty" value={receipt.faculty} />
                                <Separator />
                                <DetailRow label="Program type" value={receipt.program_type_name ?? 'Not recorded'} />
                                <Separator />
                                <DetailRow label="Graduation session" value={receipt.graduation_session} />
                            </dl>
                        </div>
                    </div>
                </section>

                <section className="space-y-5">
                    <div className="rounded-3xl border border-slate-200 bg-white p-5">
                        <p className="text-xs font-semibold tracking-[0.18em] text-slate-500 uppercase">Payment details</p>
                        <p className="mt-2 text-lg font-semibold text-slate-950">{receipt.payment_type_name}</p>
                        <p className="mt-1 text-3xl font-semibold tracking-tight text-slate-950">
                            {currencyFormatter.format(Number(receipt.amount))}
                        </p>

                        <div className="mt-5">
                            <dl>
                                <DetailRow label="Payment date" value={formatDateTime(receipt.payment_date)} />
                                <Separator />
                                <DetailRow label="Payment status" value={receipt.payment_status_label} />
                                <Separator />
                                <DetailRow label="Internal reference" value={receipt.payment_reference ?? 'Not available'} />
                                <Separator />
                                <DetailRow label="Paystack reference" value={receipt.paystack_reference ?? 'Not available'} />
                                <Separator />
                                <DetailRow label="Payment channel" value={receipt.payment_channel ?? 'Not available'} />
                                <Separator />
                                <DetailRow label="Transaction reference" value={receipt.transaction_reference ?? 'Not available'} />
                            </dl>
                        </div>
                    </div>

                    <div className="rounded-3xl border border-emerald-100 bg-emerald-50/80 p-5">
                        <p className="text-xs font-semibold tracking-[0.18em] text-emerald-800 uppercase">Official note</p>
                        <p className="mt-2 text-sm leading-6 text-emerald-950">{receipt.official_note}</p>
                        <p className="mt-3 text-sm leading-6 text-emerald-900/80">
                            Keep this receipt number and the payment references for any future verification or reprint request.
                        </p>
                    </div>
                </section>
            </div>
        </article>
    );
}
