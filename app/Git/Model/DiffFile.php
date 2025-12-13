<?php

declare(strict_types=1);

namespace App\Git\Model;

use Illuminate\Support\Collection;

/**
 * Represents a file in a diff.
 */
class DiffFile
{
    /** @var Collection<int, DiffHunk> */
    private Collection $hunks;

    private ?string $oldIndexHash = null;
    private ?string $newIndexHash = null;
    private ?string $oldFile = null;
    private ?string $newFile = null;
    private bool $isNew = false;
    private bool $isDeleted = false;
    private bool $isBinary = false;

    public function __construct(
        public readonly string $oldPath,
        public readonly string $newPath,
    ) {
        $this->hunks = collect();
    }

    public function addHunk(DiffHunk $hunk): void
    {
        $this->hunks->push($hunk);
    }

    /**
     * @return Collection<int, DiffHunk>
     */
    public function getHunks(): Collection
    {
        return $this->hunks;
    }

    public function setIndexInfo(string $oldHash, string $newHash): void
    {
        $this->oldIndexHash = $oldHash;
        $this->newIndexHash = $newHash;
    }

    public function setOldFile(string $path): void
    {
        $this->oldFile = $path;
    }

    public function setNewFile(string $path): void
    {
        $this->newFile = $path;
    }

    public function setIsNew(bool $isNew): void
    {
        $this->isNew = $isNew;
    }

    public function setIsDeleted(bool $isDeleted): void
    {
        $this->isDeleted = $isDeleted;
    }

    public function setIsBinary(bool $isBinary): void
    {
        $this->isBinary = $isBinary;
    }

    public function getOldIndexHash(): ?string
    {
        return $this->oldIndexHash;
    }

    public function getNewIndexHash(): ?string
    {
        return $this->newIndexHash;
    }

    public function getOldFile(): ?string
    {
        return $this->oldFile;
    }

    public function getNewFile(): ?string
    {
        return $this->newFile;
    }

    public function isNew(): bool
    {
        return $this->isNew;
    }

    public function isDeleted(): bool
    {
        return $this->isDeleted;
    }

    public function isBinary(): bool
    {
        return $this->isBinary;
    }

    public function isRenamed(): bool
    {
        return $this->oldPath !== $this->newPath;
    }

    /**
     * Get the display path (new path, or shows rename if applicable)
     */
    public function getDisplayPath(): string
    {
        if ($this->isRenamed()) {
            return "{$this->oldPath} → {$this->newPath}";
        }

        return $this->newPath;
    }

    /**
     * Get the file extension for syntax highlighting
     */
    public function getExtension(): string
    {
        return pathinfo($this->newPath, PATHINFO_EXTENSION);
    }

    public function getAdditions(): int
    {
        return $this->hunks->sum(fn(DiffHunk $h) => $h->getAdditions());
    }

    public function getDeletions(): int
    {
        return $this->hunks->sum(fn(DiffHunk $h) => $h->getDeletions());
    }

    /**
     * Get the status indicator (A, D, M, R)
     */
    public function getStatus(): string
    {
        if ($this->isNew) {
            return 'A';
        }
        if ($this->isDeleted) {
            return 'D';
        }
        if ($this->isRenamed()) {
            return 'R';
        }
        return 'M';
    }

    public function getStatusLabel(): string
    {
        return match ($this->getStatus()) {
            'A' => 'Added',
            'D' => 'Deleted',
            'R' => 'Renamed',
            'M' => 'Modified',
            default => 'Changed',
        };
    }

    public function getStatusClass(): string
    {
        return match ($this->getStatus()) {
            'A' => 'bg-green-100 text-green-800',
            'D' => 'bg-red-100 text-red-800',
            'R' => 'bg-purple-100 text-purple-800',
            'M' => 'bg-yellow-100 text-yellow-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}
