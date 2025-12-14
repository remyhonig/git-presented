<?php

declare(strict_types=1);

namespace App\Git\Parser;

use App\Git\Model\DiffFile;
use App\Git\Model\DiffHunk;
use App\Git\Model\DiffLine;
use Illuminate\Support\Collection;

/**
 * Parses unified diff output into structured objects using sebastian/diff concepts.
 */
class DiffParser
{
    /**
     * Parse a unified diff string into structured DiffFile objects.
     *
     * @param array $excludePatterns Optional glob patterns to skip files during parsing
     * @return Collection<int, DiffFile>
     */
    public function parse(string $diff, array $excludePatterns = []): Collection
    {
        if (empty(trim($diff))) {
            return collect();
        }

        $files = collect();
        $lines = explode("\n", $diff);
        $currentFile = null;
        $currentHunk = null;
        $oldLineNum = 0;
        $newLineNum = 0;
        $skipCurrentFile = false;

        foreach ($lines as $line) {
            // New file diff starts
            if (str_starts_with($line, 'diff --git')) {
                if ($currentFile !== null && !$skipCurrentFile) {
                    if ($currentHunk !== null) {
                        $currentFile->addHunk($currentHunk);
                    }
                    $files->push($currentFile);
                }

                // Extract file paths from "diff --git a/path b/path"
                preg_match('/diff --git a\/(.+) b\/(.+)/', $line, $matches);
                $oldPath = $matches[1] ?? '';
                $newPath = $matches[2] ?? '';

                // Check if this file should be skipped
                $skipCurrentFile = $this->shouldExclude($oldPath, $excludePatterns)
                    || $this->shouldExclude($newPath, $excludePatterns);

                if ($skipCurrentFile) {
                    $currentFile = null;
                    $currentHunk = null;
                    continue;
                }

                $currentFile = new DiffFile($oldPath, $newPath);
                $currentHunk = null;
                continue;
            }

            // Skip lines for excluded files
            if ($skipCurrentFile) {
                continue;
            }

            if ($currentFile === null) {
                continue;
            }

            // File metadata
            if (str_starts_with($line, 'index ')) {
                preg_match('/index ([a-f0-9]+)\.\.([a-f0-9]+)/', $line, $matches);
                if (isset($matches[1], $matches[2])) {
                    $currentFile->setIndexInfo($matches[1], $matches[2]);
                }
                continue;
            }

            if (str_starts_with($line, '--- ')) {
                $currentFile->setOldFile(substr($line, 4));
                continue;
            }

            if (str_starts_with($line, '+++ ')) {
                $currentFile->setNewFile(substr($line, 4));
                continue;
            }

            if (str_starts_with($line, 'new file mode')) {
                $currentFile->setIsNew(true);
                continue;
            }

            if (str_starts_with($line, 'deleted file mode')) {
                $currentFile->setIsDeleted(true);
                continue;
            }

            if (str_starts_with($line, 'Binary files')) {
                $currentFile->setIsBinary(true);
                continue;
            }

            // Hunk header: @@ -oldStart,oldCount +newStart,newCount @@ optional context
            if (str_starts_with($line, '@@')) {
                if ($currentHunk !== null) {
                    $currentFile->addHunk($currentHunk);
                }

                preg_match('/@@ -(\d+)(?:,(\d+))? \+(\d+)(?:,(\d+))? @@(.*)/', $line, $matches);

                $oldStart = (int) ($matches[1] ?? 1);
                $oldCount = (int) ($matches[2] ?? 1);
                $newStart = (int) ($matches[3] ?? 1);
                $newCount = (int) ($matches[4] ?? 1);
                $context = trim($matches[5] ?? '');

                $currentHunk = new DiffHunk($oldStart, $oldCount, $newStart, $newCount, $context);
                $oldLineNum = $oldStart;
                $newLineNum = $newStart;
                continue;
            }

            // Diff lines within a hunk
            if ($currentHunk !== null) {
                if (str_starts_with($line, '+')) {
                    $currentHunk->addLine(new DiffLine(
                        type: DiffLine::TYPE_ADD,
                        content: substr($line, 1),
                        oldLineNumber: null,
                        newLineNumber: $newLineNum++,
                    ));
                } elseif (str_starts_with($line, '-')) {
                    $currentHunk->addLine(new DiffLine(
                        type: DiffLine::TYPE_REMOVE,
                        content: substr($line, 1),
                        oldLineNumber: $oldLineNum++,
                        newLineNumber: null,
                    ));
                } elseif (str_starts_with($line, ' ') || $line === '') {
                    $content = strlen($line) > 0 ? substr($line, 1) : '';
                    $currentHunk->addLine(new DiffLine(
                        type: DiffLine::TYPE_CONTEXT,
                        content: $content,
                        oldLineNumber: $oldLineNum++,
                        newLineNumber: $newLineNum++,
                    ));
                } elseif (str_starts_with($line, '\\')) {
                    // "\ No newline at end of file" - add as a special marker
                    $currentHunk->addLine(new DiffLine(
                        type: DiffLine::TYPE_NO_NEWLINE,
                        content: $line,
                        oldLineNumber: null,
                        newLineNumber: null,
                    ));
                } elseif (str_starts_with($line, '#')) {
                    // Git comment/note line (e.g., "# Note: ...") - skip as metadata
                    continue;
                }
            }
        }

        // Add the last file
        if ($currentFile !== null && !$skipCurrentFile) {
            if ($currentHunk !== null) {
                $currentFile->addHunk($currentHunk);
            }
            $files->push($currentFile);
        }

        return $files;
    }

    /**
     * Check if a path matches any exclude pattern.
     */
    private function shouldExclude(string $path, array $patterns): bool
    {
        if (empty($patterns) || empty($path)) {
            return false;
        }

        foreach ($patterns as $pattern) {
            if (fnmatch($pattern, $path)) {
                return true;
            }
            // Also check just the filename for patterns like *.lock
            if (fnmatch($pattern, basename($path))) {
                return true;
            }
        }
        return false;
    }

    /**
     * Parse diff and return statistics.
     */
    public function getStats(string $diff): array
    {
        $files = $this->parse($diff);

        $totalAdditions = 0;
        $totalDeletions = 0;
        $fileCount = $files->count();

        foreach ($files as $file) {
            foreach ($file->getHunks() as $hunk) {
                foreach ($hunk->getLines() as $line) {
                    if ($line->isAdd()) {
                        $totalAdditions++;
                    } elseif ($line->isRemove()) {
                        $totalDeletions++;
                    }
                }
            }
        }

        return [
            'files' => $fileCount,
            'additions' => $totalAdditions,
            'deletions' => $totalDeletions,
        ];
    }
}
