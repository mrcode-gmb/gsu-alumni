import { PaymentRequestSummary } from '@/components/student-payments/payment-request-summary';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import PortalLayout from '@/layouts/portal-layout';
import { type SharedData, type StudentPaymentRequest } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowRight, CheckCircle2, Clock3, FileCheck2, RefreshCcw, XCircle } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

interface StudentPaymentShowProps {
    paymentRequest: StudentPaymentRequest;
    paymentGatewayReady: boolean;
    autoOpenCheckout: boolean;
}

interface PaystackPopupTransaction {
    reference: string;
    status?: string;
    trxref?: string;
}

interface PaystackPopupOptions {
    key: string;
    email: string;
    amount: number;
    currency?: string;
    reference?: string;
    callback_url?: string;
    metadata?: Record<string, unknown>;
    subaccountCode?: string;
    transactionCharge?: number;
    bearer?: 'account' | 'subaccount';
    onSuccess: (transaction: PaystackPopupTransaction) => void;
    onCancel: () => void;
}

interface PaystackPopupInstance {
    newTransaction: (options: PaystackPopupOptions) => void;
}

declare global {
    interface Window {
        PaystackPop?: new () => PaystackPopupInstance;
    }
}

const PAYSTACK_SCRIPT_ID = 'paystack-inline-v2-script';

function loadPaystackPopupScript(): Promise<void> {
    if (typeof window === 'undefined') {
        return Promise.reject(new Error('Paystack popup is only available in the browser.'));
    }

    if (window.PaystackPop) {
        return Promise.resolve();
    }

    const existingScript = document.getElementById(PAYSTACK_SCRIPT_ID) as HTMLScriptElement | null;

    if (existingScript) {
        return new Promise((resolve, reject) => {
            existingScript.addEventListener('load', () => resolve(), { once: true });
            existingScript.addEventListener('error', () => reject(new Error('Could not load the Paystack checkout script.')), { once: true });
        });
    }

    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.id = PAYSTACK_SCRIPT_ID;
        script.src = 'https://js.paystack.co/v2/inline.js';
        script.async = true;
        script.onload = () => resolve();
        script.onerror = () => reject(new Error('Could not load the Paystack checkout script.'));
        document.body.appendChild(script);
    });
}

async function preparePopupCheckout(publicReference: string) {
    const csrfToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';

    const response = await fetch(route('student-payments.paystack.initialize', publicReference), {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({}),
        credentials: 'same-origin',
    });

    const payload = (await response.json().catch(() => ({}))) as {
        checkout?: {
            key: string;
            email: string;
            amount: number;
            currency?: string;
            reference: string;
            callback_url?: string;
            metadata?: Record<string, unknown>;
            split_code?: string | null;
            subaccount?: string | "ACCT_kyf9e07n9i9at1p";
            transaction_charge?: number | null;
            bearer?: 'account' | 'subaccount' | null;
        };
        message?: string;
    };

    if (!response.ok || !payload.checkout) {
        throw new Error(payload.message || 'We could not prepare the Paystack popup right now. Please try again.');
    }

    return payload.checkout;
}

