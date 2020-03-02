<?php

require __DIR__.'/vendor/autoload.php';

$client = new \GuzzleHttp\Client([ // Creating a new Guzzle cleint to test api requests
    'base_url' => 'http://localhost:8000',
    'defaults' => [
        'exceptions' => false // Making it return a response always
    ]
]);

$nickname = 'ObjectOrienter'.rand(0, 999); // Creating a random nickname
$data = array( // Creating an array called $data and putting all the Programmer data inside, including the randomly generated name. This is just for testing purposes
    'nickname' => $nickname,
    'avatarNumber' => 5,
    'tagLine' => 'a test dev!'
);

// 1) Create a programmer resource
$response = $client->post('/api/programmers', [
    'body' => json_encode($data) // Adding an options array to the POST method with the key 'body' set the $data array we created above
]);
echo $response;
echo "\n\n";die;

$programmerUrl = $response->getHeader('Location');

// 2) GET a programmer resource
$response = $client->get($programmerUrl);

// 3) GET a collection of programmers
$response = $client->get('/api/programmers');

echo $response;
echo "\n\n";
