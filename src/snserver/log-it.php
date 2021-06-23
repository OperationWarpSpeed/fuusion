<?php
//
//  log-it.php - SoftNAS Logging Utility
//
//  Copyright (c) SoftNAS Inc.  All Rights Reserved.
//
//
require_once 'KLogger.php';
require_once ('utils.php');
require_once ('snasutils.php');
require_once 'logging.php';
require_once 'CommandLine.php';
require_once 'config.php';
// Enable error reporting
ini_set('log_errors', 1);
ini_set('display_errors', 0);
error_reporting(E_ALL);
$theLog = $log = "";
//
//
// Get the arguments from the PHP command line
//
if (!($argc != 4 || $argc != 5)) {
	$err = "Usage: log-it.php <logfilename> <Fatal/Error/Warn/Info/Debug> <\"The message to log within single or double quotes\">\n";
	print $err;
	exit(1);
}
$arguments = array();
$arguments = CommandLine::parseArgs($argv);
$arguments['theLog'] = $theLog;
$logfilename = $arguments[0];
$level = $arguments[1];
$message = $arguments[2];
$theLog = init_logging($_config['proddir'] . "/logs/$logfilename");
$log = $theLog;
$config_path = "{$_config['proddir']}/config/";
$inilevel = 'info';
$ini = read_ini();
if (isset($ini['support']) && isset($ini['support']['loglevel'])) {
	$inilevel = strtolower($ini['support']['loglevel']);
}
if (isset($arguments['inifile']) &&	file_exists($config_path.$arguments['inifile']) &&	
	($config = read_ini($arguments['inifile'], $config_path)) && (isset($config['support']['loglevel']) || isset($config['loglevel'])) ) {
	$inilevel = strtolower((isset($config['support']['loglevel']) ? $config['support']['loglevel'] : $config['loglevel']));
	//$inilevel = strtolower($config['support']['loglevel']);
}

$level = strtolower($level);

switch ($inilevel) {
	case 'off':
		$theLog->SetLogLevel(KLogger::OFF);
	break;
	case 'fatal':
		$theLog->SetLogLevel(KLogger::FATAL);
	break;
	case 'error':
		$theLog->SetLogLevel(KLogger::ERROR);
	break;
	case 'warn':
		$theLog->SetLogLevel(KLogger::WARN);
	break;
	case 'info':
		$theLog->SetLogLevel(KLogger::INFO);
	break;
	case 'debug':
		$theLog->SetLogLevel(KLogger::DEBUG);
	break;
	case 'maint':
		$theLog->SetLogLevel(KLogger::MAINT);
	break;
	default:
		$theLog->SetLogLevel(KLogger::INFO);
	break;
}

switch ($level) {
	case 'fatal':
		$theLog->LogFatal($message);
	break;
	case 'error':
		$theLog->LogError($message);
	break;
	case 'warn':
		$theLog->LogWarn($message);
	break;
	case 'info':
		$theLog->LogInfo($message);
	break;
	case 'debug':
		$theLog->LogDebug($message);
	break;
	case 'maint':
		$theLog->LogMaint($message);
	break;
	default:
		print "Invalid log level. Must be either Fatal/Error/Warn/Info/Debug\n";
		exit(1);
	break;
}
exit(0); // return success

?>
