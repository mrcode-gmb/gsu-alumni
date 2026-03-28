import Heading from '@/components/heading';
import { DepartmentForm, type DepartmentFormData } from '@/components/departments/department-form';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SelectOption } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Departments', href: '/admin/departments' },
    { title: 'Create', href: '/admin/departments/create' },
];

interface CreateDepartmentProps {
    facultyOptions: SelectOption[];
}

export default function CreateDepartment({ facultyOptions }: CreateDepartmentProps) {
    const { data, setData, post, processing, errors } = useForm<DepartmentFormData>({
        faculty_id: '',
        name: '',
        is_active: true,
        display_order: '',
    });

    const submit: FormEventHandler<HTMLFormElement> = (event) => {
        event.preventDefault();
        post(route('admin.departments.store'));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Department" />

            <div className="space-y-6 p-4">
                <Heading title="Create department" description="Add a department and connect it to the correct faculty for student biodata selection." />

                <DepartmentForm
                    title="Department details"
                    description="Define the faculty, department name, visibility status, and optional display order."
                    submitLabel="Create department"
                    cancelHref={route('admin.departments.index')}
                    facultyOptions={facultyOptions}
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
