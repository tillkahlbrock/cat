<?php

require __DIR__.'/../vendor/autoload.php';

$loop = React\EventLoop\Factory::create();

$dnsResolverFactory = new React\Dns\Resolver\Factory();
$dnsResolver = $dnsResolverFactory->createCached('8.8.8.8', $loop);

$factory = new React\HttpClient\Factory();
$client = $factory->create($loop, $dnsResolver);

$cities = array();

$request = $client->request('GET', 'http://127.0.0.1:6000/uk/cities');
$request->on('response', function ($response) use (&$cities) {
    $buffer = '';

    $response->on('data', function ($data) use (&$buffer) {
        $buffer .= $data;
    });

    $response->on('end', function () use (&$buffer, &$cities) {
        $cities = json_decode($buffer, true);
    });
});
$request->on('end', function ($error, $response) {
    echo $error;
});
$request->end();

$loop->run();

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
$i = count($cities);
$tempAvg = $tempSum / ($i > 0 ? $i : 1);
echo "Average (" . $i . ") temperature in England: " . $tempAvg . "\n";

function convertKelvinToCelcius($kelvinValue)
{
    return $kelvinValue - 273.15;
}
