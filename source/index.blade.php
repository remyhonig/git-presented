@extends('_layouts.main')

@section('body')
<div class="min-h-screen flex flex-col" style="background: var(--bg-primary);">
    {{-- Header (same style as step.blade.php) --}}
    <header class="slide-header px-4 py-2 flex-shrink-0">
        <div class="flex items-center justify-between">
            <div class="w-24"></div>
            {{-- Center: Title (default) / Settings (on hover) --}}
            <div class="relative flex items-center justify-center">
                {{-- Title - visible by default, hidden on header hover --}}
                <div class="header-title">
                    <span class="text-lg font-semibold" style="color: var(--text-primary);">{{ $page->siteName }}</span>
                </div>
                {{-- Settings - hidden by default, visible on header hover --}}
                <div class="header-settings absolute inset-0 flex items-center justify-center">
                    @include('_partials.settings-buttons')
                </div>
            </div>
            <div class="w-24"></div>
        </div>
    </header>

    {{-- Main Content --}}
    <main class="flex-1 overflow-y-auto">
        <div class="max-w-7xl mx-auto px-4 py-8">
            {{-- Presentations with diagonal panels --}}
            @if($page->presentations->isNotEmpty())
            <div class="space-y-0">
                @foreach($page->presentations as $presentation)
                @php
                    $firstStep = $presentation->getFirstStep();
                    $loopIndex = $loop->index;
                    // Alternate skew direction
                    $skewDeg = ($loopIndex % 2 === 0) ? -1.5 : 1.5;
                @endphp

                {{-- Diagonal panel container --}}
                <div class="relative">
                    {{-- Skewed background panel - uses theme secondary bg --}}
                    <div class="absolute inset-0 -mx-4 md:-mx-8 lg:-mx-16 overview-panel"
                         style="background: var(--bg-secondary); transform: skewY({{ $skewDeg }}deg); transform-origin: center;">
                    </div>

                    {{-- Content (not skewed) --}}
                    <div class="relative py-12 md:py-16">
                        <div class="max-w-3xl mx-auto">

                            {{-- Slide number indicator --}}
                            <div class="flex items-center justify-center mb-6">
                                <span class="inline-flex items-center justify-center w-10 h-10 rounded-full font-bold text-lg"
                                      style="background: var(--gradient-accent); color: var(--text-inverse);">
                                    {{ $loop->iteration }}
                                </span>
                            </div>

                            {{-- Title --}}
                            <div class="text-center mb-4">
                                @if($firstStep)
                                <h2 class="text-xl md:text-2xl font-semibold leading-tight prose-title" style="color: var(--text-secondary);">
                                    {!! $page->markdown($firstStep->getTitle()) !!}
                                </h2>
                                @else
                                <span class="text-xl" style="color: var(--text-muted);">No slides</span>
                                @endif
                            </div>

                            {{-- Description --}}
                            @if($firstStep && $firstStep->getDescription())
                            <div class="mb-6">
                                <div class="overview-prose prose mx-auto" style="color: var(--text-secondary);">
                                    {!! $page->markdown($firstStep->getCleanDescription()) !!}
                                </div>
                            </div>
                            @endif

                            {{-- Meta info --}}
                            <div class="flex items-center justify-center flex-wrap gap-x-4 gap-y-2 text-sm mb-6" style="color: var(--text-tertiary);">
                                @php $author = $firstStep ? $firstStep->getAuthor() : $presentation->getAuthor(); @endphp
                                @include('_partials.author-avatar', ['name' => $author->name, 'email' => $author->email, 'size' => 24])
                                <span style="color: var(--border-secondary);">&middot;</span>
                                <span class="font-mono flex items-center" style="color: var(--text-muted);">
                                    <svg class="w-4 h-4 mr-1" viewBox="0 0 16 16" fill="currentColor">
                                        <path fill-rule="evenodd" d="M11.75 2.5a.75.75 0 100 1.5.75.75 0 000-1.5zm-2.25.75a2.25 2.25 0 113 2.122V6A2.5 2.5 0 0110 8.5H6a1 1 0 00-1 1v1.128a2.251 2.251 0 11-1.5 0V5.372a2.25 2.25 0 111.5 0v1.836A2.492 2.492 0 016 7h4a1 1 0 001-1v-.628A2.25 2.25 0 019.5 3.25zM4.25 12a.75.75 0 100 1.5.75.75 0 000-1.5zM3.5 3.25a.75.75 0 111.5 0 .75.75 0 01-1.5 0z"></path>
                                    </svg>
                                    {{ $presentation->getBranchShortName() }}
                                </span>
                                <span style="color: var(--border-secondary);">&middot;</span>
                                <span>{{ $page->formatShortDate($presentation->getStartDate()) }}</span>
                                <span style="color: var(--border-secondary);">&middot;</span>
                                <span class="font-medium" style="color: var(--accent-primary);">{{ $presentation->getStepCount() }} slides</span>
                            </div>

                            {{-- Start button --}}
                            <div class="text-center">
                                <a href="{{ $firstStep ? $page->getPresentationStepUrl($presentation->id, $firstStep->id) : $page->getPresentationUrl($presentation->id) }}"
                                   class="inline-flex items-center px-6 py-3 rounded-full text-base font-semibold hover:scale-105 transition-transform"
                                   style="background: var(--gradient-accent); color: var(--text-inverse); box-shadow: var(--shadow-md);">
                                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M8 5v14l11-7z"/>
                                    </svg>
                                    Start presentation
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            {{-- No Presentations Found --}}
            <div class="max-w-2xl mx-auto">
                <div class="card p-8 text-center" style="background: var(--bg-card); border-color: var(--accent-warning);">
                    @if($page->isGitRepo)
                    <h2 class="text-xl font-semibold mb-2" style="color: var(--accent-warning);">No Presentations Found</h2>
                    <p class="mb-4" style="color: var(--text-secondary);">
                        No branches with <code style="background: var(--code-bg); color: var(--code-text);" class="px-2 py-1 rounded">#presentation</code> tag found in the repository.
                    </p>
                    <p class="text-sm" style="color: var(--text-tertiary);">
                        To create a presentation, add <code style="background: var(--code-bg); color: var(--code-text);" class="px-1 rounded">#presentation</code> to a commit message title.
                        All commits from that point to the branch tip will become slides.
                    </p>
                    @else
                    <h2 class="text-xl font-semibold mb-2" style="color: var(--accent-warning);">No Git Repository Found</h2>
                    <p class="mb-4" style="color: var(--text-secondary);">
                        Configure the <code style="background: var(--code-bg); color: var(--code-text);" class="px-2 py-1 rounded">GIT_REPO_PATH</code> environment variable
                        to point to a Git repository.
                    </p>
                    <p class="text-sm" style="color: var(--text-tertiary);">
                        See <code style="background: var(--code-bg); color: var(--code-text);" class="px-1 rounded">.env.example</code> for configuration options.
                    </p>
                    @endif
                </div>

                {{-- How to Create a Presentation --}}
                @if($page->isGitRepo)
                <div class="card mt-8 p-6" style="background: var(--bg-secondary);">
                    <h3 class="text-lg font-semibold mb-4" style="color: var(--text-primary);">How to Create a Presentation</h3>
                    <ol class="list-decimal list-inside space-y-2" style="color: var(--text-secondary);">
                        <li>Create a new branch for your presentation</li>
                        <li>Make your first commit with <code style="background: var(--code-bg); color: var(--code-text);" class="px-1 rounded">#presentation</code> in the commit title</li>
                        <li>Add more commits - each commit becomes a slide</li>
                        <li>Rebuild the site to see your presentation</li>
                    </ol>
                    <div class="mt-4 text-sm" style="color: var(--text-tertiary);">
                        Example commit message: <code style="background: var(--code-bg); color: var(--code-text);" class="px-2 py-1 rounded">Introduction to Git #presentation</code>
                    </div>
                </div>
                @endif
            </div>
            @endif
        </div>
    </main>
</div>

@endsection
