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

namespace jubianchi\PPC\Benchmarks;

use jubianchi\PPC\Failure;
use jubianchi\PPC\Incomplete;
use jubianchi\PPC\Success;
use PhpBench\Attributes\AfterMethods;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\ParamProviders;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;

use function jubianchi\PPC\many;
use function jubianchi\PPC\map;
use function jubianchi\PPC\parse;
use function jubianchi\PPC\regex;
use function jubianchi\PPC\seq;
use function jubianchi\PPC\stream;
use function jubianchi\PPC\word;

/**
 * Parses NDJSON records from a file, either streamed by chunks or loaded at once.
 */
#[Warmup(1)]
#[Revs(1)]
#[Iterations(5)]
#[BeforeMethods('setUp')]
#[AfterMethods('tearDown')]
#[ParamProviders('provideRecords')]
class Streaming
{
    private string $file;

    /**
     * @param array{records: int} $params
     */
    public function setUp(array $params): void
    {
        $this->file = (string) tempnam(sys_get_temp_dir(), 'ppc-bench-');
        $handle = fopen($this->file, 'w');
        \assert(\is_resource($handle));

        for ($id = 0; $id < $params['records']; ++$id) {
            fwrite($handle, \sprintf("{\"id\":%d,\"name\":\"%s\"}\n", $id, str_repeat('x', $id % 50)));
        }

        fclose($handle);
    }

    public function tearDown(): void
    {
        unlink($this->file);
    }

    /**
     * @return \Generator<string, array{records: int}>
     */
    public function provideRecords(): \Generator
    {
        yield '1 MB' => ['records' => 20_000];
        yield '10 MB' => ['records' => 200_000];
    }

    public function bench_stream(): void
    {
        $handle = fopen($this->file, 'r');
        \assert(\is_resource($handle));

        foreach (stream(self::record(), $handle) as $record) {
        }

        fclose($handle);
    }

    public function bench_parse(): void
    {
        foreach (parse(many(self::record()), (string) file_get_contents($this->file)) as $record) {
        }
    }

    /**
     * @return \Closure(string, int, bool=): (Success<mixed>|Failure|Incomplete)
     */
    private static function record(): \Closure
    {
        return seq(word('{"id":'), regex('/[0-9]+/'), word(',"name":"'), regex('/[a-z]*/'), word("\"}\n"))
            |> map(static fn (array $values): mixed => $values[1]);
    }
}
