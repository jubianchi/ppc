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

use jubianchi\PPC\Logger\CLI;
use jubianchi\PPC\Parser;
use jubianchi\PPC\Parser\Debugger;
use jubianchi\PPC\Parser\Result;
use jubianchi\PPC\Stream;

function debug(Parser $parser): Parser
{
    $logger = new CLI();

    return new Parser('debug', fn (Stream $stream, string $label, ?Debugger $debugger = null): Result => $parser(
        $stream,
        $debugger ?? new Parser\Debugger($logger)
    ));
}
