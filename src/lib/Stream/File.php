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

class File implements Stream
{
    protected int $line = self::FIRST_LINE;
    protected int $column = self::FIRST_COLUMN;

    private int $length;

    /**
     * @var false|resource
     */
    private $handle;
    private string $path;

    public function __construct(string $path)
    {
        $this->handle = fopen($path, 'r');
        $this->length = filesize($path);
        $this->path = $path;
    }

    public function __clone()
    {
        $offset = $this->tell();

        $this->handle = fopen($this->path, 'r');
        fseek($this->handle, $offset);
    }

    public function __destruct()
    {
        fclose($this->handle);
    }

    public function seek(int $offset): void
    {
        if (0 > $offset) {
            throw new OutOfBoundsException();
        }

        if ($offset > $this->length) {
            throw new OutOfBoundsException();
        }

        $part = fread($this->handle, $offset - $this->tell());
        $newlines = substr_count($part, "\n");

        $this->line += $newlines;
        $this->column = $offset;

        if ($newlines > 0) {
            /** @phpstan-ignore-next-line */
            $this->column = $offset - strripos($part, "\n") - 1;
        }
    }

    public function current(): string
    {
        if (feof($this->handle)) {
            return self::EOS;
        }

        $offset = $this->tell();
        $result = fread($this->handle, 1);

        fseek($this->handle, $offset);

        return $result;
    }

    public function consume(): Slice
    {
        if ($this->eos()) {
            throw new OutOfBoundsException();
        }

        $slice = new Slice($this, $this->tell(), 1);

        ++$this->column;

        $result = fread($this->handle, 1);

        if ("\n" === $result) {
            ++$this->line;
            $this->column = self::FIRST_COLUMN;
        }

        return $slice;
    }

    public function tell(): int
    {
        return ftell($this->handle);
    }

    public function eos(): bool
    {
        return $this->tell() === $this->length;
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

        $previous = $this->tell();
        fseek($this->handle, $offset);
        $result = fread($this->handle, $length);

        fseek($this->handle, $previous);

        return $result;
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
