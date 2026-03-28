import Heading from '@/components/heading';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Department, type DepartmentFilters, type DepartmentSummary, type SelectOption, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { CirclePlus, PencilLine, Power, PowerOff, Search, Trash2 } from 'lucide-react';
import { type FormEvent, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Departments', href: '/admin/departments' },
];

const defaultFilters: DepartmentFilters = {
    search: '',
    faculty_id: '',
};

interface DepartmentIndexProps {
    departments: Department[];
    filters: DepartmentFilters;
    summary: DepartmentSummary;
    facultyOptions: SelectOption[];
}

export default function DepartmentIndex({ departments, filters, summary, facultyOptions }: DepartmentIndexProps) {
    const { flash } = usePage<SharedData>().props;
    const [form, setForm] = useState<DepartmentFilters>(filters);
    const [departmentToDelete, setDepartmentToDelete] = useState<Department | null>(null);

    const submitFilters = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        const payload = Object.fromEntries(Object.entries(form).filter(([, value]) => value !== ''));

        router.get(route('admin.departments.index'), payload, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const clearFilters = () => {
        setForm(defaultFilters);

        router.get(route('admin.departments.index'), {}, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const toggleStatus = (department: Department) => {
        router.patch(
            route('admin.departments.status.update', department.id),
            { is_active: !department.is_active },
            { preserveScroll: true },
        );
    };

    const deleteDepartment = () => {
        if (!departmentToDelete) {
            return;
        }

        router.delete(route('admin.departments.destroy', departmentToDelete.id), {
            preserveScroll: true,
            onFinish: () => setDepartmentToDelete(null),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Departments" />

            <div className="space-y-6 p-4">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <Heading
                        title="Department management"
                        description="Create and manage departments under each faculty for cleaner student biodata collection and admin filtering."
                    />
                    <Button asChild>
                        <Link href={route('admin.departments.create')}>
                            <CirclePlus />
                            Add department
                        </Link>
                    </Button>
                </div>

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

                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader className="gap-0.5">
                            <CardDescription>Total departments</CardDescription>
                            <CardTitle className="text-3xl">{summary.total}</CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="gap-0.5">
                            <CardDescription>Active</CardDescription>
                            <CardTitle className="text-3xl">{summary.active}</CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="gap-0.5">
                            <CardDescription>Inactive</CardDescription>
                            <CardTitle className="text-3xl">{summary.inactive}</CardTitle>
                        </CardHeader>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Search and filter departments</CardTitle>
                        <CardDescription>Find departments by name and optionally narrow them to a specific faculty.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form className="grid gap-4 lg:grid-cols-[1fr,280px,auto,auto]" onSubmit={submitFilters}>
                            <div className="grid gap-2">
                                <Label htmlFor="search">Search</Label>
                                <div className="relative">
                                    <Search className="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                                    <Input
                                        id="search"
                                        value={form.search}
                                        onChange={(event) => setForm((current) => ({ ...current, search: event.target.value }))}
                                        placeholder="Search by department name"
                                        className="pl-9"
                                    />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="faculty_id">Faculty</Label>
                                <Select value={form.faculty_id || undefined} onValueChange={(value) => setForm((current) => ({ ...current, faculty_id: value }))}>
                                    <SelectTrigger id="faculty_id">
                                        <SelectValue placeholder="All faculties" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {facultyOptions.map((faculty) => (
                                            <SelectItem key={faculty.value} value={faculty.value}>
                                                {faculty.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="flex items-end">
                                <Button type="submit">Apply</Button>
                            </div>

                            <div className="flex items-end">
                                <Button type="button" variant="outline" onClick={clearFilters}>
                                    Clear
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Departments</CardTitle>
                        <CardDescription>{departments.length === 1 ? '1 result found.' : `${departments.length} results found.`}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        {departments.length === 0 ? (
                            <div className="text-muted-foreground rounded-lg border border-dashed px-6 py-10 text-center text-sm">
                                No departments matched your current filters. Create one or adjust the filter values.
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full min-w-[980px] text-sm">
                                    <thead>
                                        <tr className="border-b text-left">
                                            <th className="px-3 py-3 font-medium">Department name</th>
                                            <th className="px-3 py-3 font-medium">Faculty</th>
                                            <th className="px-3 py-3 font-medium">Status</th>
                                            <th className="px-3 py-3 font-medium">Order</th>
                                            <th className="px-3 py-3 font-medium">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {departments.map((department) => (
                                            <tr key={department.id} className="border-b align-top last:border-b-0">
                                                <td className="px-3 py-4 font-medium">{department.name}</td>
                                                <td className="px-3 py-4">{department.faculty_name ?? 'No faculty assigned'}</td>
                                                <td className="px-3 py-4">
                                                    <Badge variant={department.is_active ? 'default' : 'secondary'}>
                                                        {department.is_active ? 'Active' : 'Inactive'}
                                                    </Badge>
                                                </td>
                                                <td className="px-3 py-4">
                                                    {department.display_order ?? <span className="text-muted-foreground">Auto</span>}
                                                </td>
                                                <td className="px-3 py-4">
                                                    <div className="flex flex-wrap gap-2">
                                                        <Button size="sm" variant="outline" asChild>
                                                            <Link href={route('admin.departments.edit', department.id)}>
                                                                <PencilLine />
                                                                Edit
                                                            </Link>
                                                        </Button>
                                                        <Button size="sm" variant="outline" onClick={() => toggleStatus(department)}>
                                                            {department.is_active ? <PowerOff /> : <Power />}
                                                            {department.is_active ? 'Deactivate' : 'Activate'}
                                                        </Button>
                                                        <Button
                                                            size="sm"
                                                            variant="destructive"
                                                            disabled={!department.can_delete}
                                                            onClick={() => setDepartmentToDelete(department)}
                                                        >
                                                            <Trash2 />
                                                            Delete
                                                        </Button>
                                                    </div>
                                                    {!department.can_delete && (
                                                        <p className="text-muted-foreground mt-2 text-xs">
                                                            Used departments cannot be deleted.
                                                        </p>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            <Dialog open={departmentToDelete !== null} onOpenChange={(open) => !open && setDepartmentToDelete(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete department</DialogTitle>
                        <DialogDescription>
                            {departmentToDelete
                                ? `Delete "${departmentToDelete.name}" permanently? This should only be done for unused departments.`
                                : 'Delete this department permanently.'}
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDepartmentToDelete(null)}>
                            Cancel
                        </Button>
                        <Button variant="destructive" onClick={deleteDepartment}>
                            Delete department
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
