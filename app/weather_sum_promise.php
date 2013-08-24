<?php

require __DIR__.'/../vendor/autoload.php';

$cities = array();

function retrieveCities($country, $resolver)
{
    $browser = new Buzz\Browser();
    $response = $browser->get('http://127.0.0.1:6000/' . $country . '/cities');
    
    if (!$response->isSuccessful()) {
        $resolver->reject('can not fetch cities for ' . $country . "\n");
    }
    $cities = json_decode($response->getContent(), true);
    $result = array(
        'country' => $country,
        'cities' => $cities
    );
    $resolver->resolve($result);
}

function getCities($country)
{
    $deferred = new React\Promise\Deferred();

    retrieveCities($country, $deferred->resolver());

    return $deferred->promise();
}

function retrieveTemperature($city)
{
    $browser = new Buzz\Browser();
    $response = $browser->get('http://api.openweathermap.org/data/2.5/weather?q=' . $city);

    $decoded = json_decode($response->getContent(), true);
    $temp = convertKelvinToCelcius($decoded['main']['temp']);

    return $temp;
}

function convertKelvinToCelcius($kelvinValue)
{
    return $kelvinValue - 273.15;
}

function main()
{
    getCities("fr")
    ->then(
        function($result) { 
            return $result;
        },
        function($reason) {
            echo $reason;
        }
    )->then(
        function($result) {
            $sum = 0;
            foreach ($result['cities'] as $city) {
                $sum += retrieveTemperature($city);
            }
            $numCities = count($result['cities']);
            $avg = $sum / ($numCities > 0 ? $numCities : 1);
            echo "Avg temperature of " . $result['country'] . ": " . $avg . "°C\n";
        }
    );
}

main();
