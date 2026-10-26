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

namespace jubianchi\PPC\Benchmarks;

use Random\Engine\Mt19937;
use Random\Randomizer;

final class Documents
{
    public const array ASCII = ['alpha', 'beta', 'gamma', 'delta', 'epsilon'];
    public const array UTF8 = ['café', 'naïve', 'über', '日本語', 'emoji 🎉'];

    /**
     * Builds a deterministic JSON document restricted to what the sample JSON parser supports:
     * no negative numbers, no exponents and no decimals below 1.
     *
     * @param list<string> $words
     */
    public static function json(int $records, array $words): string
    {
        $random = new Randomizer(new Mt19937(42));
        $pick = static fn (): string => $words[$random->getInt(0, \count($words) - 1)];
        $data = [];

        for ($i = 0; $i < $records; ++$i) {
            $data[] = [
                'id' => $i,
                'name' => $pick().' '.$pick(),
                'score' => $random->getInt(100, 10000) / 100,
                'active' => 0 === $i % 2,
                'tags' => [$pick(), $pick(), $pick()],
                'meta' => 0 === $i % 3 ? null : ['note' => $pick(), 'rank' => $random->getInt(1, 100)],
            ];
        }

        return json_encode(
            $data,
            \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_PRESERVE_ZERO_FRACTION | \JSON_THROW_ON_ERROR,
        );
    }

    public static function composer(): string
    {
        $data = file_get_contents(__DIR__.'/../resources/composer.json');

        if (false === $data) {
            throw new \RuntimeException('Could not load file');
        }

        return $data;
    }
}
