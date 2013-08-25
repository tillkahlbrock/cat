<?php

require __DIR__.'/../vendor/autoload.php';

$cities = array();

function retrieveCities($country)
{
    $browser = new Buzz\Browser();
    $response = $browser->get('http://127.0.0.1:6000/' . $country . '/cities');
    
    if (!$response->isSuccessful()) {
        throw new Exception('can not fetch cities for ' . $country . "\n");
    }
    $cities = json_decode($response->getContent(), true);
    $result = array(
        'country' => $country,
        'cities' => $cities
    );
    return $result;
}

function getTemperature($city)
{
    $deferred = new React\Promise\Deferred();

    retrieveTemperature($city, $deferred->resolver());

    return $deferred->promise();
}

function retrieveTemperature($city, $resolver)
{
    $loop = React\EventLoop\Factory::create();

    $dnsResolverFactory = new React\Dns\Resolver\Factory();
    $dnsResolver = $dnsResolverFactory->createCached('8.8.8.8', $loop);

    $factory = new React\HttpClient\Factory();
    $client = $factory->create($loop, $dnsResolver);

    $request = $client->request('GET', 'http://api.openweathermap.org/data/2.5/weather?q=' . $city);
    $request->on('response', function ($response) use($resolver) {
        $buffer = '';

        $response->on('data', function ($data) use (&$buffer) {
            $buffer .= $data;
        });

        $response->on('end', function () use (&$buffer, $resolver) {
            $decoded = json_decode($buffer, true);
            $temp = convertKelvinToCelcius($decoded['main']['temp']);
            $resolver->resolve($temp);
        });
    });
    $request->on('end', function ($error, $response) {
        echo $error;
    });
    $request->end();
    $loop->run();
}

function convertKelvinToCelcius($kelvinValue)
{
    return $kelvinValue - 273.15;
}

function main()
{
    $result = retrieveCities("de");

    $sum = 0;

    foreach ($result['cities'] as $city) {
        getTemperature($city)
            ->then(
                function ($result) use(&$sum) { $sum += $result; },
                function ($reason) { echo $reason; }
            );
    }

    $numCities = count($result['cities']);
    $avg = round($sum / ($numCities > 0 ? $numCities : 1), 1);
    echo "Avg temperature of " . $result['country'] . ": " . $avg . "°C\n";

}

main();
