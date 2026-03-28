import Heading from '@/components/heading';
import { ProgramTypeForm, type ProgramTypeFormData } from '@/components/program-types/program-type-form';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type ProgramType } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';

const breadcrumbs = (programType: ProgramType): BreadcrumbItem[] => [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Program Types', href: '/admin/program-types' },
    { title: 'Edit', href: route('admin.program-types.edit', programType.id) },
];

interface EditProgramTypeProps {
    programType: ProgramType;
}

export default function EditProgramType({ programType }: EditProgramTypeProps) {
    const { data, setData, put, processing, errors } = useForm<ProgramTypeFormData>({
        name: programType.name,
        description: programType.description ?? '',
        is_active: programType.is_active,
        display_order: programType.display_order?.toString() ?? '',
    });

    const submit: FormEventHandler<HTMLFormElement> = (event) => {
        event.preventDefault();
        put(route('admin.program-types.update', programType.id));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs(programType)}>
            <Head title="Edit Program Type" />

            <div className="space-y-6 p-4">
                <Heading title={`Edit ${programType.name}`} description="Update the official program type details carefully." />

                <ProgramTypeForm
                    title="Program type details"
                    description="Edit the name, description, status, and display order."
                    submitLabel="Save changes"
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
