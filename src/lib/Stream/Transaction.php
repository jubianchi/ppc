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

namespace jubianchi\PPC\Stream;

use jubianchi\PPC\Slice;
use jubianchi\PPC\Stream;

class Transaction implements Stream
{
    private Stream $stream;
    private Stream $transaction;

    public function __construct(Stream $stream)
    {
        $this->stream = $stream;
        $this->transaction = clone $stream;
    }

    public function seek(int $offset): void
    {
        $this->transaction->seek($offset);
    }

    public function begin(): Transaction
    {
        return new Transaction($this->transaction);
    }

    public function commit(): void
    {
        $this->stream->seek($this->offset());
    }

    public function current(): string
    {
        return $this->transaction->current();
    }

    public function consume(): Slice
    {
        return $this->transaction->consume();
    }

    public function offset(): int
    {
        return $this->transaction->offset();
    }

    public function eos(): bool
    {
        return $this->transaction->eos();
    }

    public function cut(int $offset, ?int $length = null): string
    {
        return $this->transaction->cut($offset, $length);
    }

    public function position(): array
    {
        return $this->transaction->position();
    }
}
