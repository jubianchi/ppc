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

namespace jubianchi\PPC\Tests\Combinator;

use jubianchi\PPC\Failure;
use jubianchi\PPC\Incomplete;
use jubianchi\PPC\Success;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function jubianchi\PPC\bind;
use function jubianchi\PPC\char;
use function jubianchi\PPC\enclosed;
use function jubianchi\PPC\many;
use function jubianchi\PPC\regex;
use function jubianchi\PPC\terminated;
use function jubianchi\PPC\word;

/**
 * @internal
 */
#[Small]
final class BindTest extends TestCase
{
    #[Test]
    public function runsTheParserBuiltFromTheValueWhereTheParserStopped(): void
    {
        self::assertEquals(new Success('bold', 12), self::element()('_<b>bold</b>', 1));
    }

    #[Test]
    public function failsWhereTheBuiltParserFails(): void
    {
        self::assertEquals(new Failure(['"</b>"'], 7, 4), self::element()('<b>bold</i>', 0));
    }

    #[Test]
    public function keepsTheFailureOfTheParserWithoutBuildingTheNextOne(): void
    {
        $built = false;
        $parser = bind(char('a'), static function () use (&$built): \Closure {
            $built = true;

            return char('b');
        });

        self::assertEquals(new Failure(['"a"'], 0), $parser('b', 0));
        self::assertFalse($built);
    }

    #[Test]
    public function mergesTheFailureOfTheBuiltParserWithTheHintOfTheParser(): void
    {
        $parser = bind(many(char('a')), static fn (): \Closure => char('b'));

        self::assertEquals(new Failure(['"a"', '"b"'], 2), $parser('aac', 0));
    }

    #[Test]
    public function keepsTheHintOfTheBuiltParser(): void
    {
        $parser = bind(char('-'), static fn (): \Closure => many(char('a')));

        self::assertEquals(new Success(['a'], 2, new Failure(['"a"'], 2)), $parser('-a', 0));
    }

    /**
     * @return \Closure(string, int, bool=): (Success<string>|Failure|Incomplete)
     */
    private static function element(): \Closure
    {
        return bind(
            enclosed(char('<'), regex('/\w+/'), char('>')),
            static fn (string $tag): \Closure => terminated(regex('/[^<]*/'), word("</{$tag}>")),
        );
    }
}
