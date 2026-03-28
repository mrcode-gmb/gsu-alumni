import Heading from '@/components/heading';
import { FacultyForm, type FacultyFormData } from '@/components/faculties/faculty-form';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Faculties', href: '/admin/faculties' },
    { title: 'Create', href: '/admin/faculties/create' },
];

export default function CreateFaculty() {
    const { data, setData, post, processing, errors } = useForm<FacultyFormData>({
        name: '',
        is_active: true,
        display_order: '',
    });

    const submit: FormEventHandler<HTMLFormElement> = (event) => {
        event.preventDefault();
        post(route('admin.faculties.store'));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Faculty" />

            <div className="space-y-6 p-4">
                <Heading title="Create faculty" description="Add a faculty that can later group departments for student payment biodata selection." />

                <FacultyForm
                    title="Faculty details"
                    description="Define the faculty name, visibility status, and optional display order."
                    submitLabel="Create faculty"
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
