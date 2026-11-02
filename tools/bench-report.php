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

use jubianchi\PPC\Tools\BenchReport;

require __DIR__.'/../vendor/autoload.php';

$dump = $argv[1] ?? 'tmp/bench-functions.xml';
$criterion = $argv[2] ?? 'benchmarks/nom/target/criterion';

$read = static function (string $path): string {
    $content = is_file($path) ? file_get_contents($path) : false;

    if (false === $content) {
        fwrite(\STDERR, \sprintf("Unable to read %s\n", $path));

        exit(1);
    }

    return $content;
};

$reports = [];

if (is_dir($criterion)) {
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($criterion, FilesystemIterator::SKIP_DOTS)) as $file) {
        \assert($file instanceof SplFileInfo);

        if ('benchmark.json' === $file->getFilename() && 'new' === basename($file->getPath())) {
            $reports[] = [
                'benchmark' => $read($file->getPathname()),
                'estimates' => $read($file->getPath().'/estimates.json'),
            ];
        }
    }
}

echo BenchReport::table(BenchReport::merge(BenchReport::fromPhpBench($read($dump)), BenchReport::fromCriterion($reports)));
