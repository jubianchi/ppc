<?php

/**
 * This file is part of PPC.
 *
 * Copyright © 2026 Julien Bianchi <contact@jubianchi.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace jubianchi\PPC\Benchmarks;

use jubianchi\PPC;
use Parsica\Parsica;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;

#[Warmup(1)]
class JSON
{
    private string $data;

    public function __construct()
    {
        $data = file_get_contents(__DIR__.'/../resources/composer.json');

        if (false === $data) {
            throw new \RuntimeException('Could not load file');
        }

        $this->data = $data;
    }

    #[Revs(5)]
    #[Iterations(3)]
    public function bench_json_ppc(): void
    {
        PPC\Parsers\json()(new PPC\Stream\Char($this->data));
    }

    #[Revs(5)]
    #[Iterations(3)]
    public function bench_json_parsica(): void
    {
        Parsica\JSON\JSON::json()->tryString($this->data);
    }

    #[Revs(5)]
    #[Iterations(3)]
    public function bench_json_php(): void
    {
        json_decode($this->data);
    }
}
