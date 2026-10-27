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

use jubianchi\PPC\Success;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function jubianchi\PPC\char;
use function jubianchi\PPC\opt;

/**
 * @internal
 */
#[Small]
final class OptTest extends TestCase
{
    #[Test]
    public function returnsTheValueOfTheParser(): void
    {
        self::assertEquals(new Success('a', 1), opt(char('a'))('ab', 0));
    }

    #[Test]
    public function succeedsWithoutConsumingWhenTheParserFails(): void
    {
        self::assertEquals(new Success(null, 0), opt(char('a'))('b', 0));
    }

    #[Test]
    public function returnsTheDefaultValueWhenTheParserFails(): void
    {
        self::assertEquals(new Success('', 0), opt(char('a'), '')('b', 0));
    }
}
