import { Badge } from '@/components/ui/badge';
import { type PaymentRequestStatus } from '@/types';

const variantMap: Record<PaymentRequestStatus, { variant: 'default' | 'secondary' | 'destructive' | 'outline'; className?: string }> = {
    successful: {
        variant: 'default',
        className: 'bg-emerald-600 text-white hover:bg-emerald-600',
    },
    pending: {
        variant: 'outline',
        className: 'border-amber-200 bg-amber-50 text-amber-800',
    },
    failed: {
        variant: 'destructive',
    },
    abandoned: {
        variant: 'secondary',
        className: 'bg-slate-200 text-slate-700',
    },
};

export function PaymentStatusBadge({
    status,
    label,
}: {
    status: PaymentRequestStatus;
    label: string;
}) {
    const configuration = variantMap[status];

    return (
        <Badge variant={configuration.variant} className={configuration.className}>
            {label}
        </Badge>
    );
}
