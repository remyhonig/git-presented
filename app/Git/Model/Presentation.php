<?php

declare(strict_types=1);

namespace App\Git\Model;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Presentation
{
    /** @var Collection<int, Step> */
    private Collection $steps;

    public function __construct(
        public readonly string $id,
        public readonly string $branchName,
        public readonly string $title,
        public readonly Commit $startCommit,
        public readonly Commit $endCommit,
        ?Collection $steps = null,
    ) {
        $this->steps = $steps ?? collect();
    }

    public function getSlug(): string
    {
        return Str::slug($this->branchName);
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getBranchShortName(): string
    {
        if (str_contains($this->branchName, '/')) {
            return Str::after($this->branchName, '/');
        }
        return $this->branchName;
    }

    /**
     * @return Collection<int, Step>
     */
    public function getSteps(): Collection
    {
        return $this->steps;
    }

    public function setSteps(Collection $steps): void
    {
        $this->steps = $steps;
    }

    public function getStepCount(): int
    {
        return $this->steps->count();
    }

    public function getFirstStep(): ?Step
    {
        return $this->steps->first();
    }

    public function getLastStep(): ?Step
    {
        return $this->steps->last();
    }

    public function getStep(string $stepId): ?Step
    {
        return $this->steps->first(fn(Step $s) => $s->id === $stepId);
    }

    public function getStepNavigation(string $stepId): array
    {
        $current = null;
        $prev = null;
        $next = null;

        foreach ($this->steps as $index => $step) {
            if ($step->id === $stepId) {
                $current = $step;
                $prev = $this->steps->get($index - 1);
                $next = $this->steps->get($index + 1);
                break;
            }
        }

        return [
            'current' => $current,
            'prev' => $prev,
            'next' => $next,
            'total' => $this->steps->count(),
            'index' => $current?->index ?? 0,
        ];
    }

    public function getStartDate(): \DateTimeImmutable
    {
        return $this->startCommit->authorDate;
    }

    public function getEndDate(): \DateTimeImmutable
    {
        return $this->endCommit->authorDate;
    }

    public function getAuthor(): Author
    {
        return $this->startCommit->author;
    }
}
