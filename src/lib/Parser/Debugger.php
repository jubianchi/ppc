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

namespace jubianchi\PPC\Parser;

use jubianchi\PPC\Parser;
use jubianchi\PPC\Slice;
use jubianchi\PPC\Stream;
use Psr\Log\LoggerInterface;
use SplObjectStorage;

class Debugger
{
    private int $padding = 0;
    private int $ops = 0;
    private LoggerInterface $logger;
    private SplObjectStorage $starts;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->starts = new SplObjectStorage();
    }

    public function enter(Parser $parser, Stream $stream): self
    {
        $this->info('> '.$parser, $stream->position());

        ++$this->padding;

        $this->starts->attach($parser, microtime(true));

        return $this;
    }

    public function exit(Parser $parser, Stream $stream, Result $result): self
    {
        $context = $stream->position();

        if ($this->starts->contains($parser)) {
            $context = $context + ['duration' => round(microtime(true) - $this->starts[$parser], 6)];
            $this->starts->detach($parser);
        }

        ++$this->ops;
        --$this->padding;

        if ($result instanceof Result\Failure) {
            $this->error('< '.$parser, $context);
        } else {
            $context = $context + ($result->result() instanceof Slice ? ['consumed' => (string) $result->result()] : []);

            $this->info('< '.$parser, $context);
        }

        return $this;
    }

    /**
     * @param string       $message
     * @param array<mixed> $context
     *
     * @return $this
     */
    public function error($message, array $context = []): self
    {
        $this->logger->error($this->format($message), $this->build($context));

        return $this;
    }

    /**
     * @param string       $message
     * @param array<mixed> $context
     *
     * @return $this
     */
    public function info($message, array $context = []): self
    {
        $this->logger->info($this->format($message), $this->build($context));

        return $this;
    }

    private function format(string $message): string
    {
        $padding = $this->padding > 0 ? str_repeat('  ', $this->padding) : '';

        return $padding.$message;
    }

    /**
     * @param array<mixed> $context
     *
     * @return array<mixed>
     */
    private function build(array $context): array
    {
        $context = $context + ['ops' => $this->ops];

        return $context;
    }
}
