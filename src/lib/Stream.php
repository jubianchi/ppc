<?php
/**
 * This file is part of PPC.
 *
 * Copyright © 2020 Julien Bianchi <contact@jubianchi.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace jubianchi\PPC;

use jubianchi\PPC\Stream\Transaction;

interface Stream
{
    const EOS = __CLASS__.'::EOS';
    const FIRST_LINE = 1;
    const FIRST_COLUMN = 0;

    public function current(): string;

    public function consume(): Slice;

    public function seek(int $offset): void;

    public function offset(): int;

    public function eos(): bool;

    public function cut(int $offset, ?int $length = null): string;

    public function begin(): Transaction;

    /**
     * @return array<string, int>
     */
    public function position(): array;
}
