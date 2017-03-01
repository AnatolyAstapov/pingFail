#!/usr/bin/env php
<?php

define("ROOT", __DIR__."/../");

if (!$loader = include __DIR__ . '/../vendor/autoload.php') {
    die('You must set up the project dependencies.');
}


include_once __DIR__.'/../src/Components/Logger.php';
include_once __DIR__.'/../src/Components/HttpClient.php';
include_once __DIR__.'/../src/Controller/Monitoring.php';

$app = new \Cilex\Application('Cilex');


$app->command(new \Cilex\Command\GreetCommand());
$app->command(new \Cilex\Command\DemoInfoCommand());
$app->command(new \Cilex\Command\ServiceCommand());
$app->command('foo', function ($input, $output) {
    $output->writeln('Example output');
});

$app->run();
