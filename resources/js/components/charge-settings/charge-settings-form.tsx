import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { type ChargeCalculationMode, type ChargePreviewSample, type SelectOption } from '@/types';
import { type FormEventHandler } from 'react';

const currencyFormatter = new Intl.NumberFormat('en-NG', {
    style: 'currency',
    currency: 'NGN',
    minimumFractionDigits: 2,
});

export type ChargeSettingFormData = {
    portal_charge_mode: ChargeCalculationMode;
    portal_charge_value: string;
    paystack_percentage_rate: string;
    paystack_flat_fee: string;
    paystack_flat_fee_threshold: string;
    paystack_charge_cap: string;
};

interface ChargeSettingsFormProps {
    data: ChargeSettingFormData;
    errors: Partial<Record<keyof ChargeSettingFormData, string>>;
    processing: boolean;
    modeOptions: SelectOption[];
    previewSamples: ChargePreviewSample[];
    updatedAt: string | null;
    updatedByName: string | null;
    setData: <K extends keyof ChargeSettingFormData>(key: K, value: ChargeSettingFormData[K]) => void;
    onSubmit: FormEventHandler<HTMLFormElement>;
}

export function ChargeSettingsForm({
    data,
    errors,
    processing,
    modeOptions,
    previewSamples,
    updatedAt,
    updatedByName,
    setData,
    onSubmit,
}: ChargeSettingsFormProps) {
    return (
        <div className="grid gap-6 xl:grid-cols-[minmax(0,1.2fr),minmax(320px,0.8fr)]">
            <Card>
                <CardHeader>
                    <CardTitle>Charge configuration</CardTitle>
                    <CardDescription>
                        Configure the two separate charge types used by the portal: your own custom charge and the Paystack payment gateway charge. Enter `0` to disable any field.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form className="space-y-8" onSubmit={onSubmit}>
                        <div className="grid gap-4 lg:grid-cols-2">
                            <div className="rounded-2xl border border-emerald-200 bg-emerald-50/80 p-4">
                                <p className="text-xs font-semibold tracking-[0.16em] text-emerald-800 uppercase">Charge type 1</p>
                                <h2 className="mt-2 text-base font-semibold text-emerald-950">Our own charge</h2>
                                <p className="mt-2 text-sm leading-6 text-emerald-950/85">
                                    This is the extra charge you decide to add for the portal, school processing, or any internal handling fee.
                                </p>
                            </div>

                            <div className="rounded-2xl border border-sky-200 bg-sky-50/80 p-4">
                                <p className="text-xs font-semibold tracking-[0.16em] text-sky-800 uppercase">Charge type 2</p>
                                <h2 className="mt-2 text-base font-semibold text-sky-950">Paystack gateway charge</h2>
                                <p className="mt-2 text-sm leading-6 text-sky-950/85">
                                    This is separate from your own charge. It represents the payment gateway charge you want the student to cover.
                                </p>
                            </div>
                        </div>

                        <section className="space-y-4">
                            <div>
                                <h2 className="text-base font-semibold text-slate-950">Our own charge</h2>
                                <p className="mt-1 text-sm text-slate-600">
                                    This is your own custom charge, separate from the payment gateway. It is calculated on the payment type amount before the Paystack gateway charge is added.
                                </p>
                            </div>

                            <div className="grid gap-6 md:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="portal_charge_mode">Charge method</Label>
                                    <Select
                                        value={data.portal_charge_mode}
                                        onValueChange={(value) => setData('portal_charge_mode', value as ChargeCalculationMode)}
                                        disabled={processing}
                                    >
                                        <SelectTrigger id="portal_charge_mode" aria-invalid={errors.portal_charge_mode ? true : undefined}>
                                            <SelectValue placeholder="Select a method" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {modeOptions.map((option) => (
                                                <SelectItem key={option.value} value={option.value}>
                                                    {option.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.portal_charge_mode} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="portal_charge_value">
                                        {data.portal_charge_mode === 'percentage' ? 'Our charge percentage (%)' : 'Our fixed charge amount (NGN)'}
                                    </Label>
                                    <Input
                                        id="portal_charge_value"
                                        type="number"
                                        min="0"
                                        step={data.portal_charge_mode === 'percentage' ? '0.01' : '0.01'}
                                        value={data.portal_charge_value}
                                        onChange={(event) => setData('portal_charge_value', event.target.value)}
                                        disabled={processing}
                                    />
                                    <InputError message={errors.portal_charge_value} />
                                </div>
                            </div>
                        </section>

                        <section className="space-y-4">
                            <div>
                                <h2 className="text-base font-semibold text-slate-950">Paystack gateway charge</h2>
                                <p className="mt-1 text-sm text-slate-600">
                                    These values are only for the payment gateway side. They let you recover the Paystack charge from students while keeping it separate from your own custom charge.
                                </p>
                            </div>

                            <div className="grid gap-6 md:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="paystack_percentage_rate">Percentage rate (%)</Label>
                                    <Input
                                        id="paystack_percentage_rate"
                                        type="number"
                                        min="0"
                                        step="0.0001"
                                        value={data.paystack_percentage_rate}
                                        onChange={(event) => setData('paystack_percentage_rate', event.target.value)}
                                        disabled={processing}
                                    />
                                    <InputError message={errors.paystack_percentage_rate} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="paystack_flat_fee">Additional flat fee (NGN)</Label>
                                    <Input
                                        id="paystack_flat_fee"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value={data.paystack_flat_fee}
                                        onChange={(event) => setData('paystack_flat_fee', event.target.value)}
                                        disabled={processing}
                                    />
                                    <InputError message={errors.paystack_flat_fee} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="paystack_flat_fee_threshold">Flat-fee threshold (NGN)</Label>
                                    <Input
                                        id="paystack_flat_fee_threshold"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value={data.paystack_flat_fee_threshold}
                                        onChange={(event) => setData('paystack_flat_fee_threshold', event.target.value)}
                                        disabled={processing}
                                    />
                                    <InputError message={errors.paystack_flat_fee_threshold} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="paystack_charge_cap">Charge cap (NGN)</Label>
                                    <Input
                                        id="paystack_charge_cap"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value={data.paystack_charge_cap}
                                        onChange={(event) => setData('paystack_charge_cap', event.target.value)}
                                        disabled={processing}
                                    />
                                    <InputError message={errors.paystack_charge_cap} />
                                </div>
                            </div>
                        </section>

                        <div className="flex flex-col gap-3 sm:flex-row sm:justify-end">
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Saving changes...' : 'Save charge settings'}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <div className="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>What happens next</CardTitle>
                        <CardDescription>These settings affect new or refreshed pending requests only.</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-3 text-sm leading-6 text-slate-600">
                        <p>Students will see the base amount, your own charge, the Paystack gateway charge, and the final total before checkout.</p>
                        <p>The charge breakdown is saved onto each payment request so later verification, receipts, and admin records stay consistent even if you update settings again.</p>
                        <p>Already-successful payments and issued receipts are not changed by future updates here.</p>
                        <div className="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <p className="text-xs font-semibold tracking-[0.16em] text-slate-500 uppercase">Last updated</p>
                            <p className="mt-1 font-medium text-slate-900">
                                {updatedAt ? new Date(updatedAt).toLocaleString() : 'Not updated yet'}
                            </p>
                            <p className="mt-1 text-slate-600">{updatedByName ? `By ${updatedByName}` : 'No user recorded yet'}</p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Preview examples</CardTitle>
                        <CardDescription>Quick examples showing how the current settings affect common payment type amounts.</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {previewSamples.map((sample) => (
                            <div key={sample.base_amount} className="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <div className="flex items-center justify-between gap-3">
                                    <p className="text-sm font-semibold text-slate-900">Base amount</p>
                                    <p className="text-sm font-semibold text-slate-950">{currencyFormatter.format(Number(sample.base_amount))}</p>
                                </div>
                                <dl className="mt-3 space-y-2 text-sm text-slate-600">
                                    <div className="flex items-center justify-between gap-3">
                                        <dt>Our own charge</dt>
                                        <dd className="font-medium text-slate-900">{currencyFormatter.format(Number(sample.portal_charge_amount))}</dd>
                                    </div>
                                    <div className="flex items-center justify-between gap-3">
                                        <dt>Paystack gateway charge</dt>
                                        <dd className="font-medium text-slate-900">{currencyFormatter.format(Number(sample.paystack_charge_amount))}</dd>
                                    </div>
                                    <div className="flex items-center justify-between gap-3 border-t border-slate-200 pt-2">
                                        <dt className="font-semibold text-slate-900">Total payable</dt>
                                        <dd className="font-semibold text-slate-950">{currencyFormatter.format(Number(sample.total_amount))}</dd>
                                    </div>
                                </dl>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}
