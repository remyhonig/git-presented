@extends('_layouts.main')

@php
// Look up presentation and step from global collections
$presentation = $page->presentations->get($page->presentationId);
$step = $presentation ? $presentation->getStep($page->stepId) : null;
$nav = $presentation ? $presentation->getStepNavigation($page->stepId) : null;

// Load parsed diff on-demand
$diffFiles = $page->gitRepo && $step ? $page->gitRepo->getParsedDiff($page->stepHash) : collect();

// Check if this is a bulk change (framework/bundle install)
$maxFiles = $page->maxFilesForInlineDiff ?? 10;
$isBulkChange = $diffFiles->count() > $maxFiles;
$hasFileChanges = !$diffFiles->isEmpty();

// Get sub-slides from the step (parsed at build time)
$subSlides = $step ? $step->getSubSlides() : collect();
$introContent = $step ? $step->getIntroContent() : '';
$hasSubSlides = $subSlides->isNotEmpty();
$totalSlides = 1 + $subSlides->count(); // Title slide + sub-slides

// Pre-fetch snippet content for sub-slides that have snippets
$snippetContents = collect();
if ($page->gitRepo) {
    foreach ($subSlides as $subSlide) {
        if ($subSlide->hasSnippet()) {
            $snippet = $subSlide->snippetReference;
            $result = $page->gitRepo->getSnippetContent($page->stepHash, $snippet);
            $snippetContents[$snippet->getId()] = [
                'snippet' => $snippet,
                'content' => $result['content'],
                'lines' => $result['lines'],
                'error' => $result['error'],
                'viewType' => $result['viewType'],
            ];
        }
    }
}
@endphp

