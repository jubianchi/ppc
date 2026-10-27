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

use function jubianchi\PPC\char;
use function jubianchi\PPC\repeat;

/**
 * @internal
 */
#[Small]
final class RepeatTest extends TestCase
{
    #[Test]
    public function matchesExactlyTheGivenNumberOfTimes(): void
    {
        self::assertEquals(new Success(['a', 'a'], 2), repeat(2, char('a'))('aaa', 0));
    }

    #[Test]
    public function failsWhenThereAreNotEnoughMatches(): void
    {
        self::assertEquals(new Failure(['"a"'], 2), repeat(3, char('a'))('aab', 0));
    }

    #[Test]
    public function rejectsANegativeCount(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        repeat(-1, char('a'));
    }
}
