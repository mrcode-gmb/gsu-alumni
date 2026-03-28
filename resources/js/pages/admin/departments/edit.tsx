import Heading from '@/components/heading';
import { DepartmentForm, type DepartmentFormData } from '@/components/departments/department-form';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Department, type SelectOption } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';

interface EditDepartmentProps {
    department: Department;
    facultyOptions: SelectOption[];
}

export default function EditDepartment({ department, facultyOptions }: EditDepartmentProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Departments', href: '/admin/departments' },
        { title: 'Edit', href: route('admin.departments.edit', department.id) },
    ];

    const { data, setData, put, processing, errors } = useForm<DepartmentFormData>({
        faculty_id: department.faculty_id.toString(),
        name: department.name,
        is_active: department.is_active,
        display_order: department.display_order?.toString() ?? '',
    });

    const submit: FormEventHandler<HTMLFormElement> = (event) => {
        event.preventDefault();
        put(route('admin.departments.update', department.id));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit Department" />

            <div className="space-y-6 p-4">
                <Heading title={`Edit ${department.name}`} description="Update the department details and its faculty assignment carefully." />

                <DepartmentForm
                    title="Department details"
                    description="Edit the faculty, department name, status, and display order."
                    submitLabel="Save changes"
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
