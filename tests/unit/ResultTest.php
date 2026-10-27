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
use jubianchi\PPC\Incomplete;
use jubianchi\PPC\Result;
use jubianchi\PPC\Success;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Small]
final class ResultTest extends TestCase
{
    #[Test]
    public function isImplementedByEveryParserResult(): void
    {
        self::assertInstanceOf(Result::class, new Success('a', 1));
        self::assertInstanceOf(Result::class, new Failure(['"a"'], 0));
        self::assertInstanceOf(Result::class, new Incomplete());
    }
}
