import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import PortalLayout from '@/layouts/portal-layout';
import { type SelectOption, type SharedData, type StudentDepartmentOption, type StudentPaymentTypeOption } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/react';
import { ArrowRight, CreditCard } from 'lucide-react';
import { type FormEventHandler } from 'react';

const currencyFormatter = new Intl.NumberFormat('en-NG', {
    style: 'currency',
    currency: 'NGN',
    minimumFractionDigits: 2,
});

type StudentPaymentFormData = {
    full_name: string;
    matric_number: string;
    email: string;
    phone_number: string;
    department: string;
    faculty: string;
    program_type_id: string;
    graduation_session: string;
    payment_type_id: string;
};

interface StudentPaymentCreateProps {
    faculties: SelectOption[];
    departments: StudentDepartmentOption[];
    programTypes: SelectOption[];
    graduationSessions: SelectOption[];
    paymentTypes: StudentPaymentTypeOption[];
}

export default function StudentPaymentCreate({ faculties, departments, programTypes, graduationSessions, paymentTypes }: StudentPaymentCreateProps) {
    const { flash } = usePage<SharedData>().props;
    const { data, setData, post, processing, errors } = useForm<StudentPaymentFormData>({
        full_name: '',
        matric_number: '',
        email: '',
        phone_number: '',
        department: '',
        faculty: '',
        program_type_id: '',
        graduation_session: '',
        payment_type_id: '',
    });

    const availablePaymentTypes = paymentTypes.filter((paymentType) => paymentType.program_type_ids.includes(data.program_type_id));
    const selectedPaymentType = availablePaymentTypes.find((paymentType) => paymentType.id === Number(data.payment_type_id)) ?? null;
    const availableDepartments = departments.filter((department) => department.faculty_name === data.faculty);
    const hasFaculties = faculties.length > 0;
    const hasPaymentTypes = paymentTypes.length > 0;
    const hasProgramTypes = programTypes.length > 0;
    const hasDepartments = departments.length > 0;
    const hasGraduationSessions = graduationSessions.length > 0;
    const canSubmit = hasPaymentTypes && hasProgramTypes && hasFaculties && hasDepartments && hasGraduationSessions && !processing;

    const submit: FormEventHandler<HTMLFormElement> = (event) => {
        event.preventDefault();
        post(route('student-payments.store'));
    };

    return (
        <>
            <Head title="Member Payment Form" />

            <PortalLayout
                aside={
                    <div className="grid gap-4">
                        <Card className="border-slate-200 bg-white/90">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <CreditCard className="size-5" />
                                    Selected payment type
                                </CardTitle>
                                <CardDescription>
                                    Choose from active payment types allowed for the selected program type. The matching amount and description appear here automatically.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {selectedPaymentType ? (
                                    <>
                                        <div>
                                            <p className="text-sm font-semibold text-slate-900">{selectedPaymentType.name}</p>
                                            <p className="mt-1 text-2xl font-semibold text-slate-950">
                                                {currencyFormatter.format(Number(selectedPaymentType.amount))}
                                            </p>
                                            <p className="mt-1 text-xs font-medium tracking-[0.16em] text-slate-500 uppercase">
                                                Total payable
                                            </p>
                                        </div>
                                        <p className="text-sm leading-6 text-slate-600">
                                            {selectedPaymentType.description || 'No extra description has been provided for this payment type yet.'}
                                        </p>
                                    </>
                                ) : (
                                    <p className="text-sm leading-6 text-slate-600">
                                        Select your program type first, then choose a payment type to preview the amount and short description before you continue.
                                    </p>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                }
            >
                <Card className="border-slate-200 bg-white/95 shadow-sm">
                    <CardHeader>
                        <CardTitle>Member details</CardTitle>
                        <CardDescription>Fill in the details exactly as they should appear on the payment request.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        {flash.error && (
                            <Alert className="mb-6" variant="destructive">
                                <AlertTitle>Payment initialization failed</AlertTitle>
                                <AlertDescription>{flash.error}</AlertDescription>
                            </Alert>
                        )}

                        {!hasPaymentTypes && (
                            <Alert className="mb-6">
                                <AlertTitle>No active payment types available</AlertTitle>
                                <AlertDescription>
                                    The alumni office has not published any active payment types yet. Please check back later.
                                </AlertDescription>
                            </Alert>
                        )}

                        {data.program_type_id !== '' && availablePaymentTypes.length === 0 && (
                            <Alert className="mb-6">
                                <AlertTitle>No payment types available for this program type</AlertTitle>
                                <AlertDescription>
                                    The alumni office has not assigned any payment types to the selected program type yet. Please choose another program type or check back later.
                                </AlertDescription>
                            </Alert>
                        )}

                        {!hasProgramTypes && (
                            <Alert className="mb-6">
                                <AlertTitle>No program types available</AlertTitle>
                                <AlertDescription>
                                    The alumni office has not loaded the official program type list yet. Please check back shortly.
                                </AlertDescription>
                            </Alert>
                        )}

                        {!hasFaculties && (
                            <Alert className="mb-6">
                                <AlertTitle>No faculties available</AlertTitle>
                                <AlertDescription>
                                    The alumni office has not loaded the official faculty list yet. Please check back shortly.
                                </AlertDescription>
                            </Alert>
                        )}

                        {!hasGraduationSessions && (
                            <Alert className="mb-6">
                                <AlertTitle>No graduation sessions available</AlertTitle>
                                <AlertDescription>
                                    The alumni office has not loaded the graduation session list yet. Please check back shortly.
                                </AlertDescription>
                            </Alert>
                        )}

                        {hasFaculties && !hasDepartments && (
                            <Alert className="mb-6">
                                <AlertTitle>No departments available</AlertTitle>
                                <AlertDescription>
                                    The alumni office has not loaded the official department list yet. Please check back shortly.
                                </AlertDescription>
                            </Alert>
                        )}

                        <form className="space-y-6" onSubmit={submit}>
                            <div className="grid gap-5 md:grid-cols-2">
                                <div className="grid gap-2 md:col-span-2">
                                    <Label htmlFor="full_name">Full name</Label>
                                    <Input
                                        id="full_name"
                                        value={data.full_name}
                                        onChange={(event) => setData('full_name', event.target.value)}
                                        placeholder="Enter your full name"
                                        disabled={processing}
                                    />
                                    <InputError message={errors.full_name} />
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
                                    <InputError message={errors.email} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="phone_number">Phone number</Label>
                                    <Input
                                        id="phone_number"
                                        value={data.phone_number}
                                        onChange={(event) => setData('phone_number', event.target.value)}
                                        placeholder="08012345678"
                                        disabled={processing}
                                    />
                                    <InputError message={errors.phone_number} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="graduation_session">Graduation year / session</Label>
                                    <Select
                                        value={data.graduation_session}
                                        onValueChange={(value) => setData('graduation_session', value)}
                                        disabled={!hasGraduationSessions || processing}
                                    >
                                        <SelectTrigger id="graduation_session" aria-invalid={errors.graduation_session ? true : undefined}>
                                            <SelectValue placeholder="Select your graduation session" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {graduationSessions.map((session) => (
                                                <SelectItem key={session.value} value={session.value}>
                                                    {session.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.graduation_session} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="program_type_id">Program type</Label>
                                    <Select
                                        value={data.program_type_id}
                                        onValueChange={(value) => {
                                            setData('program_type_id', value);
                                            setData('payment_type_id', '');
                                        }}
                                        disabled={!hasProgramTypes || processing}
                                    >
                                        <SelectTrigger id="program_type_id" aria-invalid={errors.program_type_id ? true : undefined}>
                                            <SelectValue placeholder="Select your program type" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {programTypes.map((programType) => (
                                                <SelectItem key={programType.value} value={programType.value}>
                                                    {programType.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.program_type_id} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="faculty">Faculty</Label>
                                    <Select
                                        value={data.faculty}
                                        onValueChange={(value) => {
                                            setData('faculty', value);
                                            setData('department', '');
                                        }}
                                        disabled={!hasFaculties || processing}
                                    >
                                        <SelectTrigger id="faculty" aria-invalid={errors.faculty ? true : undefined}>
                                            <SelectValue placeholder="Select your faculty" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {faculties.map((faculty) => (
                                                <SelectItem key={faculty.value} value={faculty.value}>
                                                    {faculty.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.faculty} />
                                </div>

                                <div className="grid gap-2 md:col-span-2">
                                    <Label htmlFor="department">Department</Label>
                                    <Select
                                        value={data.department}
                                        onValueChange={(value) => setData('department', value)}
                                        disabled={!data.faculty || !hasDepartments || processing}
                                    >
                                        <SelectTrigger id="department" aria-invalid={errors.department ? true : undefined}>
                                            <SelectValue placeholder={data.faculty ? 'Select your department' : 'Select faculty first'} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {availableDepartments.map((department) => (
                                                <SelectItem key={`${department.faculty_name}-${department.value}`} value={department.value}>
                                                    {department.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.department} />
                                </div>

                                <div className="grid gap-2 md:col-span-2">
                                    <Label htmlFor="payment_type_id">Payment type</Label>
                                    <Select
                                        value={data.payment_type_id}
                                        onValueChange={(value) => setData('payment_type_id', value)}
                                        disabled={!data.program_type_id || availablePaymentTypes.length === 0 || processing}
                                    >
                                        <SelectTrigger id="payment_type_id" aria-invalid={errors.payment_type_id ? true : undefined}>
                                            <SelectValue placeholder={data.program_type_id ? 'Select an active payment type' : 'Select program type first'} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {availablePaymentTypes.map((paymentType) => (
                                                <SelectItem key={paymentType.id} value={paymentType.id.toString()}>
                                                    {paymentType.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.payment_type_id} />
                                </div>
                            </div>

                            <div className="rounded-2xl border bg-slate-50 p-4">
                                <p className="text-sm font-medium text-slate-700">Total payable</p>
                                <p className="mt-1 text-2xl font-semibold text-slate-950">
                                    {selectedPaymentType ? currencyFormatter.format(Number(selectedPaymentType.amount)) : 'Select a payment type'}
                                </p>
                            </div>

                            <Button type="submit" className="w-full sm:w-auto" disabled={!canSubmit}>
                                {processing ? 'Preparing request...' : 'Pay now'}
                                <ArrowRight />
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </PortalLayout>
        </>
    );
}
