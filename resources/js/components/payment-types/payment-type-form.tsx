import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { type ChargeSetting, type SelectOption } from '@/types';
import { Link } from '@inertiajs/react';
import { type CheckedState } from '@radix-ui/react-checkbox';
import { type FormEventHandler } from 'react';

export type PaymentTypeFormData = {
    name: string;
    amount: string;
    description: string;
    program_type_ids: string[];
    is_active: boolean;
    display_order: string;
};

const currencyFormatter = new Intl.NumberFormat('en-NG', {
    style: 'currency',
    currency: 'NGN',
    minimumFractionDigits: 2,
});

function toKobo(amount: string): number {
    return Math.round((Number.parseFloat(amount || '0') || 0) * 100);
}

function fromKobo(amountKobo: number): string {
    return (amountKobo / 100).toFixed(2);
}

function calculatePreview(amount: string, chargeSetting: ChargeSetting) {
    const baseAmountKobo = toKobo(amount);

    if (baseAmountKobo <= 0) {
        return {
            baseAmount: '0.00',
            serviceChargeAmount: '0.00',
            paystackChargeAmount: '0.00',
            totalAmount: '0.00',
        };
    }

    const serviceChargeAmountKobo = chargeSetting.portal_charge_mode === 'percentage'
        ? Math.ceil(baseAmountKobo * ((Number.parseFloat(chargeSetting.portal_charge_value || '0') || 0) / 100))
        : toKobo(chargeSetting.portal_charge_value);

    const subtotalKobo = baseAmountKobo + serviceChargeAmountKobo;
    const paystackPercentageRate = (Number.parseFloat(chargeSetting.paystack_percentage_rate || '0') || 0) / 100;
    const paystackFlatFeeKobo = toKobo(chargeSetting.paystack_flat_fee);
    const paystackFlatFeeThresholdKobo = toKobo(chargeSetting.paystack_flat_fee_threshold || '0');

    let grossKobo = subtotalKobo;

    for (let index = 0; index < 12; index += 1) {
        const extraFlatFeeKobo = grossKobo >= paystackFlatFeeThresholdKobo ? paystackFlatFeeKobo : 0;
        const feeKobo = Math.max(0, Math.ceil(grossKobo * paystackPercentageRate) + extraFlatFeeKobo);
        const nextGrossKobo = subtotalKobo + feeKobo;

        if (nextGrossKobo === grossKobo) {
            break;
        }

        grossKobo = nextGrossKobo;
    }

    const paystackChargeAmountKobo = Math.max(0, grossKobo - subtotalKobo);

    return {
        baseAmount: fromKobo(baseAmountKobo),
        serviceChargeAmount: fromKobo(serviceChargeAmountKobo),
        paystackChargeAmount: fromKobo(paystackChargeAmountKobo),
        totalAmount: fromKobo(grossKobo),
    };
}

interface PaymentTypeFormProps {
    title: string;
    description: string;
    submitLabel: string;
    cancelHref: string;
    programTypeOptions: SelectOption[];
    chargeSetting: ChargeSetting;
    data: PaymentTypeFormData;
    errors: Partial<Record<keyof PaymentTypeFormData, string>>;
    processing: boolean;
    setData: <K extends keyof PaymentTypeFormData>(key: K, value: PaymentTypeFormData[K]) => void;
    onSubmit: FormEventHandler<HTMLFormElement>;
}

