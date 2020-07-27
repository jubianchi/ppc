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

namespace jubianchi\PPC\Mappers;

use InvalidArgumentException;
use jubianchi\PPC\Mapper;
use jubianchi\PPC\Parser\Result;
use jubianchi\PPC\Parser\Result\Skip;
use jubianchi\PPC\Parser\Result\Success;
use jubianchi\PPC\Slice;

/**
 * @param mixed $default
 */
function otherwise($default): Mapper
{
    return new Mapper(fn (Result $result): Result => null !== $result->result() ? $result : new Success($default));
}

function concat(): Mapper
{
    $reduce = fn (Slice ...$slices): string => array_reduce(
        $slices,
        fn ($prev, $current): string => $prev.$current,
        ''
    );

    return new Mapper(fn (Result $result): Result => new Success(
        $reduce(...($result->result() ?? []))
    ));
}

/**
 * @param array<int, mixed> $mappings
 */
function structure(array $mappings): Mapper
{
    return new Mapper(fn (Result $result): Result => new Success(
        (array) array_reduce(
            array_keys($mappings),
            fn ($prev, $current) => $prev + [$mappings[$current] => $result->result()[$current]],
            []
        )
    ));
}

/**
 * @throws InvalidArgumentException
 */
function php(string $name): Mapper
{
    if (!is_callable($name)) {
        throw new InvalidArgumentException(sprintf('"%s" is not callable', $name));
    }

    return new Mapper(fn (Result $result) => new Success($name($result->result())));
}

function skip(): Mapper
{
    return new Mapper(fn (Result $result) => new Skip($result->result()));
}

function nth(int $nth): Mapper
{
    return new Mapper(fn (Result $result): Result => new Success($result->result()[$nth]));
}

function first(): Mapper
{
    return nth(0);
}

function last(): Mapper
{
    return new Mapper(fn (Result $result): Result => nth(count($result->result()) - 1)($result));
}

/**
 * @param mixed $value
 */
function value($value): Mapper
{
    return new Mapper(fn (): Result => new Success($value));
}
