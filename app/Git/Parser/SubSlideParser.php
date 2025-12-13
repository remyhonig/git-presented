<?php

declare(strict_types=1);

namespace App\Git\Parser;

use App\Git\Model\SubSlide;
use App\Git\Model\CodeSnippetReference;
use Illuminate\Support\Collection;

/**
 * Parses a commit description into sub-slides based on h2 headings.
 *
 * A description like:
 *   Some intro text.
 *
 *   ## First Section [file.php:10-20]
 *   Content for first section.
 *
 *   ## Second Section
 *   Content for second section.
 *
 * Becomes:
 *   - introContent: "Some intro text."
 *   - subSlides: [
 *       SubSlide(0, "First Section", "Content for first section.", snippet),
 *       SubSlide(1, "Second Section", "Content for second section.", null),
 *     ]
 */
final class SubSlideParser
{
    /**
     * Regex to match snippet references at end of heading text.
     * Captures: [1] file path, [2] start line, [3] end line (optional), [4] view type (optional)
     */
    private const SNIPPET_PATTERN = '/\[([^\]:\s]+):(\d+)(?:-(\d+))?(?::(diff|result))?\]\s*$/';

    /**
     * Parse a description into sub-slides.
     *
     * @return array{introContent: string, subSlides: Collection<int, SubSlide>}
     */
    public function parse(string $description): array
    {
        $lines = explode("\n", $description);
        $introContent = '';
        $subSlides = collect();

        $currentTitle = null;
        $currentSnippet = null;
        $currentContent = [];
        $slideIndex = 0;
        $foundFirstHeading = false;
        $insideCodeBlock = false;

        foreach ($lines as $line) {
            // Track code block state (lines starting with ```)
            if (preg_match('/^```/', $line)) {
                $insideCodeBlock = !$insideCodeBlock;
            }

            // Check if this line is an h1 or h2 heading (only if not inside code block)
            if (!$insideCodeBlock && preg_match('/^#{1,2}\s+(.+)$/', $line, $match)) {
                // If we have a previous section, save it
                if ($foundFirstHeading && $currentTitle !== null) {
                    $subSlides->push(new SubSlide(
                        index: $slideIndex++,
                        title: $currentTitle,
                        content: $this->trimContent($currentContent),
                        snippetReference: $currentSnippet,
                    ));
                }

                $foundFirstHeading = true;

                // Parse the new heading
                $parsed = $this->parseHeading($match[1]);
                $currentTitle = $parsed['title'];
                $currentSnippet = $parsed['snippet'];
                $currentContent = [];
            } elseif (!$foundFirstHeading) {
                // Content before first heading goes to intro
                $introContent .= $line . "\n";
            } else {
                // Content after a heading
                $currentContent[] = $line;
            }
        }

        // Don't forget the last section
        if ($foundFirstHeading && $currentTitle !== null) {
            $subSlides->push(new SubSlide(
                index: $slideIndex,
                title: $currentTitle,
                content: $this->trimContent($currentContent),
                snippetReference: $currentSnippet,
            ));
        }

        return [
            'introContent' => trim($introContent),
            'subSlides' => $subSlides,
        ];
    }

    /**
     * Parse a heading line and extract any snippet reference.
     *
     * @return array{title: string, snippet: ?CodeSnippetReference}
     */
    private function parseHeading(string $heading): array
    {
        if (!preg_match(self::SNIPPET_PATTERN, $heading, $matches)) {
            return [
                'title' => trim($heading),
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
        $cleanTitle = preg_replace(self::SNIPPET_PATTERN, '', $heading);
        $cleanTitle = trim($cleanTitle);

        return [
            'title' => $cleanTitle,
            'snippet' => $snippet,
        ];
    }

    /**
     * Trim content array to string, removing leading/trailing blank lines.
     */
    private function trimContent(array $lines): string
    {
        // Remove leading empty lines
        while (!empty($lines) && trim($lines[0]) === '') {
            array_shift($lines);
        }

        // Remove trailing empty lines
        while (!empty($lines) && trim(end($lines)) === '') {
            array_pop($lines);
        }

        return implode("\n", $lines);
    }
}
