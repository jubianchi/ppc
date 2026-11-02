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

/**
 * Combines the per-function benchmarks of PHPBench and criterion.
 *
 * Timings are mean times in nanoseconds, by function, input and library.
 *
 * @phpstan-type Timings array<string, array<string, array<string, float>>>
 */
final class BenchReport
{
    private const array LIBRARIES = ['ppc', 'parsica', 'nom'];

    /**
     * @return Timings
     */
    public static function fromPhpBench(string $dump): array
    {
        $report = simplexml_load_string($dump, options: \LIBXML_NOERROR | \LIBXML_NOWARNING);

        if (false === $report) {
            throw new \InvalidArgumentException('Invalid PHPBench dump');
        }

        $timings = [];

        foreach ($report->suite->benchmark as $benchmark) {
            $class = (string) $benchmark['class'];

            if (!str_contains($class, '\Benchmarks\Functions\\')) {
                continue;
            }

            $function = lcfirst(basename(strtr($class, '\\', '/')));

            foreach ($benchmark->subject as $subject) {
                $library = substr((string) $subject['name'], \strlen('bench_'));

                foreach ($subject->variant as $variant) {
                    $timings[$function][(string) $variant->{'parameter-set'}['name']][$library] = (float) $variant->stats['mean'] * 1000;
                }
            }
        }

        return $timings;
    }

    /**
     * @param list<array{benchmark: string, estimates: string}> $reports the benchmark.json and estimates.json of each criterion benchmark
     *
     * @return Timings
     */
    public static function fromCriterion(array $reports): array
    {
        $timings = [];

        foreach ($reports as $report) {
            $benchmark = json_decode($report['benchmark'], true);
            $estimates = json_decode($report['estimates'], true);

            if (!\is_array($benchmark) || !\is_string($benchmark['group_id'] ?? null) || !\is_string($benchmark['function_id'] ?? null) || !\is_string($benchmark['value_str'] ?? null)
                || !\is_array($estimates) || !\is_array($estimates['mean'] ?? null) || !is_numeric($estimates['mean']['point_estimate'] ?? null)) {
                throw new \InvalidArgumentException('Invalid criterion report');
            }

            $timings[$benchmark['group_id']][$benchmark['value_str']][$benchmark['function_id']] = (float) $estimates['mean']['point_estimate'];
        }

        return $timings;
    }

    /**
     * @param Timings ...$sources
     *
     * @return Timings
     */
    public static function merge(array ...$sources): array
    {
        $timings = [];

        foreach ($sources as $source) {
            foreach ($source as $function => $inputs) {
                foreach ($inputs as $input => $libraries) {
                    foreach ($libraries as $library => $time) {
                        $timings[$function][$input][$library] = $time;
                    }
                }
            }
        }

        return $timings;
    }

    /**
     * @param Timings $timings
     */
    public static function table(array $timings): string
    {
        ksort($timings);

        $rows = [['function', 'input', ...self::LIBRARIES, 'ppc / nom']];

        foreach ($timings as $function => $inputs) {
            foreach ($inputs as $input => $libraries) {
                $ratio = isset($libraries['ppc'], $libraries['nom']) && $libraries['nom'] > 0
                    ? '×'.number_format($libraries['ppc'] / $libraries['nom'], 1)
                    : '—';

                $rows[] = [
                    $function,
                    $input,
                    ...array_map(static fn (string $library): string => isset($libraries[$library]) ? self::duration($libraries[$library]) : '—', self::LIBRARIES),
                    $ratio,
                ];
            }
        }

        $widths = array_map(
            static fn (int $column): int => max(array_map(static fn (array $row): int => mb_strlen($row[$column]), $rows)),
            array_keys($rows[0]),
        );

        $lines = array_map(
            static fn (array $row): string => '| '.implode(' | ', array_map(static fn (string $cell, int $width): string => mb_str_pad($cell, $width), $row, $widths)).' |',
            $rows,
        );

        array_splice($lines, 1, 0, ['|'.implode('|', array_map(static fn (int $width): string => str_repeat('-', $width + 2), $widths)).'|']);

        return implode("\n", $lines)."\n";
    }

    private static function duration(float $nanoseconds): string
    {
        foreach ([1_000_000_000 => 's', 1_000_000 => 'ms', 1_000 => 'µs'] as $scale => $unit) {
            if ($nanoseconds >= $scale) {
                return self::number($nanoseconds / $scale).' '.$unit;
            }
        }

        return self::number($nanoseconds).' ns';
    }

    private static function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
