<?php

declare(strict_types=1);

namespace App\Git\Model;

readonly class FileChange
{
    public const STATUS_ADDED = 'A';
    public const STATUS_MODIFIED = 'M';
    public const STATUS_DELETED = 'D';
    public const STATUS_RENAMED = 'R';
    public const STATUS_COPIED = 'C';

    public function __construct(
        public string $path,
        public string $status,
        public ?string $oldPath = null,
        public int $additions = 0,
        public int $deletions = 0,
    ) {}

    public function isAdded(): bool
    {
        return $this->status === self::STATUS_ADDED;
    }

    public function isModified(): bool
    {
        return $this->status === self::STATUS_MODIFIED;
    }

    public function isDeleted(): bool
    {
        return $this->status === self::STATUS_DELETED;
    }

    public function isRenamed(): bool
    {
        return str_starts_with($this->status, self::STATUS_RENAMED);
    }

    public function getStatusLabel(): string
    {
        return match (true) {
            $this->isAdded() => 'Added',
            $this->isModified() => 'Modified',
            $this->isDeleted() => 'Deleted',
            $this->isRenamed() => 'Renamed',
            default => 'Changed',
        };
    }

    public function getStatusClass(): string
    {
        return match (true) {
            $this->isAdded() => 'text-green-600',
            $this->isModified() => 'text-yellow-600',
            $this->isDeleted() => 'text-red-600',
            $this->isRenamed() => 'text-blue-600',
            default => 'text-gray-600',
        };
    }

    public function getExtension(): string
    {
        return pathinfo($this->path, PATHINFO_EXTENSION);
    }

    public function getFilename(): string
    {
        return basename($this->path);
    }

    public function getDirectory(): string
    {
        return dirname($this->path);
    }
}
