import Heading from '@/components/heading';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type ProgramType, type ProgramTypeFilters, type ProgramTypeSummary, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { CirclePlus, GraduationCap, PencilLine, Power, PowerOff, Search, Trash2 } from 'lucide-react';
import { type FormEvent, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Program Types', href: '/admin/program-types' },
];

interface ProgramTypeIndexProps {
    programTypes: ProgramType[];
    filters: ProgramTypeFilters;
    summary: ProgramTypeSummary;
}

export default function ProgramTypeIndex({ programTypes, filters, summary }: ProgramTypeIndexProps) {
    const { flash } = usePage<SharedData>().props;
    const [search, setSearch] = useState(filters.search);
    const [programTypeToDelete, setProgramTypeToDelete] = useState<ProgramType | null>(null);

    const submitSearch = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        const trimmedSearch = search.trim();

        router.get(route('admin.program-types.index'), trimmedSearch ? { search: trimmedSearch } : {}, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const clearSearch = () => {
        setSearch('');
        router.get(route('admin.program-types.index'), {}, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const toggleStatus = (programType: ProgramType) => {
        router.patch(
            route('admin.program-types.status.update', programType.id),
            { is_active: !programType.is_active },
            { preserveScroll: true },
        );
    };

    const deleteProgramType = () => {
        if (!programTypeToDelete) {
            return;
        }

        router.delete(route('admin.program-types.destroy', programTypeToDelete.id), {
            preserveScroll: true,
            onFinish: () => setProgramTypeToDelete(null),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Program Types" />

            <div className="space-y-6 p-4">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <Heading
                        title="Program type management"
                        description="Manage the official GSU programme categories that can later feed student biodata and payment request flows."
                    />
                    <Button asChild>
                        <Link href={route('admin.program-types.create')}>
                            <CirclePlus />
                            Add program type
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
                            <CardDescription>Total program types</CardDescription>
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
                        <CardTitle>Search program types</CardTitle>
                        <CardDescription>Find program types quickly by name.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form className="flex flex-col gap-3 sm:flex-row" onSubmit={submitSearch}>
                            <div className="relative flex-1">
                                <Search className="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                                <Input
                                    value={search}
                                    onChange={(event) => setSearch(event.target.value)}
                                    placeholder="Search by program type name"
                                    className="pl-9"
                                />
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
                        <CardTitle>Program types</CardTitle>
                        <CardDescription>{programTypes.length === 1 ? '1 result found.' : `${programTypes.length} results found.`}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        {programTypes.length === 0 ? (
                            <div className="text-muted-foreground rounded-lg border border-dashed px-6 py-10 text-center text-sm">
                                No program types matched your search. Create one or adjust the search term.
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full min-w-[980px] text-sm">
                                    <thead>
                                        <tr className="border-b text-left">
                                            <th className="px-3 py-3 font-medium">Program type</th>
                                            <th className="px-3 py-3 font-medium">Description</th>
                                            <th className="px-3 py-3 font-medium">Status</th>
                                            <th className="px-3 py-3 font-medium">Order</th>
                                            <th className="px-3 py-3 font-medium">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {programTypes.map((programType) => (
                                            <tr key={programType.id} className="border-b align-top last:border-b-0">
                                                <td className="px-3 py-4">
                                                    <div className="flex items-center gap-2 font-medium">
                                                        <GraduationCap className="size-4 text-slate-500" />
                                                        {programType.name}
                                                    </div>
                                                </td>
                                                <td className="px-3 py-4 text-slate-600">
                                                    {programType.description || <span className="text-muted-foreground">No description</span>}
                                                </td>
                                                <td className="px-3 py-4">
                                                    <Badge variant={programType.is_active ? 'default' : 'secondary'}>
                                                        {programType.is_active ? 'Active' : 'Inactive'}
                                                    </Badge>
                                                </td>
                                                <td className="px-3 py-4">
                                                    {programType.display_order ?? <span className="text-muted-foreground">Auto</span>}
                                                </td>
                                                <td className="px-3 py-4">
                                                    <div className="flex flex-wrap gap-2">
                                                        <Button size="sm" variant="outline" asChild>
                                                            <Link href={route('admin.program-types.edit', programType.id)}>
                                                                <PencilLine />
                                                                Edit
                                                            </Link>
                                                        </Button>
                                                        <Button size="sm" variant="outline" onClick={() => toggleStatus(programType)}>
                                                            {programType.is_active ? <PowerOff /> : <Power />}
                                                            {programType.is_active ? 'Deactivate' : 'Activate'}
                                                        </Button>
                                                        <Button
                                                            size="sm"
                                                            variant="destructive"
                                                            disabled={!programType.can_delete}
                                                            onClick={() => setProgramTypeToDelete(programType)}
                                                        >
                                                            <Trash2 />
                                                            Delete
                                                        </Button>
                                                    </div>
                                                    {!programType.can_delete && (
                                                        <p className="text-muted-foreground mt-2 text-xs">
                                                            Used program types cannot be deleted.
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

            <Dialog open={programTypeToDelete !== null} onOpenChange={(open) => !open && setProgramTypeToDelete(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete program type</DialogTitle>
                        <DialogDescription>
                            {programTypeToDelete
                                ? `Delete "${programTypeToDelete.name}" permanently?`
                                : 'Delete this program type permanently.'}
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setProgramTypeToDelete(null)}>
                            Cancel
                        </Button>
                        <Button variant="destructive" onClick={deleteProgramType}>
                            Delete program type
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
