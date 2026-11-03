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

use function jubianchi\PPC\negate;
use function jubianchi\PPC\regex;
use function jubianchi\PPC\word;

/**
 * @internal
 */
#[Small]
final class NegateTest extends TestCase
{
    #[Test]
    public function succeedsWithNullWithoutConsumingInputWhenTheParserFails(): void
    {
        $parser = regex('/\w/') |> negate();

        self::assertEquals(new Success(null, 2), $parser('if x', 2));
    }

    #[Test]
    public function failsWithTheMatchedTextWhenTheParserSucceeds(): void
    {
        $parser = word('fy') |> negate();

        self::assertEquals(new Failure(['anything but "fy"'], 2), $parser('iffy', 2));
    }
}
