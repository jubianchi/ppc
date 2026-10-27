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
use jubianchi\PPC\Success;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function jubianchi\PPC\alt;
use function jubianchi\PPC\char;
use function jubianchi\PPC\enclosed;
use function jubianchi\PPC\recursive;

/**
 * @internal
 */
#[Small]
final class RecursiveTest extends TestCase
{
    #[Test]
    public function passesTheParserBeingDefinedToItsDefinition(): void
    {
        $nested = recursive(static fn (\Closure $nested): \Closure => alt(char('x'), enclosed(char('('), $nested, char(')'))));

        self::assertEquals(new Success('x', 7), $nested('(((x)))', 0));
    }

    #[Test]
    public function failsLikeItsDefinition(): void
    {
        $nested = recursive(static fn (\Closure $nested): \Closure => alt(char('x'), enclosed(char('('), $nested, char(')'))));

        self::assertEquals(new Failure(['"x"', '"("'], 2), $nested('((]', 0));
    }

    #[Test]
    public function callsTheDefinitionOnce(): void
    {
        $calls = 0;
        $parser = recursive(static function (\Closure $self) use (&$calls): \Closure {
            ++$calls;

            return char('a');
        });

        $parser('a', 0);
        $parser('a', 0);

        self::assertSame(1, $calls);
    }
}
