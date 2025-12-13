@extends('_layouts.main')

@section('body')
<div class="min-h-screen flex flex-col bg-white" id="slide-container">
    {{-- Slide Header --}}
    <header class="bg-gray-900 text-white px-6 py-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <a href="/" class="text-lg font-semibold hover:text-blue-300">
                    {{ $page->siteName }}
                </a>
                <span class="text-gray-500">|</span>
                <span class="text-sm text-gray-400" id="slide-counter">
                    @yield('slide-counter', 'Slide')
                </span>
            </div>
            <nav class="flex items-center space-x-2">
                <a href="/" class="text-sm text-gray-400 hover:text-white px-2 py-1">Overview</a>
                <span class="text-gray-600">|</span>
                @yield('nav-links')
                @include('_partials.settings-buttons')
            </nav>
        </div>
    </header>

    {{-- Slide Content --}}
    <main class="flex-1 flex flex-col slide-container">
        @yield('content')
    </main>

    {{-- Navigation Footer --}}
    <footer class="bg-gray-100 border-t border-gray-200 px-6 py-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2">
                @yield('prev-link')
            </div>
            <div class="text-sm text-gray-500">
                Use <kbd class="px-2 py-1 bg-gray-200 rounded text-xs">&larr;</kbd>
                <kbd class="px-2 py-1 bg-gray-200 rounded text-xs">&rarr;</kbd> to navigate
            </div>
            <div class="flex items-center space-x-2">
                @yield('next-link')
            </div>
        </div>
    </footer>
</div>

@push('scripts')
<script>
document.addEventListener('keydown', function(e) {
    const prevLink = document.getElementById('prev-link');
    const nextLink = document.getElementById('next-link');

    if (e.key === 'ArrowLeft' && prevLink) {
        window.location.href = prevLink.href;
    } else if (e.key === 'ArrowRight' && nextLink) {
        window.location.href = nextLink.href;
    } else if (e.key === 'Escape') {
        window.location.href = '/';
    }
});
</script>
@endpush
@endsection
