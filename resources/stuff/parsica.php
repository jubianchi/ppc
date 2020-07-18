<?php

declare(strict_types=1);

use Verraes\Parsica\JSON\JSON;

require_once __DIR__ . '/../../vendor/autoload.php';

$contents = file_get_contents(__DIR__ . '/../composer.json');
var_dump(JSON::json()->tryString($contents)->output());
