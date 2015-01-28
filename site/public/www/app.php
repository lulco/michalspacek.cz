<?php
use \MichalSpacekCz\Application\Bootstrap;

if (file_exists('./maintenance.php')) {
	require 'maintenance.php';
}

$rootDir = realpath(__DIR__ . '/../..');

require $rootDir . '/app/models/Application/Bootstrap.php';

$appDir = $rootDir . '/app';
$logDir = $rootDir . '/log';
$tempDir = $rootDir . '/temp';
$environment = (isset($_SERVER['ENVIRONMENT']) ? $_SERVER['ENVIRONMENT'] : Bootstrap::MODE_PRODUCTION);

$bootstrap = new Bootstrap($appDir, $logDir, $tempDir, $environment);
$bootstrap->run();
