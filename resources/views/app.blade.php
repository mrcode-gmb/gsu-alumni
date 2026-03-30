<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="color-scheme" content="light dark">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
                color-scheme: light;
            }

            html.dark {
                background-color: oklch(0.145 0 0);
                color-scheme: dark;
            }
        </style>

        <title inertia>{{ config('app.name', 'Laravel') }}</title>
        <meta name="description" content="GSU Alumni Payment Portal for Gombe State University Alumni Association.">

        <link rel="icon" href="{{ asset('images-removebg-preview.png') }}" type="image/png">
        <link rel="apple-touch-icon" href="{{ asset('images-removebg-preview.png') }}">
        <meta property="og:title" content="{{ config('app.name', 'GSU Alumni Payment Portal') }}">
        <meta property="og:description" content="GSU Alumni Payment Portal for Gombe State University Alumni Association.">
        <meta property="og:image" content="{{ asset('images-removebg-preview.png') }}">
        <meta property="og:type" content="website">
        <meta name="twitter:card" content="summary">
        <meta name="twitter:image" content="{{ asset('images-removebg-preview.png') }}">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @routes
        @if (app()->isLocal())
            @viteReactRefresh
        @endif
        @vite(['resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
