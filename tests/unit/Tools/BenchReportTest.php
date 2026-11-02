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

namespace jubianchi\PPC\Tests\Tools;

use jubianchi\PPC\Tools\BenchReport;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Small]
final class BenchReportTest extends TestCase
{
    #[Test]
    public function readsTheMeanTimeOfEachFunctionBenchmarkFromPhpBench(): void
    {
        $dump = <<<'XML'
            <?xml version="1.0"?>
            <phpbench version="1.7.0">
              <suite>
                <benchmark class="\jubianchi\PPC\Benchmarks\Functions\Combinators\Many">
                  <subject name="bench_ppc">
                    <variant>
                      <parameter-set name="1 kB"/>
                      <stats mean="208.5"/>
                    </variant>
                    <variant>
                      <parameter-set name="100 kB"/>
                      <stats mean="21560"/>
                    </variant>
                  </subject>
                  <subject name="bench_parsica">
                    <variant>
                      <parameter-set name="1 kB"/>
                      <stats mean="1656"/>
                    </variant>
                  </subject>
                </benchmark>
                <benchmark class="\jubianchi\PPC\Benchmarks\JSON">
                  <subject name="bench_json_ppc">
                    <variant>
                      <parameter-set name="10 kB"/>
                      <stats mean="3816"/>
                    </variant>
                  </subject>
                </benchmark>
              </suite>
            </phpbench>
            XML;

        self::assertSame(
            ['many' => ['1 kB' => ['ppc' => 208_500.0, 'parsica' => 1_656_000.0], '100 kB' => ['ppc' => 21_560_000.0]]],
            BenchReport::fromPhpBench($dump),
        );
    }

    #[Test]
    public function rejectsAnInvalidPhpBenchDump(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid PHPBench dump');

        BenchReport::fromPhpBench('<phpbench>');
    }

    #[Test]
    public function readsTheMeanTimeOfEachBenchmarkFromCriterion(): void
    {
        self::assertSame(
            ['many' => ['1 kB' => ['nom' => 2185.5], '100 kB' => ['nom' => 184410.0]]],
            BenchReport::fromCriterion([
                [
                    'benchmark' => '{"group_id":"many","function_id":"nom","value_str":"1 kB"}',
                    'estimates' => '{"mean":{"point_estimate":2185.5},"median":{"point_estimate":2169.0}}',
                ],
                [
                    'benchmark' => '{"group_id":"many","function_id":"nom","value_str":"100 kB"}',
                    'estimates' => '{"mean":{"point_estimate":184410},"median":{"point_estimate":184000}}',
                ],
            ]),
        );
    }

    #[Test]
    public function rejectsAnInvalidCriterionReport(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid criterion report');

        BenchReport::fromCriterion([['benchmark' => '{"group_id":"many"}', 'estimates' => '{}']]);
    }

    #[Test]
    public function mergesTheTimingsOfSeveralSources(): void
    {
        self::assertSame(
            ['many' => ['1 kB' => ['ppc' => 2.0, 'nom' => 1.0], '100 kB' => ['nom' => 3.0]], 'char' => ['1 B' => ['ppc' => 4.0]]],
            BenchReport::merge(
                ['many' => ['1 kB' => ['ppc' => 2.0]], 'char' => ['1 B' => ['ppc' => 4.0]]],
                ['many' => ['1 kB' => ['nom' => 1.0], '100 kB' => ['nom' => 3.0]]],
            ),
        );
    }

    #[Test]
    public function rendersATableComparingTheLibraries(): void
    {
        $timings = [
            'many' => [
                '1 kB' => ['ppc' => 208_500.0, 'parsica' => 1_656_000.0, 'nom' => 2185.5],
                '100 kB' => ['ppc' => 21_560_000.0, 'nom' => 184_410.0],
            ],
            'char' => [
                '1 B' => ['ppc' => 850.0],
            ],
        ];

        self::assertSame(
            <<<'TABLE'
                | function | input  | ppc      | parsica | nom       | ppc / nom |
                |----------|--------|----------|---------|-----------|-----------|
                | char     | 1 B    | 850 ns   | —       | —         | —         |
                | many     | 1 kB   | 208.5 µs | 1.66 ms | 2.19 µs   | ×95.4     |
                | many     | 100 kB | 21.56 ms | —       | 184.41 µs | ×116.9    |

                TABLE,
            BenchReport::table($timings),
        );
    }
}
