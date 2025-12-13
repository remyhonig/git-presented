@extends('_layouts.main')

@php
// Look up branch from global branches collection using branchName
$branch = $page->branches->get($page->branchName);
// Check if this branch has a presentation
$presentation = $page->presentations ? $page->presentations->first(fn($p) => $p->branchName === $page->branchName) : null;
@endphp

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
                    <span class="text-gray-300">/</span>
                    <a href="/branches" class="text-gray-600 hover:text-gray-900">Branches</a>
                    <span class="text-gray-300">/</span>
                    <span class="text-gray-600 font-mono">{{ $branch->getShortName() }}</span>
                </div>
                <nav class="flex items-center space-x-4">
                    <a href="/" class="text-sm text-gray-600 hover:text-gray-900">Overview</a>
                    <a href="/branches" class="text-sm text-gray-600 hover:text-gray-900">All Branches</a>
                    @include('_partials.settings-buttons')
                </nav>
            </div>
        </div>
    </header>

    <main class="flex-1 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 py-8">
            {{-- Header --}}
            <div class="mb-8">
                <div class="flex items-center space-x-3 mb-4">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $branch->getTypeClass() }}">
                        {{ $branch->getTypeLabel() }}
                    </span>
                    @if($branch->isRemote)
                    <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">Remote</span>
                    @endif
                </div>
                <h1 class="text-3xl font-bold font-mono text-gray-900 mb-2">{{ $branch->getShortName() }}</h1>
                <p class="text-gray-600">
                    {{ $branch->getCommitCount() }} commits &middot; Head: <span class="font-mono">{{ substr($branch->headHash, 0, 7) }}</span>
                </p>
            </div>

            {{-- Commits --}}
            <div class="space-y-4">
                <h2 class="text-xl font-semibold text-gray-900">Commits</h2>

                @if($branch->getCommits()->isEmpty())
                <div class="bg-gray-50 rounded-lg p-8 text-center">
                    <p class="text-gray-600">No commits loaded for this branch.</p>
                </div>
                @else
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 divide-y divide-gray-200">
                    @foreach($branch->getCommits() as $commit)
                    <div class="p-4 hover:bg-gray-50">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2 mb-1">
                                    <span class="font-mono text-xs text-gray-500">{{ $commit->shortHash }}</span>
                                    @if($commit->isMerge())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                        Merge
                                    </span>
                                    @endif
                                </div>
                                <h3 class="text-gray-900 font-medium">{{ preg_replace('/#presentation/i', '', $commit->subject) }}</h3>
                                <div class="mt-1 text-sm text-gray-500">
                                    {{ $commit->author->name }} &middot; {{ $commit->authorDate->format('M j, Y') }}
                                </div>
                            </div>
                            @if($presentation)
                            @php
                            $step = $presentation->getSteps()->first(fn($s) => $s->getHash() === $commit->shortHash);
                            @endphp
                            @if($step)
                            <a href="{{ $page->getPresentationStepUrl($presentation->id, $step->id) }}" class="text-blue-600 hover:text-blue-800 text-sm">
                                View Slide &rarr;
                            </a>
                            @endif
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </main>
</div>
@endsection
