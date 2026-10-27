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

use function jubianchi\PPC\any;
use function jubianchi\PPC\char;
use function jubianchi\PPC\eos;
use function jubianchi\PPC\regex;
use function jubianchi\PPC\word;

/**
 * @internal
 */
#[Small]
final class PrimitivesTest extends TestCase
{
    #[Test]
    public function wordAsksForMoreInputWhenTheBufferEndsWithAPrefixOfIt(): void
    {
        $result = word('true')('xtr', 1, true);

        self::assertInstanceOf(Partial::class, $result);
        self::assertSame('xtr', $result->input);
        self::assertEquals(new Success('true', 5), $result->resume('ue'));
        self::assertEquals(new Failure(['"true"'], 1), $result->resume(''));
    }

    #[Test]
    public function wordFailsAsSoonAsTheBufferDiffers(): void
    {
        self::assertEquals(new Failure(['"true"'], 0), word('true')('tx', 0, true));
        self::assertEquals(new Failure(['"true"'], 0), word('true')('tr', 0));
    }

    #[Test]
    public function charAsksForMoreInputWhenAMultibyteCharIsCut(): void
    {
        $result = char('é')("\xC3", 0, true);

        self::assertInstanceOf(Partial::class, $result);
        self::assertEquals(new Success('é', 2), $result->resume("\xA9"));
    }

    #[Test]
    public function anyAsksForMoreInputAtTheEndOfTheBufferOrInsideAChar(): void
    {
        $empty = any()('', 0, true);
        $cut = any()("\xE2\x82", 0, true);

        self::assertInstanceOf(Partial::class, $empty);
        self::assertEquals(new Success('a', 1), $empty->resume('a'));
        self::assertEquals(new Failure(['any char'], 0), $empty->resume(''));
        self::assertInstanceOf(Partial::class, $cut);
        self::assertEquals(new Success('€', 3), $cut->resume("\xAC"));
        self::assertEquals(new Success("\xE2", 1), $cut->resume(''));
    }

    #[Test]
    public function eosWaitsForTheEndOfTheStream(): void
    {
        $result = eos()('a', 1, true);

        self::assertInstanceOf(Partial::class, $result);
        self::assertEquals(new Success(null, 1), $result->resume(''));
        self::assertEquals(new Failure(['end of input'], 1), $result->resume('b'));
        self::assertEquals(new Failure(['end of input'], 0), eos()('a', 0, true));
    }

    #[Test]
    public function regexWaitsForItsLookaheadAfterTheMatch(): void
    {
        $parser = regex('/[0-9]+/', 2);
        $result = $parser('12', 0, true);

        self::assertInstanceOf(Partial::class, $result);

        $result = $result->resume('3');

        self::assertInstanceOf(Partial::class, $result);
        self::assertEquals(new Success('123', 3), $result->resume('ab'));
        self::assertEquals(new Success('123', 3), $result->resume(''));
        self::assertEquals(new Success('12', 2), $parser('12ab', 0, true));
    }

    #[Test]
    public function regexWaitsForItsLookaheadBeforeFailing(): void
    {
        $parser = regex('/[0-9]{4}/', 4);
        $result = $parser('12', 0, true);

        self::assertInstanceOf(Partial::class, $result);
        self::assertInstanceOf(Partial::class, $result->resume('34ab'));
        self::assertEquals(new Success('1234', 4), $result->resume('34abcd'));
        self::assertEquals(new Failure(['/[0-9]{4}/'], 0), $result->resume('ab'));
        self::assertEquals(new Failure(['/[0-9]{4}/'], 0), $result->resume(''));
        self::assertEquals(new Failure(['/[0-9]{4}/'], 0), $parser('12ab', 0, true));
    }

    #[Test]
    public function regexRequiresAPositiveLookahead(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('Expected a positive lookahead, got 0'));

        regex('/a/', 0);
    }
}
