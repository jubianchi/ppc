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

namespace jubianchi\PPC\Tests\Partial;

use jubianchi\PPC\Failure;
use jubianchi\PPC\Partial;
use jubianchi\PPC\Success;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function jubianchi\PPC\char;
use function jubianchi\PPC\consumed;
use function jubianchi\PPC\debug;
use function jubianchi\PPC\label;
use function jubianchi\PPC\map;
use function jubianchi\PPC\seq;
use function jubianchi\PPC\word;

/**
 * @internal
 */
#[Small]
final class ModifiersTest extends TestCase
{
    #[Test]
    public function mapAppliesOnceResumed(): void
    {
        $result = (word('ab') |> map(strtoupper(...)))('a', 0, true);

        self::assertInstanceOf(Partial::class, $result);
        self::assertEquals(new Success('AB', 2), $result->resume('b'));
    }

    #[Test]
    public function labelAppliesOnceResumed(): void
    {
        $result = (word('ab') |> label('ab-word'))('a', 0, true);

        self::assertInstanceOf(Partial::class, $result);
        self::assertEquals(new Failure(['ab-word'], 0), $result->resume('x'));
    }

    #[Test]
    public function consumedReadsTheResumedInput(): void
    {
        $result = (seq(char('a'), char('b')) |> consumed())('xa', 1, true);

        self::assertInstanceOf(Partial::class, $result);
        self::assertEquals(new Success('ab', 3), $result->resume('b'));
    }

    #[Test]
    public function debugTracesTheStartOnce(): void
    {
        $lines = [];
        $write = static function (string $line) use (&$lines): void {
            $lines[] = $line;
        };

        $result = (word('ab') |> debug('ab', $write))('a', 0, true);

        self::assertInstanceOf(Partial::class, $result);

        $result->resume('b');

        self::assertSame(['ab: start at line 1, column 1', 'ab: matched "ab" up to line 1, column 3'], $lines);
    }
}
