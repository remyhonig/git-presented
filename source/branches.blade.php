---
title: Branches
---
@extends('_layouts.presentation')

@section('breadcrumb')
<span class="text-gray-600">Branches</span>
@endsection

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Branches</h1>
        <span class="text-gray-500">{{ $page->branches->count() }} branches</span>
    </div>

    @if($page->branches->isEmpty())
    <div class="bg-gray-50 rounded-lg p-8 text-center">
        <p class="text-gray-600">No branches found in this repository.</p>
    </div>
    @else
    {{-- Branch Graph --}}
    @if($page->showBranchGraph)
    <div class="mb-12">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Branch Overview</h2>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="space-y-4">
                @foreach($page->branches as $branch)
                <div class="flex items-center space-x-4">
                    {{-- Branch indicator --}}
                    <div class="w-3 h-3 rounded-full
                        @if($branch->isMain()) bg-blue-500
                        @elseif($branch->isDevelop()) bg-purple-500
                        @elseif($branch->isFeature()) bg-green-500
                        @else bg-gray-400
                        @endif
                    "></div>

                    {{-- Branch info --}}
                    <div class="flex-1">
                        <div class="flex items-center space-x-2">
                            <span class="font-mono font-medium text-gray-900">{{ $branch->getShortName() }}</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $branch->getTypeClass() }}">
                                {{ $branch->getTypeLabel() }}
                            </span>
                            @if($branch->isRemote)
                            <span class="text-xs text-gray-500">(remote)</span>
                            @endif
                        </div>
                        <div class="text-sm text-gray-500">
                            {{ $branch->getCommitCount() }} commits
                            &middot;
                            <span class="font-mono text-xs">{{ substr($branch->headHash, 0, 7) }}</span>
                        </div>
                    </div>

                    {{-- Link --}}
                    <a href="/branches/{{ $branch->getSlug() }}" class="text-blue-600 hover:text-blue-800 text-sm">
                        View &rarr;
                    </a>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Branch Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($page->branches as $branch)
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h3 class="font-mono font-semibold text-gray-900">
                        <a href="/branches/{{ $branch->getSlug() }}" class="hover:text-blue-600">
                            {{ $branch->getShortName() }}
                        </a>
                    </h3>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium mt-1 {{ $branch->getTypeClass() }}">
                        {{ $branch->getTypeLabel() }}
                    </span>
                </div>
                @if($branch->isRemote)
                <span class="text-xs text-gray-400 bg-gray-100 px-2 py-1 rounded">Remote</span>
                @endif
            </div>

            <div class="space-y-2 text-sm text-gray-600">
                <div class="flex justify-between">
                    <span>Commits</span>
                    <span class="font-medium text-gray-900">{{ $branch->getCommitCount() }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Head</span>
                    <span class="font-mono text-xs text-gray-900">{{ substr($branch->headHash, 0, 7) }}</span>
                </div>
            </div>

            @if($branch->getHeadCommit())
            <div class="mt-4 pt-4 border-t border-gray-100">
                <p class="text-sm text-gray-600 truncate">
                    {{ preg_replace('/#presentation/i', '', $branch->getHeadCommit()->subject) }}
                </p>
            </div>
            @endif
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection
