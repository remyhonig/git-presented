<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

// Initialize the Git data provider
$gitProvider = createGitDataProvider();

// Check if we're in a Git repository
$gitConfig = getGitConfig();
$isGitRepo = is_dir($gitConfig['repo_path'] . '/.git');

// Get repository data if available
$repo = $isGitRepo ? $gitProvider->getRepository() : null;
$presentations = $repo ? $repo->getPresentations() : collect();
$branches = $repo ? $repo->getBranches() : collect();

// Build collection items for presentations (menu tiles)
$presentationItems = $presentations->mapWithKeys(function ($presentation) {
    return [
        $presentation->id => [
            'extends' => '_layouts.presentation-overview',
            'presentationId' => $presentation->id,
            'title' => $presentation->getTitle(),
            'branchName' => $presentation->branchName,
        ],
    ];
})->all();

// Build collection items for presentation slides
// Each presentation has its own steps, keyed by presentationId/stepNumber for uniqueness
$presentationSlideItems = [];

foreach ($presentations as $presentation) {
    foreach ($presentation->getSteps() as $step) {
        // Use composite key to avoid collisions between presentations
        // Note: Use hyphen separator (not slash) as Jigsaw uses keys for temp file paths
        $itemKey = $presentation->id . '-' . $step->id;
        $presentationSlideItems[$itemKey] = [
            'extends' => '_layouts.presentation-step',
            'presentationId' => $presentation->id,
            'stepId' => $step->id,
            'stepHash' => $step->getFullHash(),
            'stepIndex' => $step->index,
            'title' => $step->getTitle(),
            'pageTitle' => $step->getTitle() . ' - ' . $presentation->getTitle(),
        ];
    }
}

// Build collection items for branches (for reference)
$branchItems = $branches->mapWithKeys(function ($branch) {
    return [
        $branch->getSlug() => [
            'extends' => '_layouts.branch',
            'branchName' => $branch->name,
            'title' => $branch->getShortName(),
        ],
    ];
})->all();

return [
    'production' => false,
    'baseUrl' => '',
    'title' => 'Git Presentations',
    'description' => 'A collection of presentations from Git repository history',

    // Site configuration
    'siteName' => env('SITE_NAME', 'Git Presentations'),
    'siteAuthor' => env('SITE_AUTHOR', ''),

    // Theme and display mode
    'defaultTheme' => env('DEFAULT_THEME', 'light'),
    'defaultMode' => env('DEFAULT_MODE', 'browser'),

    // Git configuration
    'gitConfig' => $gitConfig,
    'isGitRepo' => $isGitRepo,

    // Git data (only load if in a Git repo)
    'gitRepo' => $repo,
    'presentations' => $presentations,
    'branches' => $branches,

    // Diff display settings
    'maxFilesForInlineDiff' => (int) env('MAX_FILES_FOR_INLINE_DIFF', 10),

    // Helper functions
    'formatDate' => function ($page, $date) {
        if ($date instanceof DateTimeInterface) {
            return $date->format('M j, Y \a\t g:i A');
        }
        return $date;
    },

    'formatShortDate' => function ($page, $date) {
        if ($date instanceof DateTimeInterface) {
            return $date->format('M j, Y');
        }
        return $date;
    },

    'getPresentationUrl' => function ($page, $presentationId) {
        return "/presentations/{$presentationId}";
    },

    'getPresentationStepUrl' => function ($page, $presentationId, $stepId) {
        return "/presentations/{$presentationId}/{$stepId}";
    },

    'getBranchUrl' => function ($page, $branchSlug) {
        return "/branches/{$branchSlug}";
    },

    // Markdown parsing helper
    'markdown' => function ($page, $text) {
        return parseMarkdown($text);
    },

    // Markdown parsing with inline code snippet support
    'markdownWithSnippets' => function ($page, $text, $commitHash = null) {
        $hash = $commitHash ?? ($page->stepHash ?? null);
        return parseMarkdownWithSnippets($text, $page->gitRepo ?? null, $hash);
    },

    // Collections for dynamic page generation
    'collections' => [
        'presentations' => [
            'items' => $presentationItems,
            'path' => 'presentations/{filename}',
        ],
        'presentationSlides' => [
            'items' => $presentationSlideItems,
            'path' => function ($page) {
                return 'presentations/' . $page->presentationId . '/' . $page->stepId;
            },
        ],
        'branches' => [
            'items' => $branchItems,
            'path' => 'branches/{filename}',
        ],
    ],
];
