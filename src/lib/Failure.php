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

namespace jubianchi\PPC;

/**
 * @implements Result<never>
 */
final readonly class Failure implements Result
{
    /**
     * @param list<string> $expected
     */
    public function __construct(
        public array $expected,
        public int $offset,
    ) {
    }
}
