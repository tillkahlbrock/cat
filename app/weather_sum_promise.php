<?php

require __DIR__.'/../vendor/autoload.php';

$cities = array();

function retrieveCities($country, $resolver)
{
    $browser = new Buzz\Browser();
    $response = $browser->get('http://127.0.0.1:6000/' . $country . '/cities');
    
    if (!$response->isSuccessful()) {
        return $resolver->reject('can not fetch cities for ' . $country);
    }
    
    return $resolver->resolve(json_decode($response->getContent(), true));
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
#    $country = "uk";
#    $cities = retrieveCities($country);
#    $sum = 0;

#    foreach ($cities as $city) {
#        $sum += retrieveTemperature($city);
#    }

#    $avg = $sum / (count($cities) > 0 ? count($cities) : 1);

#    echo "Avg temperature of " . $country . ": " . $avg . "°C\n";

    getCities("uk")->then(
        function($result) { 
            print_r($result); 
        },
        function($reason) {
            echo $reason;
        }
    );
}

main();