@section('body')
@php
$progressPercent = $nav['total'] > 1 ? (($nav['index']) / ($nav['total'] - 1)) * 100 : 100;
@endphp
<div class="min-h-screen flex flex-col slide-container" id="slide-container">
    {{-- Slide Header with Progress Background --}}
    <header class="slide-header flex-shrink-0 relative overflow-hidden">
        {{-- Progress background fill --}}
        <div class="absolute inset-0 progress-bar-fill opacity-20 transition-all duration-300" style="width: {{ $progressPercent }}%"></div>
        <div class="relative px-4 py-1.5">
            <div class="flex items-center justify-between">
                {{-- Left: Author avatar and name --}}
                <div class="flex items-center space-x-2">
                    <img src="{{ $presentation->getAuthor()->getGravatarUrl(32) }}"
                         alt="{{ $presentation->getAuthor()->name }}"
                         class="w-6 h-6 rounded-full ring-1 ring-black/10">
                    <span class="text-sm" style="color: var(--text-secondary)">
                        {{ $presentation->getAuthor()->name }}
                    </span>
                </div>

                {{-- Center: Title (default) / Settings (on hover) --}}
                <div class="relative flex items-center justify-center">
                    {{-- Title - visible by default, hidden on header hover --}}
                    <div class="header-title flex items-center space-x-2">
                        {{-- Git branch icon --}}
                        <svg class="w-4 h-4" style="color: var(--text-muted)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm0 0v10m0 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm10-6a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm0 0a4 4 0 0 1-4 4H7"></path>
                        </svg>
                        <span class="text-sm font-medium" style="color: var(--text-primary)">
                            {{ $presentation->getTitle() }}
                        </span>
                    </div>
                    {{-- Settings - hidden by default, visible on header hover --}}
                    <div class="header-settings absolute inset-0 flex items-center justify-center">
                        @include('_partials.settings-buttons')
                    </div>
                </div>

                {{-- Right: Navigation arrows and exit --}}
                <div class="flex items-center space-x-1 justify-end">
                    {{-- Previous --}}
                    @if($nav['prev'])
                    <a href="{{ $page->getPresentationStepUrl($presentation->id, $nav['prev']->id) }}"
                       id="prev-link"
                       class="nav-link p-1.5 rounded hover:bg-black/5" title="Previous (←)">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </a>
                    @else
                    <span class="p-1.5 opacity-30">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </span>
                    @endif

                    {{-- Page indicator --}}
                    <span class="text-xs px-1.5 py-0.5 rounded" style="background: var(--bg-tertiary); color: var(--text-muted)">
                        {{ $nav['index'] + 1 }} / {{ $nav['total'] }}
                    </span>

                    {{-- Next --}}
                    @if($nav['next'])
                    <a href="{{ $page->getPresentationStepUrl($presentation->id, $nav['next']->id) }}"
                       id="next-link"
                       class="nav-link p-1.5 rounded hover:bg-black/5" title="Next (→)">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </a>
                    @else
                    <a href="/"
                       id="next-link"
                       class="nav-link p-1.5 rounded hover:bg-black/5" title="Finish">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </a>
                    @endif

                    <span class="mx-1 h-4 w-px" style="background: var(--border-primary)"></span>

                    <a href="/" class="nav-link p-1.5 rounded hover:bg-black/5" title="Exit (Esc)">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </header>

    {{-- Main Content Area - Single scrollable column --}}
    <div class="flex-1 overflow-y-auto" id="slides-container">
        {{-- Title Slide (Slide 0) --}}
        <div class="slide current-slide min-h-[calc(100vh-4rem)] flex flex-col justify-center px-4 py-8" data-slide="0" id="title-slide">
            <div class="max-w-6xl mx-auto w-full">
                {{-- Main commit message (rendered as markdown for inline code support) --}}
                <h1 class="slide-title font-bold leading-tight slide-mb prose-title text-center gradient-title">
                    {!! $page->markdown($step->getTitle()) !!}
                </h1>

                {{-- Intro content (text before first h2) or full description if no sub-slides --}}
                @if(!$hasSubSlides && $step->getDescription())
                <div class="prose max-w-none mt-8 slide-prose">
                    {!! $page->markdown($step->getDescription()) !!}
                </div>
                @elseif($introContent)
                <div class="prose max-w-none mt-8 slide-prose">
                    {!! $page->markdown($introContent) !!}
                </div>
                @endif
            </div>

            {{-- Navigation arrow --}}
            @if($hasSubSlides || $hasFileChanges)
            <button class="slide-nav-arrow slide-nav-down" onclick="goToSlide(1)">&darr;</button>
            @endif
        </div>

        {{-- Sub-Slides (from h2 headings) --}}
        @foreach($subSlides as $subSlide)
        @php
            $slideIndex = $subSlide->index + 1; // +1 because title slide is 0
            $isLastSlide = $subSlide->index === $subSlides->count() - 1;
            $hasSnippet = $subSlide->hasSnippet();
            $snippetData = $hasSnippet ? $snippetContents->get($subSlide->getSnippetId()) : null;
        @endphp

        @if($hasSnippet && $snippetData)
        {{-- Stacked layout for slides with code snippets: prose above, code below --}}
        <div class="slide slide-with-snippet min-h-[calc(100vh-4rem)] flex flex-col justify-center items-center px-4 py-8" data-slide="{{ $slideIndex }}">
            <div class="w-full flex flex-col items-center">
                {{-- Title --}}
                <h2 class="slide-heading font-bold slide-mb text-center">{{ $subSlide->title }}</h2>

                {{-- Prose content above --}}
                <div class="prose max-w-none slide-content slide-prose mb-6 text-center" style="max-width: 60rem;">
                    {!! $page->markdown($subSlide->content) !!}
                </div>

                {{-- Code snippet below (centered) --}}
                <div class="slide-code">
                    @include('_partials.code-snippet', [
                        'snippet' => $snippetData['snippet'],
                        'content' => $snippetData['content'],
                        'lines' => $snippetData['lines'],
                        'error' => $snippetData['error'],
                        'viewType' => $snippetData['viewType'],
                    ])
                </div>
            </div>

            {{-- Navigation arrows --}}
            <button class="slide-nav-arrow slide-nav-up" onclick="goToSlide({{ $slideIndex - 1 }})">&uarr;</button>
            @if(!$isLastSlide || $hasFileChanges)
            <button class="slide-nav-arrow slide-nav-down" onclick="@if($isLastSlide && $hasFileChanges)document.getElementById('files').scrollIntoView({behavior:'smooth',block:'start'})@else goToSlide({{ $slideIndex + 1 }})@endif">&darr;</button>
            @endif
        </div>
        @else
        {{-- Single-column layout (no snippet) --}}
        <div class="slide min-h-[calc(100vh-4rem)] flex flex-col justify-center px-4 py-8" data-slide="{{ $slideIndex }}">
            <div class="max-w-6xl mx-auto w-full">
                {{-- Title --}}
                <h2 class="slide-heading font-bold slide-mb text-center">{!! $page->markdown($subSlide->title) !!}</h2>

                {{-- Content --}}
                <div class="prose max-w-none slide-content slide-prose">
                    {!! $page->markdown($subSlide->content) !!}
                </div>
            </div>

            {{-- Navigation arrows --}}
            <button class="slide-nav-arrow slide-nav-up" onclick="goToSlide({{ $slideIndex - 1 }})">&uarr;</button>
            @if(!$isLastSlide || $hasFileChanges)
            <button class="slide-nav-arrow slide-nav-down" onclick="@if($isLastSlide && $hasFileChanges)document.getElementById('files').scrollIntoView({behavior:'smooth',block:'start'})@else goToSlide({{ $slideIndex + 1 }})@endif">&darr;</button>
            @endif
        </div>
        @endif
        @endforeach

        {{-- Files Section - only show if there are file changes --}}
        @if($hasFileChanges)
        <div id="files" class="files-section px-4 py-8">
            <div class="w-full">
            @if($isBulkChange)
            {{-- Bulk Change Notice --}}
            <div class="max-w-2xl mx-auto text-center py-12">
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-8">
                    <svg class="w-16 h-16 text-amber-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    <h2 class="text-xl font-bold text-amber-800 mb-2">Large Changeset</h2>
                    <p class="text-gray-600 mb-4">
                        This commit includes {{ $diffFiles->count() }} changed files, which typically indicates a framework or dependency installation.
                        Individual diffs are hidden to keep the presentation focused.
                    </p>
                    <div class="inline-flex items-center space-x-4 text-sm bg-white border border-gray-200 rounded-lg px-4 py-2">
                        <span class="text-gray-500">{{ $step->getFilesChangedCount() }} files</span>
                        <span class="text-green-600 font-mono">+{{ $step->getTotalAdditions() }}</span>
                        <span class="text-red-600 font-mono">-{{ $step->getTotalDeletions() }}</span>
                    </div>
                </div>

                {{-- File list summary --}}
                <div class="mt-8 text-left">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">Files in this commit</h3>
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 max-h-64 overflow-y-auto">
                        <div class="space-y-1 text-xs font-mono">
                            @foreach($diffFiles->take(50) as $file)
                            <div class="flex items-center space-x-2 text-gray-600">
                                <span class="w-4 text-center
                                    @if($file->isNew()) text-green-600
                                    @elseif($file->isDeleted()) text-red-600
                                    @else text-yellow-600
                                    @endif">{{ $file->getStatus() }}</span>
                                <span class="truncate">{{ $file->newPath }}</span>
                            </div>
                            @endforeach
                            @if($diffFiles->count() > 50)
                            <div class="text-gray-400 pt-2">... and {{ $diffFiles->count() - 50 }} more files</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @else
            {{-- Diff Files --}}
            <div class="space-y-4">
                @foreach($diffFiles as $file)
                @include('_partials.diff-file-compact', ['file' => $file, 'commitHash' => $page->stepHash, 'gitRepo' => $page->gitRepo])
                @endforeach
            </div>
            @endif
            </div>
        </div>
        @endif
    </div>
