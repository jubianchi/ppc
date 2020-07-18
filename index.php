<?php
/*
 * This file is part of PPC.
 *
 * © Julien Bianchi <contact@jubianchi.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace jubianchi\PPC;

use function jubianchi\PPC\Combinators\debug;
use function jubianchi\PPC\Parsers\json;

require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/parsers/json.php';

$stream = new Stream(file_get_contents(__DIR__.'/resources/composer.json'));
//$stream = new Stream('"foo": "bar", "bar": "baz", "boo": false');
//$stream = new Stream('{"foo": false, "bar": "baz", "boo": false}');
//$stream = new Stream('["foo", true, false]');
$stream = new Stream('{"foo": false, "bar": "baz", "boo": ["foo", true, false], "bee": null, "bii": ""}');
//$stream = new Stream('"foo"');
$parser = debug(json());
var_dump($parser($stream)->result());
