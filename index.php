<?php

include 'vendor/autoload.php';

$regionEuw = new \EloGank\Api\Region\Region('EUW', 'Europe West', 'prod.eu.lol.riotgames.com', 'https://lq.eu.lol.riotgames.com');
//$regionNa = new \EloGank\Api\Region\Region('NA', 'North America', 'prod.na1.lol.riotgames.com', 'https://lq.na1.lol.riotgames.com');

$client = new \EloGank\Api\Client\LOLClient($regionEuw, 'username', 'password', '4.3.14_02_25_12_04');
$client->auth();