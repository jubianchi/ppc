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
use PHPUnit\Framework\TestCase;

use function jubianchi\PPC\eos;

/**
 * @internal
 */
#[Small]
final class EosTest extends TestCase
{
    #[Test]
    public function matchesAtTheEndOfInput(): void
    {
        self::assertEquals(new Success(null, 3), eos()('abc', 3));
    }

    #[Test]
    public function matchesAnEmptyInput(): void
    {
        self::assertEquals(new Success(null, 0), eos()('', 0));
    }

    #[Test]
    public function failsBeforeTheEndOfInput(): void
    {
        self::assertEquals(new Failure(['end of input'], 2), eos()('abc', 2));
    }
}
