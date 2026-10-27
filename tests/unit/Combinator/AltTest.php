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
use function jubianchi\PPC\seq;
use function jubianchi\PPC\word;

/**
 * @internal
 */
#[Small]
final class AltTest extends TestCase
{
    #[Test]
    public function returnsTheFirstSuccess(): void
    {
        self::assertEquals(new Success('a', 1), alt(char('a'), word('ab'))('ab', 0));
    }

    #[Test]
    public function triesTheNextParserFromTheSameOffset(): void
    {
        self::assertEquals(
            new Success('ac', 3),
            alt(word('ab'), word('ac'))('_ac', 1),
        );
    }

    #[Test]
    public function returnsTheFailureThatWentTheFurthest(): void
    {
        self::assertEquals(
            new Failure(['"c"'], 1),
            alt(word('xy'), seq(char('a'), char('c')), char('b'))('ax', 0),
        );
    }

    #[Test]
    public function mergesTheExpectationsOfFailuresAtTheSameOffset(): void
    {
        self::assertEquals(
            new Failure(['"a"', '"b"', '"c"'], 0),
            alt(char('a'), char('b'), char('c'))('x', 0),
        );
    }
}
