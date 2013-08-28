<?php

use \Cat\City\Service as CityService;
use \React\HttpClient\Client as HttpClient;
use \React\HttpClient\Response;

const SYNC = 'sync';
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
                        $resolver->resolve($decoded['main']['temp']);
                    }
                    $resolver->reject("could not retrieve temperature:\n" . print_r($decoded, true));
                }
            );
        }
    );
    $request->end();

    return $deferred->promise();
}

function retrieveTemperature_sync($city)
{
    $browser = new Buzz\Browser();
    $response = $browser->get('http://api.openweathermap.org/data/2.5/weather?q=' . $city);
    if (!$response->isSuccessful()) {
        return -1;
    }

    $decoded = json_decode($response->getContent(), true);
    return $decoded['main']['temp'];
}

function convertKelvinToCelcius($kelvinValue)
{
    return $kelvinValue - 273.15;
}

function cat_async($cities)
{
    $loop = React\EventLoop\Factory::create();

    $dnsResolverFactory = new React\Dns\Resolver\Factory();
    $dnsResolver = $dnsResolverFactory->createCached('8.8.8.8', $loop);

    $factory = new React\HttpClient\Factory();
    $client = $factory->create($loop, $dnsResolver);

    $sum = array('count' => 0, 'sum' => 0);

    $promises = array();

    foreach ($cities as $city) {
        $promises[] = getTemperature($city, $client);
    }
    React\Promise\When::reduce(
        $promises,
        function ($sum, $temp) {
            $sum['sum'] += $temp;
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

    return $sum;
}

function cat_sync($cities)
{
    $sum = array('count' => 0, 'sum' => 0);

    print_r($cities);

    foreach ($cities as $city) {
        $temp = retrieveTemperature_sync($city);
        if ($temp >= 0) {
            $sum['sum'] += $temp;
            $sum['count'] += 1;
        }
    }

    return $sum;

}

function cat($country, $type)
{
    $cities = CityService::getCities($country);

    if ($type == SYNC) {
        $sum = cat_sync($cities);
    } else {
        $sum = cat_async($cities);
    }

    $summedTemperature = $sum['sum'];
    $processedCities = $sum['count'];

    $avgKelvin = round($summedTemperature / ($processedCities > 0 ? $processedCities : 1), 1);
    $avgCelcius = convertKelvinToCelcius($avgKelvin);
    echo "Avg temperature of " . $country . ": " . $avgCelcius . "°C\n";
}
