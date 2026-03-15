<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['text-[13px]', 'dark' => ($appearance ?? 'system') == 'dark'])>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Inline script to detect system dark mode preference and apply it immediately --}}
    <script>
        (function() {
            const appearance = '{{ $appearance ?? 'system' }}';

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
        }

        html.dark {
            background-color: oklch(0.145 0 0);
        }
    </style>

    <title inertia>{{ config('app.name', 'Laravel') }}</title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    <link rel="preload" href="{{ asset('vendor/fontAwesomePro-6.7.1/webfonts/fa-solid-900.woff2') }}" as="font"
        type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('vendor/fontAwesomePro-6.7.1/webfonts/fa-light-300.woff2') }}" as="font"
        type="font/woff2" crossorigin>
    <link href="{{ asset('vendor/fontAwesomePro-6.7.1/all.css') }}" rel="stylesheet" />

    <script>
        (function() {
            try {
                var rawScrollState = sessionStorage.getItem('crm.list.scroll.state');
                var navEntries = (performance.getEntriesByType && performance.getEntriesByType('navigation')) || [];
                var navType = navEntries[0] && navEntries[0].type;
                var isReloadNav = navType === 'reload';
                if (isReloadNav) {
                    sessionStorage.removeItem('crm.list.scroll.state');
                    document.documentElement.setAttribute('data-pending-table-scroll', '0');
                }
                if (rawScrollState) {
                    var parsedScrollState = JSON.parse(rawScrollState);
                    var isSamePath = parsedScrollState && parsedScrollState.pathname === window.location.pathname;
                    var isFresh = parsedScrollState && (Date.now() - Number(parsedScrollState.timestamp || 0) < 15000);
                    var isPreserveMode = !parsedScrollState || parsedScrollState.mode !== 'table-top';
                    if (!isReloadNav && isSamePath && isFresh && !isPreserveMode) {
                        document.documentElement.setAttribute('data-pending-table-scroll', '1');
                    } else {
                        document.documentElement.setAttribute('data-pending-table-scroll', '0');
                    }
                } else {
                    document.documentElement.setAttribute('data-pending-table-scroll', '0');
                }
            } catch (_error) {}

            var saved = localStorage.getItem('crm.sidebar.expanded');
            var expanded = saved !== '0';
            document.documentElement.setAttribute('data-sidebar-expanded', expanded ? '1' : '0');
            document.documentElement.setAttribute('data-sidebar-transition', '0');
            document.documentElement.setAttribute('data-sidebar-direction', 'idle');
            document.documentElement.style.setProperty('--crm-initial-sidebar-width', expanded ? '14rem' : '4rem');
        })();
    </script>
    <style>
        [x-cloak] {
            display: none !important;
        }

        html[data-sidebar-expanded='0'][data-sidebar-transition='0'] .crm-sidebar-desktop [data-sidebar-chevron] {
            display: none !important;
        }

        html[data-sidebar-expanded='1'] .crm-sidebar-desktop [data-sidebar-toggle-expanded] {
            display: inline-block !important;
        }

        html[data-sidebar-expanded='1'] .crm-sidebar-desktop [data-sidebar-toggle-collapsed] {
            display: none !important;
        }

        html[data-sidebar-expanded='0'] .crm-sidebar-desktop [data-sidebar-toggle-expanded] {
            display: none !important;
        }

        html[data-sidebar-expanded='0'] .crm-sidebar-desktop [data-sidebar-toggle-collapsed] {
            display: inline-block !important;
        }

        html[data-pending-table-scroll='1'] .crm-main-with-sidebar {
            visibility: hidden;
        }
    </style>

    @viteReactRefresh
    @vite(['resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
    @inertiaHead
</head>

<body class="font-sans antialiased">
    @inertia
</body>

</html>