export function PaymentTypeForm({
    title,
    description,
    submitLabel,
    cancelHref,
    programTypeOptions,
    chargeSetting,
    data,
    errors,
    processing,
    setData,
    onSubmit,
}: PaymentTypeFormProps) {
    const preview = calculatePreview(data.amount, chargeSetting);

    const toggleProgramType = (programTypeId: string, checked: CheckedState) => {
        const nextProgramTypeIds = checked === true
            ? Array.from(new Set([...data.program_type_ids, programTypeId]))
            : data.program_type_ids.filter((currentProgramTypeId) => currentProgramTypeId !== programTypeId);

        setData('program_type_ids', nextProgramTypeIds);
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle>{title}</CardTitle>
                <CardDescription>{description}</CardDescription>
            </CardHeader>
            <CardContent>
                <form className="space-y-6" onSubmit={onSubmit}>
                    <div className="grid gap-6 md:grid-cols-2">
                        <div className="grid gap-2 md:col-span-2">
                            <Label htmlFor="name">Payment name</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(event) => setData('name', event.target.value)}
                                placeholder="Certificate Registration"
                                disabled={processing}
                                maxLength={255}
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="amount">Amount (NGN)</Label>
                            <Input
                                id="amount"
                                type="number"
                                min="0.01"
                                step="0.01"
                                value={data.amount}
                                onChange={(event) => setData('amount', event.target.value)}
                                placeholder="5000.00"
                                disabled={processing}
                            />
                            <p className="text-muted-foreground text-xs">This is the base payment amount. The two configured admin charges are added automatically below.</p>
                            <InputError message={errors.amount} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="display_order">Display order</Label>
                            <Input
                                id="display_order"
                                type="number"
                                min="0"
                                step="1"
                                value={data.display_order}
                                onChange={(event) => setData('display_order', event.target.value)}
                                placeholder="Optional"
                                disabled={processing}
                            />
                            <p className="text-muted-foreground text-xs">Lower values appear earlier when students are allowed to pay later.</p>
                            <InputError message={errors.display_order} />
                        </div>

                        <div className="grid gap-2 md:col-span-2">
                            <Label htmlFor="description">Description</Label>
                            <Textarea
                                id="description"
                                value={data.description}
                                onChange={(event) => setData('description', event.target.value)}
                                placeholder="Short explanation of what this payment covers."
                                disabled={processing}
                                maxLength={1000}
                            />
                            <InputError message={errors.description} />
                        </div>

                        <div className="grid gap-3 md:col-span-2">
                            <div className="rounded-xl border border-emerald-200 bg-emerald-50/70 p-4">
                                <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <p className="text-sm font-semibold text-emerald-950">Automatic charge preview</p>
                                        <p className="mt-1 text-sm leading-6 text-emerald-950/80">
                                            These values come from the current charge settings and will be saved on this payment type when you create or update it.
                                            Your website service charge is always added, while the extra Paystack flat fee applies when the payable amount reaches the configured threshold or more.
                                        </p>
                                    </div>
                                    <div className="text-xs font-medium tracking-[0.16em] text-emerald-800 uppercase">
                                        Based on current admin settings
                                    </div>
                                </div>

                                <div className="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                                    <div className="rounded-lg border border-emerald-200 bg-white px-4 py-3">
                                        <p className="text-xs font-semibold tracking-[0.14em] text-slate-500 uppercase">Base amount</p>
                                        <p className="mt-1 text-lg font-semibold text-slate-950">{currencyFormatter.format(Number(preview.baseAmount))}</p>
                                    </div>
                                    <div className="rounded-lg border border-emerald-200 bg-white px-4 py-3">
                                        <p className="text-xs font-semibold tracking-[0.14em] text-slate-500 uppercase">Website charge</p>
                                        <p className="mt-1 text-lg font-semibold text-slate-950">{currencyFormatter.format(Number(preview.serviceChargeAmount))}</p>
                                    </div>
                                    <div className="rounded-lg border border-emerald-200 bg-white px-4 py-3">
                                        <p className="text-xs font-semibold tracking-[0.14em] text-slate-500 uppercase">Paystack charge</p>
                                        <p className="mt-1 text-lg font-semibold text-slate-950">{currencyFormatter.format(Number(preview.paystackChargeAmount))}</p>
                                    </div>
                                    <div className="rounded-lg border border-emerald-400 bg-emerald-900 px-4 py-3">
                                        <p className="text-xs font-semibold tracking-[0.14em] text-emerald-100 uppercase">Total payable</p>
                                        <p className="mt-1 text-lg font-semibold text-white">{currencyFormatter.format(Number(preview.totalAmount))}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="grid gap-3 md:col-span-2">
                            <div className="space-y-2">
                                <Label>Applicable program types</Label>
                                <p className="text-muted-foreground text-sm">
                                    Select one or more program types that should see this payment option on the student form.
                                </p>
                            </div>
                            <div className="grid gap-3 rounded-lg border p-4 md:grid-cols-2">
                                {programTypeOptions.map((programType) => (
                                    <div key={programType.value} className="flex items-start gap-3 rounded-md border border-dashed p-3">
                                        <Checkbox
                                            id={`program_type_${programType.value}`}
                                            checked={data.program_type_ids.includes(programType.value)}
                                            disabled={processing}
                                            onCheckedChange={(checked: CheckedState) => toggleProgramType(programType.value, checked)}
                                        />
                                        <Label htmlFor={`program_type_${programType.value}`} className="cursor-pointer leading-6">
                                            {programType.label}
                                        </Label>
                                    </div>
                                ))}
                            </div>
                            <InputError message={errors.program_type_ids} />
                        </div>

                        <div className="grid gap-3 md:col-span-2">
                            <div className="flex items-start gap-3 rounded-lg border p-4">
                                <Checkbox
                                    id="is_active"
                                    checked={data.is_active}
                                    disabled={processing}
                                    onCheckedChange={(checked: CheckedState) => setData('is_active', checked === true)}
                                />
                                <div className="space-y-1">
                                    <Label htmlFor="is_active" className="cursor-pointer">
                                        Active payment type
                                    </Label>
                                    <p className="text-muted-foreground text-sm">
                                        Inactive payment types stay in admin records but will not be available for student payment selection later.
                                    </p>
                                </div>
                            </div>
                            <InputError message={errors.is_active} />
                        </div>
                    </div>

                    <div className="flex flex-col gap-3 sm:flex-row sm:justify-end">
                        <Button type="button" variant="outline" asChild>
                            <Link href={cancelHref}>Cancel</Link>
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {submitLabel}
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}
