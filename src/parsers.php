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

namespace jubianchi\PPC\Parsers;

use jubianchi\PPC\Parser;
use jubianchi\PPC\Parser\Result;
use jubianchi\PPC\Parser\Result\Failure;
use jubianchi\PPC\Parser\Result\Success;
use jubianchi\PPC\Slice;
use jubianchi\PPC\Stream;
use OutOfBoundsException;

function char(string $char): Parser
{
    $format = fn (string $char): string => str_replace(["\r", "\n", "\t"], ['\r', '\n', '\t'], $char);

    return (new Parser('char', function (Stream $stream, string $label) use ($char, $format): Result {
        if ($stream->eos()) {
            return Failure::create(
                $label,
                $char,
                Stream::EOS,
                $stream,
            );
        }

        $current = $stream->current();

        if ($current !== $char) {
            return Failure::create(
                $label,
                $format($char),
                $current,
                $stream,
            );
        }

        return new Success($stream->consume());
    }))
        ->stringify(fn (string $label): string => sprintf('%s(%s)', $label, $format($char)));
}

function regex(string $pattern): Parser
{
    return (new Parser('regex', function (Stream $stream, string $label) use ($pattern): Result {
        if ($stream->eos()) {
            return Failure::create(
                $label,
                $pattern,
                Stream::EOS,
                $stream,
            );
        }

        $current = $stream->current();

        if (0 === preg_match($pattern, $current)) {
            return Failure::create(
                $label,
                $pattern,
                $current,
                $stream,
            );
        }

        return new Success($stream->consume());
    }))
        ->stringify(fn (string $label): string => sprintf('%s(%s)', $label, $pattern));
}

function word(string $word): Parser
{
    $format = fn (string $char): string => str_replace(["\r", "\n", "\t"], ['\r', '\n', '\t'], $char);

    return (new Parser('word', function (Stream $stream, string $label) use ($word, $format): Result {
        if ($stream->eos()) {
            return Failure::create(
                $label,
                $format($word),
                Stream::EOS,
                $stream,
            );
        }

        $length = mb_strlen($word);

        try {
            $actual = $stream->cut($stream->tell(), $length);
        } catch (OutOfBoundsException $exception) {
            return Failure::create(
                $label,
                $word,
                $stream->cut($stream->tell()).' . '.Stream::EOS,
                $stream,
            );
        }

        if ($actual !== $word) {
            return Failure::create(
                $label,
                $format($word),
                $actual,
                $stream,
            );
        }

        $slice = new Slice($stream, $stream->tell(), $length);

        $stream->seek($stream->tell() + $length);

        return new Success($slice);
    }))
        ->stringify(fn (string $label): string => sprintf('%s(%s)', $label, $format($word)));
}

function any(): Parser
{
    return new Parser('any', function (Stream $stream, string $label): Result {
        if ($stream->eos()) {
            return Failure::create(
                $label,
                'any',
                Stream::EOS,
                $stream,
            );
        }

        return new Success($stream->consume());
    });
}

function eos(): Parser
{
    return new Parser('eos', function (Stream $stream, string $label): Result {
        if (!$stream->eos()) {
            return Failure::create(
                $label,
                Stream::EOS,
                $stream->current(),
                $stream,
            );
        }

        return new Success(null);
    });
}
