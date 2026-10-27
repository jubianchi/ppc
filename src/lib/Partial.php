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
 * A parser ran out of input before it could decide: resuming it with more input continues where it stopped.
 *
 * @template-covariant T
 */
final readonly class Partial
{
    /**
     * @param string                                                  $input        the input the parser ran out of
     * @param \Closure(string, bool): (Success<T>|Failure|Partial<T>) $continuation receives the extended input and whether more may come
     */
    public function __construct(public string $input, public \Closure $continuation)
    {
    }

    /**
     * Resumes the parser with the next chunk of input; an empty chunk means the end of the input.
     *
     * @return Success<T>|Failure|Partial<T>
     */
    public function resume(string $chunk): Success|Failure|self
    {
        return ($this->continuation)($this->input.$chunk, '' !== $chunk);
    }
}
