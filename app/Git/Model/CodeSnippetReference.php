<?php

declare(strict_types=1);

namespace App\Git\Model;

/**
 * Represents a reference to a code snippet extracted from a commit message.
 * Used to display specific file content alongside slide prose.
 */
final class CodeSnippetReference
{
    public const VIEW_RESULT = 'result';
    public const VIEW_DIFF = 'diff';

    public function __construct(
        public readonly string $filePath,
        public readonly int $startLine,
        public readonly int $endLine,
        public readonly string $viewType = self::VIEW_RESULT,
        public readonly ?string $commitHash = null,
    ) {
    }

    /**
     * Check if this snippet references a specific commit.
     */
    public function hasCommitHash(): bool
    {
        return $this->commitHash !== null;
    }

    /**
     * Generate a unique ID for this snippet reference.
     * Used to match pre-rendered content with slides.
     */
    public function getId(): string
    {
        $base = $this->filePath . ':' . $this->startLine . '-' . $this->endLine . ':' . $this->viewType;
        if ($this->commitHash !== null) {
            $base .= '@' . $this->commitHash;
        }
        return md5($base);
    }

    /**
     * Get the file extension for syntax highlighting.
     */
    public function getExtension(): string
    {
        $parts = explode('.', $this->filePath);
        return count($parts) > 1 ? strtolower(end($parts)) : '';
    }

    /**
     * Get display string for the line range.
     */
    public function getLineRangeDisplay(): string
    {
        if ($this->startLine === $this->endLine) {
            return "Line {$this->startLine}";
        }
        return "Lines {$this->startLine}-{$this->endLine}";
    }

    /**
     * Check if this is a diff view.
     */
    public function isDiffView(): bool
    {
        return $this->viewType === self::VIEW_DIFF;
    }

    /**
     * Check if this is a result view.
     */
    public function isResultView(): bool
    {
        return $this->viewType === self::VIEW_RESULT;
    }
}
