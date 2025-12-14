<?php

declare(strict_types=1);

use App\Git\Provider\GitDataProvider;
use League\CommonMark\GithubFlavoredMarkdownConverter;

/*
 * Helper functions for Git Presented
 *
 * These are loaded by config.php and provide Git configuration
 * and data provider access.
 */

// Load environment variables if .env exists (only once)
if (!defined('GIT_PRESENTED_HELPERS_LOADED')) {
    define('GIT_PRESENTED_HELPERS_LOADED', true);

    if (file_exists(__DIR__ . '/.env')) {
        // Use createMutable to ensure vars are set even if already in environment
        // Load into both $_ENV and $_SERVER for maximum compatibility
        $dotenv = Dotenv\Dotenv::createMutable(__DIR__);
        $dotenv->load();
    }
}

if (!function_exists('env')) {
    /**
     * Get environment variable with fallback, checking multiple sources
     */
    function env(string $key, mixed $default = null): mixed
    {
        // Check $_ENV first (populated by dotenv)
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }

        // Check getenv() as fallback (system environment)
        $value = getenv($key);
        if ($value !== false && $value !== '') {
            return $value;
        }

        // Check $_SERVER as last resort
        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            return $_SERVER[$key];
        }

        return $default;
    }
}

if (!function_exists('getGitConfig')) {
    /**
     * Get Git configuration from environment or defaults
     */
    function getGitConfig(): array
    {
        $excludePatterns = env('GIT_EXCLUDE_PATTERNS');

        return [
            'repo_path' => env('GIT_REPO_PATH', __DIR__),
            'exclude_patterns' => $excludePatterns !== null
                ? array_map('trim', explode(',', str_replace('"', '', $excludePatterns)))
                : ['vendor/*', '*.lock', 'composer.lock', 'package-lock.json', 'yarn.lock', 'node_modules/*', 'config/reference.php', '*/reference.php', 'CLAUDE.md'],
        ];
    }
}

if (!function_exists('createGitDataProvider')) {
    /**
     * Create and return the Git data provider
     */
    function createGitDataProvider(): GitDataProvider
    {
        return new GitDataProvider(getGitConfig());
    }
}

if (!function_exists('parseMarkdown')) {
    /**
     * Parse Markdown text to HTML using GitHub Flavored Markdown
     */
    function parseMarkdown(string $text): string
    {
        static $converter = null;

        if ($converter === null) {
            $converter = new GithubFlavoredMarkdownConverter([
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]);
        }

        return $converter->convert($text)->getContent();
    }
}

if (!function_exists('getMarkdownConverter')) {
    /**
     * Get the Markdown converter instance for use in templates
     */
    function getMarkdownConverter(): GithubFlavoredMarkdownConverter
    {
        static $converter = null;

        if ($converter === null) {
            $converter = new GithubFlavoredMarkdownConverter([
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]);
        }

        return $converter;
    }
}

