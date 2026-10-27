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

namespace jubianchi\PPC\Tests;

use jubianchi\PPC\Failure;
use jubianchi\PPC\Success;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Small]
final class SuccessTest extends TestCase
{
    #[Test]
    public function hasNoHintByDefault(): void
    {
        self::assertNull(new Success('a', 1)->hint);
    }

    #[Test]
    #[TestWith([1])]
    #[TestWith([2])]
    public function keepsAHintAtOrPastItsOffset(int $offset): void
    {
        $hint = new Failure(['"b"'], $offset);

        self::assertSame($hint, new Success('a', 1, $hint)->hint);
    }

    #[Test]
    public function dropsAHintBeforeItsOffsetAsNoLaterFailureCanBeCloser(): void
    {
        self::assertNull(new Success('a', 1, new Failure(['"b"'], 0))->hint);
    }
}
