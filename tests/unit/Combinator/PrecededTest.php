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
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

use function jubianchi\PPC\char;
use function jubianchi\PPC\many;
use function jubianchi\PPC\preceded;
use function jubianchi\PPC\word;

/**
 * @internal
 */
#[Small]
final class PrecededTest extends TestCase
{
    #[Test]
    public function returnsTheValueAfterThePrefix(): void
    {
        self::assertEquals(new Success('ab', 3), preceded(char('-'), word('ab'))('-ab', 0));
    }

    #[Test]
    #[TestWith(['+ab', 0, '"-"'])]
    #[TestWith(['-ac', 1, '"ab"'])]
    public function failsOnAnyMissingPart(string $input, int $offset, string $expected): void
    {
        self::assertEquals(new Failure([$expected], $offset), preceded(char('-'), word('ab'))($input, 0));
    }

    #[Test]
    public function mergesTheFailureOfTheParserWithTheHintOfThePrefix(): void
    {
        self::assertEquals(new Failure(['"a"', '"b"'], 1), preceded(many(char('a')), char('b'))('ac', 0));
    }

    #[Test]
    public function keepsTheHintOfTheParser(): void
    {
        self::assertEquals(new Success(['a'], 2, new Failure(['"a"'], 2)), preceded(char('-'), many(char('a')))('-a', 0));
    }
}
