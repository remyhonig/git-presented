<?php

declare(strict_types=1);

namespace App\Git\Model;

use DateTimeImmutable;
use Illuminate\Support\Collection;

class Commit
{
    /** @var Collection<int, FileChange> */
    private Collection $fileChanges;

    /** @var array<string> */
    private array $parentHashes;

    private ?string $diff = null;

    public function __construct(
        public readonly string $hash,
        public readonly string $shortHash,
        public readonly string $subject,
        public readonly string $body,
        public readonly Author $author,
        public readonly Author $committer,
        public readonly DateTimeImmutable $authorDate,
        public readonly DateTimeImmutable $commitDate,
        array $parentHashes = [],
        ?Collection $fileChanges = null,
    ) {
        $this->parentHashes = $parentHashes;
        $this->fileChanges = $fileChanges ?? collect();
    }

    public function isMerge(): bool
    {
        return count($this->parentHashes) > 1;
    }

    public function isInitial(): bool
    {
        return count($this->parentHashes) === 0;
    }

    /**
     * @return array<string>
     */
    public function getParentHashes(): array
    {
        return $this->parentHashes;
    }

    public function getFirstParentHash(): ?string
    {
        return $this->parentHashes[0] ?? null;
    }

    /**
     * @return Collection<int, FileChange>
     */
    public function getFileChanges(): Collection
    {
        return $this->fileChanges;
    }

    public function setFileChanges(Collection $fileChanges): void
    {
        $this->fileChanges = $fileChanges;
    }

    public function getDiff(): ?string
    {
        return $this->diff;
    }

    public function setDiff(string $diff): void
    {
        $this->diff = $diff;
    }

    public function getFullMessage(): string
    {
        return trim($this->subject . "\n\n" . $this->body);
    }

    public function getBodyFirstParagraph(): string
    {
        $paragraphs = preg_split('/\n\s*\n/', trim($this->body));
        return $paragraphs[0] ?? '';
    }

    public function getTotalAdditions(): int
    {
        return $this->fileChanges->sum('additions');
    }

    public function getTotalDeletions(): int
    {
        return $this->fileChanges->sum('deletions');
    }

    public function getFilesChangedCount(): int
    {
        return $this->fileChanges->count();
    }

    public function getMergeSubject(): ?string
    {
        if (!$this->isMerge()) {
            return null;
        }

        // Parse common merge commit patterns
        if (preg_match("/^Merge (?:pull request|branch) ['\"]?(.+?)['\"]?/", $this->subject, $matches)) {
            return $matches[1];
        }

        return $this->subject;
    }

    public function getStepId(): string
    {
        return $this->shortHash;
    }
}
