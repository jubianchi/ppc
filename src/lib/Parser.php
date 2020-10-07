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
use jubianchi\PPC\Parser\Debugger;
use jubianchi\PPC\Parser\Result;
use RuntimeException;

class Parser
{
    /**
     * @var callable(Stream, string, ?Debugger): Result
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
     * @var callable
     */
    private $action;

    /**
     * @param callable(Stream, string, ?Debugger): Result $parser
     */
    public function __construct(string $label, callable $parser)
    {
        $this->originalLabel = $this->label = $label;
        $this->parser = $parser;
        $this->mapper = fn (Result $result): Result => $result;
        $this->stringify = fn (string $label): string => $label;
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
            $result = ($this->parser)($stream, $this->label, $debugger);

            $debugger and $debugger->exit($this, $stream, $result);

            if ($result->isFailure()) {
                return $result;
            }

            $mapped = ($this->mapper)($result);

            if (null !== $this->action) {
                ($this->action)($mapped);
            }

            return $mapped;
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
        $parser = clone $this;
        $parser->mapper = $mapper;

        return $parser;
    }

    public function do(?callable $action): self
    {
        $parser = clone $this;
        $parser->action = $action;

        return $parser;
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
