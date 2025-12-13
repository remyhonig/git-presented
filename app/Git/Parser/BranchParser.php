<?php

declare(strict_types=1);

namespace App\Git\Parser;

use App\Git\Model\Branch;
use Illuminate\Support\Collection;

class BranchParser
{
    public function __construct(
        private readonly GitCommandRunner $git,
    ) {}

    /**
     * @return Collection<string, Branch>
     */
    public function parseAllBranches(bool $includeRemotes = false): Collection
    {
        $formatArg = "--format='%(refname:short) %(objectname)'";

        $args = [$formatArg];
        if ($includeRemotes) {
            $args[] = '-a';
        }

        $output = $this->git->runRaw('branch ' . implode(' ', $args));

        return collect(explode("\n", trim($output)))
            ->filter()
            ->map(function (string $line): Branch {
                $parts = preg_split('/\s+/', trim($line), 2);
                $name = $parts[0];
                $hash = $parts[1] ?? '';

                $isRemote = str_starts_with($name, 'remotes/') ||
                           str_starts_with($name, 'origin/');

                // Clean up remote prefix for display
                $name = preg_replace('/^remotes\//', '', $name);

                return new Branch(
                    name: $name,
                    headHash: $hash,
                    isRemote: $isRemote,
                );
            })
            ->keyBy(fn(Branch $b) => $b->name);
    }

    /**
     * @param array<string> $patterns Glob patterns like "feature/*", "main", "develop"
     * @return Collection<string, Branch>
     */
    public function parseBranchesMatchingPatterns(array $patterns): Collection
    {
        $allBranches = $this->parseAllBranches(true);

        return $allBranches->filter(function (Branch $branch) use ($patterns): bool {
            foreach ($patterns as $pattern) {
                if (fnmatch($pattern, $branch->name) || fnmatch($pattern, $branch->getShortName())) {
                    return true;
                }
            }
            return false;
        });
    }

    public function getCurrentBranch(): ?string
    {
        try {
            $output = $this->git->runRaw('branch --show-current');
            return trim($output) ?: null;
        } catch (\RuntimeException) {
            return null;
        }
    }

    public function getMainBranch(): string
    {
        // Try common main branch names
        $candidates = ['main', 'master'];

        $allBranches = $this->parseAllBranches();

        foreach ($candidates as $candidate) {
            if ($allBranches->has($candidate)) {
                return $candidate;
            }
        }

        // Fall back to the first branch
        return $allBranches->keys()->first() ?? 'main';
    }

    public function getMergeBase(string $branch1, string $branch2): ?string
    {
        try {
            $output = $this->git->runRaw("merge-base {$branch1} {$branch2}");
            return trim($output) ?: null;
        } catch (\RuntimeException) {
            return null;
        }
    }

    /**
     * Find all branches that were merged into the target branch
     * @return Collection<string, string> branch name => merge commit hash
     */
    public function findMergedBranches(string $targetBranch): Collection
    {
        try {
            $output = $this->git->runRaw("branch --merged {$targetBranch} --format='%(refname:short)'");

            return collect(explode("\n", trim($output)))
                ->filter()
                ->filter(fn(string $name) => $name !== $targetBranch)
                ->mapWithKeys(function (string $branchName) use ($targetBranch): array {
                    // Find the merge commit
                    $mergeCommit = $this->findMergeCommit($branchName, $targetBranch);
                    return [$branchName => $mergeCommit];
                })
                ->filter();
        } catch (\RuntimeException) {
            return collect();
        }
    }

    private function findMergeCommit(string $branch, string $targetBranch): ?string
    {
        try {
            // Look for merge commits that mention this branch
            $output = $this->git->runRaw(
                "log --oneline --merges --grep=\"Merge.*{$branch}\" {$targetBranch} -n 1 --format=%H"
            );
            return trim($output) ?: null;
        } catch (\RuntimeException) {
            return null;
        }
    }
}
