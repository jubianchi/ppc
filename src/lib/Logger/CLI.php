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
use Psr\Log\LogLevel;

class CLI extends AbstractLogger
{
    public function log($level, $message, array $context = []): void
    {
        $context = json_encode($context);
        $label = str_pad('['.$level.']', 9, ' ');

        $format = function (string $level, string $message): string {
            switch ($level) {
                case LogLevel::INFO:
                    return "\033[38m".$message."\033[0m";

                case LogLevel::ERROR:
                    return "\033[1;31m".$message."\033[0m";

                case LogLevel::WARNING:
                    return "\033[1;33m".$message."\033[0m";

                default:
                    return $message;
            }
        };

        echo $format($level, $label."\t".$message.' '.$context).PHP_EOL;
    }
}
