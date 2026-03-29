import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import PortalLayout from '@/layouts/portal-layout';
import { type SharedData } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/react';
import { Lock, ShieldCheck } from 'lucide-react';

type AccessFormData = {
    matric_number: string;
    email: string;
};

interface AccessPageProps {
    publicReference: string;
}

export default function StudentPaymentAccess({ publicReference }: AccessPageProps) {
    const { errors } = usePage<SharedData>().props;
    const { data, setData, post, processing } = useForm<AccessFormData>({
        matric_number: '',
        email: '',
    });

    const submit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        post(route('student-payments.access', publicReference));
    };

    return (
        <>
            <Head title="Access Payment Request" />

            <PortalLayout
                eyebrow="Access Request"
                title="Confirm your details to open this payment request"
                description="For security, enter the same matric number and email used when creating the request."
                aside={
                    <Card className="border-slate-200 bg-white/95 shadow-sm">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Lock className="size-5 text-emerald-700" />
                                Protected request
                            </CardTitle>
                            <CardDescription>
                                Payment requests are protected so only the right member can continue with payment.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="flex items-center gap-3 rounded-xl border border-emerald-100 bg-emerald-50/80 px-3 py-3 text-sm text-emerald-900">
                                <ShieldCheck className="size-5 text-emerald-700" />
                                Use the exact matric number and email from the payment form.
                            </div>
                        </CardContent>
                    </Card>
                }
            >
                <Card className="border-slate-200 bg-white/95 shadow-sm">
                    <CardHeader>
                        <CardTitle>Access payment request</CardTitle>
                        <CardDescription>Enter your details to continue to the payment page.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form className="space-y-5" onSubmit={submit}>
                            <div className="grid gap-2">
                                <Label htmlFor="matric_number">Matric number</Label>
                                <Input
                                    id="matric_number"
                                    value={data.matric_number}
                                    onChange={(event) => setData('matric_number', event.target.value)}
                                    placeholder="GSU/19/1234"
                                    disabled={processing}
                                />
                                <InputError message={errors?.matric_number} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(event) => setData('email', event.target.value)}
                                    placeholder="student@example.com"
                                    disabled={processing}
                                />
                                <InputError message={errors?.email} />
                            </div>

                            <Button type="submit" className="w-full sm:w-auto" disabled={processing}>
                                {processing ? 'Checking...' : 'Continue to payment'}
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                {(errors?.matric_number || errors?.email) && (
                    <Alert variant="destructive">
                        <AlertTitle>Access denied</AlertTitle>
                        <AlertDescription>We could not match the details to this payment request.</AlertDescription>
                    </Alert>
                )}
            </PortalLayout>
        </>
    );
}
