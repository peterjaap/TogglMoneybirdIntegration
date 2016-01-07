#!/usr/bin/env php
<?php

ini_set('display_errors', true);
error_reporting(E_ALL);

require __DIR__.'/vendor/autoload.php';
require __DIR__.'/TogglMoneybird/MyApplication.php';
require __DIR__.'/TogglMoneybird/IntegrateCommand.php';

use TogglMoneybird\MyApplication;

$application = new MyApplication();
$application->run();