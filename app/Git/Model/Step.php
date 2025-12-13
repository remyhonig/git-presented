<?php

declare(strict_types=1);

namespace App\Git\Model;

use App\Git\Parser\SubSlideParser;
use DateTimeImmutable;
use Illuminate\Support\Collection;

class Step
{
    private ?array $parsedDescription = null;

    public function __construct(
        public readonly string $id,
        public readonly int $index,
        public readonly Commit $commit,
    ) {}

    public function getTitle(): string
    {
        // Remove #presentation tag (case-insensitive) and clean up
        $title = preg_replace('/#presentation/i', '', $this->commit->subject);
        return trim(preg_replace('/\s+/', ' ', $title));
    }

    public function getDescription(): string
    {
        return trim($this->commit->body);
    }

    /**
     * Parse the description into sub-slides (cached).
     */
    private function getParsedDescription(): array
    {
        if ($this->parsedDescription === null) {
            $parser = new SubSlideParser();
            $this->parsedDescription = $parser->parse($this->getDescription());
        }
        return $this->parsedDescription;
    }

    /**
     * Get intro content (text before first h2 heading).
     */
    public function getIntroContent(): string
    {
        return $this->getParsedDescription()['introContent'];
    }

    /**
     * Get all sub-slides parsed from h2 headings.
     *
     * @return Collection<int, SubSlide>
     */
    public function getSubSlides(): Collection
    {
        return $this->getParsedDescription()['subSlides'];
    }

    /**
     * Check if this step has sub-slides (h2 headings in description).
     */
    public function hasSubSlides(): bool
    {
        return $this->getSubSlides()->isNotEmpty();
    }

    /**
     * Get total number of visual slides for this step.
     * Always at least 1 (the title slide), plus one per sub-slide.
     */
    public function getTotalSlideCount(): int
    {
        return 1 + $this->getSubSlides()->count();
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->commit->authorDate;
    }

    public function getAuthor(): Author
    {
        return $this->commit->author;
    }

    /**
     * @return Collection<string, Author>
     */
    public function getAuthors(): Collection
    {
        return collect([$this->commit->author->email => $this->commit->author]);
    }

    public function isMerge(): bool
    {
        return $this->commit->isMerge();
    }

    public function getHash(): string
    {
        return $this->commit->shortHash;
    }

    public function getFullHash(): string
    {
        return $this->commit->hash;
    }

    /**
     * @return Collection<int, FileChange>
     */
    public function getFileChanges(): Collection
    {
        return $this->commit->getFileChanges();
    }

    public function getFilesChangedCount(): int
    {
        return $this->commit->getFilesChangedCount();
    }

    public function getTotalAdditions(): int
    {
        return $this->commit->getTotalAdditions();
    }

    public function getTotalDeletions(): int
    {
        return $this->commit->getTotalDeletions();
    }

    public function getTypeLabel(): string
    {
        if ($this->isMerge()) {
            return 'Merge';
        }

        return 'Commit';
    }

    public function getTypeClass(): string
    {
        if ($this->isMerge()) {
            return 'bg-blue-100 text-blue-800';
        }

        return 'bg-gray-100 text-gray-800';
    }
}
