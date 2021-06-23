<?php
//
// Set up logging and return log object
//
define('DEFAULT_LOG_PATH', __DIR__.'/../logs/snserv.log');
define('FLEXFILES_LOG_PATH', __DIR__.'/../logs/flexfiles.log');

require_once __DIR__.'/KLogger.php';

function init_logging($logname = DEFAULT_LOG_PATH) {
	$loglevel = "Info";
	require_once __DIR__.'/utils.php';
	$ini = read_ini();
	if (isset($ini['support']) && isset($ini['support']['loglevel'])) {
		$loglevel = $ini['support']['loglevel'];
	}
	$loglevel = strtolower($loglevel);
	switch ($loglevel) {
		case 'off':
			$level = KLogger::OFF;
		break;
		case 'fatal':
			$level = KLogger::FATAL;
		break;
		case 'error':
			$level = KLogger::ERROR;
		break;
		case 'warn':
			$level = KLogger::WARN;
		break;
		case 'info':
			$level = KLogger::INFO;
		break;
		case 'maint':
			$level = KLogger::MAINT;
		break;
		default:
		case 'debug':
			$level = KLogger::DEBUG;
		break;
	}
	$log = new KLogger($logname, $level);
	return $log;
}
?>
