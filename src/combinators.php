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

namespace jubianchi\PPC\Combinators;

use Exception;
use jubianchi\PPC\Parser;
use jubianchi\PPC\Parser\Result;
use jubianchi\PPC\Parser\Result\Failure;
use jubianchi\PPC\Parser\Result\Skip;
use jubianchi\PPC\Parser\Result\Success;
use function jubianchi\PPC\Parsers\any;
use jubianchi\PPC\Stream;

function alt(Parser $first, Parser $second, Parser ...$parsers): Parser
{
    array_unshift($parsers, $first, $second);

    return (new Parser('alt', function (Stream $stream) use ($parsers): Result {
        $failure = null;

        $this->logger and $this->logger->indent();

        foreach ($parsers as $parser) {
            $transaction = $stream->begin();
            $result = $parser->logger($this->logger)($transaction);

            if ($result->isSuccess()) {
                $stream->commit();

                $this->logger and $this->logger->dedent();

                return $result;
            }

            $stream->rollback();

            if (null === $failure) {
                $failure = $result;
            }
        }

        $this->logger and $this->logger->dedent();

        return $failure;
    }))
        ->stringify(fn (string $label): string => $label.'('.implode(', ', $parsers).')');
}

function seq(Parser $first, Parser $second, Parser ...$parsers): Parser
{
    array_unshift($parsers, $first, $second);

    return (new Parser('seq', function (Stream $stream) use ($parsers): Result {
        $results = [];

        $this->logger and $this->logger->indent();

        $transaction = $stream->begin();

        foreach ($parsers as $parser) {
            $result = $parser->logger($this->logger)($transaction);

            if ($result->isFailure()) {
                $stream->rollback();

                $this->logger and $this->logger->dedent();

                return $result;
            }

            if (!($result instanceof Skip)) {
                $results[] = $result->result();
            }
        }

        $stream->commit();

        $this->logger and $this->logger->dedent();

        return new Success($results);
    }))->stringify(fn (string $label): string => $label.'('.implode(', ', $parsers).')');
}

function opt(Parser $parser): Parser
{
    return (new Parser('opt', function (Stream $stream) use ($parser): Result {
        $this->logger and $this->logger->indent();

        $transaction = $stream->begin();
        $result = $parser->logger($this->logger)($transaction);

        if ($result->isSuccess()) {
            $stream->commit();

            $this->logger and $this->logger->dedent();

            return $result;
        }

        $stream->rollback();

        $this->logger and $this->logger->dedent();

        return new Success(null);
    }))
        ->stringify(fn (string $label): string => $label.'('.$parser.')');
}

function many(Parser $parser): Parser
{
    return (new Parser('many', function (Stream $stream) use ($parser): Result {
        $results = [];

        $this->logger and $this->logger->indent();

        while (true) {
            $transaction = $stream->begin();
            $result = $parser->logger($this->logger)($transaction);

            if ($result->isFailure()) {
                $stream->rollback();

                if (0 === count($results)) {
                    $this->logger and $this->logger->dedent();

                    return $result;
                }

                break;
            }

            if (!($result instanceof Skip)) {
                $results[] = $result->result();
            }

            $stream->commit();
        }

        $this->logger and $this->logger->dedent();

        return new Success($results);
    }))
        ->stringify(fn (string $label): string => $label.'('.$parser.')');
}

function repeat(int $times, Parser $parser): Parser
{
    return (new Parser('repeat', function (Stream $stream) use ($parser, $times): Result {
        $results = [];
        $transaction = $stream->begin();

        $this->logger and $this->logger->indent();

        while ($times-- > 0) {
            $result = $parser->logger($this->logger)($transaction);

            if ($result->isFailure()) {
                $stream->rollback();

                return $result;
            }

            if (!($result instanceof Skip)) {
                $results[] = $result->result();
            }
        }

        $stream->commit();

        $this->logger and $this->logger->dedent();

        return new Success($results);
    }))
        ->stringify(fn (string $label): string => $label.'('.$times.', '.$parser.')');
}

function not(Parser $parser, Parser ...$parsers): Parser
{
    array_unshift($parsers, $parser);

    return (new Parser('not', function (Stream $stream) use ($parsers): Result {
        $this->logger and $this->logger->indent();

        foreach ($parsers as $parser) {
            $transaction = $stream->begin();
            $result = $parser->logger($this->logger)($transaction);
            $stream->rollback();

            if ($result->isSuccess()) {
                $this->logger and $this->logger->dedent();

                return new Failure(
                    $this->label,
                    sprintf(
                        'Expected "%s" not to match, got "%s" at line %s offset %d',
                        $parser,
                        $stream->current(),
                        $stream->position()['line'],
                        $stream->key()
                    )
                );
            }
        }

        $result = any()($stream);

        $this->logger and $this->logger->dedent();

        return $result;
    }))
        ->stringify(fn (string $label): string => $label.'('.implode(', ', $parsers).')');
}

function recurse(?Parser &$parser): Parser
{
    return (new Parser('recurse', function (Stream $stream) use (&$parser): Result {
        if (null === $parser) {
            throw new Exception('Could not call parser');
        }

        return $parser->logger($this->logger)($stream);
    }))
        ->stringify(function (string $label) use (&$parser): string {
            if (null === $parser) {
                throw new Exception('Could not call parser');
            }

            return $label.'('.$parser->stringify(fn (string $label): string => $label).')';
        });
}

function enclosed(Parser $before, Parser $parser, ?Parser $after = null): Parser
{
    $after = $after ?? $before;

    return (new Parser('enclosed', function (Stream $stream) use ($before, $parser, $after): Result {
        $this->logger and $this->logger->indent();

        $transaction = $stream->begin();
        $beforeResult = $before->logger($this->logger)($transaction);

        if ($beforeResult->isFailure()) {
            $stream->rollback();

            $this->logger and $this->logger->dedent();

            return $beforeResult;
        }

        $result = $parser->logger($this->logger)($transaction);

        if ($result->isFailure()) {
            $stream->rollback();

            $this->logger and $this->logger->dedent();

            return $result;
        }

        $afterResult = $after->logger($this->logger)($transaction);

        if ($afterResult->isFailure()) {
            $stream->rollback();

            $this->logger and $this->logger->dedent();

            return $afterResult;
        }

        $stream->commit();

        $this->logger and $this->logger->dedent();

        return $result;
    }))
        ->stringify(fn (string $label): string => $label.'('.$before.', '.$parser.', '.$after.')');
}

function separated(Parser $separator, Parser $parser): Parser
{
    return (new Parser('separated', function (Stream $stream) use ($separator, $parser): Result {
        $this->logger and $this->logger->indent();

        $results = [];
        $transaction = $stream->begin();

        while (true) {
            $childTransaction = $transaction->begin();

            if (count($results) > 0) {
                $result = $separator->logger($this->logger)($childTransaction);

                if ($result->isFailure()) {
                    $transaction->rollback();

                    break;
                }
            }

            $result = $parser->logger($this->logger)($childTransaction);

            if ($result->isFailure()) {
                $transaction->rollback();

                if (0 === count($results)) {
                    $this->logger and $this->logger->dedent();

                    return $result;
                }

                break;
            }

            $transaction->commit();
            $results[] = $result->result();
        }

        $stream->commit();

        $this->logger and $this->logger->dedent();

        return new Success($results);
    }))
        ->stringify(fn (string $label): string => $label.'('.$separator.', '.$parser.')');
}
