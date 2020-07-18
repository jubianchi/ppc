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

use Exception;
use Iterator;
use OutOfBoundsException;

/**
 * @implements Iterator<int, string>
 */
class Stream implements Iterator
{
    const EOS = __CLASS__.'::EOS';

    private string $contents;
    private int $length;

    private int $position = 0;
    private string $current;

    /**
     * @var self[]
     */
    private array $transactions = [];

    private int $line = 1;
    private int $column = 0;

    public function __construct(string $contents)
    {
        $this->contents = $contents;
        $this->length = mb_strlen($this->contents);

        if ($this->position >= $this->length) {
            $this->current = self::EOS;
        } else {
            $this->current = mb_substr($this->contents, $this->position, 1);
        }

        if ("\n" === $this->current) {
            ++$this->line;
            $this->column = 0;
        }
    }

    public function current(): string
    {
        return $this->current;
    }

    public function next(): void
    {
        ++$this->position;
        ++$this->column;

        if ($this->position >= $this->length) {
            $this->current = self::EOS;
        } else {
            $this->current = mb_substr($this->contents, $this->position, 1);
        }

        if ("\n" === $this->current) {
            ++$this->line;
            $this->column = 0;
        }
    }

    public function key(): int
    {
        return $this->position;
    }

    public function valid(): bool
    {
        return $this->position < $this->length;
    }

    public function rewind(): void
    {
        $this->position = 0;

        $this->current = mb_substr($this->contents, $this->position, 1);

        if ("\n" === $this->current) {
            ++$this->line;
            $this->column = 0;
        }
    }

    public function cut(int $offset, ?int $length = null): string
    {
        $length = $length ?? $this->length - $this->position;

        if (0 > $offset || $offset >= $this->length || $offset + $length > $this->length) {
            throw new OutOfBoundsException();
        }

        return mb_substr($this->contents, $offset, $length);
    }

    public function begin(): self
    {
        $transaction = clone $this;
        $this->transactions[] = $transaction;

        return $transaction;
    }

    public function commit(): void
    {
        $transaction = array_pop($this->transactions);

        if (null === $transaction) {
            throw new Exception('There is no active transaction');
        }

        $this->position = $transaction->position;
        $this->line = $transaction->line;
        $this->column = $transaction->column;
        $this->current = $transaction->current;
    }

    public function rollback(): void
    {
        array_pop($this->transactions);
    }

    /**
     * @return array<string, int>
     */
    public function position(): array
    {
        return [
            'line' => $this->line,
            'column' => $this->column,
        ];
    }
}
