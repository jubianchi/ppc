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
use jubianchi\PPC\Success;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function jubianchi\PPC\char;
use function jubianchi\PPC\many;
use function jubianchi\PPC\peek;
use function jubianchi\PPC\word;

/**
 * @internal
 */
#[Small]
final class PeekTest extends TestCase
{
    #[Test]
    public function succeedsWithTheValueOfTheParserWithoutConsumingInput(): void
    {
        $parser = word('if') |> peek();

        self::assertEquals(new Success('if', 1), $parser('_if', 1));
    }

    #[Test]
    public function keepsTheHintOfTheParser(): void
    {
        $parser = many(char('a')) |> peek();

        self::assertEquals(new Success(['a', 'a'], 0, new Failure(['"a"'], 2)), $parser('aab', 0));
    }

    #[Test]
    public function keepsTheFailure(): void
    {
        $parser = word('if') |> peek();

        self::assertEquals(new Failure(['"if"'], 0), $parser('of', 0));
    }
}
