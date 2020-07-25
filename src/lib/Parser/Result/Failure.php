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

use Exception;
use jubianchi\PPC\Parser\Result;
use jubianchi\PPC\Stream;

class Failure extends Exception implements Result
{
    public function __construct(string $label, string $message)
    {
        parent::__construct(sprintf('%s: %s', $label, $message));
    }

    public function isSuccess(): bool
    {
        return false;
    }

    public function isFailure(): bool
    {
        return true;
    }

    /**
     * @throws $this
     */
    public function result()
    {
        throw $this;
    }

    public static function create(string $label, string $expected, string $actual, Stream $stream): self
    {
        ['line' => $line, 'column' => $column] = $stream->position();

        return new self($label, sprintf(
            'Expected "%s", got "%s" at line %d offset %d',
            $expected,
            $actual,
            $line,
            $column,
        ));
    }
}
