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

namespace jubianchi\PPC\Tests\Modifiers;

use jubianchi\PPC\Failure;
use jubianchi\PPC\Success;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function jubianchi\PPC\alt;
use function jubianchi\PPC\char;
use function jubianchi\PPC\label;
use function jubianchi\PPC\seq;

/**
 * @internal
 */
#[Small]
final class LabelTest extends TestCase
{
    #[Test]
    public function keepsTheSuccess(): void
    {
        $parser = char('0') |> label('digit');

        self::assertEquals(new Success('0', 1), $parser('0', 0));
    }

    #[Test]
    public function replacesTheExpectationsOfAFailureWithoutProgress(): void
    {
        $parser = alt(char('0'), char('1')) |> label('digit');

        self::assertEquals(new Failure(['digit'], 0), $parser('x', 0));
    }

    #[Test]
    public function keepsAFailureThatWentFurther(): void
    {
        $parser = seq(char('0'), char('1')) |> label('binary');

        self::assertEquals(new Failure(['"1"'], 1), $parser('0x', 0));
    }
}
