<?php

declare(strict_types=1);

namespace App\Git;

use App\Git\Model\Branch;
use App\Git\Model\CodeSnippetReference;
use App\Git\Model\DiffLine;
use App\Git\Model\Presentation;
use App\Git\Parser\BranchParser;
use App\Git\Parser\CodeSnippetParser;
use App\Git\Parser\CommitParser;
use App\Git\Parser\DiffParser;
use App\Git\Parser\GitCommandRunner;
use App\Git\Parser\PresentationBuilder;
use Illuminate\Support\Collection;

class Repository
{
    private GitCommandRunner $git;
    private CommitParser $commitParser;
    private BranchParser $branchParser;
    private DiffParser $diffParser;
    private PresentationBuilder $presentationBuilder;
    private CodeSnippetParser $snippetParser;

    /** @var Collection<string, Branch>|null */
    private ?Collection $branches = null;

    /** @var Collection<string, Presentation>|null */
    private ?Collection $presentations = null;

    public function __construct(
        private readonly string $path,
        private readonly array $config = [],
    ) {
        $this->git = new GitCommandRunner($path);
        $this->commitParser = new CommitParser($this->git);
        $this->branchParser = new BranchParser($this->git);
        $this->diffParser = new DiffParser();
        $this->presentationBuilder = new PresentationBuilder($this->commitParser, $this->branchParser);
        $this->snippetParser = new CodeSnippetParser();
    }

    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return Collection<string, Branch>
     */
    public function getBranches(): Collection
    {
        if ($this->branches === null) {
            $patterns = $this->config['include_branches'] ?? ['*'];
            $this->branches = $this->branchParser->parseBranchesMatchingPatterns($patterns);

            // Set up lazy commit loading for each branch
            // Commits are only loaded when actually accessed (e.g., on branch detail page)
            foreach ($this->branches as $branch) {
                $commitParser = $this->commitParser;
                $branchName = $branch->name;
                $branch->setCommitLoader(function () use ($commitParser, $branchName) {
                    return $commitParser->parseAllCommits($branchName, 50);
                });
            }
        }

        return $this->branches;
    }

    /**
     * @return Collection<string, Presentation>
     */
    public function getPresentations(): Collection
    {
        if ($this->presentations === null) {
            $this->presentations = $this->presentationBuilder->findPresentations();

            // Enrich each presentation with file changes
            foreach ($this->presentations as $presentation) {
                $this->presentationBuilder->enrichPresentation($presentation);
            }
        }

        return $this->presentations;
    }

    public function getPresentation(string $id): ?Presentation
    {
        return $this->getPresentations()->get($id);
    }

    /**
     * Get parsed diff as structured DiffFile objects.
     * Excluded files are skipped during parsing to save memory.
     *
     * @return \Illuminate\Support\Collection<int, \App\Git\Model\DiffFile>
     */
    public function getParsedDiff(string $hash): Collection
    {
        $rawDiff = $this->commitParser->getDiff($hash);
        $excludePatterns = $this->config['exclude_patterns'] ?? [];

        // Pass exclude patterns to parser so excluded files are never loaded into memory
        return $this->diffParser->parse($rawDiff, $excludePatterns);
    }

    public function getFileContent(string $hash, string $path): string
    {
        return $this->commitParser->getFileContent($hash, $path);
    }

    public function getFileContentAtParent(string $hash, string $path): string
    {
        return $this->commitParser->getFileContentAtParent($hash, $path);
    }

    /**
     * Get file content for a specific line range.
     *
     * @return array{content: ?string, error: ?string}
     */
    public function getFileContentRange(string $hash, string $filePath, int $startLine, int $endLine): array
    {
        try {
            $fullContent = $this->commitParser->getFileContent($hash, $filePath);
            $lines = explode("\n", $fullContent);
            $totalLines = count($lines);

            // Validate line range
            if ($startLine < 1 || $startLine > $totalLines) {
                return [
                    'content' => null,
                    'error' => "Start line {$startLine} is out of range (file has {$totalLines} lines)",
                ];
            }

            if ($endLine > $totalLines) {
                $endLine = $totalLines;
            }

            // Extract line range (1-indexed to 0-indexed conversion)
            $selectedLines = array_slice($lines, $startLine - 1, $endLine - $startLine + 1);

            // Dedent: remove common leading whitespace
            $selectedLines = $this->dedentLines($selectedLines);

            return [
                'content' => implode("\n", $selectedLines),
                'error' => null,
            ];
        } catch (\RuntimeException $e) {
            return [
                'content' => null,
                'error' => "File not found: {$filePath}",
            ];
        }
    }

    /**
     * Remove common leading whitespace from lines.
     */
    private function dedentLines(array $lines): array
    {
        // Find minimum indentation (ignoring empty lines)
        $minIndent = PHP_INT_MAX;
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue; // Skip empty lines
            }
            preg_match('/^(\s*)/', $line, $matches);
            $indent = strlen($matches[1] ?? '');
            if ($indent < $minIndent) {
                $minIndent = $indent;
            }
        }

        if ($minIndent === PHP_INT_MAX || $minIndent === 0) {
            return $lines;
        }

        // Remove the common indentation
        return array_map(function ($line) use ($minIndent) {
            if (trim($line) === '') {
                return $line; // Keep empty lines as-is
            }
            return substr($line, $minIndent);
        }, $lines);
    }

    /**
     * Get diff lines for a specific file filtered by line range.
     * Returns only the diff lines that fall within the specified line range in the new file.
     *
     * @return array{lines: Collection<int, DiffLine>, error: ?string}
     */
    public function getDiffLinesInRange(string $hash, string $filePath, int $startLine, int $endLine): array
    {
        $diffFiles = $this->getParsedDiff($hash);

        // Find the file in the diff
        $diffFile = $diffFiles->first(function (DiffFile $file) use ($filePath) {
            return $file->newPath === $filePath || $file->oldPath === $filePath;
        });

        if ($diffFile === null) {
            return [
                'lines' => collect(),
                'error' => "File not found in diff: {$filePath}",
            ];
        }

        // Collect all lines that fall within the range
        $linesInRange = collect();

        foreach ($diffFile->getHunks() as $hunk) {
            foreach ($hunk->getLines() as $line) {
                // For added lines and context, check newLineNumber
                // For removed lines, check oldLineNumber
                $lineNum = $line->newLineNumber ?? $line->oldLineNumber;

                if ($lineNum !== null && $lineNum >= $startLine && $lineNum <= $endLine) {
                    $linesInRange->push($line);
                }
            }
        }

        return [
            'lines' => $linesInRange,
            'error' => null,
        ];
    }

    /**
     * Get snippet content for a CodeSnippetReference.
     *
     * @return array{content: ?string, lines: ?Collection, error: ?string, viewType: string}
     */
    public function getSnippetContent(string $hash, CodeSnippetReference $snippet): array
    {
        if ($snippet->isDiffView()) {
            $result = $this->getDiffLinesInRange(
                $hash,
                $snippet->filePath,
                $snippet->startLine,
                $snippet->endLine
            );

            return [
                'content' => null,
                'lines' => $result['lines'],
                'error' => $result['error'],
                'viewType' => CodeSnippetReference::VIEW_DIFF,
            ];
        }

        // Result view
        $result = $this->getFileContentRange(
            $hash,
            $snippet->filePath,
            $snippet->startLine,
            $snippet->endLine
        );

        return [
            'content' => $result['content'],
            'lines' => null,
            'error' => $result['error'],
            'viewType' => CodeSnippetReference::VIEW_RESULT,
        ];
    }
}
