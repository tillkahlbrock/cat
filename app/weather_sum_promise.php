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
    $browser = new Buzz\Browser();
    $response = $browser->get('http://api.openweathermap.org/data/2.5/weather?q=' . $city);

    if (!$response->isSuccessful()) {
        $resolver->reject('can not fetch temperature for ' . $city . "\n");
    }
    $decoded = json_decode($response->getContent(), true);
    $temp = convertKelvinToCelcius($decoded['main']['temp']);

    $resolver->resolve($temp);
}

function convertKelvinToCelcius($kelvinValue)
{
    return $kelvinValue - 273.15;
}

function main()
{
    $result = retrieveCities("fr");

    $sum = 0;

    foreach ($result['cities'] as $city) {
        getTemperature($city)
            ->then(
                function ($result) use(&$sum) { $sum += $result; },
                function ($reason) { echo $reason; }
            );
    }

    $numCities = count($result['cities']);
    $avg = $sum / ($numCities > 0 ? $numCities : 1);
    echo "Avg temperature of " . $result['country'] . ": " . $avg . "°C\n";

}

main();
