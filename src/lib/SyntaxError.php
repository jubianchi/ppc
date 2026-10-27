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

final class SyntaxError extends \RuntimeException
{
    public function __construct(
        public readonly Failure $failure,
        string $message,
    ) {
        parent::__construct($message);
    }
}
