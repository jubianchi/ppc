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

namespace jubianchi\PPC\Logger;

use jubianchi\PPC\Logger;
use Psr\Log\AbstractLogger;

class Nil extends AbstractLogger implements Logger
{
    private static ?self $instance = null;

    private function __construct()
    {
    }

    public static function get(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function indent(): self
    {
        return $this;
    }

    public function dedent(): self
    {
        return $this;
    }

    public function log($level, $message, array $context = []): void
    {
    }
}
