import { Card, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { type AdminPaymentDashboardSummary } from '@/types';

const currencyFormatter = new Intl.NumberFormat('en-NG', {
    style: 'currency',
    currency: 'NGN',
    minimumFractionDigits: 2,
});

const summaryItems: Array<{
    key: keyof AdminPaymentDashboardSummary;
    label: string;
    isCurrency?: boolean;
}> = [
    { key: 'total_payment_requests', label: 'Total payment requests' },
    { key: 'total_successful_payments', label: 'Successful payments' },
    { key: 'total_pending_payments', label: 'Pending payments' },
    { key: 'total_failed_payments', label: 'Failed payments' },
    { key: 'total_amount_collected', label: 'Amount collected', isCurrency: true },
];

export function PaymentRecordSummaryCards({ summary }: { summary: AdminPaymentDashboardSummary }) {
    return (
        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            {summaryItems.map((item) => (
                <Card key={item.key}>
                    <CardHeader className="gap-1">
                        <CardDescription>{item.label}</CardDescription>
                        <CardTitle className="text-3xl">
                            {item.isCurrency
                                ? currencyFormatter.format(Number(summary[item.key]))
                                : summary[item.key]}
                        </CardTitle>
                    </CardHeader>
                </Card>
            ))}
        </div>
    );
}
