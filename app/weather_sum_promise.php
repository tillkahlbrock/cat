<?php

require __DIR__ . '/../vendor/autoload.php';

use \Cat\City\Service as CityService;
use \React\HttpClient\Client as HttpClient;
use \React\HttpClient\Response;

function getTemperature($city, HttpClient $client)
{
    $deferred = new React\Promise\Deferred();

    $resolver = $deferred->resolver();
    $request = $client->request('GET', 'http://api.openweathermap.org/data/2.5/weather?q=' . $city);
    $request->on(
        'response',
        function (Response $response) use ($resolver) {
            $buffer = '';

            $response->on(
                'data',
                function ($data) use (&$buffer) {
                    $buffer .= $data;
                }
            );

            $response->on(
                'end',
                function () use (&$buffer, $resolver) {
                    $decoded = json_decode($buffer, true);
                    if (isset($decoded['main']['temp'])) {
                        $temp = convertKelvinToCelcius($decoded['main']['temp']);
                        $resolver->resolve($temp);
                    }
                    $resolver->reject("could not retrieve temperature:\n" . print_r($decoded, true));
                }
            );
        }
    );
    $request->on(
        'end',
        function ($error, $response) {
            echo $error;
        }
    );
    $request->end();

    return $deferred->promise();
}

function convertKelvinToCelcius($kelvinValue)
{
    return $kelvinValue - 273.15;
}

function main()
{
    $loop = React\EventLoop\Factory::create();

    $dnsResolverFactory = new React\Dns\Resolver\Factory();
    $dnsResolver = $dnsResolverFactory->createCached('8.8.8.8', $loop);

    $factory = new React\HttpClient\Factory();
    $client = $factory->create($loop, $dnsResolver);

    $country = 'de';
    $cities = CityService::getCities($country);

    $sum = array('count' => 0, 'sum' => 0);

    $promises = array();

    foreach ($cities as $city) {
        $promises[] = getTemperature($city, $client);
    }
    React\Promise\When::reduce(
        $promises,
        function ($sum, $val) {
            $sum['sum'] += $val;
            $sum['count'] += 1;
            return $sum;
        },
        array('count' => 0, 'sum' => 0)
    )->then(
            function ($result) use (&$sum) {
                $sum = $result;
            },
            function ($reason) {
                echo $reason;
            }
        );

    $loop->run();

    $numCities = $sum['count'] > 0 ? $sum['count'] : 1;
    $avg = round($sum['sum'] / $numCities, 1);
    echo "Avg temperature of " . $country . ": " . $avg . "°C\n";
}

main();
