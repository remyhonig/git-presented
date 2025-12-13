<?php

declare(strict_types=1);

namespace App\Git\Provider;

use App\Git\Repository;

class GitDataProvider
{
    private ?Repository $repository = null;

    public function __construct(
        private readonly array $config,
    ) {}

    public function getRepository(): Repository
    {
        if ($this->repository === null) {
            $repoPath = $this->config['repo_path'] ?? getcwd();

            $this->repository = new Repository($repoPath, [
                'include_branches' => $this->config['include_branches'] ?? ['*'],
                'exclude_patterns' => $this->config['exclude_patterns'] ?? [],
            ]);
        }

        return $this->repository;
    }
}
