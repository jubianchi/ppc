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
use jubianchi\PPC\Parser\Result;
use RuntimeException;

class Parser
{
    /**
     * @var callable(Stream): Result
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
    private ?Logger $logger = null;

    /**
     * @param callable(Stream): Result $parser
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
        $this->stringify = Closure::fromCallable($this->stringify)->bindTo($this, self::class);
    }

    public function __toString(): string
    {
        return ($this->stringify)($this->label);
    }

    /**
     * @throws Exception
     */
    public function __invoke(Stream $stream): Result
    {
        $this->logger and $this->logger->info('> '.$this, $stream->position());

        try {
            $result = ($this->parser)($stream);

            if ($result->isFailure()) {
                $this->logger and $this->logger->error('< '.$this, $stream->position());

                return $result;
            }

            $context = $result->result() instanceof Slice ? ['consumed' => (string) $result->result()] : [];
            $this->logger and $this->logger->info('< '.$this, $stream->position() + $context);

            return ($this->mapper)($result);
        } catch (Exception $exception) {
            throw new RuntimeException($this->label.': '.$exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    public function label(string $label): self
    {
        $parser = clone $this;
        $parser->label = $this->originalLabel.'•'.$label;

        return $parser;
    }

    /**
     * @return $this
     */
    public function logger(?Logger $logger = null): self
    {
        $this->logger = $logger;

        return $this;
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
        $parser->stringify = Closure::fromCallable($stringify)->bindTo($parser, self::class);

        return $parser;
    }
}
