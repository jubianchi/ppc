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
use jubianchi\PPC\Parser\Debugger;
use jubianchi\PPC\Parser\Result;
use jubianchi\PPC\Parser\Result\Failure;
use jubianchi\PPC\Parser\Result\Skip;
use jubianchi\PPC\Parser\Result\Success;
use function jubianchi\PPC\Parsers\any;
use jubianchi\PPC\Stream;

function alt(Parser $first, Parser $second, Parser ...$parsers): Parser
{
    array_unshift($parsers, $first, $second);

    return (new Parser('alt', function (Stream $stream, string $label, ?Debugger $debugger = null) use ($parsers): Result {
        $failure = null;

        foreach ($parsers as $parser) {
            $transaction = $stream->begin();
            $result = $parser($transaction, $debugger);

            if ($result->isSuccess()) {
                $transaction->commit();

                return $result;
            }

            if (null === $failure) {
                $failure = $result;
            }
        }

        return $failure;
    }))
        ->stringify(fn (string $label): string => $label.'('.implode(', ', $parsers).')');
}

function seq(Parser $first, Parser $second, Parser ...$parsers): Parser
{
    array_unshift($parsers, $first, $second);

    return (new Parser('seq', function (Stream $stream, string $label, ?Debugger $debugger = null) use ($parsers): Result {
        $results = [];
        $transaction = $stream->begin();

        foreach ($parsers as $parser) {
            $result = $parser($transaction, $debugger);

            if ($result->isFailure()) {
                return $result;
            }

            if (!($result instanceof Skip)) {
                $results[] = $result->result();
            }
        }

        $transaction->commit();

        return new Success($results);
    }))->stringify(fn (string $label): string => $label.'('.implode(', ', $parsers).')');
}

function opt(Parser $parser): Parser
{
    return (new Parser('opt', function (Stream $stream, string $label, ?Debugger $debugger = null) use ($parser): Result {
        $transaction = $stream->begin();
        $result = $parser($transaction, $debugger);

        if ($result->isSuccess()) {
            $transaction->commit();

            return $result;
        }

        return new Success(null);
    }))
        ->stringify(fn (string $label): string => $label.'('.$parser.')');
}

function many(Parser $parser): Parser
{
    return (new Parser('many', function (Stream $stream, string $label, ?Debugger $debugger = null) use ($parser): Result {
        $results = [];

        while (true) {
            $transaction = $stream->begin();
            $result = $parser($transaction, $debugger);

            if ($result->isFailure()) {
                if (0 === count($results)) {
                    return $result;
                }

                break;
            }

            if (!($result instanceof Skip)) {
                $results[] = $result->result();
            }

            $transaction->commit();
        }

        return new Success($results);
    }))
        ->stringify(fn (string $label): string => $label.'('.$parser.')');
}

function repeat(int $times, Parser $parser): Parser
{
    return (new Parser('repeat', function (Stream $stream, string $label, ?Debugger $debugger = null) use ($parser, $times): Result {
        $results = [];
        $transaction = $stream->begin();

        while ($times-- > 0) {
            $result = $parser($transaction, $debugger);

            if ($result->isFailure()) {
                return $result;
            }

            if (!($result instanceof Skip)) {
                $results[] = $result->result();
            }
        }

        $transaction->commit();

        return new Success($results);
    }))
        ->stringify(fn (string $label): string => $label.'('.$times.', '.$parser.')');
}

function not(Parser $parser, Parser ...$parsers): Parser
{
    array_unshift($parsers, $parser);

    return (new Parser('not', function (Stream $stream, string $label, ?Debugger $debugger = null) use ($parsers): Result {
        foreach ($parsers as $parser) {
            $transaction = $stream->begin();
            $result = $parser($transaction, $debugger);

            if ($result->isSuccess()) {
                return new Failure(
                    $label,
                    sprintf(
                        'Expected "%s" not to match, got "%s" at line %s offset %d',
                        $parser,
                        $stream->current(),
                        $stream->position()['line'],
                        $stream->offset()
                    )
                );
            }
        }

        $result = any()($stream, $debugger);

        return $result;
    }))
        ->stringify(fn (string $label): string => $label.'('.implode(', ', $parsers).')');
}

function recurse(?Parser &$parser): Parser
{
    return (new Parser('recurse', function (Stream $stream, string $label, ?Debugger $debugger = null) use (&$parser): Result {
        if (null === $parser) {
            throw new Exception('Could not call parser');
        }

        return $parser($stream, $debugger);
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

    return (new Parser('enclosed', function (Stream $stream, string $label, ?Debugger $debugger = null) use ($before, $parser, $after): Result {
        $transaction = $stream->begin();
        $beforeResult = $before($transaction, $debugger);

        if ($beforeResult->isFailure()) {
            return $beforeResult;
        }

        $result = $parser($transaction, $debugger);

        if ($result->isFailure()) {
            return $result;
        }

        $afterResult = $after($transaction, $debugger);

        if ($afterResult->isFailure()) {
            return $afterResult;
        }

        $transaction->commit();

        return $result;
    }))
        ->stringify(fn (string $label): string => $label.'('.$before.', '.$parser.', '.$after.')');
}

function separated(Parser $separator, Parser $parser): Parser
{
    return (new Parser('separated', function (Stream $stream, string $label, ?Debugger $debugger = null) use ($separator, $parser): Result {
        $results = [];

        while (true) {
            $transaction = $stream->begin();

            if (count($results) > 0) {
                $result = $separator($transaction, $debugger);

                if ($result->isFailure()) {
                    break;
                }
            }

            $result = $parser($transaction, $debugger);

            if ($result->isFailure()) {
                if (0 === count($results)) {
                    return $result;
                }

                break;
            }

            $transaction->commit();
            $results[] = $result->result();
        }

        return new Success($results);
    }))
        ->stringify(fn (string $label): string => $label.'('.$separator.', '.$parser.')');
}
