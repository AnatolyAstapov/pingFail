#!/usr/bin/env php
<?php

ini_set('memory_limit', '512M');

define("ROOT", __DIR__."/../");

if (!$loader = include __DIR__ . '/../vendor/autoload.php') {
    die('You must set up the project dependencies.');
}


include_once __DIR__.'/../src/Components/Logger.php';
include_once __DIR__.'/../src/Components/HttpClient.php';
include_once __DIR__.'/../src/Controller/Monitoring.php';

$app = new \Cilex\Application('Cilex');

$app->command(new \Cilex\Command\ServiceCommand());

$app->run();
