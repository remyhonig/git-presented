<?php

declare(strict_types=1);

namespace App\Git\Parser;

use App\Git\Model\Branch;
use App\Git\Model\Commit;
use App\Git\Model\Presentation;
use App\Git\Model\Step;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use App\Git\Parser\CodeSnippetParser;

class PresentationBuilder
{
    private const PRESENTATION_TAG = '#presentation';

    public function __construct(
        private readonly CommitParser $commitParser,
        private readonly BranchParser $branchParser,
    ) {}

    /**
     * Find all presentations across all branches.
     * A presentation is defined by a commit with #presentation in the subject.
     *
     * @return Collection<string, Presentation>
     */
    public function findPresentations(): Collection
    {
        $branches = $this->branchParser->parseAllBranches(true);
        $presentations = collect();

        foreach ($branches as $branch) {
            $presentation = $this->findPresentationInBranch($branch);
            if ($presentation !== null) {
                $presentations[$presentation->id] = $presentation;
            }
        }

        return $presentations;
    }

    /**
     * Find a presentation in a specific branch.
     * Uses git grep to efficiently find #presentation tag without loading all commits.
     */
    public function findPresentationInBranch(Branch $branch): ?Presentation
    {
        // First, efficiently find if there's a #presentation commit on this branch
        $startHash = $this->commitParser->findPresentationStartHash($branch->name);

        if ($startHash === null) {
            return null;
        }

        // Only load commits from the presentation start to the branch tip
        // This avoids loading potentially hundreds of commits we don't need
        $commits = $this->commitParser->parseCommitsInRange($branch->name, $startHash);

        if ($commits->isEmpty()) {
            return null;
        }

        // Commits are returned newest-first, reverse to get oldest-first
        $presentationCommits = $commits->reverse()->values();

        // The start commit is now the first one (oldest)
        $startCommit = $presentationCommits->first();

        // Extract title from the start commit (remove the #presentation tag)
        $title = $this->extractTitle($startCommit);

        // Build steps from commits
        $steps = $this->buildStepsFromCommits($presentationCommits, $branch->name);

        // Create unique ID based on branch name
        $id = Str::slug($branch->name);

        return new Presentation(
            id: $id,
            branchName: $branch->name,
            title: $title,
            startCommit: $startCommit,
            endCommit: $commits->first(), // Branch tip (newest)
            steps: $steps,
        );
    }

    /**
     * Check if a commit has the #presentation tag in its subject.
     */
    private function hasPresentationTag(Commit $commit): bool
    {
        return str_contains(strtolower($commit->subject), self::PRESENTATION_TAG);
    }

    /**
     * Extract the presentation title from a commit subject.
     * Removes the #presentation tag, code snippet references, and cleans up the title.
     */
    private function extractTitle(Commit $commit): string
    {
        $title = $commit->subject;

        // Remove #presentation tag (case-insensitive)
        $title = preg_replace('/' . preg_quote(self::PRESENTATION_TAG, '/') . '/i', '', $title);

        // Clean up extra spaces
        $title = trim(preg_replace('/\s+/', ' ', $title));

        // Parse out any code snippet references
        $snippetParser = new CodeSnippetParser();
        $parsed = $snippetParser->parseHeading($title);
        $title = $parsed['title'];

        return $title ?: 'Untitled Presentation';
    }

    /**
     * Build Step objects from a collection of commits.
     *
     * @param Collection<int, Commit> $commits
     * @return Collection<int, Step>
     */
    private function buildStepsFromCommits(Collection $commits, string $branchName): Collection
    {
        return $commits->map(function (Commit $commit, int $index): Step {
            // Step ID is just the 1-based index (URL context provides presentation)
            $stepId = (string) ($index + 1);

            return new Step(
                id: $stepId,
                index: $index,
                commit: $commit,
            );
        });
    }

    /**
     * Enrich presentation steps with file changes.
     */
    public function enrichPresentation(Presentation $presentation): Presentation
    {
        $enrichedSteps = $presentation->getSteps()->map(function (Step $step): Step {
            $fileChanges = $this->commitParser->parseFileChangesWithStats($step->commit->hash);
            $step->commit->setFileChanges($fileChanges);
            return $step;
        });

        $presentation->setSteps($enrichedSteps);
        return $presentation;
    }
}
