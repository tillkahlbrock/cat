<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/weather_sum.php';
require __DIR__ . '/../vendor/docopt/docopt/src/docopt.php';

$doc = <<<DOC
Usage: cat.php <COUNTRY> [--help] [--async | --sync]

-h --help    show this
-a --async   use asynchronous calls for retrieving weather data
-s --sync    use synchronous calls for retrieving weather data

DOC;

$args = Docopt\docopt($doc);
$country = $args->args['<COUNTRY>'];

if (isset($args->args['--sync']) && $args->args['--sync']) {
    cat_sync($country);
} else {
    cat_async($country);
}