export default function StudentPaymentShow({ paymentRequest, paymentGatewayReady, autoOpenCheckout }: StudentPaymentShowProps) {
    const { flash } = usePage<SharedData>().props;
    const [popupReady, setPopupReady] = useState(false);
    const [popupLoading, setPopupLoading] = useState(false);
    const [inlineNotice, setInlineNotice] = useState<string | null>(null);
    const [inlineError, setInlineError] = useState<string | null>(null);
    const [checkoutPrepared, setCheckoutPrepared] = useState(Boolean(paymentRequest.paystack_reference));
    const autoOpenedCheckout = useRef(false);

    const isSuccessful = paymentRequest.payment_status === 'successful';
    const isPending = paymentRequest.payment_status === 'pending';
    const isFailed = paymentRequest.payment_status === 'failed';
    const isAbandoned = paymentRequest.payment_status === 'abandoned';
    const successMessage = !isPending ? flash.success : null;
    const hasPreviousSuccessfulPayments = paymentRequest.previous_successful_payments_count > 0;

    useEffect(() => {
        if (!isPending || !paymentRequest.can_initialize_payment || !paymentGatewayReady) {
            setPopupReady(false);
            return;
        }

        let active = true;

        setPopupLoading(true);

        loadPaystackPopupScript()
            .then(() => {
                if (!active) {
                    return;
                }

                setPopupReady(true);
            })
            .catch((error: unknown) => {
                if (!active) {
                    return;
                }

                setInlineError(error instanceof Error ? error.message : 'Could not load the Paystack popup.');
            })
            .finally(() => {
                if (active) {
                    setPopupLoading(false);
                }
            });

        return () => {
            active = false;
        };
    }, [isPending, paymentGatewayReady, paymentRequest.can_initialize_payment]);

    async function handleInlineCheckout() {
        setInlineError(null);
        setInlineNotice(null);
        setPopupLoading(true);

        try {
            await loadPaystackPopupScript();

            const checkout = await preparePopupCheckout(paymentRequest.public_reference);
            const PaystackPop = window.PaystackPop;

            setCheckoutPrepared(true);

            if (!PaystackPop) {
                throw new Error('Paystack checkout could not be loaded in this browser.');
            }

            const popup = new PaystackPop();

            try {
                const hasSubaccount = Boolean(checkout.subaccount);
                const hasSplitCode = Boolean(checkout.split_code);

                if (hasSplitCode && !hasSubaccount) {
                    console.warn('Paystack popup split_code ignored because InlineJS expects split objects or subaccountCode.', {
                        split_code: checkout.split_code,
                    });
                }

                popup.newTransaction({
                    key: checkout.key,
                    email: checkout.email,
                    amount: checkout.amount,
                    currency: checkout.currency,
                    reference: checkout.reference,
                    callback_url: checkout.callback_url,
                    metadata: checkout.metadata,
                    subaccountCode: checkout.subaccount ?? undefined,
                    transactionCharge: checkout.transaction_charge ?? undefined,
                    bearer: checkout.bearer ?? undefined,
                    onSuccess: () => {
                        router.post(route('student-payments.paystack.verify', paymentRequest.public_reference), {}, {
                            preserveScroll: true,
                        });
                    },
                    onCancel: () => {
                        setInlineNotice('Checkout was closed. If you already completed payment, use "Check payment status".');
                    },
                });
            } catch (popupError: unknown) {
                console.error('Paystack popup launch error', popupError);
                const message = popupError instanceof Error ? popupError.message : String(popupError ?? '');
                throw new Error(message || 'Paystack checkout could not start in this browser.');
            }
        } catch (error: unknown) {
            console.error('Paystack popup error', error);
            const message = error instanceof Error ? error.message : String(error ?? '');
            setInlineError(message || 'We could not launch the Paystack popup right now.');
        } finally {
            setPopupLoading(false);
        }
    }

    useEffect(() => {
        if (!autoOpenCheckout || autoOpenedCheckout.current || !isPending || !paymentRequest.can_initialize_payment || !paymentGatewayReady || !popupReady || popupLoading) {
            return;
        }

        autoOpenedCheckout.current = true;
        void handleInlineCheckout();
    }, [autoOpenCheckout, isPending, paymentGatewayReady, paymentRequest.can_initialize_payment, popupReady, popupLoading]);

    return (
        <>
            <Head title="Payment Request Review" />

            <PortalLayout
                aside={
                    <Card className="border-slate-200 bg-white/95 shadow-sm">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                {isSuccessful ? <CheckCircle2 className="size-5 text-emerald-600" /> : isPending ? <Clock3 className="size-5" /> : <XCircle className="size-5 text-amber-600" />}
                                Next action
                            </CardTitle>
                            <CardDescription>
                                {isSuccessful
                                    ? 'Your payment is complete. Use the action below to open the official receipt.'
                                    : isPending
                                      ? 'Use the action below to open Paystack on this page instead of leaving the portal.'
                                      : 'This request is no longer open for payment initialization.'}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {isSuccessful ? (
                                <>
                                    <p className="text-sm leading-6 text-slate-600">
                                        Receipts are generated from verified successful payments only. Opening the receipt will reuse the same official record if it already exists.
                                    </p>
                                    <Button className="w-full sm:w-auto" asChild>
                                        <Link
                                            href={route('student-receipts.from-payment-request', paymentRequest.public_reference)}
                                            method="post"
                                            as="button"
                                        >
                                            View receipt
                                            <FileCheck2 />
                                        </Link>
                                    </Button>
                                </>
                            ) : isPending ? (
                                <>
                                    <p className="text-sm leading-6 text-slate-600">
                                        The portal prepares the request on the backend first, then launches Paystack in an in-page popup for a smoother student experience.
                                    </p>
                                    {paymentRequest.can_initialize_payment && paymentGatewayReady ? (
                                        <Button className="w-full sm:w-auto" onClick={handleInlineCheckout} disabled={popupLoading || !popupReady}>
                                            {popupLoading ? 'Opening checkout...' : 'Pay with Paystack'}
                                            <ArrowRight />
                                        </Button>
                                    ) : (
                                        <Button disabled className="w-full sm:w-auto">
                                            {popupLoading ? 'Opening checkout...' : 'Pay with Paystack'}
                                            <ArrowRight />
                                        </Button>
                                    )}
                                    {(paymentRequest.paystack_reference || checkoutPrepared) && (
                                        <Button variant="outline" asChild>
                                            <Link
                                                href={route('student-payments.paystack.verify', paymentRequest.public_reference)}
                                                method="post"
                                                as="button"
                                            >
                                                Check payment status
                                                <RefreshCcw />
                                            </Link>
                                        </Button>
                                    )}
                                    {!paymentGatewayReady && (
                                        <p className="text-sm text-amber-700">
                                            Paystack public and secret keys must both be configured before checkout can open.
                                        </p>
                                    )}
                                </>
                            ) : (
                                <p className="text-sm leading-6 text-slate-600">
                                    If you still need to pay, create a fresh payment request and try again.
                                </p>
                            )}
                        </CardContent>
                    </Card>
                }
            >
                <div className="space-y-4">
                    {isPending && hasPreviousSuccessfulPayments && (
                        <Alert>
                            <AlertTitle>Previous payment found</AlertTitle>
                            <AlertDescription>
                                This matric number already has {paymentRequest.previous_successful_payments_count} successful payment
                                {paymentRequest.previous_successful_payments_count === 1 ? '' : 's'} for this payment type. A new payment is still allowed.
                            </AlertDescription>
                        </Alert>
                    )}

                    {successMessage && (
                        <Alert>
                            <AlertTitle>Payment update</AlertTitle>
                            <AlertDescription>{successMessage}</AlertDescription>
                        </Alert>
                    )}

                    {flash.error && (
                        <Alert variant="destructive">
                            <AlertTitle>Payment update</AlertTitle>
                            <AlertDescription>{flash.error}</AlertDescription>
                        </Alert>
                    )}

                    {inlineError && (
                        <Alert variant="destructive">
                            <AlertTitle>Checkout issue</AlertTitle>
                            <AlertDescription>{inlineError}</AlertDescription>
                        </Alert>
                    )}

                    {inlineNotice && (
                        <Alert>
                            <AlertTitle>Checkout status</AlertTitle>
                            <AlertDescription>{inlineNotice}</AlertDescription>
                        </Alert>
                    )}

                    <PaymentRequestSummary paymentRequest={paymentRequest} />
                </div>
            </PortalLayout>
        </>
    );
}
