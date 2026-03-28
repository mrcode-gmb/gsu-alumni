import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Link } from '@inertiajs/react';
import { type CheckedState } from '@radix-ui/react-checkbox';
import { type FormEventHandler } from 'react';

export type ProgramTypeFormData = {
    name: string;
    description: string;
    is_active: boolean;
    display_order: string;
};

interface ProgramTypeFormProps {
    title: string;
    description: string;
    submitLabel: string;
    cancelHref: string;
    data: ProgramTypeFormData;
    errors: Partial<Record<keyof ProgramTypeFormData, string>>;
    processing: boolean;
    setData: <K extends keyof ProgramTypeFormData>(key: K, value: ProgramTypeFormData[K]) => void;
    onSubmit: FormEventHandler<HTMLFormElement>;
}

export function ProgramTypeForm({
    title,
    description,
    submitLabel,
    cancelHref,
    data,
    errors,
    processing,
    setData,
    onSubmit,
}: ProgramTypeFormProps) {
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
                            <Label htmlFor="name">Program type name</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(event) => setData('name', event.target.value)}
                                placeholder="Undergraduate"
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
                            <p className="text-muted-foreground text-xs">Lower values appear earlier in admin lists and future student selections.</p>
                            <InputError message={errors.display_order} />
                        </div>

                        <div className="grid gap-2 md:col-span-2">
                            <Label htmlFor="description">Description</Label>
                            <Textarea
                                id="description"
                                value={data.description}
                                onChange={(event) => setData('description', event.target.value)}
                                placeholder="Short note about when this program type should be used."
                                disabled={processing}
                                maxLength={1000}
                            />
                            <InputError message={errors.description} />
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
                                        Active program type
                                    </Label>
                                    <p className="text-muted-foreground text-sm">
                                        Inactive program types stay available in admin records but can be hidden from student-facing selection later.
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
