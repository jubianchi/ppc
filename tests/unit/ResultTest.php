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

namespace jubianchi\PPC\Tests;

use jubianchi\PPC\Failure;
use jubianchi\PPC\Incomplete;
use jubianchi\PPC\Result;
use jubianchi\PPC\Success;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function jubianchi\PPC\alt;
use function jubianchi\PPC\char;
use function jubianchi\PPC\consumed;
use function jubianchi\PPC\enclosed;
use function jubianchi\PPC\many;
use function jubianchi\PPC\many1;
use function jubianchi\PPC\map;
use function jubianchi\PPC\opt;
use function jubianchi\PPC\repeat;
use function jubianchi\PPC\separated;
use function jubianchi\PPC\seq;

/**
 * @internal
 */
#[Small]
final class ResultTest extends TestCase
{
    #[Test]
    public function isImplementedByEveryParserResult(): void
    {
        self::assertInstanceOf(Result::class, new Success('a', 1));
        self::assertInstanceOf(Result::class, new Failure(['"a"'], 0));
        self::assertInstanceOf(Result::class, new Incomplete());
    }

    /**
     * @return \Generator<string, array{\Closure(string, int, bool=): (Success<mixed>|Failure|Incomplete)}>
     */
    public static function parsers(): \Generator
    {
        $invalid = self::invalid();

        yield 'seq' => [seq($invalid)];
        yield 'alt' => [alt($invalid)];
        yield 'opt' => [opt($invalid)];
        yield 'many' => [many($invalid)];
        yield 'many1' => [many1($invalid)];
        yield 'repeat' => [repeat(1, $invalid)];
        yield 'separated' => [separated(char(','), $invalid)];
        yield 'enclosed' => [enclosed(char('('), $invalid)];
        yield 'map' => [$invalid |> map(strtoupper(...))];
        yield 'consumed' => [$invalid |> consumed()];
    }

    /**
     * @param \Closure(string, int, bool=): (Success<mixed>|Failure|Incomplete) $parser
     */
    #[Test]
    #[DataProvider('parsers')]
    public function isEnforcedOnParsersBuiltByCombinators(\Closure $parser): void
    {
        $this->expectException(\TypeError::class);

        $parser('(', 0);
    }

    /**
     * Stands for a parser written without static analysis, returning something else than a Result.
     *
     * @return \Closure(string, int, bool=): (Success<string>|Failure|Incomplete)
     */
    private static function invalid(): \Closure
    {
        // @phpstan-ignore return.type (deliberately invalid parser)
        return static fn (string $input, int $offset): string => 'not a result';
    }
}
