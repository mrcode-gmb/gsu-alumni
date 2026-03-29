import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { type StudentPaymentRequest } from '@/types';

const currencyFormatter = new Intl.NumberFormat('en-NG', {
    style: 'currency',
    currency: 'NGN',
    minimumFractionDigits: 2,
});

function SummaryRow({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex flex-col gap-1 py-3 sm:flex-row sm:items-center sm:justify-between">
            <dt className="text-sm font-medium text-slate-500">{label}</dt>
            <dd className="text-sm font-semibold text-slate-900">{value}</dd>
        </div>
    );
}

export function PaymentRequestSummary({ paymentRequest }: { paymentRequest: StudentPaymentRequest }) {
    return (
        <Card className="border-slate-200 bg-white/95 shadow-sm">
            <CardHeader>
                <CardTitle>Payment request summary</CardTitle>
                <CardDescription>Review the details saved for this payment request and keep the references for follow-up.</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                <div className="flex flex-col gap-3 rounded-2xl border bg-slate-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p className="text-xs font-semibold tracking-[0.18em] text-slate-500 uppercase">Request reference</p>
                        <p className="mt-1 font-mono text-sm font-semibold text-slate-900">{paymentRequest.public_reference}</p>
                    </div>
                    <Badge>{paymentRequest.payment_status_label}</Badge>
                </div>

                <dl>
                    <SummaryRow label="Full name" value={paymentRequest.full_name} />
                    <Separator />
                    <SummaryRow label="Matric number" value={paymentRequest.matric_number} />
                    <Separator />
                    <SummaryRow label="Email address" value={paymentRequest.email} />
                    <Separator />
                    <SummaryRow label="Phone number" value={paymentRequest.phone_number} />
                    <Separator />
                    <SummaryRow label="Department" value={paymentRequest.department} />
                    <Separator />
                    <SummaryRow label="Faculty" value={paymentRequest.faculty} />
                    <Separator />
                    <SummaryRow label="Program type" value={paymentRequest.program_type_name ?? 'Not specified'} />
                    <Separator />
                    <SummaryRow label="Graduation year / session" value={paymentRequest.graduation_session} />
                    <Separator />
                    <SummaryRow label="Payment type" value={paymentRequest.payment_type_name} />
                    <Separator />
                    <SummaryRow label="Total payable" value={currencyFormatter.format(Number(paymentRequest.amount))} />
                    {paymentRequest.payment_reference && (
                        <>
                            <Separator />
                            <SummaryRow label="Internal payment reference" value={paymentRequest.payment_reference} />
                        </>
                    )}
                    {paymentRequest.paystack_reference && (
                        <>
                            <Separator />
                            <SummaryRow label="Paystack reference" value={paymentRequest.paystack_reference} />
                        </>
                    )}
                    {paymentRequest.payment_channel && (
                        <>
                            <Separator />
                            <SummaryRow label="Payment channel" value={paymentRequest.payment_channel} />
                        </>
                    )}
                    {paymentRequest.paid_at && (
                        <>
                            <Separator />
                            <SummaryRow label="Paid at" value={new Date(paymentRequest.paid_at).toLocaleString()} />
                        </>
                    )}
                    {paymentRequest.gateway_response && (
                        <>
                            <Separator />
                            <SummaryRow label="Gateway response" value={paymentRequest.gateway_response} />
                        </>
                    )}
                </dl>
            </CardContent>
        </Card>
    );
}
