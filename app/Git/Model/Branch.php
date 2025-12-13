<?php

declare(strict_types=1);

namespace App\Git\Model;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Branch
{
    /** @var Collection<int, Commit>|null */
    private ?Collection $commits = null;

    /** @var callable|null */
    private $commitLoader = null;

    public function __construct(
        public readonly string $name,
        public readonly string $headHash,
        public readonly bool $isRemote = false,
    ) {}

    public function getShortName(): string
    {
        // Remove remote prefix (e.g., "origin/feature/foo" -> "feature/foo")
        if ($this->isRemote && str_contains($this->name, '/')) {
            return Str::after($this->name, '/');
        }

        return $this->name;
    }

    public function isMain(): bool
    {
        $shortName = $this->getShortName();
        return in_array($shortName, ['main', 'master'], true);
    }

    public function isFeature(): bool
    {
        return str_starts_with($this->getShortName(), 'feature/');
    }

    public function isDevelop(): bool
    {
        return in_array($this->getShortName(), ['develop', 'dev', 'development'], true);
    }

    /**
     * Set a lazy loader for commits.
     * The loader will be called only when commits are first accessed.
     */
    public function setCommitLoader(callable $loader): void
    {
        $this->commitLoader = $loader;
    }

    /**
     * @return Collection<int, Commit>
     */
    public function getCommits(): Collection
    {
        if ($this->commits === null) {
            if ($this->commitLoader !== null) {
                $this->commits = ($this->commitLoader)();
                $this->commitLoader = null; // Clear loader after use
            } else {
                $this->commits = collect();
            }
        }

        return $this->commits;
    }

    public function getCommitCount(): int
    {
        return $this->getCommits()->count();
    }

    public function getHeadCommit(): ?Commit
    {
        return $this->getCommits()->first();
    }

    public function getSlug(): string
    {
        return Str::slug($this->getShortName());
    }

    public function getTypeLabel(): string
    {
        return match (true) {
            $this->isMain() => 'Main',
            $this->isDevelop() => 'Development',
            $this->isFeature() => 'Feature',
            default => 'Branch',
        };
    }

    public function getTypeClass(): string
    {
        return match (true) {
            $this->isMain() => 'bg-blue-100 text-blue-800',
            $this->isDevelop() => 'bg-purple-100 text-purple-800',
            $this->isFeature() => 'bg-green-100 text-green-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}
