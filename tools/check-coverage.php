<?php

/**
 * This file is part of PPC.
 *
 * Copyright © 2026 Julien Bianchi <contact@jubianchi.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

use jubianchi\PPC\Tools\Coverage;

require __DIR__.'/../vendor/autoload.php';

$path = $argv[1] ?? 'tmp/clover.xml';
$clover = is_file($path) ? file_get_contents($path) : false;

if (false === $clover) {
    fwrite(\STDERR, \sprintf("Unable to read the Clover report %s\n", $path));

    exit(1);
}

$uncovered = Coverage::uncovered($clover);

foreach ($uncovered as $file => $lines) {
    fwrite(\STDERR, \sprintf("%s: %s\n", str_replace(getcwd().'/', '', $file), implode(', ', $lines)));
}

if ([] !== $uncovered) {
    fwrite(\STDERR, "Code coverage is below 100%: the lines above are not covered\n");

    exit(1);
}

echo "Code coverage: 100%\n";
