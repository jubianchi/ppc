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

namespace jubianchi\PPC\Tests\Combinator;

use jubianchi\PPC\Failure;
use jubianchi\PPC\Incomplete;
use jubianchi\PPC\Success;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function jubianchi\PPC\chainl1;
use function jubianchi\PPC\chainr1;
use function jubianchi\PPC\char;
use function jubianchi\PPC\map;
use function jubianchi\PPC\opt;
use function jubianchi\PPC\regex;

/**
 * @internal
 */
#[Small]
final class ChainTest extends TestCase
{
    #[Test]
    public function chainl1FoldsFromTheLeft(): void
    {
        self::assertEquals(new Success(5, 6, new Failure(['"-"'], 6)), chainl1(self::number(), self::minus())('10-2-3', 0));
    }

    #[Test]
    public function chainr1FoldsFromTheRight(): void
    {
        self::assertEquals(new Success(11, 6, new Failure(['"-"'], 6)), chainr1(self::number(), self::minus())('10-2-3', 0));
    }

    #[Test]
    public function succeedsWithASingleOperand(): void
    {
        self::assertEquals(new Success(7, 1, new Failure(['"-"'], 1)), chainl1(self::number(), self::minus())('7', 0));
        self::assertEquals(new Success(7, 1, new Failure(['"-"'], 1)), chainr1(self::number(), self::minus())('7', 0));
    }

    #[Test]
    public function failsWithoutAnOperand(): void
    {
        self::assertEquals(new Failure(['/\d+/'], 0), chainl1(self::number(), self::minus())('-1', 0));
        self::assertEquals(new Failure(['/\d+/'], 0), chainr1(self::number(), self::minus())('-1', 0));
    }

    #[Test]
    public function stopsBeforeATrailingOperatorAndKeepsTheFailureAfterItAsHint(): void
    {
        self::assertEquals(new Success(1, 1, new Failure(['/\d+/'], 2)), chainl1(self::number(), self::minus())('1-]', 0));
        self::assertEquals(new Success(1, 1, new Failure(['/\d+/'], 2)), chainr1(self::number(), self::minus())('1-]', 0));
    }

    #[Test]
    public function propagatesIncomplete(): void
    {
        self::assertEquals(new Incomplete(1), chainl1(self::digit(), self::minus())('1-', 0, true));
        self::assertEquals(new Incomplete(1), chainr1(self::digit(), self::minus())('1-', 0, true));
    }

    #[Test]
    public function rejectsAnOperatorAndAnOperandThatSucceedWithoutConsuming(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Operator and operand succeeded without consuming input at offset 1');

        $operand = opt(char('1'), '1') |> map(self::integer(...));
        $operator = opt(char('-')) |> map(static fn (): \Closure => static fn (int $a, int $b): int => $a - $b);

        chainl1($operand, $operator)('1]', 0);
    }

    /**
     * @return \Closure(string, int, bool=): (Success<int>|Failure|Incomplete)
     */
    private static function number(): \Closure
    {
        return regex('/\d+/') |> map(self::integer(...));
    }

    /**
     * @return \Closure(string, int, bool=): (Success<int>|Failure|Incomplete)
     */
    private static function digit(): \Closure
    {
        return char('1') |> map(self::integer(...));
    }

    private static function integer(string $digits): int
    {
        return (int) $digits;
    }

    /**
     * @return \Closure(string, int, bool=): (Success<\Closure(int, int): int>|Failure|Incomplete)
     */
    private static function minus(): \Closure
    {
        return char('-') |> map(static fn (): \Closure => static fn (int $a, int $b): int => $a - $b);
    }
}
