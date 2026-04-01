import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import PortalLayout from '@/layouts/portal-layout';
import { type SharedData } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/react';
import { FileSearch, Printer, ShieldCheck } from 'lucide-react';
import { type FormEventHandler } from 'react';

type ReceiptLookupForm = {
    email: string;
    matric_number: string;
};

export default function StudentReceiptLookup() {
    const { flash } = usePage<SharedData>().props;
    const { data, setData, post, processing, errors } = useForm<ReceiptLookupForm>({
        email: '',
        matric_number: '',
    });

    const submit: FormEventHandler<HTMLFormElement> = (event) => {
        event.preventDefault();
        post(route('student-receipts.search'));
    };

    return (
        <>
            <Head title="Receipt Lookup" />

            <PortalLayout
                eyebrow="Receipt Lookup"
                title="Find and reprint a verified payment receipt"
                description="Use the same email address and matric number used during payment to reopen the latest verified receipt safely."
                aside={
                    <div className="grid gap-4">
                        <Card className="border-emerald-100 bg-emerald-50/80">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-emerald-950">
                                    <ShieldCheck className="size-5" />
                                    Safe access
                                </CardTitle>
                                <CardDescription className="text-emerald-800/80">
                                    Receipts are not exposed by guessable URLs. The lookup form matches the member email address with the matric number first.
                                </CardDescription>
                            </CardHeader>
                        </Card>

                        <Card className="border-slate-200 bg-white/90">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Printer className="size-5" />
                                    What you need
                                </CardTitle>
                                <CardDescription>Only successfully verified payments can produce a receipt.</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-3 text-sm leading-6 text-slate-600">
                                <p>Use the same email address entered during the payment request.</p>
                                <p>Enter the same matric number used on the payment request.</p>
                                <p>If the member has more than one successful payment, the latest receipt will open first.</p>
                            </CardContent>
                        </Card>
                    </div>
                }
            >
                <Card className="border-slate-200 bg-white/95 shadow-sm">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <FileSearch className="size-5" />
                            Receipt details
                        </CardTitle>
                        <CardDescription>Provide the receipt number and matric number exactly as recorded.</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        {flash.success && (
                            <Alert>
                                <AlertTitle>Lookup update</AlertTitle>
                                <AlertDescription>{flash.success}</AlertDescription>
                            </Alert>
                        )}

                        {flash.error && (
                            <Alert variant="destructive">
                                <AlertTitle>Lookup update</AlertTitle>
                                <AlertDescription>{flash.error}</AlertDescription>
                            </Alert>
                        )}

                        <form className="space-y-5" onSubmit={submit}>
                            <div className="grid gap-5">
                                <div className="grid gap-2">
                                    <Label htmlFor="email">Email address</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        value={data.email}
                                        onChange={(event) => setData('email', event.target.value)}
                                        placeholder="member@example.com"
                                        disabled={processing}
                                    />
                                    <InputError message={errors.email} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="matric_number">Matric number</Label>
                                    <Input
                                        id="matric_number"
                                        value={data.matric_number}
                                        onChange={(event) => setData('matric_number', event.target.value)}
                                        placeholder="GSU/19/1234"
                                        disabled={processing}
                                    />
                                    <InputError message={errors.matric_number} />
                                </div>
                            </div>

                            <Button type="submit" className="w-full sm:w-auto" disabled={processing}>
                                {processing ? 'Searching...' : 'Find receipt'}
                                <FileSearch />
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </PortalLayout>
        </>
    );
}
