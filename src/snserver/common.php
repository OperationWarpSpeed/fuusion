<?php
//
// common.php - Common functions used by most components
//
//
// Copyright (c) SoftNAS Inc.  All Rights Reserved.
//
require_once(__DIR__."/config.php");
//
// SUDO command execution
//
function sudo_execute($command, $raw_output = false, $redirect_output = true) {
	global $_config;
	global $log;
	if (!is_object($log)) {
		require_once __DIR__.'/logging.php';
		$log = init_logging(__DIR__.'/../logs/snserv.log');
	}
	if (!isset($_config['systemcmd'])) {
		include __DIR__ . '/config.php';
	}

	$log->LogDebug("sudo_execute: Executing command: $command");
	if ($raw_output === false) {
		if ($redirect_output) exec($_config['systemcmd']['sudo'] . ' TERM=dumb ' . $command . ' 2>&1', $result, $rv);
		else exec($_config['systemcmd']['sudo'] . ' TERM=dumb ' . $command, $result, $rv);
		$result_str = implode(chr(10) , $result);
		return array(
			'rv' => $rv,
			'output_arr' => $result,
			'output_str' => $result_str
		);
	} //$raw_output === false
	else {
		if ($redirect_output) system($_config['systemcmd']['sudo'] . ' ' . $command . ' 2>&1', $rv);
		else system($_config['systemcmd']['sudo'] . ' ' . $command, $rv);
		return $rv;
	}
}
// execute script at elevated privileges
function super_script($script_name, $parameters = '', $path = "../scripts") {
	global $_config;
	global $log;
	$log->LogDebug("super_script:  script name: $script_name");
	$log->LogDebug("super_script:  script params: $parameters");
	if (@strlen($script_name) < 1) {
		$log->LogError('Error: No script name provided');
	}
	$command = $path . "/" . $script_name . '.sh ' . $parameters;
	$result = sudo_execute($command);
	return $result;
}


