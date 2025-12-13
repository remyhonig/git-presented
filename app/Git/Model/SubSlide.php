<?php

declare(strict_types=1);

namespace App\Git\Model;

/**
 * Represents a sub-slide within a step.
 *
 * A step's description can be split into multiple sub-slides based on h2 headings.
 * Each sub-slide has a title (from h2), content (markdown between headings),
 * and optionally a code snippet reference.
 */
class SubSlide
{
    public function __construct(
        public readonly int $index,
        public readonly string $title,
        public readonly string $content,
        public readonly ?CodeSnippetReference $snippetReference = null,
    ) {}

    public function hasSnippet(): bool
    {
        return $this->snippetReference !== null;
    }

    public function getSnippetId(): ?string
    {
        return $this->snippetReference?->getId();
    }
}
