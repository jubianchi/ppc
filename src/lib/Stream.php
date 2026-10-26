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

use jubianchi\PPC\Stream\Transaction;

interface Stream
{
    public const EOS = __CLASS__.'::EOS';
    public const FIRST_LINE = 1;
    public const FIRST_COLUMN = 0;

    public function begin(): Transaction;

    public function consume(): Slice;

    public function current(): string;

    public function cut(int $offset, ?int $length = null): string;

    public function eos(): bool;

    public function seek(int $offset): void;

    public function tell(): int;

    /**
     * @return array<string, int>
     */
    public function position(): array;
}
