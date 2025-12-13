<!DOCTYPE html>
<html lang="{{ $page->language ?? 'en' }}" data-theme="{{ $page->defaultTheme ?? 'light' }}" data-mode="{{ $page->defaultMode ?? 'browser' }}" data-default-theme="{{ $page->defaultTheme ?? 'light' }}" data-default-mode="{{ $page->defaultMode ?? 'browser' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="canonical" href="{{ $page->getUrl() }}">
        <meta name="description" content="{{ $page->description ?? 'Git repository presentation' }}">
        <title>{{ $page->pageTitle ?? $page->title ?? $page->siteName }}</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&family=Fira+Code:wght@400;500&family=IBM+Plex+Mono:wght@400;500&family=Figtree:wght@400;500;600;700&family=Open+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
        <script src="https://cdn.tailwindcss.com?plugins=typography"></script>
        {{-- HTMX for SPA-like navigation (preserves fullscreen) --}}
        <script src="https://unpkg.com/htmx.org@2.0.4"></script>
        {{-- Highlight.js for syntax highlighting --}}
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.9.0/build/styles/github.min.css" id="hljs-light">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.9.0/build/styles/github-dark.min.css" id="hljs-dark" disabled>
        <script>window.hljs = window.hljs || {}; window.hljs.configure = window.hljs.configure || function(){};</script>
        <script src="https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.9.0/build/highlight.min.js"></script>
        <script>hljs.configure({ ignoreUnescapedHTML: true });</script>
        <script src="https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.9.0/build/languages/php.min.js"></script>
        <script src="https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.9.0/build/languages/yaml.min.js"></script>
        <script src="https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.9.0/build/languages/dockerfile.min.js"></script>
        <script src="https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.9.0/build/languages/twig.min.js"></script>
        <script src="https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.9.0/build/languages/scss.min.js"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        fontFamily: {
                            sans: ['Inter', 'system-ui', 'sans-serif'],
                            mono: ['JetBrains Mono', 'monospace'],
                        },
                        typography: {
                            DEFAULT: {
                                css: {
                                    maxWidth: 'none',
                                    color: 'var(--text-primary)',
                                    p: {
                                        marginTop: '0.75em',
                                        marginBottom: '0.75em',
                                    },
                                    'code::before': {
                                        content: '""',
                                    },
                                    'code::after': {
                                        content: '""',
                                    },
                                    code: {
                                        backgroundColor: 'var(--code-bg)',
                                        padding: '0.2em 0.4em',
                                        borderRadius: '0.25rem',
                                        fontWeight: '400',
                                    },
                                },
                            },
                            lg: {
                                css: {
                                    p: {
                                        marginTop: '0.75em',
                                        marginBottom: '0.75em',
                                    },
                                },
                            },
                            xl: {
                                css: {
                                    p: {
                                        marginTop: '0.75em',
                                        marginBottom: '0.75em',
                                    },
                                },
                            },
                        },
                    },
                },
            }
        </script>
        {{-- Main CSS with defaults, then theme overrides --}}
        <link rel="stylesheet" href="/css/main.css">
        {{-- Theme stylesheets (override main.css :root variables via [data-theme="..."]) --}}
        <link rel="stylesheet" href="/css/themes/light.css">
        <link rel="stylesheet" href="/css/themes/dark.css">
        <link rel="stylesheet" href="/css/themes/presentation.css">
        <link rel="stylesheet" href="/css/themes/schiphol.css">
        <link rel="stylesheet" href="/css/themes/laravel.css">
        <link rel="stylesheet" href="/css/themes/symfony.css">
        <link rel="stylesheet" href="/css/themes/microsoft.css">
        <link rel="stylesheet" href="/css/themes/catppuccin-latte.css">
        <link rel="stylesheet" href="/css/themes/catppuccin-frappe.css">
        <link rel="stylesheet" href="/css/themes/catppuccin-macchiato.css">
        <link rel="stylesheet" href="/css/themes/catppuccin-mocha.css">
        {{-- Theme and mode initialization script - runs before body renders to prevent flash --}}
        <script>
            (function() {
                const html = document.documentElement;
                const defaultTheme = html.dataset.defaultTheme || 'light';
                const defaultMode = html.dataset.defaultMode || 'browser';
                const savedTheme = localStorage.getItem('presentation-theme') || defaultTheme;
                const savedMode = localStorage.getItem('presentation-mode') || defaultMode;
                html.setAttribute('data-theme', savedTheme);
                html.setAttribute('data-mode', savedMode);
            })();
        </script>
    </head>
    <body class="antialiased min-h-screen" style="font-family: var(--font-family-sans);" hx-boost="true" hx-swap="innerHTML show:window:top" hx-target="body" hx-push-url="true">
        @yield('body')

        {{-- Theme toast notification --}}
        <div id="theme-toast" class="theme-toast">
            <span id="theme-toast-icon"></span>
            <span id="theme-toast-text"></span>
            <kbd>Ctrl+K</kbd>
        </div>

        {{-- Mode toast notification --}}
        <div id="mode-toast" class="theme-toast">
            <span id="mode-toast-icon"></span>
            <span id="mode-toast-text"></span>
            <kbd>Ctrl+L</kbd>
        </div>

        {{-- Main JavaScript --}}
        <script src="/js/main.js"></script>

        @stack('scripts')
    </body>
</html>
