{{-- Compact Diff File Component for Slide View (GitHub Light Theme) --}}
@php
/** @var \App\Git\Model\DiffFile $file */
/** @var string $commitHash */
/** @var \App\Git\Repository $gitRepo */
$fileId = 'diff-' . md5($file->newPath);
$extension = $file->getExtension();
// Map file extensions to highlight.js language names
$languageMap = [
    'php' => 'php',
    'js' => 'javascript',
    'ts' => 'typescript',
    'jsx' => 'javascript',
    'tsx' => 'typescript',
    'vue' => 'xml',
    'html' => 'xml',
    'htm' => 'xml',
    'xml' => 'xml',
    'css' => 'css',
    'scss' => 'scss',
    'sass' => 'sass',
    'less' => 'less',
    'json' => 'json',
    'yaml' => 'yaml',
    'yml' => 'yaml',
    'md' => 'markdown',
    'py' => 'python',
    'rb' => 'ruby',
    'java' => 'java',
    'kt' => 'kotlin',
    'go' => 'go',
    'rs' => 'rust',
    'c' => 'c',
    'cpp' => 'cpp',
    'h' => 'c',
    'hpp' => 'cpp',
    'cs' => 'csharp',
    'swift' => 'swift',
    'sh' => 'bash',
    'bash' => 'bash',
    'zsh' => 'bash',
    'sql' => 'sql',
    'graphql' => 'graphql',
    'dockerfile' => 'dockerfile',
    'makefile' => 'makefile',
    'env' => 'ini',
    'ini' => 'ini',
    'toml' => 'ini',
    'twig' => 'twig',
    'blade.php' => 'php',
];
$language = $languageMap[strtolower($extension)] ?? 'plaintext';

// Determine which views are available
$isNewFile = $file->isNew();
$isDeletedFile = $file->isDeleted();
$isBinaryFile = $file->isBinary();

// Get file contents for the different views (only if not binary)
$newContent = null;
$oldContent = null;
if (!$isBinaryFile && isset($gitRepo) && isset($commitHash)) {
    try {
        if (!$isDeletedFile) {
            $newContent = $gitRepo->getFileContent($commitHash, $file->newPath);
        }
    } catch (\Exception $e) {
        $newContent = null;
    }
    try {
        if (!$isNewFile) {
            $oldContent = $gitRepo->getFileContentAtParent($commitHash, $file->oldPath ?: $file->newPath);
        }
    } catch (\Exception $e) {
        $oldContent = null;
    }
}
@endphp

