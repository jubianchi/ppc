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
use jubianchi\PPC\Success;
use jubianchi\PPC\SyntaxError;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function jubianchi\PPC\parse;
use function jubianchi\PPC\word;

/**
 * @internal
 */
#[Small]
final class ParseTest extends TestCase
{
    #[Test]
    public function returnsTheValueOfASuccessfulParse(): void
    {
        self::assertSame('null', parse(word('null'), 'null'));
    }

    #[Test]
    public function runsTheParserFromTheStartOfTheInput(): void
    {
        $offsets = [];
        $parser = static function (string $input, int $offset) use (&$offsets): Success {
            $offsets[] = $offset;

            return new Success(null, $offset);
        };

        parse($parser, 'abc');

        self::assertSame([0], $offsets);
    }

    #[Test]
    public function throwsOnFailure(): void
    {
        $failure = new Failure(['"a"', '"b"', '"c"'], 0);

        $error = null;

        try {
            parse(self::failWith($failure), 'x');
        } catch (SyntaxError $error) {
        }

        self::assertInstanceOf(SyntaxError::class, $error);
        self::assertSame('Expected "a", "b" or "c", got "x" at line 1, column 1', $error->getMessage());
        self::assertSame($failure, $error->failure);
    }

    #[Test]
    public function locatesTheFailureInCharsOnItsLine(): void
    {
        $this->expectExceptionMessage('Expected "null", got "日" at line 2, column 5');

        parse(self::failWith(new Failure(['"null"'], 15)), "[\"été\",\n\"é\",日本]");
    }

    #[Test]
    public function reportsTheEndOfInput(): void
    {
        $this->expectExceptionMessage('Expected "]", got end of input at line 1, column 4');

        parse(self::failWith(new Failure(['"]"'], 3)), '[1,');
    }

    /**
     * @return \Closure(string, int): (Success<never>|Failure)
     */
    private static function failWith(Failure $failure): \Closure
    {
        return static fn (): Failure => $failure;
    }
}
