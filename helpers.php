<?php

declare(strict_types=1);

use App\Git\Provider\GitDataProvider;
use League\CommonMark\GithubFlavoredMarkdownConverter;

/*
 * Helper functions for Git Presented
 *
 * These are loaded by config.php and provide Git configuration
 * and data provider access.
 */

// Load environment variables if .env exists (only once)
if (!defined('GIT_PRESENTED_HELPERS_LOADED')) {
    define('GIT_PRESENTED_HELPERS_LOADED', true);

    if (file_exists(__DIR__ . '/.env')) {
        // Use createMutable to ensure vars are set even if already in environment
        // Load into both $_ENV and $_SERVER for maximum compatibility
        $dotenv = Dotenv\Dotenv::createMutable(__DIR__);
        $dotenv->load();
    }
}

if (!function_exists('env')) {
    /**
     * Get environment variable with fallback, checking multiple sources
     */
    function env(string $key, mixed $default = null): mixed
    {
        // Check $_ENV first (populated by dotenv)
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }

        // Check getenv() as fallback (system environment)
        $value = getenv($key);
        if ($value !== false && $value !== '') {
            return $value;
        }

        // Check $_SERVER as last resort
        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            return $_SERVER[$key];
        }

        return $default;
    }
}

if (!function_exists('getGitConfig')) {
    /**
     * Get Git configuration from environment or defaults
     */
    function getGitConfig(): array
    {
        $excludePatterns = env('GIT_EXCLUDE_PATTERNS');

        return [
            'repo_path' => env('GIT_REPO_PATH', __DIR__),
            'exclude_patterns' => $excludePatterns !== null
                ? array_map('trim', explode(',', str_replace('"', '', $excludePatterns)))
                : ['vendor/*', '*.lock', 'composer.lock', 'package-lock.json', 'yarn.lock', 'node_modules/*', 'config/reference.php', '*/reference.php', 'CLAUDE.md'],
        ];
    }
}

if (!function_exists('createGitDataProvider')) {
    /**
     * Create and return the Git data provider
     */
    function createGitDataProvider(): GitDataProvider
    {
        return new GitDataProvider(getGitConfig());
    }
}

if (!function_exists('parseMarkdown')) {
    /**
     * Parse Markdown text to HTML using GitHub Flavored Markdown
     */
    function parseMarkdown(string $text): string
    {
        static $converter = null;

        if ($converter === null) {
            $converter = new GithubFlavoredMarkdownConverter([
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]);
        }

        return $converter->convert($text)->getContent();
    }
}

if (!function_exists('getMarkdownConverter')) {
    /**
     * Get the Markdown converter instance for use in templates
     */
    function getMarkdownConverter(): GithubFlavoredMarkdownConverter
    {
        static $converter = null;

        if ($converter === null) {
            $converter = new GithubFlavoredMarkdownConverter([
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]);
        }

        return $converter;
    }
}
