<?php

require_once __DIR__  . '/../bootstrap.php';

$app = new \watoki\stepper\cli\Stepper(getcwd());
$app->run();