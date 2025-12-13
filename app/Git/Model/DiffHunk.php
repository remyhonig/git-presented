<?php

declare(strict_types=1);

namespace App\Git\Model;

use Illuminate\Support\Collection;

/**
 * Represents a hunk (section) in a diff file.
 */
class DiffHunk
{
    /** @var Collection<int, DiffLine> */
    private Collection $lines;

    public function __construct(
        public readonly int $oldStart,
        public readonly int $oldCount,
        public readonly int $newStart,
        public readonly int $newCount,
        public readonly string $context = '',
    ) {
        $this->lines = collect();
    }

    public function addLine(DiffLine $line): void
    {
        $this->lines->push($line);
    }

    /**
     * @return Collection<int, DiffLine>
     */
    public function getLines(): Collection
    {
        return $this->lines;
    }

    /**
     * Get the hunk header string (e.g., "@@ -1,5 +1,7 @@")
     */
    public function getHeader(): string
    {
        $old = $this->oldCount === 1 ? (string) $this->oldStart : "{$this->oldStart},{$this->oldCount}";
        $new = $this->newCount === 1 ? (string) $this->newStart : "{$this->newStart},{$this->newCount}";
        $ctx = $this->context ? " {$this->context}" : '';

        return "@@ -{$old} +{$new} @@{$ctx}";
    }

    public function getAdditions(): int
    {
        return $this->lines->filter(fn(DiffLine $l) => $l->isAdd())->count();
    }

    public function getDeletions(): int
    {
        return $this->lines->filter(fn(DiffLine $l) => $l->isRemove())->count();
    }
}
