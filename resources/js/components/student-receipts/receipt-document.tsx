import AppLogoIcon from '@/components/app-logo-icon';
import { Badge } from '@/components/ui/badge';
import { type StudentReceipt } from '@/types';

const currencyFormatter = new Intl.NumberFormat('en-NG', {
    style: 'currency',
    currency: 'NGN',
    minimumFractionDigits: 2,
});

function DetailRow({ label, value }: { label: string; value: string }) {
    return (
        <div className="grid grid-cols-[132px,1fr] gap-3 py-1.5 text-sm sm:grid-cols-[160px,1fr]">
            <dt className="font-semibold text-slate-700">{label}:</dt>
            <dd className="font-medium break-words text-slate-950">{value}</dd>
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
        <article className="receipt-document relative overflow-hidden rounded-[1.5rem] border-2 border-slate-300 bg-white shadow-[0_24px_80px_rgba(15,23,42,0.08)]">
            <div className="pointer-events-none absolute inset-0 flex items-center justify-center">
                <div className="receipt-watermark flex h-[26rem] w-[26rem] items-center justify-center opacity-[0.055] sm:h-[30rem] sm:w-[30rem]">
                    <AppLogoIcon className="size-full" alt="GSU watermark" />
                </div>
            </div>

            <div className="relative">
                <div className="border-b-4 border-emerald-700 px-5 py-6 sm:px-8">
                    <div className="grid gap-5 md:grid-cols-[88px,1fr,190px] md:items-start">
                        <div className="mx-auto flex size-20 shrink-0 items-center justify-center md:mx-0">
                            <AppLogoIcon className="size-full" alt="GSU Alumni Association logo" />
                        </div>

                        <div className="text-center md:text-left">
                            <p className="text-xl font-bold tracking-tight text-slate-950 sm:text-3xl">GOMBE STATE UNIVERSITY ALUMNI ASSOCIATION</p>
                            <p className="mt-2 text-sm font-semibold tracking-wide text-slate-800 uppercase">
                                Tudun Wada Gombe, Gombe State
                            </p>
                            <p className="mt-2 text-sm font-semibold text-slate-800">
                                {receipt.graduation_session} Academic Session
                            </p>
                            <h1 className="mt-3 text-2xl font-bold text-slate-950 underline underline-offset-4 sm:text-3xl">
                                Member&apos;s Receipt
                            </h1>
                        </div>

                        <div className="rounded-2xl border border-slate-300 bg-slate-50/90 p-4 text-sm">
                            <p className="text-xs font-semibold tracking-[0.14em] text-slate-500 uppercase">Receipt number</p>
                            <p className="mt-1 text-base font-bold text-slate-950">{receipt.receipt_number}</p>
                            <p className="mt-4 text-xs font-semibold tracking-[0.14em] text-slate-500 uppercase">Payment status</p>
                            <Badge className="mt-2 bg-emerald-600 text-white hover:bg-emerald-600">{receipt.payment_status_label}</Badge>
                            <p className="mt-4 text-xs font-semibold tracking-[0.14em] text-slate-500 uppercase">Issued at</p>
                            <p className="mt-1 font-semibold text-slate-950">{formatDateTime(receipt.issued_at)}</p>
                        </div>
                    </div>
                </div>

                <div className="px-5 py-5 sm:px-8">
                    <div className="flex flex-col gap-2 border-b border-slate-300 pb-4 sm:flex-row sm:items-center sm:justify-between">
                        <p className="text-lg font-bold text-slate-950">
                            Payment Reference:{' '}
                            <span className="text-emerald-700">{receipt.payment_reference ?? receipt.payment_request_public_reference}</span>
                        </p>
                        <p className="text-sm font-medium text-slate-700">
                            Verified payment for {receipt.payment_type_name}
                        </p>
                    </div>

                    <div className="mt-5 grid gap-6 lg:grid-cols-2">
                        <section className="rounded-2xl border border-slate-300 bg-white/90 p-4">
                            <h2 className="text-sm font-bold tracking-[0.18em] text-slate-700 uppercase">Member details</h2>
                            <div className="mt-3">
                                <DetailRow label="Name" value={receipt.full_name} />
                                <DetailRow label="Matric Number" value={receipt.matric_number} />
                                <DetailRow label="Email" value={receipt.email} />
                                <DetailRow label="Phone No." value={receipt.phone_number} />
                                <DetailRow label="Faculty" value={receipt.faculty} />
                                <DetailRow label="Department" value={receipt.department} />
                                <DetailRow label="Programme" value={receipt.program_type_name ?? 'Not recorded'} />
                            </div>
                        </section>

                        <section className="rounded-2xl border border-slate-300 bg-white/90 p-4">
                            <h2 className="text-sm font-bold tracking-[0.18em] text-slate-700 uppercase">Payment details</h2>
                            <div className="mt-3">
                                <DetailRow label="Academic Session" value={receipt.graduation_session} />
                                <DetailRow label="Payment Date" value={formatDateTime(receipt.payment_date)} />
                                <DetailRow label="Payment Type" value={receipt.payment_type_name} />
                                <DetailRow label="Paystack Ref." value={receipt.paystack_reference ?? 'Not available'} />
                                <DetailRow label="Transaction Ref." value={receipt.transaction_reference ?? 'Not available'} />
                                <DetailRow label="Channel" value={receipt.payment_channel ?? 'Not available'} />
                            </div>
                        </section>
                    </div>

                    <div className="mt-6 overflow-hidden rounded-2xl border-2 border-slate-900 bg-white">
                        <table className="w-full border-collapse text-sm sm:text-base">
                            <tbody>
                                <tr className="border-b border-slate-900">
                                    <th className="w-[68%] bg-slate-100 px-4 py-3 text-left font-bold text-slate-900">Description</th>
                                    <th className="bg-slate-100 px-4 py-3 text-right font-bold text-slate-900">Amount (NGN)</th>
                                </tr>
                                <tr>
                                    <td className="bg-slate-900 px-4 py-3 text-base font-bold text-white">Payment Amount</td>
                                    <td className="bg-slate-900 px-4 py-3 text-right text-base font-bold text-white">
                                        {currencyFormatter.format(Number(receipt.base_amount))}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div className="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50/85 px-4 py-4">
                        <p className="text-sm font-semibold text-emerald-950">Official note</p>
                        <p className="mt-2 text-sm leading-6 text-emerald-950">
                            {receipt.official_note} This receipt was generated from a verified payment record and is valid without signature or stamp.
                        </p>
                        <p className="mt-2 text-sm leading-6 text-emerald-900/85">
                            Keep this receipt number and payment reference for reprint or future verification.
                        </p>
                    </div>
                </div>
            </div>
        </article>
    );
}