</div>

<script>
// Total slides count (set by PHP)
const TOTAL_SLIDES = {{ $totalSlides }};

// Get all slides
function getSlides() {
    return Array.from(document.querySelectorAll('.slide'));
}

// Get current slide index based on scroll position
function getCurrentSlideIndex() {
    const slides = getSlides();
    const scrollY = window.scrollY;
    const viewportHeight = window.innerHeight;

    for (let i = slides.length - 1; i >= 0; i--) {
        const slideTop = slides[i].offsetTop;
        if (scrollY >= slideTop - viewportHeight / 3) {
            return i;
        }
    }
    return 0;
}

// Navigate to slide
function goToSlide(index) {
    const slides = getSlides();
    const filesSection = document.getElementById('files');

    if (index === 0) {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } else if (index > 0 && index < slides.length) {
        slides[index].scrollIntoView({ behavior: 'smooth', block: 'start' });
    } else if (index >= slides.length && filesSection) {
        filesSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

function nextSlide() {
    const currentIndex = getCurrentSlideIndex();
    const slides = getSlides();
    const filesSection = document.getElementById('files');

    if (currentIndex < slides.length - 1) {
        goToSlide(currentIndex + 1);
        return true;
    } else if (filesSection) {
        const filesSectionTop = filesSection.offsetTop;
        const scrollY = window.scrollY;

        if (scrollY < filesSectionTop - 50) {
            filesSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            return true;
        }
    }
    return false;
}

function prevSlide() {
    const currentIndex = getCurrentSlideIndex();
    const filesSection = document.getElementById('files');
    const scrollY = window.scrollY;

    if (filesSection) {
        const filesSectionTop = filesSection.offsetTop;
        const slides = getSlides();
        if (scrollY >= filesSectionTop - 50 && slides.length > 0) {
            goToSlide(slides.length - 1);
            return true;
        }
    }

    if (currentIndex > 0) {
        goToSlide(currentIndex - 1);
        return true;
    } else if (scrollY > 50) {
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return true;
    }
    return false;
}

// HTMX-powered navigation to preserve fullscreen
function navigateTo(url) {
    // Scroll to top immediately before swap to prevent glitch
    window.scrollTo(0, 0);

    if (typeof htmx !== 'undefined') {
        htmx.ajax('GET', url, {target: 'body', swap: 'innerHTML'}).then(function() {
            history.pushState({}, '', url);
            window.scrollTo(0, 0);
        });
    } else {
        window.location.href = url;
    }
}

document.addEventListener('keydown', function(e) {
    const prevLink = document.getElementById('prev-link');
    const nextLink = document.getElementById('next-link');

    if (e.key === 'ArrowLeft' && prevLink) {
        e.preventDefault();
        navigateTo(prevLink.href);
    } else if (e.key === 'ArrowRight' && nextLink) {
        e.preventDefault();
        navigateTo(nextLink.href);
    } else if (e.key === 'ArrowDown' || e.key === ' ' || e.key === 'PageDown') {
        if (nextSlide()) {
            e.preventDefault();
        }
    } else if (e.key === 'ArrowUp' || e.key === 'PageUp') {
        if (prevSlide()) {
            e.preventDefault();
        }
    } else if (e.key === 'Escape') {
        e.preventDefault();
        navigateTo('/');
    }
});

// Update current slide class based on scroll position
function updateCurrentSlide() {
    const slides = getSlides();
    const currentIndex = getCurrentSlideIndex();
    slides.forEach((slide, index) => {
        if (index === currentIndex) {
            slide.classList.add('current-slide');
        } else {
            slide.classList.remove('current-slide');
        }
    });
}

window.addEventListener('scroll', updateCurrentSlide);

// Smooth scroll for file navigation
function initSmoothScroll() {
    document.querySelectorAll('a[href^="#diff-"]').forEach(link => {
        if (link.dataset.scrollInit) return;
        link.dataset.scrollInit = 'true';
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                target.classList.add('ring-2', 'ring-blue-500');
                setTimeout(() => target.classList.remove('ring-2', 'ring-blue-500'), 2000);
            }
        });
    });
}

