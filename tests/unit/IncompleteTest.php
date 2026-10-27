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
use jubianchi\PPC\Success;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function jubianchi\PPC\alt;
use function jubianchi\PPC\char;
use function jubianchi\PPC\consumed;
use function jubianchi\PPC\debug;
use function jubianchi\PPC\enclosed;
use function jubianchi\PPC\label;
use function jubianchi\PPC\many;
use function jubianchi\PPC\many1;
use function jubianchi\PPC\map;
use function jubianchi\PPC\not;
use function jubianchi\PPC\opt;
use function jubianchi\PPC\recursive;
use function jubianchi\PPC\repeat;
use function jubianchi\PPC\separated;
use function jubianchi\PPC\seq;

/**
 * Combinators and modifiers forward the partial flag and propagate Incomplete without interpreting it.
 *
 * @internal
 */
#[Small]
final class IncompleteTest extends TestCase
{
    /**
     * @return \Generator<string, array{\Closure(string, int, bool=): (Success<mixed>|Failure|Incomplete), string}>
     */
    public static function parsers(): \Generator
    {
        $stub = self::stub();

        yield 'seq' => [seq(char('a'), $stub), 'a'];
        yield 'alt does not try the next parser' => [alt($stub, char('a')), 'a'];
        yield 'alt after a failure' => [alt(char('b'), $stub), 'a'];
        yield 'opt' => [opt($stub), 'a'];
        yield 'many' => [many($stub), 'a'];
        yield 'many after a success' => [many(alt(char('a'), $stub)), 'a'];
        yield 'many1' => [many1($stub), 'a'];
        yield 'many1 after a success' => [many1(alt(char('a'), $stub)), 'a'];
        yield 'repeat' => [repeat(2, alt(char('a'), $stub)), 'a'];
        yield 'separated' => [separated(char(','), $stub), 'a'];
        yield 'separated separator' => [separated($stub, char('a')), 'a'];
        yield 'separated after a separator' => [separated(char(','), alt(char('a'), $stub)), 'a,'];
        yield 'enclosed before' => [enclosed($stub, char('a')), 'a'];
        yield 'enclosed parser' => [enclosed(char('('), $stub, char(')')), '('];
        yield 'enclosed after' => [enclosed(char('('), char('a'), $stub), '(a'];
        yield 'not' => [not($stub), 'a'];
        yield 'recursive' => [recursive(static fn (\Closure $self): \Closure => $stub), 'a'];
        yield 'map' => [$stub |> map(static fn (mixed $value): mixed => $value), 'a'];
        yield 'label' => [$stub |> label('stub'), 'a'];
        yield 'consumed' => [$stub |> consumed(), 'a'];
        yield 'debug' => [$stub |> debug('stub', static fn (string $line): null => null), 'a'];
    }

    /**
     * @param \Closure(string, int, bool=): (Success<mixed>|Failure|Incomplete) $parser
     */
    #[Test]
    #[DataProvider('parsers')]
    public function propagatesIncompleteOnPartialInput(\Closure $parser, string $input): void
    {
        self::assertEquals(new Incomplete(2), $parser($input, 0, true));
    }

    /**
     * @param \Closure(string, int, bool=): (Success<mixed>|Failure|Incomplete) $parser
     */
    #[Test]
    #[DataProvider('parsers')]
    public function isNeverIncompleteOnCompleteInput(\Closure $parser, string $input): void
    {
        self::assertNotInstanceOf(Incomplete::class, $parser($input, 0));
    }

    /**
     * Incomplete on a partial input, failing otherwise.
     *
     * @return \Closure(string, int, bool=): (Success<string>|Failure|Incomplete)
     */
    private static function stub(): \Closure
    {
        return static fn (string $input, int $offset, bool $partial = false): Failure|Incomplete => $partial
            ? new Incomplete(2)
            : new Failure(['stub'], $offset);
    }
}
