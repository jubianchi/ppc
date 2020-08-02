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

    private const FIRST_LINE = 1;
    private const FIRST_COLUMN = 0;

    private string $contents;
    private int $length;

    private int $offset = 0;
    private string $current;

    /**
     * @var self[]
     */
    private array $transactions = [];

    private int $line = self::FIRST_LINE;
    private int $column = self::FIRST_COLUMN;

    public function __construct(string $contents)
    {
        $this->contents = $contents;
        $this->length = mb_strlen($this->contents);

        if ($this->offset >= $this->length) {
            $this->current = self::EOS;
        } else {
            $this->current = mb_substr($this->contents, $this->offset, 1);
        }

        if ("\n" === $this->current) {
            ++$this->line;
            $this->column = self::FIRST_COLUMN;
        }
    }

    public function current(): string
    {
        return $this->current;
    }

    public function next(): void
    {
        ++$this->offset;
        ++$this->column;

        if ($this->offset >= $this->length) {
            $this->current = self::EOS;
        } else {
            $this->current = mb_substr($this->contents, $this->offset, 1);
        }

        if ("\n" === $this->current) {
            ++$this->line;
            $this->column = self::FIRST_COLUMN;
        }
    }

    public function key(): int
    {
        return $this->offset;
    }

    public function valid(): bool
    {
        return $this->offset < $this->length;
    }

    public function rewind(): void
    {
        $this->offset = 0;

        $this->current = mb_substr($this->contents, $this->offset, 1);

        if ("\n" === $this->current) {
            ++$this->line;
            $this->column = self::FIRST_COLUMN;
        }
    }

    public function cut(int $offset, ?int $length = null): string
    {
        $length = $length ?? $this->length - $this->offset;

        if (0 > $offset || $offset + $length > $this->length) {
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

        $this->offset = $transaction->offset;
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
