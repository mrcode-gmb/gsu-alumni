import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import { type PropsWithChildren, type ReactNode } from 'react';

interface PortalLayoutProps extends PropsWithChildren {
    eyebrow?: string;
    title?: string;
    description?: string;
    aside?: ReactNode;
}

export default function PortalLayout({ eyebrow, title, description, aside, children }: PortalLayoutProps) {
    const hasIntro = Boolean(eyebrow || title || description);
    const whatsappUrl = 'https://wa.me/2348037858023';

    return (
        <div className="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(5,150,105,0.12),_transparent_36%),linear-gradient(180deg,_#f7faf8_0%,_#f3f4f6_100%)]">
            <header className="mx-auto flex max-w-6xl flex-col gap-4 px-4 py-5 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
                <Link href={route('home')} className="flex min-w-0 items-start gap-3 sm:items-center">
                    <div className="flex size-12 shrink-0 items-center justify-center rounded-2xl border border-emerald-100 bg-white/90 p-2 shadow-sm sm:size-14">
                        <AppLogoIcon className="size-full" alt="GSU Alumni Association logo" />
                    </div>
                    <div className="flex min-w-0 flex-1 flex-col">
                        <span className="text-xs font-semibold tracking-[0.22em] text-emerald-700 uppercase sm:text-sm">GSU Alumni</span>
                        <span className="text-foreground text-base leading-tight font-semibold sm:text-lg">Payment Portal</span>
                        <span className="max-w-[16rem] text-[11px] leading-4 font-semibold tracking-wide text-slate-600 uppercase sm:max-w-none sm:text-xs">
                            Gombe State University Alumni Association
                        </span>
                    </div>
                </Link>

                <div className="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:flex-wrap sm:items-center sm:justify-end">
                    <Button variant="outline" className="w-full sm:w-auto" asChild>
                        <a href={whatsappUrl} target="_blank" rel="noreferrer">
                            <span className="sm:hidden">WhatsApp support</span>
                            <span className="hidden sm:inline">WhatsApp: 08037858023</span>
                        </a>
                    </Button>
                    <Button variant="ghost" className="w-full sm:w-auto" asChild>
                        <Link href={route('student-receipts.lookup')}>Find receipt</Link>
                    </Button>
                </div>
            </header>

            <main className="mx-auto grid max-w-6xl gap-6 px-4 pb-10 sm:px-6 lg:grid-cols-[1.08fr,0.92fr] lg:px-8">
                <section className="order-2 min-w-0 space-y-6 lg:order-1">
                    {hasIntro && (
                        <div className="space-y-4 rounded-3xl border bg-white/80 p-6 shadow-sm backdrop-blur sm:p-8">
                            {eyebrow && <p className="text-sm font-semibold tracking-[0.22em] text-emerald-700 uppercase">{eyebrow}</p>}
                            <div className="space-y-3">
                                {title && <h1 className="max-w-2xl text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">{title}</h1>}
                                {description && <p className="max-w-2xl text-base leading-7 text-slate-600">{description}</p>}
                            </div>
                        </div>
                    )}

                    {aside}
                </section>

                <section className="order-1 min-w-0 lg:order-2">{children}</section>
            </main>
        </div>
    );
}
