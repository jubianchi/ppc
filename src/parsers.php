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

    return (new Parser('char', function (Stream $stream) use ($char, $format): Result {
        if (!$stream->valid()) {
            $this->logger->error('< '.$this, $stream->position());

            return Failure::create(
                $this->label,
                $char,
                Stream::EOS,
                $stream->position()['line'],
                $stream->position()['column'],
            );
        }

        $current = $stream->current();

        if ($current !== $char) {
            return Failure::create(
                $this->label,
                $format($char),
                $current,
                $stream->position()['line'],
                $stream->position()['column'],
            );
        }

        $slice = new Slice($stream, $stream->key(), 1);

        $stream->next();

        return new Success($slice);
    }))
        ->stringify(fn (string $label): string => sprintf('%s(%s)', $label, $format($char)));
}

function regex(string $pattern): Parser
{
    return (new Parser('regex', function (Stream $stream) use ($pattern): Result {
        if (!$stream->valid()) {
            return Failure::create(
                $this->label,
                $pattern,
                Stream::EOS,
                $stream->position()['line'],
                $stream->position()['column'],
            );
        }

        $current = $stream->current();

        if (0 === preg_match($pattern, $current)) {
            return Failure::create(
                $this->label,
                $pattern,
                $current,
                $stream->position()['line'],
                $stream->position()['column'],
            );
        }

        $slice = new Slice($stream, $stream->key(), 1);

        $stream->next();

        return new Success($slice);
    }))
        ->stringify(fn (string $label): string => sprintf('%s(%s)', $label, $pattern));
}

function word(string $word): Parser
{
    $format = fn (string $char): string => str_replace(["\r", "\n", "\t"], ['\r', '\n', '\t'], $char);

    return (new Parser('word', function (Stream $stream) use ($word, $format): Result {
        if (!$stream->valid()) {
            return Failure::create(
                $this->label,
                $format($word),
                Stream::EOS,
                $stream->position()['line'],
                $stream->position()['column'],
            );
        }

        $length = mb_strlen($word);

        try {
            $actual = $stream->cut($stream->key(), $length);
        } catch (OutOfBoundsException $exception) {
            return Failure::create(
                $this->label,
                $word,
                $stream->cut($stream->key()).' . '.Stream::EOS,
                $stream->position()['line'],
                $stream->position()['column'],
            );
        }

        if ($actual !== $word) {
            return Failure::create(
                $this->label,
                $format($word),
                $actual,
                $stream->position()['line'],
                $stream->position()['column'],
            );
        }

        $slice = new Slice($stream, $stream->key(), $length);

        while ($length > 0 && $stream->valid()) {
            --$length;

            $stream->next();
        }

        return new Success($slice);
    }))
        ->stringify(fn (string $label): string => sprintf('%s(%s)', $label, $format($word)));
}

function any(): Parser
{
    return new Parser('any', function (Stream $stream): Result {
        if (!$stream->valid()) {
            return Failure::create(
                $this->label,
                'any',
                Stream::EOS,
                $stream->position()['line'],
                $stream->position()['column'],
            );
        }

        $slice = new Slice($stream, $stream->key(), 1);

        $stream->next();

        return new Success($slice);
    });
}

function eos(): Parser
{
    return new Parser('eos', function (Stream $stream): Result {
        if ($stream->valid()) {
            return Failure::create(
                $this->label,
                Stream::EOS,
                $stream->current(),
                $stream->position()['line'],
                $stream->position()['column'],
            );
        }

        return new Success(null);
    });
}
