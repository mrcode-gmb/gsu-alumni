import Heading from '@/components/heading';
import { PaymentTypeForm, type PaymentTypeFormData } from '@/components/payment-types/payment-type-form';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type ChargeSetting, type SelectOption } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Payment Types',
        href: '/admin/payment-types',
    },
    {
        title: 'Create',
        href: '/admin/payment-types/create',
    },
];

interface CreatePaymentTypeProps {
    programTypeOptions: SelectOption[];
    chargeSetting: ChargeSetting;
}

export default function CreatePaymentType({ programTypeOptions, chargeSetting }: CreatePaymentTypeProps) {
    const { data, setData, post, processing, errors } = useForm<PaymentTypeFormData>({
        name: '',
        amount: '',
        description: '',
        program_type_ids: [],
        is_active: true,
        display_order: '',
    });

    const submit: FormEventHandler<HTMLFormElement> = (event) => {
        event.preventDefault();
        post(route('admin.payment-types.store'));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Payment Type" />

            <div className="space-y-6 p-4">
                <Heading
                    title="Create payment type"
                    description="Add a new school-approved payment category. The current admin charge settings will calculate the extra two charge amounts automatically."
                />

                <PaymentTypeForm
                    title="Payment type details"
                    description="Define the payment name, amount, description, visibility status, and optional display order."
                    submitLabel="Create payment type"
                    cancelHref={route('admin.payment-types.index')}
                    programTypeOptions={programTypeOptions}
                    chargeSetting={chargeSetting}
                    data={data}
                    errors={errors}
                    processing={processing}
                    setData={setData}
                    onSubmit={submit}
                />
            </div>
        </AppLayout>
    );
}
