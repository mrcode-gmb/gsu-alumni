import AppLogoIcon from './app-logo-icon';

export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-10 shrink-0 items-center justify-center rounded-xl border border-emerald-100 bg-white p-1.5 shadow-sm">
                <AppLogoIcon className="size-full" alt="GSU Alumni Payment Portal logo" />
            </div>
            <div className="ml-2 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-none font-semibold">GSU Alumni</span>
                <span className="text-muted-foreground truncate text-xs">Payment Portal</span>
            </div>
        </>
    );
}
