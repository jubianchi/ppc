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

namespace jubianchi\PPC\Tests\Primitives;

use jubianchi\PPC\Failure;
use jubianchi\PPC\Incomplete;
use jubianchi\PPC\Success;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

use function jubianchi\PPC\noneOf;

/**
 * @internal
 */
#[Small]
final class NoneOfTest extends TestCase
{
    #[Test]
    #[TestWith(['a'])]
    #[TestWith(['é'])]
    #[TestWith(['🎉'])]
    public function consumesOneCharNotInTheString(string $char): void
    {
        self::assertEquals(new Success($char, 1 + \strlen($char)), noneOf('"\\')('_'.$char.'_', 1));
    }

    #[Test]
    #[TestWith(['"', 'any char but "\""'])]
    #[TestWith(['"', 'any char but "\"" or "\\\\"', '"\\'])]
    #[TestWith(['1', 'any char but "\"", "1" or "\\\\"', '"1\\"'])]
    public function failsOnACharOfTheString(string $char, string $expected, string $chars = '"'): void
    {
        self::assertEquals(new Failure([$expected], 0), noneOf($chars)($char, 0));
    }

    #[Test]
    public function failsAtTheEndOfACompleteInput(): void
    {
        self::assertEquals(new Failure(['any char but "\""'], 1), noneOf('"')('a', 1));
    }

    #[Test]
    public function isIncompleteAtTheEndOfAPartialInput(): void
    {
        self::assertEquals(new Incomplete(1), noneOf('"')('a', 1, true));
    }

    #[Test]
    public function rejectsAnEmptySetOfChars(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a non-empty set of chars');

        noneOf('');
    }
}