function simple_exec($command) {
	global $_config;
	if (!isset($_config['systemcmd'])) {
		include __DIR__ . '/config.php';
	}
	exec($command . ' 2>&1', $result, $rv);
	$result_str = implode(chr(10) , $result);
	return array(
		'rv' => $rv,
		'output_arr' => $result,
		'output_str' => $result_str
	);
}
// returns human readable size in bytes from integer (1000)
function sizehuman($bytes, $precision = 0) {
	$units = array(
		'B',
		'KB',
		'MB',
		'GB',
		'TB'
	);
	$bytes = max($bytes, 0);
	$pow = floor(($bytes ? log($bytes) : 0) / log(1000));
	$pow = min($pow, count($units) - 1);
	$bytes/= pow(1000, $pow);
	return round($bytes, $precision) . ' ' . $units[$pow];
}
// returns human readable size in binary bytes (1024)
function sizebinary($bytes, $precision = 0) {
	$units = array(
		'B',
		'KiB',
		'MiB',
		'GiB',
		'TiB'
	);
	$bytes = max($bytes, 0);
	$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
	$pow = min($pow, count($units) - 1);
	$bytes/= pow(1024, $pow);
	return round($bytes, $precision) . ' ' . $units[$pow];
}
// returns true if input conforms to rule pattern; optional modify string
function sanitize($input, $rules = false, &$modify, $maxlen = 0) {
	if (!is_string($rules)) $rules = 'a-zA-Z0-9_-';
	if ($maxlen == 0) $modify = preg_replace('/[^' . $rules . ']/', '', $input);
	else $modify = substr(preg_replace('/[^' . $rules . ']/', '', $input) , 0, $maxlen);
	if ($modify == '') return false;
	elseif ($modify == $input) return true;
	else return false;
}
// format size output for easy handling of data in megabytes
function simpleSize($input) {
	global $log;
	$size = 0;
	if (strpos($input, 'T', 1)) {
		$size = preg_replace("/[^0-9.]/", "", $input);
		$log->LogDebug("preg_replace size: ".$size);
		$size = ($size * (1024 * 1024));
	}
	if (strpos($input, 'M', 1)) {
		$size = preg_replace("/[^0-9.]/", "", $input);
		$log->LogDebug("preg_replace size: ".$size);
	}
	if (strpos($input, 'K', 1)) {
		$size = preg_replace("/[^0-9.]/", "", $input);
		$log->LogDebug("preg_replace size: ".$size);
		$size = ($size / 1024);
	}
	if (strpos($input, 'G', 1)) {
		$size = preg_replace("/[^0-9.]/", "", $input);
		$log->LogDebug("preg_replace size: ".$size);
		$size = ($size * 1024);
	}
	$log->LogDebug("final size: ".$size);
	if ((float)$size > 0) {
		return round($size, 2);
	}
	return false;
}
// Dynamically load server PHP file at runtime (used to dynamically load files that are only sometimes used)
function dynamic_load($dyn_file) {
	$path = './' . $dyn_file;
	include_once ($path);
}
function sync_server_timezone() {
	// sync php timezone  with server timezone
	$result = sudo_execute('date +%Z');
	if ($result['rv'] == 0 && strlen($result['output_str']) > 0) {
		date_default_timezone_set(timezone_name_from_abbr($result['output_str']));
	}
}
function shutdown() {
        
    $output = 'Invocation Type : ';
    if (php_sapi_name() == 'cli') {
    	global $argv;
    	$output .= 'Cli'.PHP_EOL;
    	$command = implode(' ', $argv);
    	$output .= 'Command line : '. $command.PHP_EOL;
    }
    else {
    	$output .= 'Web'.PHP_EOL;
    	$output .= 'REQUEST URI : '.$_SERVER['REQUEST_URI'].PHP_EOL;
    	$output .= 'REQUEST Method : '.$_SERVER['REQUEST_METHOD'].PHP_EOL;
    	if (count($_REQUEST) > 0) {
	    	$output .= 'REQUEST Data : '.PHP_EOL;
	    	foreach ($_REQUEST as $key => $value) {
	    		$output .= $key .' : '.$value.PHP_EOL;
	    	}
    	}
    }

    $output .= 'Exit Type : ';
    $error = error_get_last();
    if ($error['type'] == E_ERROR) {
        // fatal error has occured
    	$output .= 'Fatal Error'.PHP_EOL;

    }
    else {
    	$output .= 'Normal '.PHP_EOL;
    }
    if (is_array($error) && count($error) > 0) {
    	foreach ($error as $key => $value) {
    		$output .= $key.' : '.$value.PHP_EOL;
    	}
    }
    
    if (!class_exists('KLogger')) {
    	include "KLogger.php";
    }
    $path = dirname(__DIR__);
    $log = new KLogger("$path/logs/shutdown.log", KLogger::DEBUG);
    $log->LogDebug($output);
    
}


function log_error( $num, $message, $file, $line, $context = null )
{
    $output = 'Invocation Type : ';
    if (php_sapi_name() == 'cli') {
    	global $argv;
    	$output .= 'Cli'.PHP_EOL;
    	$command = implode(' ', $argv);
    	$output .= 'Command line : '. $command.PHP_EOL;
    }
    else {
    	$output .= 'Web'.PHP_EOL;
    	$output .= 'REQUEST URI : '.$_SERVER['REQUEST_URI'].PHP_EOL;
    	$output .= 'REQUEST Method : '.$_SERVER['REQUEST_METHOD'].PHP_EOL;
    	if (count($_REQUEST) > 0) {
	    	$output .= 'REQUEST Data : '.PHP_EOL;
	    	foreach ($_REQUEST as $key => $value) {
	    		$output .= $key .' : '.$value.PHP_EOL;
	    	}
    	}
    }

    //$output .= 'Exit Type : ';
    
   /* if ($num == E_ERROR) {
        // fatal error has occured
    	$output .= 'Fatal Error'.PHP_EOL;

    }
    else {
    	$output .= 'Normal '.PHP_EOL;
    }
    
    foreach ($error as $key => $value) {
    	$output .= $key.' : '.$value.PHP_EOL;
    }*/
    $output .= "type:$num\nmessage:$message\nfile:$file\nline:$line\n".print_r($context, true);
    if (!class_exists('KLogger')) {
    	include "KLogger.php";
    }
    $path = dirname(__DIR__);
    $log = new KLogger("$path/logs/shutdown.log", KLogger::DEBUG);
    $log->LogDebug($output);
    //error_log($output);
}

//register_shutdown_function( "check_for_fatal" );



?>
