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

namespace jubianchi\PPC\Benchmarks\Functions\Modifiers;

use jubianchi\PPC\Failure;
use jubianchi\PPC\Incomplete;
use jubianchi\PPC\Success;
use Parsica\Parsica\Parser;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\ParamProviders;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;

use function jubianchi\PPC\char;
use function jubianchi\PPC\many;
use function jubianchi\PPC\parse;
use function jubianchi\PPC\peek;
use function jubianchi\PPC\preceded;

/**
 * many(preceded(char('a') |> peek(), char('a'))) on a run of "a": each char is peeked, then consumed.
 */
#[Warmup(1)]
#[Revs(1)]
#[Iterations(5)]
class Peek
{
    private string $input;

    /**
     * @var \Closure(string, int, bool=): (Success<list<string>>|Failure|Incomplete)
     */
    private \Closure $ppc;

    /**
     * @var Parser<list<string>>
     */
    private Parser $parsica;

    /**
     * @param array{fixture: string} $params
     */
    public function setUpPpc(array $params): void
    {
        $this->input = self::fixture($params['fixture']);
        $this->ppc = many(preceded(char('a') |> peek(), char('a')));

        if (\strlen($this->input) !== \count(parse($this->ppc, $this->input))) {
            throw new \LogicException('PPC does not consume the whole input');
        }
    }

    /**
     * @param array{fixture: string} $params
     */
    public function setUpParsica(array $params): void
    {
        $this->input = self::fixture($params['fixture']);
        $this->parsica = \Parsica\Parsica\many(\Parsica\Parsica\keepSecond(\Parsica\Parsica\lookAhead(\Parsica\Parsica\char('a')), \Parsica\Parsica\char('a')));

        if (!$this->parsica->tryString($this->input)->remainder()->isEOF()) {
            throw new \LogicException('Parsica does not consume the whole input');
        }
    }

    /**
     * @return \Generator<string, array{fixture: string}>
     */
    public function provideInputs(): \Generator
    {
        yield from $this->provideSmallInputs();
        yield '100 kB' => ['fixture' => '100kB.txt'];
    }

    /**
     * Parsica's many() is quadratic: seconds on 100 kB.
     *
     * @return \Generator<string, array{fixture: string}>
     */
    public function provideSmallInputs(): \Generator
    {
        yield '1 kB' => ['fixture' => '1kB.txt'];
    }

    #[BeforeMethods('setUpPpc')]
    #[ParamProviders('provideInputs')]
    public function bench_ppc(): void
    {
        parse($this->ppc, $this->input);
    }

    #[BeforeMethods('setUpParsica')]
    #[ParamProviders('provideSmallInputs')]
    public function bench_parsica(): void
    {
        $this->parsica->tryString($this->input);
    }

    private static function fixture(string $name): string
    {
        $input = file_get_contents(__DIR__.'/../../fixtures/peek/'.$name);

        if (false === $input) {
            throw new \RuntimeException(\sprintf('Unable to read the fixture %s', $name));
        }

        return $input;
    }
}
