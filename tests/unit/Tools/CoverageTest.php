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

namespace jubianchi\PPC\Tests\Tools;

use jubianchi\PPC\Tools\Coverage;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Small]
final class CoverageTest extends TestCase
{
    #[Test]
    public function listsTheUncoveredLinesOfEachFile(): void
    {
        $clover = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <coverage>
              <project>
                <file name="/ppc/src/a.php">
                  <line num="3" type="stmt" count="1"/>
                  <line num="4" type="stmt" count="0"/>
                  <line num="7" type="stmt" count="0"/>
                </file>
                <file name="/ppc/src/b.php">
                  <line num="2" type="stmt" count="2"/>
                </file>
                <package name="parsers">
                  <file name="/ppc/parsers/c.php">
                    <line num="5" type="method" count="0"/>
                  </file>
                </package>
              </project>
            </coverage>
            XML;

        self::assertSame(
            ['/ppc/src/a.php' => [4, 7], '/ppc/parsers/c.php' => [5]],
            Coverage::uncovered($clover),
        );
    }

    #[Test]
    public function listsNothingWhenEveryLineIsCovered(): void
    {
        $clover = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <coverage>
              <project>
                <file name="/ppc/src/a.php">
                  <line num="3" type="stmt" count="1"/>
                </file>
              </project>
            </coverage>
            XML;

        self::assertSame([], Coverage::uncovered($clover));
    }

    #[Test]
    public function rejectsAnInvalidReport(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Clover report');

        Coverage::uncovered('<coverage>');
    }
}