<div id="{{ $fileId }}" class="diff-file-compact scroll-mt-4 border border-gray-300 rounded-md overflow-hidden shadow-sm" x-data="{ viewMode: 'diff' }">
    {{-- File Header (GitHub-style) --}}
    <div class="diff-file-header bg-gray-100 px-3 py-2 flex items-center justify-between border-b border-gray-300">
        <div class="flex items-center space-x-2 min-w-0">
            <span class="flex-shrink-0 inline-flex items-center justify-center w-5 h-5 rounded text-xs font-medium
                @if($file->isNew()) bg-green-100 text-green-700 border border-green-200
                @elseif($file->isDeleted()) bg-red-100 text-red-700 border border-red-200
                @elseif($file->isRenamed()) bg-purple-100 text-purple-700 border border-purple-200
                @else bg-yellow-100 text-yellow-700 border border-yellow-200
                @endif">
                {{ $file->getStatus() }}
            </span>
            <span class="font-mono text-sm text-gray-900 truncate font-medium">{{ $file->getDisplayPath() }}</span>
        </div>
        <div class="flex-shrink-0 flex items-center space-x-3 ml-2">
            {{-- View Toggle Button (cycles through available views) --}}
            @if(!$isBinaryFile)
            @php
                $availableViews = ['diff'];
                if (!$isDeletedFile && $newContent !== null) $availableViews[] = 'new';
                if (!$isNewFile && $oldContent !== null) $availableViews[] = 'old';
            @endphp
            @if(count($availableViews) > 1)
            <button @click="viewMode = viewMode === 'diff' ? '{{ $availableViews[1] ?? 'diff' }}' : (viewMode === '{{ $availableViews[1] ?? '' }}' ? '{{ $availableViews[2] ?? 'diff' }}' : 'diff')"
                    class="view-toggle-btn px-2 py-1 text-xs rounded border transition-colors opacity-60 hover:opacity-100"
                    :title="'View: ' + (viewMode === 'diff' ? 'Diff' : (viewMode === 'new' ? 'Result' : 'Before'))">
                <span x-text="viewMode === 'diff' ? 'Diff' : (viewMode === 'new' ? 'Result' : 'Before')"></span>
            </button>
            @endif
            @endif
            {{-- Stats --}}
            <div class="flex items-center space-x-2 text-xs font-mono">
                @if($file->getAdditions() > 0)
                <span class="text-green-700 bg-green-50 px-1.5 py-0.5 rounded">+{{ $file->getAdditions() }}</span>
                @endif
                @if($file->getDeletions() > 0)
                <span class="text-red-700 bg-red-50 px-1.5 py-0.5 rounded">-{{ $file->getDeletions() }}</span>
                @endif
            </div>
        </div>
    </div>

    @if($isBinaryFile)
    {{-- Binary file notice --}}
    <div class="bg-gray-50 px-3 py-6 text-center text-gray-500 text-sm">
        Binary file not shown
    </div>
    @else
    {{-- Diff View --}}
    <div x-show="viewMode === 'diff'" class="diff-hunks bg-white">
        @foreach($file->getHunks() as $hunk)
        <div class="diff-hunk">
            {{-- Diff Lines (GitHub-style table, optimized for projection) --}}
            <table class="w-full font-mono border-separate" style="border-spacing: 0; font-size: 1rem; line-height: 1.8;">
                <tbody>
                    @foreach($hunk->getLines() as $line)
                    <tr class="
                        @if($line->isAdd()) bg-green-50 hover:bg-green-100
                        @elseif($line->isRemove()) bg-red-50 hover:bg-red-100
                        @else hover:bg-gray-50
                        @endif">
                        {{-- Old line number --}}
                        <td class="text-right pr-3 select-none text-gray-400 border-r border-gray-200 align-baseline
                            @if($line->isAdd()) bg-green-100/50
                            @elseif($line->isRemove()) bg-red-100/50
                            @else bg-gray-50
                            @endif" style="min-width: 3rem; font-size: 1rem; line-height: 1.8;">
                            <span class="px-1">{{ $line->oldLineNumber ?? '' }}</span>
                        </td>
                        {{-- New line number --}}
                        <td class="text-right pr-3 select-none text-gray-400 border-r border-gray-200 align-baseline
                            @if($line->isAdd()) bg-green-100/50
                            @elseif($line->isRemove()) bg-red-100/50
                            @else bg-gray-50
                            @endif" style="min-width: 3rem; font-size: 1rem; line-height: 1.8;">
                            <span class="px-1">{{ $line->newLineNumber ?? '' }}</span>
                        </td>
                        {{-- Prefix (+/-/space) --}}
                        <td class="text-center select-none align-baseline
                            @if($line->isAdd()) text-green-700 bg-green-100
                            @elseif($line->isRemove()) text-red-700 bg-red-100
                            @elseif($line->isNoNewline()) text-gray-400 bg-gray-100 italic
                            @else text-gray-400
                            @endif" style="width: 1.5rem; font-size: 1rem; line-height: 1.8;">
                            {{ $line->getPrefix() }}
                        </td>
                        {{-- Content with syntax highlighting --}}
                        <td class="diff-line-content pl-2 whitespace-pre overflow-x-auto align-baseline
                            @if($line->isAdd()) text-green-900
                            @elseif($line->isRemove()) text-red-900
                            @elseif($line->isNoNewline()) text-gray-500 italic
                            @else text-gray-800
                            @endif" style="line-height: 1.8;"><code class="language-{{ $language }}" data-highlighted="no">{{ $line->content }}</code></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endforeach
    </div>

    {{-- Result (New) View (optimized for projection) --}}
    @if(!$isDeletedFile && $newContent !== null)
    <div x-show="viewMode === 'new'" style="display: none;" class="bg-white">
        <table class="w-full font-mono border-separate" style="border-spacing: 0; font-size: 1rem; line-height: 1.8;">
            <tbody>
                @foreach(explode("\n", $newContent) as $lineNum => $lineContent)
                <tr class="hover:bg-gray-50">
                    <td class="text-right pr-3 select-none text-gray-400 border-r border-gray-200 bg-gray-50 align-baseline" style="min-width: 3rem; font-size: 1rem; line-height: 1.8;">
                        <span class="px-1">{{ $lineNum + 1 }}</span>
                    </td>
                    <td class="pl-3 whitespace-pre overflow-x-auto text-gray-800 align-baseline" style="line-height: 1.8;"><code class="language-{{ $language }}" data-highlighted="no">{{ $lineContent }}</code></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Before (Old) View (optimized for projection) --}}
    @if(!$isNewFile && $oldContent !== null)
    <div x-show="viewMode === 'old'" style="display: none;" class="bg-white">
        <table class="w-full font-mono border-separate" style="border-spacing: 0; font-size: 1rem; line-height: 1.8;">
            <tbody>
                @foreach(explode("\n", $oldContent) as $lineNum => $lineContent)
                <tr class="hover:bg-gray-50">
                    <td class="text-right pr-3 select-none text-gray-400 border-r border-gray-200 bg-gray-50 align-baseline" style="min-width: 3rem; font-size: 1rem; line-height: 1.8;">
                        <span class="px-1">{{ $lineNum + 1 }}</span>
                    </td>
                    <td class="pl-3 whitespace-pre overflow-x-auto text-gray-800 align-baseline" style="line-height: 1.8;"><code class="language-{{ $language }}" data-highlighted="no">{{ $lineContent }}</code></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
    @endif
</div>
