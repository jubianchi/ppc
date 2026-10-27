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

namespace jubianchi\PPC\Tests\Examples;

use jubianchi\PPC\Benchmarks\Documents;
use jubianchi\PPC\Failure;
use jubianchi\PPC\Partial;
use jubianchi\PPC\Success;
use jubianchi\PPC\SyntaxError;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

use function jubianchi\PPC\parse;
use function jubianchi\PPC\Parsers\json;

/**
 * @internal
 */
#[Small]
final class JsonTest extends TestCase
{
    #[Test]
    #[TestWith(['null'])]
    #[TestWith(['true'])]
    #[TestWith(['false'])]
    #[TestWith(['0'])]
    #[TestWith(['-12'])]
    #[TestWith(['0.57'])]
    #[TestWith(['1.0'])]
    #[TestWith(['-1.5e10'])]
    #[TestWith(['2E-3'])]
    #[TestWith(['12.5e+3'])]
    #[TestWith(['99999999999999999999'])]
    #[TestWith(['""'])]
    #[TestWith(['"café 日本語 🎉"'])]
    #[TestWith(['"\" \\\\ \/ \b \f \n \r \t"'])]
    #[TestWith(['"é日🎉"'])]
    #[TestWith(['[]'])]
    #[TestWith(['{}'])]
    #[TestWith(['[1, "a", [true, null], {"b": 2.5}]'])]
    #[TestWith(['{"a": {"b": {"c": []}}, "": 1, "a b": "c"}'])]
    #[TestWith([" \t\n\r{ \"a\" :\n[ 1 ,2 ] } \n"])]
    public function decodesLikeJsonDecode(string $json): void
    {
        self::assertSame(
            var_export(json_decode($json, flags: \JSON_THROW_ON_ERROR), true),
            var_export(parse(json(), $json), true),
        );
    }

    #[Test]
    #[TestWith(['{"a": tru}', 'Expected value, got "t" at line 1, column 7'])]
    #[TestWith(['[1, "x]', 'Expected character, escape sequence or "\"", got end of input at line 1, column 8'])]
    #[TestWith(["{\n  \"a\": [1, 2 3]\n}", 'Expected "," or "]", got "3" at line 2, column 14'])]
    #[TestWith(['[1] 2', 'Expected end of input, got "2" at line 1, column 5'])]
    #[TestWith(['01', 'Expected end of input, got "1" at line 1, column 2'])]
    #[TestWith(["\"\t\"", 'Expected character, escape sequence or "\"", got "\t" at line 1, column 2'])]
    #[TestWith(['{"a" 1}', 'Expected ":", got "1" at line 1, column 6'])]
    public function reportsWhereTheDocumentIsInvalid(string $json, string $message): void
    {
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage($message);

        parse(json(), $json);
    }

    /**
     * @param positive-int $size
     */
    #[Test]
    #[TestWith([1])]
    #[TestWith([7])]
    #[TestWith([100])]
    public function decodesChunkByChunkLikeACompleteInput(int $size): void
    {
        $document = Documents::json(5, Documents::UTF8);

        self::assertEquals(json()($document, 0), self::chunked($document, $size));
    }

    #[Test]
    #[TestWith(['{"a": tru}'])]
    #[TestWith(['[1, "x]'])]
    #[TestWith(["{\n  \"a\": [1, 2 3]\n}"])]
    #[TestWith(['[1] 2'])]
    #[TestWith(['01'])]
    public function failsChunkByChunkLikeACompleteInput(string $json): void
    {
        self::assertEquals(json()($json, 0), self::chunked($json, 1));
    }

    /**
     * @param positive-int $size
     *
     * @return Success<mixed>|Failure
     */
    private static function chunked(string $document, int $size): Success|Failure
    {
        $chunks = str_split($document, $size);
        $result = json()((string) array_shift($chunks), 0, true);

        foreach ([...$chunks, ''] as $chunk) {
            if (!$result instanceof Partial) {
                break;
            }

            $result = $result->resume($chunk);
        }

        self::assertNotInstanceOf(Partial::class, $result);

        return $result;
    }
}
