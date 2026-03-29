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

    return (
        <div className="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(5,150,105,0.12),_transparent_36%),linear-gradient(180deg,_#f7faf8_0%,_#f3f4f6_100%)]">
            <header className="mx-auto flex max-w-6xl items-center justify-between px-4 py-5 sm:px-6 lg:px-8">
                <Link href={route('home')} className="flex items-center gap-3">
                    <div className="flex size-14 shrink-0 items-center justify-center rounded-2xl border border-emerald-100 bg-white/90 p-2 shadow-sm">
                        <AppLogoIcon className="size-full" alt="Gombe State University logo" />
                    </div>
                    <div className="flex flex-col">
                        <span className="text-sm font-semibold tracking-[0.22em] text-emerald-700 uppercase">GSU Alumni</span>
                        <span className="text-foreground text-lg font-semibold">Payment Portal</span>
                    </div>
                </Link>

                <div className="flex flex-wrap items-center justify-end gap-3">
                    <Button variant="ghost" asChild>
                        <Link href={route('student-receipts.lookup')}>Find receipt</Link>
                    </Button>
                </div>
            </header>

            <main className="mx-auto grid max-w-6xl gap-6 px-4 pb-10 sm:px-6 lg:grid-cols-[1.08fr,0.92fr] lg:px-8">
                <section className="space-y-6">
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

                <section>{children}</section>
            </main>
        </div>
    );
}
