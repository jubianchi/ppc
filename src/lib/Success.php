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
 * @template-covariant T
 */
final readonly class Success
{
    /**
     * The furthest failure met while succeeding, e.g. the one that stopped many(): when the next parser fails,
     * this hint may be closer to the actual error. A hint before the offset is dropped, as the next parser
     * cannot fail before it.
     */
    public ?Failure $hint;

    /**
     * @param T $value
     */
    public function __construct(
        public mixed $value,
        public int $offset,
        ?Failure $hint = null,
    ) {
        $this->hint = null !== $hint && $hint->offset >= $offset ? $hint : null;
    }
}
