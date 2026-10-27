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
 * The input ended before the parser could decide: run it again from the start once more input is available.
 */
final readonly class Incomplete
{
    /**
     * @param ?positive-int $needed the number of missing bytes, when known
     */
    public function __construct(public ?int $needed = null)
    {
    }
}
