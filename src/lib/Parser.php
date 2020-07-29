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

use Closure;
use Exception;
use jubianchi\PPC\Parser\Debugger;
use jubianchi\PPC\Parser\Result;
use RuntimeException;

class Parser
{
    /**
     * @var callable(Stream, ?Debugger): Result
     */
    private $parser;

    /**
     * @var callable(Result): Result
     */
    private $mapper;

    /**
     * @var callable(string): string
     */
    private $stringify;

    private string $label;
    private string $originalLabel;

    /**
     * @param callable(Stream, ?Debugger): Result $parser
     */
    public function __construct(string $label, callable $parser)
    {
        if ($parser instanceof self) {
            $parser = $parser->parser;
        }

        $this->originalLabel = $this->label = $label;
        $this->parser = Closure::fromCallable($parser)->bindTo($this, self::class);
        $this->mapper = fn (Result $result): Result => $result;
        $this->stringify = fn (string $label): string => $label;
    }

    public function __clone()
    {
        $this->parser = Closure::fromCallable($this->parser)->bindTo($this, self::class);
    }

    public function __toString(): string
    {
        return ($this->stringify)($this->label);
    }

    /**
     * @throws Exception
     */
    public function __invoke(Stream $stream, ?Debugger $debugger = null): Result
    {
        $debugger and $debugger->enter($this, $stream);

        try {
            $result = ($this->parser)($stream, $debugger);

            $debugger and $debugger->exit($this, $stream, $result);

            if ($result->isFailure()) {
                return $result;
            }

            return ($this->mapper)($result);
        } catch (Exception $exception) {
            throw new RuntimeException($this->label.': '.$exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    public function label(string $label): self
    {
        $parser = clone $this;
        $parser->label = $label.'•'.$this->originalLabel;

        return $parser;
    }

    /**
     * @param callable(Result): Result $mapper
     *
     * @return $this
     */
    public function map(callable $mapper): self
    {
        $this->mapper = $mapper;

        return $this;
    }

    /**
     * @param callable(string): string $stringify
     *
     * @return $this
     */
    public function stringify(callable $stringify): self
    {
        $parser = clone $this;
        $parser->stringify = $stringify;

        return $parser;
    }
}
