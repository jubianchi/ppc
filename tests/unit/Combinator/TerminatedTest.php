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
use function jubianchi\PPC\terminated;
use function jubianchi\PPC\word;

/**
 * @internal
 */
#[Small]
final class TerminatedTest extends TestCase
{
    #[Test]
    public function returnsTheValueBeforeTheSuffix(): void
    {
        self::assertEquals(new Success('ab', 3), terminated(word('ab'), char(';'))('ab;', 0));
    }

    #[Test]
    #[TestWith(['ac;', 0, '"ab"'])]
    #[TestWith(['ab,', 2, '";"'])]
    public function failsOnAnyMissingPart(string $input, int $offset, string $expected): void
    {
        self::assertEquals(new Failure([$expected], $offset), terminated(word('ab'), char(';'))($input, 0));
    }

    #[Test]
    public function mergesTheFailureOfTheSuffixWithTheHintOfTheParser(): void
    {
        self::assertEquals(new Failure(['"a"', '";"'], 1), terminated(many(char('a')), char(';'))('ab', 0));
    }

    #[Test]
    public function keepsTheHintOfTheSuffix(): void
    {
        self::assertEquals(new Success('-', 2, new Failure(['"a"'], 2)), terminated(char('-'), many(char('a')))('-a', 0));
    }
}
