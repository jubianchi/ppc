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
use jubianchi\PPC\Partial;
use jubianchi\PPC\Success;
use jubianchi\PPC\SyntaxError;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function jubianchi\PPC\char;
use function jubianchi\PPC\map;
use function jubianchi\PPC\regex;
use function jubianchi\PPC\seq;
use function jubianchi\PPC\stream;

/**
 * @internal
 */
#[Medium]
final class StreamTest extends TestCase
{
    #[Test]
    public function yieldsEachItemOfTheStream(): void
    {
        self::assertSame(['1', '22', '333', 'é'], iterator_to_array(stream(self::line(), self::source("1\n22\n333\né\n"), 2), false));
    }

    #[Test]
    public function yieldsNothingFromAnEmptyStream(): void
    {
        self::assertSame([], iterator_to_array(stream(self::line(), self::source(''))));
    }

    #[Test]
    public function locatesErrorsFromTheStartOfTheStream(): void
    {
        $error = null;

        try {
            iterator_to_array(stream(self::line(), self::source("1\n22\n3x\n"), 3));
        } catch (SyntaxError $error) {
        }

        self::assertInstanceOf(SyntaxError::class, $error);
        self::assertSame('Expected "\n", got "x" at line 3, column 2', $error->getMessage());
        self::assertEquals(new Failure(['"\n"'], 6), $error->failure);
    }

    #[Test]
    public function locatesErrorsOnTheLineOfThePreviousItems(): void
    {
        $item = seq(regex('/é+/u', 1), char(','));

        $this->expectExceptionObject(new SyntaxError(new Failure(['","'], 10), 'Expected ",", got "x" at line 1, column 7'));

        iterator_to_array(stream($item, self::source('é,éé,éx,'), 1));
    }

    #[Test]
    public function rejectsItemsConsumingNothing(): void
    {
        $this->expectExceptionObject(new \LogicException('Item parser succeeded without consuming input at offset 2'));

        iterator_to_array(stream(regex('/a?/', 1), self::source('aab')));
    }

    #[Test]
    public function keepsOnlyTheCurrentItemInMemory(): void
    {
        $source = tmpfile();
        self::assertIsResource($source);

        for ($i = 0; $i < 100_000; ++$i) {
            fwrite($source, \sprintf("record %d with some padding to make it longer\n", $i));
        }

        rewind($source);
        memory_reset_peak_usage();
        $before = memory_get_usage();
        $count = 0;

        foreach (stream(seq(regex('/[^\n]*/', 1), char("\n")), $source) as $line) {
            ++$count;
        }

        self::assertSame(100_000, $count);
        self::assertGreaterThan(4_000_000, ftell($source));
        self::assertLessThan(256 * 1024, memory_get_peak_usage() - $before);
    }

    /**
     * @return \Closure(string, int, bool=): (Success<mixed>|Failure|Partial<mixed>)
     */
    private static function line(): \Closure
    {
        return seq(regex('/[0-9é]+/u', 1), char("\n")) |> map(static fn (array $values): mixed => $values[0]);
    }

    /**
     * @return resource
     */
    private static function source(string $content)
    {
        $source = fopen('php://memory', 'r+');
        self::assertIsResource($source);
        fwrite($source, $content);
        rewind($source);

        return $source;
    }
}
