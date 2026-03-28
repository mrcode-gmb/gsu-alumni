import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Link } from '@inertiajs/react';
import { type CheckedState } from '@radix-ui/react-checkbox';
import { type FormEventHandler } from 'react';

export type FacultyFormData = {
    name: string;
    is_active: boolean;
    display_order: string;
};

interface FacultyFormProps {
    title: string;
    description: string;
    submitLabel: string;
    cancelHref: string;
    data: FacultyFormData;
    errors: Partial<Record<keyof FacultyFormData, string>>;
    processing: boolean;
    setData: <K extends keyof FacultyFormData>(key: K, value: FacultyFormData[K]) => void;
    onSubmit: FormEventHandler<HTMLFormElement>;
}

export function FacultyForm({
    title,
    description,
    submitLabel,
    cancelHref,
    data,
    errors,
    processing,
    setData,
    onSubmit,
}: FacultyFormProps) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>{title}</CardTitle>
                <CardDescription>{description}</CardDescription>
            </CardHeader>
            <CardContent>
                <form className="space-y-6" onSubmit={onSubmit}>
                    <div className="grid gap-6 md:grid-cols-2">
                        <div className="grid gap-2 md:col-span-2">
                            <Label htmlFor="name">Faculty name</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(event) => setData('name', event.target.value)}
                                placeholder="Faculty of Sciences"
                                disabled={processing}
                                maxLength={255}
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="display_order">Display order</Label>
                            <Input
                                id="display_order"
                                type="number"
                                min="0"
                                step="1"
                                value={data.display_order}
                                onChange={(event) => setData('display_order', event.target.value)}
                                placeholder="Optional"
                                disabled={processing}
                            />
                            <p className="text-muted-foreground text-xs">Lower values appear earlier in admin and student selection lists.</p>
                            <InputError message={errors.display_order} />
                        </div>

                        <div className="grid gap-3 md:col-span-2">
                            <div className="flex items-start gap-3 rounded-lg border p-4">
                                <Checkbox
                                    id="is_active"
                                    checked={data.is_active}
                                    disabled={processing}
                                    onCheckedChange={(checked: CheckedState) => setData('is_active', checked === true)}
                                />
                                <div className="space-y-1">
                                    <Label htmlFor="is_active" className="cursor-pointer">
                                        Active faculty
                                    </Label>
                                    <p className="text-muted-foreground text-sm">
                                        Inactive faculties stay in admin records but will not appear in the student payment form.
                                    </p>
                                </div>
                            </div>
                            <InputError message={errors.is_active} />
                        </div>
                    </div>

                    <div className="flex flex-col gap-3 sm:flex-row sm:justify-end">
                        <Button type="button" variant="outline" asChild>
                            <Link href={cancelHref}>Cancel</Link>
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {submitLabel}
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}
