<?php

use App\Git\Provider\GitDataProvider;
use TightenCo\Jigsaw\Jigsaw;

/** @var \Illuminate\Container\Container $container */
/** @var \TightenCo\Jigsaw\Events\EventBus $events */

/*
 * Git Presented Bootstrap
 *
 * This file is loaded by Jigsaw to register event handlers.
 * Helper functions are defined in helpers.php and loaded via config.php.
 */

/*
 * Register beforeBuild event to ensure Git data is loaded
 */
$events->beforeBuild(function (Jigsaw $jigsaw) {
    // Git data is loaded in config.php, nothing extra needed here
    // This hook is available for custom preprocessing if needed
});

/*
 * Register afterBuild event for any post-processing
 */
$events->afterBuild(function (Jigsaw $jigsaw) {
    $destPath = $jigsaw->getDestinationPath();

    // Create present.sh script in the build output
    $serveScript = <<<'BASH'
#!/bin/bash

# Git Presented - Serve the presentation
# Starts a PHP server to view the presentation on macOS

PORT="${1:-8000}"

echo "Serving presentation at http://localhost:$PORT"
echo "Press Ctrl+C to stop"
echo ""

php -S "localhost:$PORT" -t "$(dirname "$0")"
BASH;

    file_put_contents($destPath . '/present.sh', $serveScript);
    chmod($destPath . '/present.sh', 0755);
});
