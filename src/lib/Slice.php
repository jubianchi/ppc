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

class Slice
{
    private Stream $stream;
    private int $offset;
    private int $length;

    public function __construct(Stream $stream, int $offset, int $length)
    {
        $this->stream = $stream;
        $this->offset = $offset;
        $this->length = $length;
    }

    public function __toString(): string
    {
        return $this->stream->cut($this->offset, $this->length);
    }
}
