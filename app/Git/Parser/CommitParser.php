<?php

declare(strict_types=1);

namespace App\Git\Parser;

use App\Git\Model\Author;
use App\Git\Model\Commit;
use App\Git\Model\FileChange;
use DateTimeImmutable;
use Illuminate\Support\Collection;

class CommitParser
{
    private const LOG_FORMAT = '%H%x00%h%x00%s%x00%b%x00%an <%ae>%x00%cn <%ce>%x00%aI%x00%cI%x00%P%x00';
    private const COMMIT_SEPARATOR = '%x01';

    public function __construct(
        private readonly GitCommandRunner $git,
    ) {}

    /**
     * @return Collection<int, Commit>
     */
    public function parseAllCommits(?string $branch = null, ?int $limit = null): Collection
    {
        $format = escapeshellarg('--format=' . self::LOG_FORMAT . self::COMMIT_SEPARATOR);
        $args = [
            $format,
            '--date-order',
        ];

        if ($limit !== null) {
            $args[] = "-n {$limit}";
        }

        if ($branch !== null) {
            $args[] = escapeshellarg($branch);
        } else {
            $args[] = '--all';
        }

        $output = $this->git->runRaw('log ' . implode(' ', $args));

        return $this->parseLogOutput($output);
    }

    /**
     * @return Collection<int, FileChange>
     */
    public function parseFileChanges(string $hash): Collection
    {
        // Get the file changes with stats
        $output = $this->git->runRaw("diff-tree --no-commit-id --name-status -r {$hash}");

        return collect(explode("\n", trim($output)))
            ->filter()
            ->map(function (string $line): FileChange {
                $parts = preg_split('/\s+/', $line, 3);

                if (count($parts) < 2) {
                    return null;
                }

                $status = $parts[0];
                $path = $parts[1];
                $oldPath = null;

                // Handle renames (R100 old_path new_path)
                if (str_starts_with($status, 'R') && isset($parts[2])) {
                    $oldPath = $path;
                    $path = $parts[2];
                }

                return new FileChange(
                    path: $path,
                    status: $status[0], // Normalize to single char
                    oldPath: $oldPath,
                );
            })
            ->filter()
            ->values();
    }

    /**
     * @return Collection<int, FileChange>
     */
    public function parseFileChangesWithStats(string $hash): Collection
    {
        // Get numstat for additions/deletions
        $statsOutput = $this->git->runRaw("diff-tree --no-commit-id --numstat -r {$hash}");
        $stats = [];

        foreach (explode("\n", trim($statsOutput)) as $line) {
            if (empty($line)) continue;

            $parts = preg_split('/\s+/', $line, 3);
            if (count($parts) >= 3) {
                $additions = $parts[0] === '-' ? 0 : (int) $parts[0];
                $deletions = $parts[1] === '-' ? 0 : (int) $parts[1];
                $path = $parts[2];

                // Handle renames: "old_path => new_path" or "{old => new}/path"
                if (str_contains($path, ' => ')) {
                    $path = preg_replace('/.*\s+=>\s+/', '', $path);
                    $path = preg_replace('/\{[^}]*\s+=>\s+([^}]*)\}/', '$1', $path);
                }

                $stats[$path] = [
                    'additions' => $additions,
                    'deletions' => $deletions,
                ];
            }
        }

        return $this->parseFileChanges($hash)->map(function (FileChange $change) use ($stats): FileChange {
            $stat = $stats[$change->path] ?? ['additions' => 0, 'deletions' => 0];

            return new FileChange(
                path: $change->path,
                status: $change->status,
                oldPath: $change->oldPath,
                additions: $stat['additions'],
                deletions: $stat['deletions'],
            );
        });
    }

    /**
     * Find the first commit with #presentation tag on a branch.
     * Returns only the hash to minimize memory usage.
     */
    public function findPresentationStartHash(string $branch): ?string
    {
        // Use git log --grep to find commits with #presentation, limit to 1
        $output = $this->git->runRaw(
            'log --grep=' . escapeshellarg('#presentation') . ' -i --format=%H -n 1 ' . escapeshellarg($branch)
        );

        $hash = trim($output);
        return $hash !== '' ? $hash : null;
    }

    /**
     * Parse commits in a range (from startHash to branch tip, inclusive).
     * This is memory-efficient as it only loads commits we actually need.
     *
     * @return Collection<int, Commit>
     */
    public function parseCommitsInRange(string $branch, string $startHash): Collection
    {
        // Get commits from startHash to branch tip (inclusive)
        // Using startHash^..branch to include startHash itself
        $format = escapeshellarg('--format=' . self::LOG_FORMAT . self::COMMIT_SEPARATOR);
        $range = escapeshellarg("{$startHash}^..{$branch}");

        $output = $this->git->runRaw("log {$format} --date-order {$range}");

        return $this->parseLogOutput($output);
    }

    public function getDiff(string $hash): string
    {
        return $this->git->runRaw("show --color=never --format='' {$hash}");
    }

    public function getFileContent(string $hash, string $filePath): string
    {
        return $this->git->runRaw("show {$hash}:" . escapeshellarg($filePath));
    }

    public function getFileContentAtParent(string $hash, string $filePath): string
    {
        return $this->git->runRaw("show {$hash}^:" . escapeshellarg($filePath));
    }

    /**
     * @return Collection<int, Commit>
     */
    private function parseLogOutput(string $output): Collection
    {
        if (empty(trim($output))) {
            return collect();
        }

        $commitStrings = explode("\x01", $output);

        return collect($commitStrings)
            ->filter(fn(string $s) => !empty(trim($s)))
            ->map(function (string $commitString): ?Commit {
                $parts = explode("\x00", trim($commitString));

                if (count($parts) < 9) {
                    return null;
                }

                [$hash, $shortHash, $subject, $body, $author, $committer, $authorDate, $commitDate, $parents] = $parts;

                $parentHashes = array_filter(explode(' ', trim($parents)));

                return new Commit(
                    hash: $hash,
                    shortHash: $shortHash,
                    subject: $subject,
                    body: trim($body),
                    author: Author::fromGitFormat($author),
                    committer: Author::fromGitFormat($committer),
                    authorDate: new DateTimeImmutable($authorDate),
                    commitDate: new DateTimeImmutable($commitDate),
                    parentHashes: $parentHashes,
                );
            })
            ->filter()
            ->values();
    }
}
