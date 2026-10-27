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
use jubianchi\PPC\Success;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

use function jubianchi\PPC\any;

/**
 * @internal
 */
#[Small]
final class AnyTest extends TestCase
{
    #[Test]
    #[TestWith(['a', 2])]
    #[TestWith(['é', 3])]
    #[TestWith(['日', 4])]
    #[TestWith(['🎉', 5])]
    public function consumesOneCharWhateverItsByteLength(string $char, int $offset): void
    {
        self::assertEquals(new Success($char, $offset), any()('_'.$char.'_', 1));
    }

    #[Test]
    public function consumesASingleByteOfATruncatedMultiByteChar(): void
    {
        self::assertEquals(new Success("\xE6", 1), any()("\xE6\x97", 0));
    }

    #[Test]
    public function failsAtTheEndOfInput(): void
    {
        self::assertEquals(new Failure(['any char'], 3), any()('abc', 3));
    }
}
