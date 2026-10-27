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
use function jubianchi\PPC\enclosed;
use function jubianchi\PPC\word;

/**
 * @internal
 */
#[Small]
final class EnclosedTest extends TestCase
{
    #[Test]
    public function returnsTheValueBetweenTheDelimiters(): void
    {
        self::assertEquals(new Success('ab', 4), enclosed(char('('), word('ab'), char(')'))('(ab)', 0));
    }

    #[Test]
    public function usesTheOpeningDelimiterToCloseByDefault(): void
    {
        self::assertEquals(new Success('ab', 4), enclosed(char('"'), word('ab'))('"ab"', 0));
    }

    #[Test]
    #[TestWith(['[ab)', 0, '"("'])]
    #[TestWith(['(ac)', 1, '"ab"'])]
    #[TestWith(['(ab]', 3, '")"'])]
    public function failsOnAnyMissingPart(string $input, int $offset, string $expected): void
    {
        self::assertEquals(new Failure([$expected], $offset), enclosed(char('('), word('ab'), char(')'))($input, 0));
    }
}