// Initialize syntax highlighting for all code views
function initSyntaxHighlighting() {
    document.querySelectorAll('.diff-file-compact code[class*="language-"], .code-snippet code[class*="language-"]').forEach(el => {
        if (el.className.includes('language-plaintext') || el.classList.contains('hljs')) {
            return;
        }

        const langMatch = el.className.match(/language-(\w+)/);
        const language = langMatch ? langMatch[1] : null;

        const text = el.textContent;

        const row = el.closest('tr');
        const isInDiffView = el.closest('.diff-line-content') !== null;
        const isAddedLine = isInDiffView && row && row.classList.contains('bg-green-50');

        const isDocblockLine = /^\s*(\*|\/\*\*|\/\*|\*\/)/.test(text) ||
                              /^\s*\*\s/.test(text) ||
                              /^\s*\/\//.test(text);

        if (language && hljs.getLanguage(language)) {
            try {
                const result = hljs.highlight(text, { language: language, ignoreIllegals: true });
                el.innerHTML = result.value;
                el.classList.add('hljs');

                if (isAddedLine && isDocblockLine && !el.querySelector('.hljs-comment')) {
                    el.innerHTML = '<span class="hljs-comment">' + el.innerHTML + '</span>';
                }
            } catch (e) {
                // Ignore highlight errors
            }
        } else if (isAddedLine && isDocblockLine) {
            el.innerHTML = '<span class="hljs-comment">' + el.textContent.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</span>';
            el.classList.add('hljs');
        }
    });
}

// Initialize all presentation step functionality
function initPresentationStep() {
    initSmoothScroll();
    initSyntaxHighlighting();
    updateCurrentSlide();
}

// Run initialization immediately (works for both initial load and HTMX swap)
initPresentationStep();
</script>
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
@endsection
