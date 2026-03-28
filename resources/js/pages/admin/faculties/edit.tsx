import Heading from '@/components/heading';
import { FacultyForm, type FacultyFormData } from '@/components/faculties/faculty-form';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Faculty } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';

interface EditFacultyProps {
    faculty: Faculty;
}

export default function EditFaculty({ faculty }: EditFacultyProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Faculties', href: '/admin/faculties' },
        { title: 'Edit', href: route('admin.faculties.edit', faculty.id) },
    ];

    const { data, setData, put, processing, errors } = useForm<FacultyFormData>({
        name: faculty.name,
        is_active: faculty.is_active,
        display_order: faculty.display_order?.toString() ?? '',
    });

    const submit: FormEventHandler<HTMLFormElement> = (event) => {
        event.preventDefault();
        put(route('admin.faculties.update', faculty.id));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit Faculty" />

            <div className="space-y-6 p-4">
                <Heading title={`Edit ${faculty.name}`} description="Update the faculty details while keeping the student biodata structure organized." />

                <FacultyForm
                    title="Faculty details"
                    description="Edit the faculty name, status, and display order."
                    submitLabel="Save changes"
                    cancelHref={route('admin.faculties.index')}
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
