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
use OutOfBoundsException;

class Char implements Stream
{
    protected int $offset = 0;
    protected string $current;
    protected int $line = self::FIRST_LINE;
    protected int $column = self::FIRST_COLUMN;

    private string $contents;
    private int $length;

    public function __construct(string $contents)
    {
        $this->contents = $contents;
        $this->length = strlen($this->contents);

        if ($this->offset >= $this->length) {
            $this->current = self::EOS;
        } else {
            $this->current = substr($this->contents, $this->offset, 1);
        }
    }

    public function seek(int $offset): void
    {
        if (0 > $offset) {
            throw new OutOfBoundsException();
        }

        if ($offset > $this->length) {
            throw new OutOfBoundsException();
        }

        $part = substr($this->contents, 0, $offset);
        $newlines = substr_count($part, "\n");

        $this->offset = $offset;
        $this->line = self::FIRST_LINE + $newlines;
        $this->column = $offset;

        if ($newlines > 0) {
            /* @phpstan-ignore-next-line */
            $this->column = $offset - strripos($part, "\n") - 1;
        }

        if ($this->offset === $this->length) {
            $this->current = self::EOS;
        } else {
            $this->current = substr($this->contents, $this->offset, 1);
        }
    }

    public function current(): string
    {
        return $this->current;
    }

    public function consume(): Slice
    {
        if ($this->offset === $this->length) {
            throw new OutOfBoundsException();
        }

        $slice = new Slice($this, $this->offset, 1);

        ++$this->offset;
        ++$this->column;

        if ("\n" === $this->current) {
            ++$this->line;
            $this->column = self::FIRST_COLUMN;
        }

        if ($this->offset === $this->length) {
            $this->current = self::EOS;
        } else {
            $this->current = substr($this->contents, $this->offset, 1);
        }

        return $slice;
    }

    public function tell(): int
    {
        return $this->offset;
    }

    public function eos(): bool
    {
        return $this->offset >= $this->length;
    }

    public function cut(int $offset, ?int $length = null): string
    {
        $length = $length ?? $this->length - $offset;

        if (0 > $offset) {
            throw new OutOfBoundsException();
        }

        if ($offset + $length > $this->length) {
            throw new OutOfBoundsException();
        }

        return substr($this->contents, $offset, $length);
    }

    public function begin(): Transaction
    {
        return new Transaction($this);
    }

    public function position(): array
    {
        return [
            'line' => $this->line,
            'column' => $this->column,
        ];
    }
}
