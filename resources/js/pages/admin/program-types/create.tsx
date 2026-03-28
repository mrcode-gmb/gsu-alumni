import Heading from '@/components/heading';
import { ProgramTypeForm, type ProgramTypeFormData } from '@/components/program-types/program-type-form';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Program Types', href: '/admin/program-types' },
    { title: 'Create', href: '/admin/program-types/create' },
];

export default function CreateProgramType() {
    const { data, setData, post, processing, errors } = useForm<ProgramTypeFormData>({
        name: '',
        description: '',
        is_active: true,
        display_order: '',
    });

    const submit: FormEventHandler<HTMLFormElement> = (event) => {
        event.preventDefault();
        post(route('admin.program-types.store'));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Program Type" />

            <div className="space-y-6 p-4">
                <Heading
                    title="Create program type"
                    description="Add an official GSU program type that can later be reused on student biodata and payment request forms."
                />

                <ProgramTypeForm
                    title="Program type details"
                    description="Define the program type name, a short description, visibility status, and optional display order."
                    submitLabel="Create program type"
                    cancelHref={route('admin.program-types.index')}
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
