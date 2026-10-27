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
use function jubianchi\PPC\many;
use function jubianchi\PPC\map;

/**
 * @internal
 */
#[Small]
final class MapTest extends TestCase
{
    #[Test]
    public function transformsTheValue(): void
    {
        $parser = char('a') |> map(strtoupper(...));

        self::assertEquals(new Success('A', 1), $parser('a', 0));
    }

    #[Test]
    public function keepsTheHint(): void
    {
        $parser = many(char('a')) |> map(implode(...));

        self::assertEquals(new Success('aa', 2, new Failure(['"a"'], 2)), $parser('aab', 0));
    }

    #[Test]
    public function keepsTheFailure(): void
    {
        $parser = char('a') |> map(strtoupper(...));

        self::assertEquals(new Failure(['"a"'], 0), $parser('b', 0));
    }
}
