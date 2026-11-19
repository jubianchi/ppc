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

use function jubianchi\PPC\letter;

/**
 * @internal
 */
#[Small]
final class LetterTest extends TestCase
{
    #[Test]
    #[TestWith(['a'])]
    #[TestWith(['Z'])]
    #[TestWith(['é'])]
    #[TestWith(['日'])]
    #[TestWith(['Ж'])]
    public function consumesOneUnicodeLetter(string $letter): void
    {
        self::assertEquals(new Success($letter, 1 + \strlen($letter)), letter()('_'.$letter.'_', 1));
    }

    #[Test]
    #[TestWith(['a'])]
    #[TestWith(['Z'])]
    public function consumesOneAsciiLetter(string $letter): void
    {
        self::assertEquals(new Success($letter, 2), letter(ascii: true)('_'.$letter.'_', 1));
    }

    #[Test]
    #[TestWith(['1'])]
    #[TestWith(['_'])]
    #[TestWith(['🎉'])]
    public function failsOnAnythingButALetter(string $char): void
    {
        self::assertEquals(new Failure(['letter'], 0), letter()($char, 0));
    }

    #[Test]
    #[TestWith(['é'])]
    #[TestWith(['日'])]
    #[TestWith(['1'])]
    public function failsOnAnythingButAnAsciiLetterInAsciiMode(string $char): void
    {
        self::assertEquals(new Failure(['ASCII letter'], 0), letter(ascii: true)($char, 0));
    }

    #[Test]
    public function isIncompleteWhenAPartialInputEndsInsideAMultiByteChar(): void
    {
        self::assertEquals(new Incomplete(1), letter()("\xC3", 0, true));
    }
}
