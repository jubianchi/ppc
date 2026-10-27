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
use jubianchi\PPC\Partial;
use jubianchi\PPC\Success;
use PhpBench\Attributes\AfterMethods;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\ParamProviders;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;

use function jubianchi\PPC\char;
use function jubianchi\PPC\many;
use function jubianchi\PPC\parse;
use function jubianchi\PPC\regex;
use function jubianchi\PPC\seq;
use function jubianchi\PPC\stream;

#[Warmup(1)]
#[Revs(1)]
#[Iterations(5)]
#[BeforeMethods('setUp')]
#[AfterMethods('tearDown')]
#[ParamProviders('provideLines')]
class Streaming
{
    /**
     * @var resource
     */
    private $source;

    /**
     * @var \Closure(string, int, bool=): (Success<list<mixed>>|Failure|Partial<list<mixed>>)
     */
    private \Closure $line;

    /**
     * @param array{lines: int} $params
     */
    public function setUp(array $params): void
    {
        $source = tmpfile();
        \assert(\is_resource($source));

        for ($i = 0; $i < $params['lines']; ++$i) {
            fwrite($source, \sprintf("record %d with some padding to make it longer\n", $i));
        }

        $this->source = $source;
        $this->line = seq(regex('/[^\n]*/', 1), char("\n"));
    }

    public function tearDown(): void
    {
        fclose($this->source);
    }

    /**
     * @return \Generator<string, array{lines: int}>
     */
    public function provideLines(): \Generator
    {
        yield '1 MB' => ['lines' => 20_000];
        yield '10 MB' => ['lines' => 200_000];
    }

    public function bench_stream(): void
    {
        rewind($this->source);

        foreach (stream($this->line, $this->source) as $line) {
        }
    }

    public function bench_parse_whole_input(): void
    {
        rewind($this->source);

        parse(many($this->line), (string) stream_get_contents($this->source));
    }
}