if (!function_exists('parseMarkdownWithSnippets')) {
    /**
     * Parse Markdown text to HTML, replacing inline [file:lines] references with rendered code snippets.
     *
     * Supports two types of file references:
     * - [file.php:10-20] - File from the current commit
     * - [/path/to/file.php:10-20] - File from the working directory (starts with /)
     *
     * @param string $text The markdown text
     * @param \App\Git\Repository|null $gitRepo The git repository for fetching file content
     * @param string|null $commitHash The commit hash to fetch content from
     * @return string The rendered HTML with inline code snippets
     */
    function parseMarkdownWithSnippets(string $text, $gitRepo = null, ?string $commitHash = null): string
    {
        if ($gitRepo === null) {
            return parseMarkdown($text);
        }

        // Helper function to check if an offset is inside backtick-delimited code
        $isInsideBackticks = function (string $text, int $offset): bool {
            // Check for code blocks (```) - find all code block regions
            preg_match_all('/```[\s\S]*?```/m', $text, $codeBlocks, PREG_OFFSET_CAPTURE);
            foreach ($codeBlocks[0] as $block) {
                $blockStart = $block[1];
                $blockEnd = $blockStart + strlen($block[0]);
                if ($offset >= $blockStart && $offset < $blockEnd) {
                    return true;
                }
            }

            // Check for inline code (`) - but not inside code blocks
            // Find all inline code regions (single backticks, not triple)
            preg_match_all('/(?<!`)`(?!`)([^`\n]+)`(?!`)/m', $text, $inlineCode, PREG_OFFSET_CAPTURE);
            foreach ($inlineCode[0] as $code) {
                $codeStart = $code[1];
                $codeEnd = $codeStart + strlen($code[0]);
                if ($offset >= $codeStart && $offset < $codeEnd) {
                    return true;
                }
            }

            return false;
        };

        // Pattern to match [file:lines] or [file:lines:diff] or [file:lines:diff@commit] anywhere in text
        // Must be on its own line (possibly with whitespace)
        // File path can optionally start with / for working directory files
        // Optional @commitHash suffix to reference a specific commit
        $pattern = '/^\s*\[(\/?)([^\]:\s]+):(\d+)(?:-(\d+))?(?::(diff|result))?(?:@([a-fA-F0-9]+))?\]\s*$/m';

        // Find all matches and store snippet data with placeholders
        if (!preg_match_all($pattern, $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            return parseMarkdown($text);
        }

        // Generate unique placeholders and collect snippets to render
        $snippets = [];
        $counter = 0;

        // Process matches in reverse order to preserve offsets when replacing
        $matches = array_reverse($matches);

        foreach ($matches as $match) {
            $fullMatch = $match[0][0];
            $offset = $match[0][1];

            // Skip if inside backticks (code block or inline code)
            if ($isInsideBackticks($text, $offset)) {
                continue;
            }

            $isWorkingDir = $match[1][0] === '/';
            $filePath = $match[2][0];
            $startLine = (int) $match[3][0];
            $endLine = isset($match[4][0]) && $match[4][0] !== '' ? (int) $match[4][0] : $startLine;
            $viewType = isset($match[5][0]) && $match[5][0] !== '' ? $match[5][0] : 'result';
            $specificCommit = isset($match[6][0]) && $match[6][0] !== '' ? $match[6][0] : null;

            // Ensure start <= end
            if ($startLine > $endLine) {
                [$startLine, $endLine] = [$endLine, $startLine];
            }

            // Create unique placeholder that won't be stripped by markdown
            // Use a format that becomes a paragraph with identifiable content
            $placeholder = "SNIPPETPLACEHOLDER{$counter}ENDSNIPPET";
            $counter++;

            // Create snippet reference
            $snippet = new \App\Git\Model\CodeSnippetReference(
                filePath: $filePath,
                startLine: $startLine,
                endLine: $endLine,
                viewType: $viewType,
                commitHash: $specificCommit,
            );

            // Fetch content - from working directory or commit
            // Priority: 1) Working dir (/ prefix), 2) Specific commit (@hash), 3) Current commit, 4) Working dir fallback
            if ($isWorkingDir) {
                $result = $gitRepo->getWorkingSnippetContent($snippet);
            } elseif ($specificCommit !== null) {
                // Use the explicitly specified commit
                $result = $gitRepo->getSnippetContent($specificCommit, $snippet);
            } elseif ($commitHash !== null) {
                $result = $gitRepo->getSnippetContent($commitHash, $snippet);
            } else {
                // No commit hash, try working directory as fallback
                $result = $gitRepo->getWorkingSnippetContent($snippet);
            }

            // Store for later replacement
            $snippets[$placeholder] = renderInlineSnippet($snippet, $result);

            // Replace snippet reference with placeholder
            $text = substr_replace($text, $placeholder, $offset, strlen($fullMatch));
        }

        // Parse the markdown (placeholders are HTML comments, will pass through)
        $html = parseMarkdown($text);

        // Replace placeholders with actual snippet HTML
        foreach ($snippets as $placeholder => $snippetHtml) {
            // Handle various ways markdown might wrap the placeholder:
            // 1. Wrapped in its own <p> tag
            $html = preg_replace(
                '/<p>' . preg_quote($placeholder, '/') . '<\/p>/',
                $snippetHtml,
                $html
            );
            // 2. At the start of a <p> tag with content after
            $html = preg_replace(
                '/<p>' . preg_quote($placeholder, '/') . '\n/',
                $snippetHtml . "\n<p>",
                $html
            );
            // 3. Plain replacement (shouldn't happen, but just in case)
            $html = str_replace($placeholder, $snippetHtml, $html);
        }

        return $html;
    }
}

if (!function_exists('renderInlineSnippet')) {
    /**
     * Render a code snippet as inline HTML.
     *
     * @param \App\Git\Model\CodeSnippetReference $snippet
     * @param array $result The result from getSnippetContent
     * @return string HTML for the code snippet
     */
    function renderInlineSnippet(\App\Git\Model\CodeSnippetReference $snippet, array $result): string
    {
        $extension = $snippet->getExtension();
        $content = $result['content'] ?? null;
        $lines = $result['lines'] ?? null;
        $error = $result['error'] ?? null;
        $viewType = $result['viewType'] ?? 'result';

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

        $html = '<div class="code-snippet inline-snippet rounded-lg overflow-hidden flex flex-col my-4 mx-auto" style="width: 100%; background: var(--bg-card); border: 1px solid var(--border-primary); box-shadow: var(--shadow-lg);">';

        // Title Bar
        $html .= '<div class="px-4 py-2 flex items-center justify-center" style="background: var(--bg-secondary); border-bottom: 1px solid var(--border-primary);">';
        $html .= '<span class="text-sm font-medium truncate" style="color: var(--text-primary);" title="' . htmlspecialchars($snippet->filePath) . '">';
        $html .= htmlspecialchars(basename($snippet->filePath));
        $html .= '</span></div>';

        // Content
        $html .= '<div class="flex-1 overflow-auto" style="padding: 0.5rem 0.75rem 0.5rem 0;">';

        if ($error) {
            // Error state
            $html .= '<div class="p-4 bg-red-50 text-red-700">';
            $html .= '<p class="text-sm font-medium">' . htmlspecialchars($error) . '</p>';
            $html .= '<p class="text-xs text-red-500 mt-1">' . htmlspecialchars($snippet->filePath) . ':' . $snippet->startLine . '-' . $snippet->endLine . '</p>';
            $html .= '</div>';
        } elseif ($viewType === 'diff' && $lines !== null) {
            // Diff view
            $html .= '<table class="font-mono border-separate" style="border-spacing: 0; font-size: var(--font-code-snippet); line-height: var(--line-height-code);"><tbody>';
            foreach ($lines as $line) {
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

                $html .= '<tr style="' . $bgStyle . '">';
                $html .= '<td class="text-right pr-2 select-none align-baseline" style="min-width: 2.5rem; font-size: inherit; line-height: inherit; color: var(--text-muted); border-right: 1px solid var(--border-primary); ' . ($line->type === 'remove' ? $lineNumBg : '') . '">' . ($line->oldLineNumber ?? '') . '</td>';
                $html .= '<td class="text-right pr-2 select-none align-baseline" style="min-width: 2.5rem; font-size: inherit; line-height: inherit; color: var(--text-muted); border-right: 1px solid var(--border-primary); ' . ($line->type === 'add' ? $lineNumBg : '') . '">' . ($line->newLineNumber ?? '') . '</td>';
                $html .= '<td class="text-center select-none align-baseline" style="width: 1.5rem; font-size: inherit; line-height: inherit; ' . $prefixStyle . '">' . $prefix . '</td>';
                $html .= '<td class="diff-line-content pl-2 whitespace-pre align-baseline" style="line-height: inherit;"><code class="language-' . $language . '" data-highlighted="no" style="color: var(--text-primary);">' . htmlspecialchars($line->content) . '</code></td>';
                $html .= '</tr>';
            }
            if (count($lines) === 0) {
                $html .= '<tr><td colspan="4" class="p-4 text-center" style="color: var(--text-muted);">No changes in this line range</td></tr>';
            }
            $html .= '</tbody></table>';
        } else {
            // Result view
            $html .= '<table class="font-mono" style="border-collapse: collapse; font-size: var(--font-code-snippet);"><tbody>';
            $contentLines = explode("\n", $content ?? '');
            foreach ($contentLines as $index => $lineContent) {
                $html .= '<tr>';
                $html .= '<td class="text-right select-none" style="padding: 0 1em 0 1em; line-height: 1.4; color: var(--text-muted); vertical-align: top;">' . ($snippet->startLine + $index) . '</td>';
                $html .= '<td class="whitespace-pre" style="line-height: 1.4; vertical-align: top;"><code class="language-' . $language . '" data-highlighted="no" style="font-size: inherit; color: var(--text-primary);">' . htmlspecialchars($lineContent) . '</code></td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
        }

        $html .= '</div></div>';

        return $html;
    }
}
