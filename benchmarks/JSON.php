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
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\ParamProviders;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;

#[Warmup(1)]
#[Revs(1)]
#[Iterations(5)]
#[BeforeMethods('setUp')]
class JSON
{
    private string $data;

    /**
     * @param array{records: int} $params
     */
    public function setUp(array $params): void
    {
        $this->data = 0 === $params['records'] ? Documents::composer() : Documents::json($params['records'], Documents::ASCII);
    }

    /**
     * @return \Generator<string, array{records: int}>
     */
    public function provideDocuments(): \Generator
    {
        yield from $this->provideSmallDocuments();
        yield '100 kB' => ['records' => 400];
    }

    /**
     * @return \Generator<string, array{records: int}>
     */
    public function provideSmallDocuments(): \Generator
    {
        yield 'composer' => ['records' => 0];
        yield '10 kB' => ['records' => 40];
    }

    #[ParamProviders('provideDocuments')]
    public function bench_json_ppc(): void
    {
        PPC\Parsers\json()(new PPC\Stream\Char($this->data))->result();
    }

    #[ParamProviders('provideSmallDocuments')]
    public function bench_json_parsica(): void
    {
        Parsica\JSON\JSON::json()->tryString($this->data);
    }

    #[ParamProviders('provideDocuments')]
    public function bench_json_php(): void
    {
        json_decode($this->data, flags: \JSON_THROW_ON_ERROR);
    }
}
