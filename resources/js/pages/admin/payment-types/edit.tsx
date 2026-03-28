import Heading from '@/components/heading';
import { PaymentTypeForm, type PaymentTypeFormData } from '@/components/payment-types/payment-type-form';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type PaymentType, type SelectOption } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';

interface EditPaymentTypeProps {
    paymentType: PaymentType;
    programTypeOptions: SelectOption[];
}

export default function EditPaymentType({ paymentType, programTypeOptions }: EditPaymentTypeProps) {
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
            title: 'Edit',
            href: route('admin.payment-types.edit', paymentType.id),
        },
    ];

    const { data, setData, put, processing, errors } = useForm<PaymentTypeFormData>({
        name: paymentType.name,
        amount: paymentType.amount,
        description: paymentType.description ?? '',
        program_type_ids: paymentType.program_type_ids,
        is_active: paymentType.is_active,
        display_order: paymentType.display_order?.toString() ?? '',
    });

    const submit: FormEventHandler<HTMLFormElement> = (event) => {
        event.preventDefault();
        put(route('admin.payment-types.update', paymentType.id));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit Payment Type" />

            <div className="space-y-6 p-4">
                <Heading
                    title={`Edit ${paymentType.name}`}
                    description="Update the payment details while preserving future compatibility with payment records."
                />

                <PaymentTypeForm
                    title="Payment type details"
                    description="Edit the payment name, amount, description, status, and display order."
                    submitLabel="Save changes"
                    cancelHref={route('admin.payment-types.index')}
                    programTypeOptions={programTypeOptions}
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
