import Heading from '@/components/heading';
import { ChargeSettingsForm, type ChargeSettingFormData } from '@/components/charge-settings/charge-settings-form';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type ChargePreviewSample, type ChargeSetting, type SelectOption, type SharedData } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/react';
import { type FormEventHandler } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Charge Settings', href: '/admin/charge-settings' },
];

interface EditChargeSettingsProps {
    chargeSetting: ChargeSetting;
    modeOptions: SelectOption[];
    previewSamples: ChargePreviewSample[];
}

export default function EditChargeSettings({ chargeSetting, modeOptions, previewSamples }: EditChargeSettingsProps) {
    const { flash } = usePage<SharedData>().props;
    const { data, setData, put, processing, errors } = useForm<ChargeSettingFormData>({
        portal_charge_mode: chargeSetting.portal_charge_mode,
        portal_charge_value: chargeSetting.portal_charge_value,
        paystack_percentage_rate: chargeSetting.paystack_percentage_rate,
        paystack_flat_fee: chargeSetting.paystack_flat_fee,
        paystack_flat_fee_threshold: chargeSetting.paystack_flat_fee_threshold,
    });

    const submit: FormEventHandler<HTMLFormElement> = (event) => {
        event.preventDefault();
        put(route('admin.charge-settings.update'));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Charge Settings" />

            <div className="space-y-6 p-4">
                <Heading
                    title="Charge settings"
                    description="Manage the two separate charges used by the portal: your website service charge and the Paystack payment gateway charge."
                />

                {flash.success && (
                    <Alert>
                        <AlertTitle>Success</AlertTitle>
                        <AlertDescription>{flash.success}</AlertDescription>
                    </Alert>
                )}

                {flash.error && (
                    <Alert variant="destructive">
                        <AlertTitle>Action blocked</AlertTitle>
                        <AlertDescription>{flash.error}</AlertDescription>
                    </Alert>
                )}

                <ChargeSettingsForm
                    data={data}
                    errors={errors}
                    processing={processing}
                    modeOptions={modeOptions}
                    previewSamples={previewSamples}
                    updatedAt={chargeSetting.updated_at}
                    updatedByName={chargeSetting.updated_by_name}
                    setData={setData}
                    onSubmit={submit}
                />
            </div>
        </AppLayout>
    );
}
