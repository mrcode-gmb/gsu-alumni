import AppLogoIcon from '@/components/app-logo-icon';
import { PaymentStatusBadge } from '@/components/payment-records/payment-status-badge';
import { Card, CardContent } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { type AdminPaymentRecordDetail } from '@/types';

const currencyFormatter = new Intl.NumberFormat('en-NG', {
    style: 'currency',
    currency: 'NGN',
    minimumFractionDigits: 2,
});

function formatDateTime(value: string | null): string {
    if (!value) {
        return 'Not recorded';
    }

    return new Date(value).toLocaleString();
}

function DetailRow({ label, value }: { label: string; value: string }) {
    return (
        <div className="grid gap-1 py-3 sm:grid-cols-[190px,1fr] sm:gap-4">
            <dt className="text-xs font-semibold tracking-[0.16em] text-slate-500 uppercase">{label}</dt>
            <dd className="text-sm font-medium text-slate-900">{value}</dd>
        </div>
    );
}

export function PaymentRecordDocument({
    paymentRecord,
    note = 'This printout is an internal admin copy of the stored payment record.',
}: {
    paymentRecord: AdminPaymentRecordDetail;
    note?: string;
}) {
    const transactionCharges = Number(paymentRecord.portal_charge_amount) + Number(paymentRecord.paystack_charge_amount);

    return (
        <article className="admin-print-document overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-[0_20px_70px_rgba(15,23,42,0.08)]">
            <div className="border-b border-slate-200 bg-[linear-gradient(135deg,_#eff6ff_0%,_#ffffff_42%,_#f8fafc_100%)] px-6 py-6 sm:px-8">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div className="space-y-3">
                        <div className="flex items-start gap-4">
                            <div className="flex size-16 shrink-0 items-center justify-center rounded-2xl border border-emerald-100 bg-white p-2 shadow-sm">
                                <AppLogoIcon className="size-full" alt="Gombe State University logo" />
                            </div>
                            <div>
                                <p className="text-sm font-semibold tracking-[0.22em] text-emerald-700 uppercase">GSU Alumni Payment Portal</p>
                                <h1 className="mt-2 text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl">Admin Payment Record</h1>
                                <p className="mt-2 max-w-2xl text-sm leading-6 text-slate-600">{note}</p>
                            </div>
                        </div>

                        <div className="flex flex-wrap items-center gap-3">
                            <PaymentStatusBadge status={paymentRecord.payment_status} label={paymentRecord.payment_status_label} />
                            <span className="text-sm text-slate-600">Reference: {paymentRecord.payment_reference ?? paymentRecord.public_reference}</span>
                        </div>
                    </div>

                    <div className="rounded-2xl border border-slate-200 bg-white/90 px-4 py-3 text-sm shadow-sm">
                        <p className="text-xs font-semibold tracking-[0.16em] text-slate-500 uppercase">Total amount</p>
                        <p className="mt-1 text-2xl font-semibold text-slate-950">
                            {currencyFormatter.format(Number(paymentRecord.amount))}
                        </p>
                    </div>
                </div>
            </div>

            <div className="grid gap-6 px-6 py-6 sm:px-8 xl:grid-cols-2">
                <Card className="gap-0 border-slate-200 py-0 shadow-none">
                    <CardContent className="px-5 py-5">
                        <h2 className="text-base font-semibold text-slate-950">Student information</h2>
                        <dl className="mt-4">
                            <DetailRow label="Full name" value={paymentRecord.full_name} />
                            <Separator />
                            <DetailRow label="Matric number" value={paymentRecord.matric_number} />
                            <Separator />
                            <DetailRow label="Email address" value={paymentRecord.email} />
                            <Separator />
                            <DetailRow label="Phone number" value={paymentRecord.phone_number} />
                            <Separator />
                            <DetailRow label="Department" value={paymentRecord.department} />
                            <Separator />
                            <DetailRow label="Faculty" value={paymentRecord.faculty} />
                            <Separator />
                            <DetailRow label="Graduation session" value={paymentRecord.graduation_session} />
                        </dl>
                    </CardContent>
                </Card>

                <Card className="gap-0 border-slate-200 py-0 shadow-none">
                    <CardContent className="px-5 py-5">
                        <h2 className="text-base font-semibold text-slate-950">Payment information</h2>
                        <dl className="mt-4">
                            <DetailRow label="Payment type" value={paymentRecord.payment_type_name} />
                            <Separator />
                            <DetailRow label="Description" value={paymentRecord.payment_type_description || 'No description recorded.'} />
                            <Separator />
                            <DetailRow label="Base amount" value={currencyFormatter.format(Number(paymentRecord.base_amount))} />
                            <Separator />
                            <DetailRow label="Transaction charges" value={currencyFormatter.format(transactionCharges)} />
                            <Separator />
                            <DetailRow label="Total amount" value={currencyFormatter.format(Number(paymentRecord.amount))} />
                            <Separator />
                            <DetailRow label="Status" value={paymentRecord.payment_status_label} />
                            <Separator />
                            <DetailRow label="Internal payment reference" value={paymentRecord.payment_reference ?? 'Not generated'} />
                            <Separator />
                            <DetailRow label="Paystack reference" value={paymentRecord.paystack_reference ?? 'Not generated'} />
                            <Separator />
                            <DetailRow label="Transaction reference" value={paymentRecord.transaction_reference ?? 'Not available'} />
                            <Separator />
                            <DetailRow label="Payment channel" value={paymentRecord.payment_channel ?? 'Not available'} />
                            <Separator />
                            <DetailRow label="Gateway response" value={paymentRecord.gateway_response ?? 'No gateway response stored.'} />
                        </dl>
                    </CardContent>
                </Card>

                <Card className="gap-0 border-slate-200 py-0 shadow-none">
                    <CardContent className="px-5 py-5">
                        <h2 className="text-base font-semibold text-slate-950">Receipt information</h2>
                        <dl className="mt-4">
                            <DetailRow label="Receipt number" value={paymentRecord.receipt_number ?? 'No receipt issued yet'} />
                            <Separator />
                            <DetailRow label="Receipt issued at" value={formatDateTime(paymentRecord.receipt_issued_at)} />
                        </dl>
                    </CardContent>
                </Card>

                <Card className="gap-0 border-slate-200 py-0 shadow-none">
                    <CardContent className="px-5 py-5">
                        <h2 className="text-base font-semibold text-slate-950">Record timeline</h2>
                        <dl className="mt-4">
                            <DetailRow label="Request created" value={formatDateTime(paymentRecord.created_at)} />
                            <Separator />
                            <DetailRow label="Paid at" value={formatDateTime(paymentRecord.paid_at)} />
                            <Separator />
                            <DetailRow label="Last updated" value={formatDateTime(paymentRecord.updated_at)} />
                        </dl>
                    </CardContent>
                </Card>
            </div>
        </article>
    );
}
