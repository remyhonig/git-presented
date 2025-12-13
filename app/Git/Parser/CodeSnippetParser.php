<?php

declare(strict_types=1);

namespace App\Git\Parser;

use App\Git\Model\CodeSnippetReference;
use Illuminate\Support\Collection;

/**
 * Parses code snippet references from commit message text.
 *
 * Supports syntax like:
 *   [filename.php:10-20]        - Result view (default), lines 10-20
 *   [filename.php:10]           - Result view, single line 10
 *   [filename.php:10-20:diff]   - Diff view, lines 10-20
 *   [filename.php:10-20:result] - Explicit result view
 */
final class CodeSnippetParser
{
    /**
     * Regex pattern to match snippet references at end of h2 lines.
     * Captures: [1] file path, [2] start line, [3] end line (optional), [4] view type (optional)
     */
    private const PATTERN = '/\[([^\]:\s]+):(\d+)(?:-(\d+))?(?::(diff|result))?\]\s*$/';

    /**
     * Parse a heading line and extract any snippet reference.
     * Returns the clean title and the snippet reference (if any).
     *
     * @return array{title: string, snippet: ?CodeSnippetReference}
     */
    public function parseHeading(string $heading): array
    {
        if (!preg_match(self::PATTERN, $heading, $matches)) {
            return [
                'title' => $heading,
                'snippet' => null,
            ];
        }

        $filePath = $matches[1];
        $startLine = (int) $matches[2];
        $endLine = isset($matches[3]) && $matches[3] !== '' ? (int) $matches[3] : $startLine;
        $viewType = $matches[4] ?? CodeSnippetReference::VIEW_RESULT;

        // Ensure start <= end
        if ($startLine > $endLine) {
            [$startLine, $endLine] = [$endLine, $startLine];
        }

        $snippet = new CodeSnippetReference(
            filePath: $filePath,
            startLine: $startLine,
            endLine: $endLine,
            viewType: $viewType,
        );

        // Remove the tag from the title
        $cleanTitle = preg_replace(self::PATTERN, '', $heading);
        $cleanTitle = rtrim($cleanTitle);

        return [
            'title' => $cleanTitle,
            'snippet' => $snippet,
        ];
    }

    /**
     * Extract all snippet references from a description text.
     * Looks for h2 headings (lines starting with ##) and extracts their references.
     *
     * @return Collection<int, array{title: string, snippet: CodeSnippetReference}>
     */
    public function extractAllFromDescription(string $description): Collection
    {
        $snippets = collect();

        // Match markdown h2 headings (## Title [file:lines])
        preg_match_all('/^##\s+(.+)$/m', $description, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $parsed = $this->parseHeading($match[1]);
            if ($parsed['snippet'] !== null) {
                $snippets->push($parsed);
            }
        }

        return $snippets;
    }

    /**
     * Remove snippet tags from a description text.
     * Returns the description with all [file:lines] tags removed from headings (h1 and h2).
     */
    public function cleanDescription(string $description): string
    {
        return preg_replace_callback(
            '/^(#{1,2}\s+)(.+)$/m',
            function ($match) {
                $parsed = $this->parseHeading($match[2]);
                return $match[1] . $parsed['title'];
            },
            $description
        );
    }
}
