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

use jubianchi\PPC\Parser\Result;

class Mapper
{
    /**
     * @var callable(Result): Result
     */
    private $mapper;

    /**
     * @var ?callable(Result): Result
     */
    private $next = null;

    public function __construct(callable $mapper)
    {
        $this->mapper = $mapper;
    }

    public function __invoke(Result $result): Result
    {
        if (null === $this->next) {
            return ($this->mapper)($result);
        }

        return ($this->next)(($this->mapper)($result));
    }

    /**
     * @param callable(Result): Result $next
     *
     * @return $this
     */
    public function then(callable $next): self
    {
        $this->next = $next;

        return $this;
    }
}
