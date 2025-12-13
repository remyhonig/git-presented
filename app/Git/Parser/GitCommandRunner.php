<?php

declare(strict_types=1);

namespace App\Git\Parser;

use RuntimeException;

class GitCommandRunner
{
    public function __construct(
        private readonly string $repoPath,
    ) {
        if (!is_dir($repoPath)) {
            throw new RuntimeException("Repository path does not exist: {$repoPath}");
        }

        if (!is_dir($repoPath . '/.git') && !is_file($repoPath . '/HEAD')) {
            throw new RuntimeException("Path is not a Git repository: {$repoPath}");
        }
    }

    public function run(string $command, array $args = []): string
    {
        $escapedArgs = array_map('escapeshellarg', $args);
        $fullCommand = sprintf(
            'cd %s && git %s %s 2>&1',
            escapeshellarg($this->repoPath),
            $command,
            implode(' ', $escapedArgs)
        );

        $output = [];
        $returnCode = 0;
        exec($fullCommand, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new RuntimeException(
                "Git command failed: {$fullCommand}\n" . implode("\n", $output)
            );
        }

        return implode("\n", $output);
    }

    public function runRaw(string $rawCommand): string
    {
        $fullCommand = sprintf(
            'cd %s && git %s 2>&1',
            escapeshellarg($this->repoPath),
            $rawCommand
        );

        $output = [];
        $returnCode = 0;
        exec($fullCommand, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new RuntimeException(
                "Git command failed: {$fullCommand}\n" . implode("\n", $output)
            );
        }

        return implode("\n", $output);
    }

    public function getRepoPath(): string
    {
        return $this->repoPath;
    }
}
