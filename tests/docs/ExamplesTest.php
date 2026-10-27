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

namespace jubianchi\PPC\Tests\Docs;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Runs every ```php block of the documentation with assertions enabled. When a block is followed by an "Output" tab,
 * its output must match the documented one. Partial snippets are written as ```phps blocks and are not run.
 *
 * @internal
 */
#[Medium]
final class ExamplesTest extends TestCase
{
    private const string ROOT = __DIR__.'/../..';

    /**
     * @return \Generator<string, array{string, ?string}>
     */
    public static function examples(): \Generator
    {
        foreach (['README.md', 'docs/*.md', 'docs/tutorials/*.md'] as $pattern) {
            /** @var \SplFileInfo $file */
            foreach (new \GlobIterator(self::ROOT.'/'.$pattern) as $file) {
                $content = (string) file_get_contents($file->getPathname());
                preg_match_all('/^```php\n(.*?)^```\n(?:\s*### \*\* Output \*\*\s*```\n(.*?)^```$)?/ms', $content, $matches, \PREG_SET_ORDER | \PREG_OFFSET_CAPTURE | \PREG_UNMATCHED_AS_NULL);

                foreach ($matches as $match) {
                    $line = substr_count($content, "\n", 0, $match[0][1]) + 1;

                    yield ltrim(\dirname($pattern).'/', './').$file->getFilename().':'.$line => [(string) $match[1][0], $match[2][0] ?? null];
                }
            }
        }
    }

    #[Test]
    #[DataProvider('examples')]
    public function runs(string $code, ?string $output): void
    {
        $file = tempnam(sys_get_temp_dir(), 'ppc-example-');
        self::assertIsString($file);
        file_put_contents($file, $code);

        try {
            $process = proc_open(
                [\PHP_BINARY, '-d', 'zend.assertions=1', '-d', 'assert.exception=1', '-d', 'auto_prepend_file='.self::ROOT.'/vendor/autoload.php', $file],
                [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes,
            );
            self::assertIsResource($process);

            $stdout = (string) stream_get_contents($pipes[1]);
            $stderr = (string) stream_get_contents($pipes[2]);

            self::assertSame(0, proc_close($process), $stderr.$stdout);

            if (null !== $output) {
                self::assertSame($output, $stdout);
            }
        } finally {
            unlink($file);
        }
    }
}
