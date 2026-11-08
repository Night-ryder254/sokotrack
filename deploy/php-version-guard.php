<?php

// Fails the Docker build if PHP is older than the release that fixes PHP2Shell
// (CVE-2026-109618 and CVE-2026-109619). Usage: php php-version-guard.php [version-to-check]

$version = $argv[1] ?? PHP_VERSION;

$fixedIn = ['8.2' => '8.2.35', '8.3' => '8.3.36', '8.4' => '8.4.27', '8.5' => '8.5.12'];

$branch = implode('.', array_slice(explode('.', $version), 0, 2));

if (isset($fixedIn[$branch]) && version_compare($version, $fixedIn[$branch], '<')) {
    fwrite(STDERR, "PHP {$version} is older than {$fixedIn[$branch]}, which fixes PHP2Shell. Rebuild without cache.\n");
    exit(1);
}

echo "PHP {$version} includes the PHP2Shell fix.\n";
