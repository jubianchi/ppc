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

namespace jubianchi\PPC\Parser\Result;

use jubianchi\PPC\Parser\Result;

class Success implements Result
{
    /**
     * @var mixed
     */
    private $result;

    /**
     * @param mixed $result
     */
    public function __construct($result)
    {
        $this->result = $result;
    }

    public function isSuccess(): bool
    {
        return true;
    }

    public function isFailure(): bool
    {
        return false;
    }

    public function result()
    {
        return $this->result;
    }
}
