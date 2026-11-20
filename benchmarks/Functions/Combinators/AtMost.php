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

namespace jubianchi\PPC\Benchmarks\Functions\Combinators;

use jubianchi\PPC\Failure;
use jubianchi\PPC\Incomplete;
use jubianchi\PPC\Success;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\ParamProviders;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;

use function jubianchi\PPC\atMost;
use function jubianchi\PPC\char;
use function jubianchi\PPC\parse;

/**
 * atMost(char('a'), $n) on a run of $n "a": the whole input is consumed.
 *
 * Parsica has no repetition with a maximum.
 */
#[Warmup(1)]
#[Revs(1)]
#[Iterations(5)]
class AtMost
{
    private string $input;

    /**
     * @var \Closure(string, int, bool=): (Success<list<string>>|Failure|Incomplete)
     */
    private \Closure $ppc;

    /**
     * @param array{fixture: string} $params
     */
    public function setUpPpc(array $params): void
    {
        $this->input = self::fixture($params['fixture']);
        $this->ppc = atMost(char('a'), \strlen($this->input));

        if (\strlen($this->input) !== \count(parse($this->ppc, $this->input))) {
            throw new \LogicException('PPC does not consume the whole input');
        }
    }

    /**
     * @return \Generator<string, array{fixture: string}>
     */
    public function provideInputs(): \Generator
    {
        yield '1 kB' => ['fixture' => '1kB.txt'];
        yield '100 kB' => ['fixture' => '100kB.txt'];
    }

    #[BeforeMethods('setUpPpc')]
    #[ParamProviders('provideInputs')]
    public function bench_ppc(): void
    {
        parse($this->ppc, $this->input);
    }

    private static function fixture(string $name): string
    {
        $input = file_get_contents(__DIR__.'/../../fixtures/many/'.$name);

        if (false === $input) {
            throw new \RuntimeException(\sprintf('Unable to read the fixture %s', $name));
        }

        return $input;
    }
}
