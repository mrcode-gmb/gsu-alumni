import Heading from '@/components/heading';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Faculty, type FacultyFilters, type FacultySummary, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Building2, CirclePlus, PencilLine, Power, PowerOff, Search, Trash2 } from 'lucide-react';
import { type FormEvent, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Faculties', href: '/admin/faculties' },
];

interface FacultyIndexProps {
    faculties: Faculty[];
    filters: FacultyFilters;
    summary: FacultySummary;
}

export default function FacultyIndex({ faculties, filters, summary }: FacultyIndexProps) {
    const { flash } = usePage<SharedData>().props;
    const [search, setSearch] = useState(filters.search);
    const [facultyToDelete, setFacultyToDelete] = useState<Faculty | null>(null);

    const submitSearch = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        const trimmedSearch = search.trim();

        router.get(route('admin.faculties.index'), trimmedSearch ? { search: trimmedSearch } : {}, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const clearSearch = () => {
        setSearch('');
        router.get(route('admin.faculties.index'), {}, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const toggleStatus = (faculty: Faculty) => {
        router.patch(
            route('admin.faculties.status.update', faculty.id),
            { is_active: !faculty.is_active },
            { preserveScroll: true },
        );
    };

    const deleteFaculty = () => {
        if (!facultyToDelete) {
            return;
        }

        router.delete(route('admin.faculties.destroy', facultyToDelete.id), {
            preserveScroll: true,
            onFinish: () => setFacultyToDelete(null),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Faculties" />

            <div className="space-y-6 p-4">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <Heading
                        title="Faculty management"
                        description="Create and manage the approved faculty list used across student biodata and admin records."
                    />
                    <Button asChild>
                        <Link href={route('admin.faculties.create')}>
                            <CirclePlus />
                            Add faculty
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
                            <CardDescription>Total faculties</CardDescription>
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
                        <CardTitle>Search faculties</CardTitle>
                        <CardDescription>Find faculties quickly by name.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form className="flex flex-col gap-3 sm:flex-row" onSubmit={submitSearch}>
                            <div className="relative flex-1">
                                <Search className="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                                <Input value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Search by faculty name" className="pl-9" />
                            </div>
                            <Button type="submit">Search</Button>
                            <Button type="button" variant="outline" onClick={clearSearch}>
                                Clear
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Faculties</CardTitle>
                        <CardDescription>{faculties.length === 1 ? '1 result found.' : `${faculties.length} results found.`}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        {faculties.length === 0 ? (
                            <div className="text-muted-foreground rounded-lg border border-dashed px-6 py-10 text-center text-sm">
                                No faculties matched your search. Create one or adjust the search term.
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full min-w-[920px] text-sm">
                                    <thead>
                                        <tr className="border-b text-left">
                                            <th className="px-3 py-3 font-medium">Faculty name</th>
                                            <th className="px-3 py-3 font-medium">Departments</th>
                                            <th className="px-3 py-3 font-medium">Status</th>
                                            <th className="px-3 py-3 font-medium">Order</th>
                                            <th className="px-3 py-3 font-medium">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {faculties.map((faculty) => (
                                            <tr key={faculty.id} className="border-b align-top last:border-b-0">
                                                <td className="px-3 py-4">
                                                    <div className="flex items-center gap-2 font-medium">
                                                        <Building2 className="size-4 text-slate-500" />
                                                        {faculty.name}
                                                    </div>
                                                </td>
                                                <td className="px-3 py-4">{faculty.departments_count}</td>
                                                <td className="px-3 py-4">
                                                    <Badge variant={faculty.is_active ? 'default' : 'secondary'}>
                                                        {faculty.is_active ? 'Active' : 'Inactive'}
                                                    </Badge>
                                                </td>
                                                <td className="px-3 py-4">
                                                    {faculty.display_order ?? <span className="text-muted-foreground">Auto</span>}
                                                </td>
                                                <td className="px-3 py-4">
                                                    <div className="flex flex-wrap gap-2">
                                                        <Button size="sm" variant="outline" asChild>
                                                            <Link href={route('admin.faculties.edit', faculty.id)}>
                                                                <PencilLine />
                                                                Edit
                                                            </Link>
                                                        </Button>
                                                        <Button size="sm" variant="outline" onClick={() => toggleStatus(faculty)}>
                                                            {faculty.is_active ? <PowerOff /> : <Power />}
                                                            {faculty.is_active ? 'Deactivate' : 'Activate'}
                                                        </Button>
                                                        <Button size="sm" variant="destructive" disabled={!faculty.can_delete} onClick={() => setFacultyToDelete(faculty)}>
                                                            <Trash2 />
                                                            Delete
                                                        </Button>
                                                    </div>
                                                    {!faculty.can_delete && (
                                                        <p className="text-muted-foreground mt-2 text-xs">
                                                            Faculties with departments or payment records cannot be deleted.
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

            <Dialog open={facultyToDelete !== null} onOpenChange={(open) => !open && setFacultyToDelete(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete faculty</DialogTitle>
                        <DialogDescription>
                            {facultyToDelete
                                ? `Delete "${facultyToDelete.name}" permanently? This should only be done for unused faculties without departments.`
                                : 'Delete this faculty permanently.'}
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setFacultyToDelete(null)}>
                            Cancel
                        </Button>
                        <Button variant="destructive" onClick={deleteFaculty}>
                            Delete faculty
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
