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

use function jubianchi\PPC\alt;
use function jubianchi\PPC\char;
use function jubianchi\PPC\enclosed;
use function jubianchi\PPC\many;
use function jubianchi\PPC\many1;
use function jubianchi\PPC\not;
use function jubianchi\PPC\opt;
use function jubianchi\PPC\recursive;
use function jubianchi\PPC\repeat;
use function jubianchi\PPC\separated;
use function jubianchi\PPC\seq;
use function jubianchi\PPC\word;

/**
 * @internal
 */
#[Small]
final class CombinatorsTest extends TestCase
{
    #[Test]
    public function seqResumesWhereItStopped(): void
    {
        $parser = seq(word('abc'), char('d'), char('e'));

        $result = self::partial($parser('ab', 0, true));
        $result = self::partial($result->resume('cd'));

        self::assertEquals(new Success(['abc', 'd', 'e'], 5), $result->resume('e'));
        self::assertEquals(new Failure(['"e"'], 4), $result->resume('x'));
    }

    #[Test]
    public function altTriesTheNextBranchesOnceTheCurrentOneIsDecided(): void
    {
        $parser = alt(char('x'), word('abc'), word('abd'));
        $result = self::partial($parser('ab', 0, true));

        self::assertEquals(new Success('abc', 3), $result->resume('c'));
        self::assertEquals(new Success('abd', 3), $result->resume('d'));
        self::assertEquals(new Failure(['"x"', '"abc"', '"abd"'], 0), $result->resume('e'));
        self::assertEquals(new Failure(['"x"', '"abc"', '"abd"'], 0), $result->resume(''));
    }

    #[Test]
    public function optFallsBackToItsDefaultOnceTheParserFails(): void
    {
        $result = self::partial(opt(word('ab'), 'none')('a', 0, true));

        self::assertEquals(new Success('ab', 2), $result->resume('b'));
        self::assertEquals(new Success('none', 0, new Failure(['"ab"'], 0)), $result->resume('x'));
    }

    #[Test]
    public function manyKeepsMatchingAfterResuming(): void
    {
        $result = self::partial(many(char('a'))('aa', 0, true));

        self::assertEquals(new Success(['a', 'a'], 2, new Failure(['"a"'], 2)), $result->resume(''));

        $result = self::partial($result->resume('a'));

        self::assertEquals(new Success(['a', 'a', 'a'], 3, new Failure(['"a"'], 3)), $result->resume('b'));
    }

    #[Test]
    public function many1AndRepeatResume(): void
    {
        self::assertEquals(new Success(['a', 'a'], 2, new Failure(['"a"'], 2)), self::partial(many1(char('a'))('', 0, true))->resume('aab'));
        self::assertEquals(new Failure(['"a"'], 0), self::partial(many1(char('a'))('', 0, true))->resume('b'));
        self::assertEquals(new Success(['a', 'a'], 2), self::partial(repeat(2, char('a'))('a', 0, true))->resume('a'));
        self::assertEquals(new Success([], 0), repeat(0, char('a'))('', 0, true));
    }

    #[Test]
    public function separatedResumesOnSeparatorsAndItems(): void
    {
        $parser = separated(char(','), char('a'));
        $result = self::partial($parser('a,', 0, true));

        self::assertEquals(new Success(['a'], 1, new Failure(['"a"'], 2)), $result->resume(''));

        $result = self::partial($result->resume('a'));

        self::assertEquals(new Success(['a', 'a'], 3, new Failure(['","'], 3)), $result->resume(''));
        self::assertEquals(new Success([], 0, new Failure(['"a"'], 0)), self::partial($parser('', 0, true))->resume('b'));
    }

    #[Test]
    public function enclosedResumes(): void
    {
        $parser = enclosed(char('('), word('ab'), char(')'));

        self::assertEquals(new Success('ab', 4), self::partial($parser('(a', 0, true))->resume('b)'));
        self::assertEquals(new Failure(['")"'], 3), self::partial($parser('(a', 0, true))->resume('b]'));
    }

    #[Test]
    public function notDecidesOnceTheParserIsDecided(): void
    {
        $result = self::partial(not(word('ab'))('a', 0, true));

        self::assertEquals(new Success('a', 1), $result->resume('c'));
        self::assertEquals(new Failure(['anything but "ab"'], 0), $result->resume('b'));
    }

    #[Test]
    public function recursivePassesThePartialMode(): void
    {
        $parser = recursive(static fn (\Closure $nested): \Closure => alt(char('x'), enclosed(char('('), $nested, char(')'))));

        self::assertEquals(new Success('x', 5), self::partial($parser('((x', 0, true))->resume('))'));
    }

    /**
     * @template T
     *
     * @param Success<T>|Failure|Partial<T> $result
     *
     * @return Partial<T>
     */
    private static function partial(Success|Failure|Partial $result): Partial
    {
        self::assertInstanceOf(Partial::class, $result);

        return $result;
    }
}
