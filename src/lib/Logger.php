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

namespace jubianchi\PPC;

use Psr\Log\LoggerInterface;

interface Logger extends LoggerInterface
{
    public function indent(): self;

    public function dedent(): self;
}
