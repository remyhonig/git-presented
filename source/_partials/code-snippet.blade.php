{{-- Code Snippet Component for Two-Column Slides --}}
@php
/**
 * @var \App\Git\Model\CodeSnippetReference $snippet
 * @var string|null $content - For result view
 * @var \Illuminate\Support\Collection|null $lines - For diff view
 * @var string|null $error - Error message if content couldn't be loaded
 * @var string $viewType - 'result' or 'diff'
 */

$extension = $snippet->getExtension();

// Calculate longest line length for sizing
$maxLineLength = 0;
if ($viewType === 'diff' && $lines !== null) {
    foreach ($lines as $line) {
        $maxLineLength = max($maxLineLength, mb_strlen($line->content));
    }
} elseif ($content) {
    foreach (explode("\n", $content) as $line) {
        $maxLineLength = max($maxLineLength, mb_strlen($line));
    }
}
// Add padding for line numbers (~6ch) and some extra space
$snippetWidth = $maxLineLength + 10;
// Clamp between reasonable min/max values
$snippetWidth = max(40, min($snippetWidth, 120));

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
@endphp

<div class="code-snippet rounded-lg overflow-hidden flex flex-col" style="width: fit-content; max-width: 100%; background: var(--bg-card); border: 1px solid var(--border-primary); box-shadow: var(--shadow-lg);" data-snippet-width="{{ $snippetWidth }}">
    {{-- Title Bar --}}
    <div class="px-4 py-2 flex items-center justify-center" style="background: var(--bg-secondary); border-bottom: 1px solid var(--border-primary);">
        <span class="text-sm font-medium truncate" style="color: var(--text-primary);" title="{{ $snippet->filePath }}">
            {{ basename($snippet->filePath) }}
        </span>
    </div>

    {{-- Content --}}
    <div class="flex-1 overflow-auto py-4 px-4">
        @if($error)
        {{-- Error State --}}
        <div class="p-4 bg-red-50 text-red-700 h-full flex items-center justify-center">
            <div class="text-center">
                <svg class="w-8 h-8 mx-auto mb-2 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <p class="text-sm font-medium">{{ $error }}</p>
                <p class="text-xs text-red-500 mt-1">{{ $snippet->filePath }}:{{ $snippet->startLine }}-{{ $snippet->endLine }}</p>
            </div>
        </div>
        @elseif($viewType === 'diff' && $lines !== null)
        {{-- Diff View (optimized for projection - theme aware) --}}
        <table class="font-mono border-separate" style="border-spacing: 0; font-size: var(--font-code-snippet); line-height: var(--line-height-code);">
            <tbody>
                @foreach($lines as $line)
                @php
                    $bgStyle = match($line->type) {
                        'add' => 'background: rgba(34, 197, 94, 0.1);',
                        'remove' => 'background: rgba(239, 68, 68, 0.1);',
                        default => '',
                    };
                    $prefix = match($line->type) {
                        'add' => '+',
                        'remove' => '-',
                        default => ' ',
                    };
                    $prefixStyle = match($line->type) {
                        'add' => 'color: var(--accent-success);',
                        'remove' => 'color: var(--accent-danger);',
                        default => 'color: var(--text-muted);',
                    };
                    $lineNumBg = match($line->type) {
                        'add' => 'background: rgba(34, 197, 94, 0.15);',
                        'remove' => 'background: rgba(239, 68, 68, 0.15);',
                        default => '',
                    };
                @endphp
                <tr style="{{ $bgStyle }}">
                    <td class="text-right pr-2 select-none align-baseline" style="min-width: 2.5rem; font-size: inherit; line-height: inherit; color: var(--text-muted); border-right: 1px solid var(--border-primary); {{ $line->type === 'remove' ? $lineNumBg : '' }}">
                        {{ $line->oldLineNumber ?? '' }}
                    </td>
                    <td class="text-right pr-2 select-none align-baseline" style="min-width: 2.5rem; font-size: inherit; line-height: inherit; color: var(--text-muted); border-right: 1px solid var(--border-primary); {{ $line->type === 'add' ? $lineNumBg : '' }}">
                        {{ $line->newLineNumber ?? '' }}
                    </td>
                    <td class="text-center select-none align-baseline" style="width: 1.5rem; font-size: inherit; line-height: inherit; {{ $prefixStyle }}">{{ $prefix }}</td>
                    <td class="diff-line-content pl-2 whitespace-pre align-baseline" style="line-height: inherit;">
                        <code class="language-{{ $language }}" data-highlighted="no" style="color: var(--text-primary);">{{ $line->content }}</code>
                    </td>
                </tr>
                @endforeach

                @if($lines->isEmpty())
                <tr>
                    <td colspan="4" class="p-4 text-center" style="color: var(--text-muted);">
                        No changes in this line range
                    </td>
                </tr>
                @endif
            </tbody>
        </table>
        @else
        {{-- Result View (optimized for projection - theme aware) --}}
        <table class="font-mono border-separate" style="border-spacing: 0; font-size: var(--font-code-snippet);">
            <tbody>
                @foreach(explode("\n", $content ?? '') as $index => $lineContent)
                <tr>
                    <td class="text-left pl-2 select-none align-top" style="width: 4em; line-height: var(--line-height-code); padding-top: 0.1em; padding-right: 2em; color: var(--text-muted);">{{ $snippet->startLine + $index }}</td>
                    <td class="whitespace-pre align-top" style="line-height: var(--line-height-code);"><code class="language-{{ $language }}" data-highlighted="no" style="font-size: inherit; color: var(--text-primary);">{{ $lineContent }}</code></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>
