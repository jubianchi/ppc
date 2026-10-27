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
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\ParamProviders;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;

#[Warmup(1)]
#[Revs(1)]
#[Iterations(5)]
#[BeforeMethods('setUp')]
#[ParamProviders('provideDocuments')]
class Unicode
{
    private string $data;

    /**
     * @param array{records: int} $params
     */
    public function setUp(array $params): void
    {
        $this->data = Documents::json($params['records'], Documents::UTF8);
    }

    /**
     * @return \Generator<string, array{records: int}>
     */
    public function provideDocuments(): \Generator
    {
        yield '3 kB' => ['records' => 10];
        yield '10 kB' => ['records' => 40];
    }

    public function bench_json_ppc(): void
    {
        PPC\parse(PPC\Parsers\json(), $this->data);
    }
}
