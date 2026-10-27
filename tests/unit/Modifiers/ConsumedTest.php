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

use function jubianchi\PPC\char;
use function jubianchi\PPC\consumed;
use function jubianchi\PPC\many;
use function jubianchi\PPC\seq;

/**
 * @internal
 */
#[Small]
final class ConsumedTest extends TestCase
{
    #[Test]
    public function returnsTheTextConsumedByTheParser(): void
    {
        $parser = seq(char('a'), many(char('é'))) |> consumed();

        self::assertEquals(new Success('aéé', 6, new Failure(['"é"'], 6)), $parser('_aééb', 1));
    }

    #[Test]
    public function keepsTheFailure(): void
    {
        $parser = char('a') |> consumed();

        self::assertEquals(new Failure(['"a"'], 0), $parser('b', 0));
    }
}
