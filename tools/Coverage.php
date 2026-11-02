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

namespace jubianchi\PPC\Tools;

final class Coverage
{
    /**
     * @return array<string, non-empty-list<int>> the uncovered line numbers, by file
     */
    public static function uncovered(string $clover): array
    {
        $report = simplexml_load_string($clover, options: \LIBXML_NOERROR | \LIBXML_NOWARNING);

        if (false === $report) {
            throw new \InvalidArgumentException('Invalid Clover report');
        }

        $files = $report->xpath('//file');
        $uncovered = [];

        foreach (\is_array($files) ? $files : [] as $file) {
            $lines = [];

            foreach ($file->line as $line) {
                if (0 === (int) $line['count']) {
                    $lines[] = (int) $line['num'];
                }
            }

            if ([] !== $lines) {
                $uncovered[(string) $file['name']] = $lines;
            }
        }

        return $uncovered;
    }
}
