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

use function jubianchi\PPC\atMost;
use function jubianchi\PPC\char;

/**
 * @internal
 */
#[Small]
final class AtMostTest extends TestCase
{
    #[Test]
    public function stopsAtTheMaximumWithoutConsumingMore(): void
    {
        self::assertEquals(new Success(['a', 'a'], 2), atMost(char('a'), 2)('aaa', 0));
    }

    #[Test]
    public function matchesNothing(): void
    {
        self::assertEquals(new Success([], 0, new Failure(['"a"'], 0)), atMost(char('a'), 2)('b', 0));
    }

    #[Test]
    public function rejectsANegativeMaximum(): void
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('Expected a maximum of at least 0, got -1');

        atMost(char('a'), -1);
    }
}
