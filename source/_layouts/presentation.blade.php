@extends('_layouts.main')

@section('body')
<div class="min-h-screen flex flex-col">
    {{-- Header --}}
    <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 py-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <a href="/" class="text-xl font-bold text-gray-900 hover:text-blue-600">
                        {{ $page->siteName }}
                    </a>
                    @hasSection('breadcrumb')
                        <span class="text-gray-300">/</span>
                        @yield('breadcrumb')
                    @endif
                </div>
                <nav class="flex items-center space-x-4">
                    @hasSection('nav')
                        @yield('nav')
                    @else
                        <a href="/" class="text-sm text-gray-600 hover:text-gray-900">Presentations</a>
                    @endif
                    @include('_partials.settings-buttons')
                </nav>
            </div>
        </div>
    </header>

    {{-- Main Content --}}
    <main class="flex-1">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="bg-white border-t border-gray-200 py-4">
        <div class="max-w-7xl mx-auto px-4 text-center text-sm text-gray-500">
            Generated from Git repository
            @if($page->siteAuthor)
                by {{ $page->siteAuthor }}
            @endif
        </div>
    </footer>
</div>
@endsection
