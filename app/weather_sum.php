<?php

require __DIR__.'/../vendor/autoload.php';

$loop = React\EventLoop\Factory::create();

$dnsResolverFactory = new React\Dns\Resolver\Factory();
$dnsResolver = $dnsResolverFactory->createCached('8.8.8.8', $loop);

$factory = new React\HttpClient\Factory();
$client = $factory->create($loop, $dnsResolver);

$cities = array('London', 'Manchester');
$tempSum = 0;

foreach($cities as $city) {
    $request = $client->request('GET', 'http://api.openweathermap.org/data/2.5/weather?q=' . $city);
    $request->on('response', function ($response) use(&$tempSum) {
        $buffer = '';

        $response->on('data', function ($data) use (&$buffer) {
            $buffer .= $data;
        });

        $response->on('end', function () use (&$buffer, &$tempSum) {
            $decoded = json_decode($buffer, true);
            $temp = convertKelvinToCelcius($decoded['main']['temp']);
            $tempSum += $temp;
        });
    });
    $request->on('end', function ($error, $response) {
        echo $error;
    });
    $request->end();
}

$loop->run();
$tempAvg = $tempSum / count($cities);
echo "Average temperature in England: " . $tempAvg . "\n";

function convertKelvinToCelcius($kelvinValue)
{
    return $kelvinValue - 273.15;
}
