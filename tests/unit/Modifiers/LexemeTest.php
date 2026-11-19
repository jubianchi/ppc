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

namespace jubianchi\PPC\Tests\Modifiers;

use jubianchi\PPC\Failure;
use jubianchi\PPC\Incomplete;
use jubianchi\PPC\Success;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

use function jubianchi\PPC\char;
use function jubianchi\PPC\lexeme;
use function jubianchi\PPC\many;
use function jubianchi\PPC\word;

/**
 * @internal
 */
#[Small]
final class LexemeTest extends TestCase
{
    #[Test]
    public function skipsTheSpacesTabsAndLineBreaksAfterTheParser(): void
    {
        $parser = word('if') |> lexeme();

        self::assertEquals(new Success('if', 8), $parser("_if \t\r\n x", 1));
    }

    #[Test]
    #[TestWith(['ifx'])]
    #[TestWith(["if\fx"])]
    #[TestWith(['if'])]
    public function succeedsWithoutWhitespace(string $input): void
    {
        $parser = word('if') |> lexeme();

        self::assertEquals(new Success('if', 2), $parser($input, 0));
    }

    #[Test]
    public function keepsTheFailureOfTheParser(): void
    {
        $parser = word('if') |> lexeme();

        self::assertEquals(new Failure(['"if"'], 0, 2), $parser('of', 0));
    }

    #[Test]
    public function keepsTheHintOfTheParser(): void
    {
        $parser = many(char('a')) |> lexeme();

        self::assertEquals(new Success(['a', 'a'], 3, new Failure(['"a"'], 2)), $parser('aa b', 0));
    }

    #[Test]
    public function isIncompleteWhenAPartialInputEndsInTheWhitespace(): void
    {
        $parser = word('if') |> lexeme();

        self::assertEquals(new Incomplete(1), $parser('if  ', 0, true));
    }

    #[Test]
    public function skipsACustomWhitespace(): void
    {
        $parser = word('if') |> lexeme(many(char('_')));

        self::assertEquals(new Success('if', 4, new Failure(['"_"'], 4)), $parser('if__ x', 0));
    }

    #[Test]
    public function failsWhenACustomWhitespaceFails(): void
    {
        $parser = word('if') |> lexeme(char(';'));

        self::assertEquals(new Failure(['";"'], 2), $parser('if,', 0));
    }
}
