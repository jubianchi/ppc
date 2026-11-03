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

use function jubianchi\PPC\anyBut;
use function jubianchi\PPC\char;
use function jubianchi\PPC\word;

/**
 * @internal
 */
#[Small]
final class AnyButTest extends TestCase
{
    #[Test]
    public function consumesOneCharWhenTheParserFails(): void
    {
        self::assertEquals(new Success('é', 3), anyBut(char('"'))('aé"', 1));
    }

    #[Test]
    public function failsWhenTheParserMatches(): void
    {
        self::assertEquals(new Failure(['anything but "\""'], 1), anyBut(char('"'))('a"', 1));
        self::assertEquals(new Failure(['anything but "*/"'], 1, 2), anyBut(word('*/'))('a*/', 1));
    }

    #[Test]
    public function onlyConsumesOneCharOfAPartialMatch(): void
    {
        self::assertEquals(new Success('a', 1), anyBut(word('ab'))('ac', 0));
    }

    #[Test]
    public function failsAtTheEndOfInput(): void
    {
        self::assertEquals(new Failure(['any char'], 1), anyBut(char('"'))('a', 1));
    }
}
