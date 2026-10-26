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

use jubianchi\PPC;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\ParamProviders;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;
use Random\Engine\Mt19937;
use Random\Randomizer;

use function jubianchi\PPC\Combinators\alt;
use function jubianchi\PPC\Combinators\separated;
use function jubianchi\PPC\Combinators\seq;
use function jubianchi\PPC\Parsers\char;
use function jubianchi\PPC\Parsers\eos;
use function jubianchi\PPC\Parsers\word;

#[Warmup(1)]
#[Revs(1)]
#[Iterations(5)]
#[BeforeMethods('setUp')]
#[ParamProviders('provideKeywords')]
class Backtracking
{
    private const string PREFIX = 'common_keyword_prefix_';

    private string $data;
    private PPC\Parser $parser;

    /**
     * @param array{keywords: int} $params
     */
    public function setUp(array $params): void
    {
        $random = new Randomizer(new Mt19937(42));
        $keywords = [];

        for ($i = 0; $i < $params['keywords']; ++$i) {
            $keywords[] = self::PREFIX.$random->getInt(1, 9);
        }

        $this->data = implode(' ', $keywords);
        $this->parser = seq(
            separated(
                char(' '),
                alt(...array_map(static fn (int $suffix): PPC\Parser => seq(word(self::PREFIX), char((string) $suffix)), range(1, 9))),
            ),
            eos(),
        );
    }

    /**
     * @return \Generator<string, array{keywords: int}>
     */
    public function provideKeywords(): \Generator
    {
        yield '100 keywords' => ['keywords' => 100];
        yield '1000 keywords' => ['keywords' => 1000];
    }

    public function bench_alternatives_with_common_prefix(): void
    {
        ($this->parser)(new PPC\Stream\Char($this->data))->result();
    }
}
