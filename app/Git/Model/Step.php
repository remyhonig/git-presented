<?php

declare(strict_types=1);

namespace App\Git\Model;

use App\Git\Parser\SubSlideParser;
use App\Git\Parser\CodeSnippetParser;
use DateTimeImmutable;
use Illuminate\Support\Collection;

class Step
{
    private ?array $parsedDescription = null;
    private ?array $parsedTitle = null;

    public function __construct(
        public readonly string $id,
        public readonly int $index,
        public readonly Commit $commit,
    ) {}

    /**
     * Parse the title and extract any code snippet reference (cached).
     */
    private function getParsedTitle(): array
    {
        if ($this->parsedTitle === null) {
            // First remove #presentation tag
            $rawTitle = preg_replace('/#presentation/i', '', $this->commit->subject);
            $rawTitle = trim(preg_replace('/\s+/', ' ', $rawTitle));

            // Then parse for code snippet references
            $parser = new CodeSnippetParser();
            $this->parsedTitle = $parser->parseHeading($rawTitle);
        }
        return $this->parsedTitle;
    }

    /**
     * Get the clean title without code snippet references.
     */
    public function getTitle(): string
    {
        return $this->getParsedTitle()['title'];
    }

    /**
     * Get the code snippet reference from the title, if any.
     */
    public function getTitleSnippet(): ?CodeSnippetReference
    {
        return $this->getParsedTitle()['snippet'];
    }

    /**
     * Check if the title has a code snippet reference.
     */
    public function hasTitleSnippet(): bool
    {
        return $this->getTitleSnippet() !== null;
    }

    public function getDescription(): string
    {
        return trim($this->commit->body);
    }

    /**
     * Get the description with code snippet references removed from headings.
     */
    public function getCleanDescription(): string
    {
        $parser = new CodeSnippetParser();
        return $parser->cleanDescription($this->getDescription());
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
