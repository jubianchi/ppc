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
 * A position in the input, in chars, starting at line 1, column 1.
 */
final readonly class Position
{
    /**
     * @param positive-int $line
     * @param positive-int $column
     */
    public function __construct(
        public int $line,
        public int $column,
    ) {
    }
}
