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

use function jubianchi\PPC\oneOf;

/**
 * @internal
 */
#[Small]
final class OneOfTest extends TestCase
{
    #[Test]
    #[TestWith(['+'])]
    #[TestWith(['-'])]
    #[TestWith(['é'])]
    #[TestWith(['1'])]
    public function consumesOneOfTheChars(string $char): void
    {
        self::assertEquals(new Success($char, 1 + \strlen($char)), oneOf('+-é1')('_'.$char.'_', 1));
    }

    #[Test]
    #[TestWith(['*'])]
    #[TestWith(['e'])]
    #[TestWith(["\xC3"])]
    public function failsOnAnyOtherChar(string $char): void
    {
        self::assertEquals(new Failure(['"+"', '"-"', '"é"', '"1"'], 0), oneOf('+-é1+')($char, 0));
    }

    #[Test]
    public function isIncompleteWhenAPartialInputEndsInsideAMultiByteChar(): void
    {
        self::assertEquals(new Incomplete(1), oneOf('+-é')("\xC3", 0, true));
    }

    #[Test]
    public function rejectsAnEmptySetOfChars(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a non-empty set of chars');

        oneOf('');
    }
}
