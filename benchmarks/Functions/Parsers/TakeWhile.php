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

namespace jubianchi\PPC\Benchmarks\Functions\Parsers;

use jubianchi\PPC\Failure;
use jubianchi\PPC\Incomplete;
use jubianchi\PPC\Success;
use Parsica\Parsica\Parser;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\ParamProviders;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;

use function jubianchi\PPC\parse;
use function jubianchi\PPC\takeWhile;

/**
 * takeWhile(fn ($char) => 'a' === $char) on a run of "a": the whole input is consumed.
 */
#[Warmup(1)]
#[Revs(1)]
#[Iterations(5)]
class TakeWhile
{
    private string $input;

    /**
     * @var \Closure(string, int, bool=): (Success<string>|Failure|Incomplete)
     */
    private \Closure $ppc;

    /**
     * Parsica's takeWhile() does not bind its template.
     *
     * @var Parser<mixed>
     */
    private Parser $parsica;

    /**
     * @param array{fixture: string} $params
     */
    public function setUpPpc(array $params): void
    {
        $this->input = self::fixture($params['fixture']);
        $this->ppc = takeWhile(static fn (string $char): bool => 'a' === $char);

        if ($this->input !== parse($this->ppc, $this->input)) {
            throw new \LogicException('PPC does not consume the whole input');
        }
    }

    /**
     * @param array{fixture: string} $params
     */
    public function setUpParsica(array $params): void
    {
        $this->input = self::fixture($params['fixture']);
        $this->parsica = \Parsica\Parsica\takeWhile(static fn (string $char): bool => 'a' === $char);

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
     * Parsica's takeWhile() is quadratic: 8 seconds on 100 kB.
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
        $input = file_get_contents(__DIR__.'/../../fixtures/takeWhile/'.$name);

        if (false === $input) {
            throw new \RuntimeException(\sprintf('Unable to read the fixture %s', $name));
        }

        return $input;
    }
}
