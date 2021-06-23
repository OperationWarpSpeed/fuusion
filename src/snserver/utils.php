<?php
//
// utils.php - SoftNAS(tm) server utility functions
//
//  Copyright (c) SoftNAS Inc.  All Rights Reserved.
//
//
require_once __DIR__."/encrypt.php";
require_once __DIR__."/config.php";
// Normalize
function size_normalize($size, $forceUnits = "G") {
	if ($size == 0) return $size;
	$units = array(
		'B',
		'K',
		'M',
		'G',
		'T',
		'P',
		'E',
		'Z',
		'Y'
	);
	if (!in_array($forceUnits, $units)) {
		$forceUnits = "G";
	}
	$power = 0;
	$unitStr = $forceUnits; // default to units value passed; e.g., "10G"
	foreach ($units as $unit) if ($unit == $unitStr) break;
	else $power++;
	$amount = $size / pow(1024, $power);
	return $amount;
}

// Convert integer unformatted size to formatted size (single character size notation)
function size_formatted($size, $forceUnits = "none", $no_comma = false) {
	$units = array(
		'B',
		'K',
		'M',
		'G',
		'T',
		'P',
		'E',
		'Z',
		'Y'
	);
	$k = 1024.0;
	if ($size > 0) $power = floor(log(floatval($size) , $k));
	else $power = 0;
	if ($forceUnits != "none") {
		$power = 0;
		foreach ($units as $unit) // force to a particular power / units
		if ($unit == $forceUnits) break;
		else $power++;
	}
	if ($no_comma) return number_format($size / pow(1024.0, $power) , 1, '.', '') . $units[$power]; // e.g., 1234.56
	else return number_format($size / pow(1024.0, $power) , 1, '.', ',') . $units[$power]; // e.g., 1,234.56
	
}
// Convert formatted size to unformatted size
function size_unformatted($size, $forceUnits = "none") {
	$units = array(
		'B',
		'K',
		'M',
		'G',
		'T',
		'P',
		'E',
		'Z',
		'Y',
		'xx'
	);
	$val = preg_split('/[0-9,.]+/', $size, -1, PREG_SPLIT_NO_EMPTY);
	$power = 0;
	$unitStr = @$val[0]; // default to units value passed; e.g., "10G"
	if ($forceUnits != "none") $unitStr = $forceUnits; // override and force to a particular unit
	foreach ($units as $unit) if ($unit == $unitStr) break;
	else $power++;
	if ($unit == "xx") // error on input
	{
		$rval[0] = "error";
	} //$unit == "xx"
	else {
		$val = sscanf($size, "%f");
		$val = $val[0];
		$amount = $val * pow(1024, $power);
		$rval[0] = $amount; // full integer size
		$rval[1] = $unit; // abbrev. units
		$rval[2] = $val; // abbrev. numeric value
		
	}
	return $rval;
}
// Convert integer unformatted size to formatted size (two character size notation)
function size_formatted2($size, $forceUnits = "none", $no_comma = false) {
	$units = array(
		'BB',
		'KB',
		'MB',
		'GB',
		'TB',
		'PB',
		'EB',
		'ZB',
		'YB'
	);
	$k = 1024.0;
	if ($size > 0) $power = floor(log(floatval($size) , $k));
	else $power = 0;
	if ($forceUnits != "none") {
		$power = 0;
		foreach ($units as $unit) // force to a particular power / units
		if ($unit == $forceUnits) break;
		else $power++;
	}
	if ($no_comma) return number_format($size / pow(1024.0, $power) , 1, '.', '') . $units[$power]; // e.g., 1234.56
	else return number_format($size / pow(1024.0, $power) , 1, '.', ',') . $units[$power]; // e.g., 1,234.56
	
}
// Convert formatted size to unformatted size
function size_unformatted2($size, $forceUnits = "none") {
	$units = array(
		'BB',
		'KB',
		'MB',
		'GB',
		'TB',
		'PB',
		'EB',
		'ZB',
		'YB',
		'xx'
	);
	$val = preg_split('/[0-9,.]+/', $size, -1, PREG_SPLIT_NO_EMPTY);
	$power = 0;
	$unitStr = "";
	if (isset($val[0])) $unitStr = $val[0]; // default to units value passed; e.g., "10GB"
	if ($forceUnits != "none") $unitStr = $forceUnits; // override and force to a particular unit
	foreach ($units as $unit) if ($unit == $unitStr) break;
	else $power++;
	if ($unit == "xx") // error on input
	{
		$rval[0] = "error";
	} //$unit == "xx"
	else {
		$val = sscanf($size, "%f");
		$val = $val[0];
		$amount = $val * pow(1024, $power);
		$rval[0] = $amount; // full integer size
		$rval[1] = $unit; // abbrev. units
		$rval[2] = $val; // abbrev. numeric value
		
	}
	return $rval;
}
// Format seconds to readable time
function time_formatted($seconds) {
	$units = array(
		'Y' => 31536000,
		'M' => 2592000,
		'd' => 86400,
		'h' => 3600,
		'm' => 60,
		's' => 1
	);
	$seconds = (int)$seconds;
	$time = "";
	foreach ($units as $unit => $val) {
		$x = floor($seconds/$val);
		if ($x > 0) {
			$seconds %= $val;
			$time.= "{$x}$unit ";
		}
	}
	return $time !== "" ? $time : "0s";
}

function time_formatted_ms($miliseconds) {
	$ms = sprintf("%03d", $miliseconds % 1000);
	$seconds = floor($miliseconds/1000);
	$seconds = time_formatted($seconds);
	return str_ireplace("s", ".{$ms}s", $seconds);
}

//
// Clean GET and POST elements passed as arguments
//
function clean($elem) {
	//include dirname(__FILE__).'/Security.php';
	//$securety=new Security();
	//return $securety->xss_clean($elem);
	if (!is_array($elem)) {
		$elem = htmlentities($elem, ENT_QUOTES, "UTF-8");
	} //!is_array( $elem )
	else {
		foreach ($elem as $key => $arcstatsalue) {
			$elem[$key] = clean($arcstatsalue);
		} //$elem as $key => $arcstatsalue
		
	}
	return $elem;
}
function exec_command($cmd) {
	$result = sudo_execute($cmd, false, true);
	return $result;
}
//
// writes an INI file from associative array
//
function write_ini_file($assoc_arr, $path, $has_sections = FALSE) {
	global $log;
	if (!is_object($log)) {
		require_once __DIR__.'/logging.php';
		$log = init_logging(__DIR__.'/../snserv.log');
	}
	$content = "";
	if ($has_sections) {
		foreach ($assoc_arr as $key => $elem) {
			$content.= "[" . $key . "]\n";
			foreach ($elem as $key2 => $elem2) {
				if (is_array($elem2)) {
					for ($i = 0;$i < count($elem2);$i++) {
						$content.= $key2 . "[] = \"" . $elem2[$i] . "\"\n";
					}
				} else if ($elem2 == "") $content.= $key2 . " = \n";
				else $content.= $key2 . " = \"" . $elem2 . "\"\n";
			}
		}
	} else {
		foreach ($assoc_arr as $key => $elem) {
			if (is_array($elem)) {
				for ($i = 0;$i < count($elem);$i++) {
					$content.= $key . "[] = \"" . $elem[$i] . "\"\n";
				}
			} else if ($elem == "") $content.= $key . " = \n";
			else $content.= $key . " = \"" . $elem . "\"\n";
		}
	}
	return safe_file_rewrite($path, $content);
}

/*
 * write JSON configuration
 */
function write_json(array $array, $path, $overridepath = "") {
	global $_config, $log;
	if ($overridepath === '') $fullpath = $_config['proddir']."/config/".$path;
	else $fullpath = $overridepath . $path;
	if (file_exists($fullpath) && !is_writeable($fullpath)) {
		$log->LogError('JSON configuration not writeable: '.$fullpath);
	}
	return file_put_contents($fullpath, json_encode($array, true));
}

/*
 * read JSON configuration
 */
function read_json($path, $overridepath = '') {
	global $_config, $log;
	if ($overridepath == '') $fullpath = $_config['proddir']."/config/" . $path;
	else $fullpath = $overridepath . $path;
	if (!file_exists($fullpath)) {
		$log->LogDebug('JSON configuration does not exist: '.$fullpath);
		return false;
	}
	$raw = file_get_contents($fullpath);
	$decoded = json_decode($raw, true);
	if (is_array($decoded)) {
		return $decoded;
	}
}
//
// Simple INI access functions for Softnas.ini (default)
//
function write_ini($assoc_arr, $path = "softnas.ini", $overridepath = "") {
	global $_config, $log;
	if ($overridepath == "") $fullpath = $_config['proddir']."/config/" . $path;
	else $fullpath = $overridepath . $path;
	if (!is_writeable($fullpath) && file_exists($fullpath)) {
		$log->LogDebug('Configuration INI not writeable: '.$fullpath.' - changing ownership!');
		exec('sudo chown apache: '.$fullpath);
		if (!is_writeable($fullpath)) {
			$log->LogError('Configuration INI '.$fullpath.' still not writeable after changing ownership!');
		}
	}
	$ret = write_ini_file($assoc_arr, $fullpath, true);
	$log->LogDebug('write_ini return '.$ret);
	return $ret;
}
function read_ini($path = "softnas.ini", $overridepath = "") {
	global $_config;
	global $log;

	if ($overridepath == "") $fullpath = $_config['proddir']."/config/" . $path;
	else $fullpath = $overridepath . $path;
	if (!file_exists($fullpath)) {
		if (!empty($log)) $log->LogDebug("read_ini: $fullpath does not exist");
		return false;
	}
	if (!is_readable($fullpath)) {
		sudo_execute("chown apache $fullpath");
	}
	// Clear files status cache to avoid getting stalled content of the file if a write to it was very recent
	clearstatcache();
	$ini_file = new SplFileObject($fullpath);
	if (!$ini_file->isReadable()) {
		if (!empty($log)) $log->LogDebug("read_ini: $fullpath is not readable, changing ownership");
		sudo_execute("chown apache $fullpath");
	}
	if (!empty($log)) $log->LogDebug("read_ini: Opening $fullpath for reading, file size: {$ini_file->getSize()}");
	// retry to read if file size is zero to avoid false zero reads
	$size_retry = 0;
	do {
		if ($ini_file->getSize() <= 0) {
			if (!empty($log)) $log->LogDebug("read_ini: $fullpath is empty, retrying to open it again");
			sleep(1);
			clearstatcache();
			$ini_file = new SplFileObject($fullpath);
			$size_retry++;
		} else {
			break;
		}
	} while ($size_retry < 3);
	// Return an empty array if file size is zero
	if ($ini_file->getSize() <= 0) {
		if (!empty($log)) $log->LogDebug("read_ini: $fullpath is empty, returning an empty array");
		$ini_file = null;
		return [];
	}
	if (!empty($log)) $log->LogDebug("read_ini: Acquiring lock for $fullpath");
	if (!$ini_file->flock(LOCK_SH)) {
		if (!empty($log)) $log->LogError("Unable to acquire lock for $fullpath for reading");
		$ini_file = null;
		return false;
	}
	if (!empty($log)) $log->LogDebug("read_ini: Reading $fullpath");
	$reading_start = time();
	do {
		$ini_content = $ini_file->fread($ini_file->getSize());
		if ($ini_content === FALSE) {
			if (!empty($log)) $log->LogDebug("read_ini: unable to read ini $fullpath. Retrying...");
			// reopen file to update it's parameters
			$ini_file->flock(LOCK_UN);
			$ini_file = null;
			sleep(rand(0,5));
			$ini_file = new SplFileObject($fullpath);
			if (!empty($log)) $log->LogDebug("read_ini: Opening $fullpath for reading, file size: {$ini_file->getSize()}");
			$ini_file->flock(LOCK_SH);
		}
	} while ($ini_content === FALSE && time() - $reading_start < 60); // retry failed read for 1 minutes
	// Last check for the failed read
	if ($ini_content === FALSE) {
		if (!empty($log)) $log->LogError("Unable to read ini $fullpath");
		$ini_file->flock(LOCK_UN);
		$ini_file = null;
		return false;
	}
	if (!empty($log)) $log->LogDebug("read_ini: $fullpath content length: " . strlen($ini_content));
	$rc = parse_ini_string($ini_content, true);
	if (empty($rc)) {
		if (!empty($log)) $log->LogDebug("read_ini: $fullpath is empty");
	}
	if (!empty($log)) $log->LogDebug("read_ini: Releasing lock for $fullpath");
	$ini_file->flock(LOCK_UN);
	if (!empty($log)) $log->LogDebug("read_ini: Closing lock for $fullpath for reading");
	$ini_file = null;
	return $rc;
}

/**
 * Get platform ( VM, amazon, azure, ... )
 *
 * @version Mihajlo 16.10.2014 - code isolated in one function
 */
function get_system_platform() {
	global $_config;
	$cmd = "discovery";

	$currDir = __DIR__ . '/../scripts';

	$result = super_script($cmd, '', $currDir);
	return $result['output_str'];
}

//
// This function generates a hardware ID that uniquely identifies this SoftNAS instance within the customer environment;
// e.g., IP address, EC2 instance ID, MAC address, etc.
//
function get_hardware_id() {
	global $log;
	global $_config;
	$hwid = "";
	$vmplatform = "";
	$ini = read_ini(); // read the INI contents
	$vmplatform = "";
	if ($ini) {
		if (array_key_exists('system', $ini)) {
			$system = $ini['system'];
			$vmplatform = $system['platform'];
		}
	}
	$hwid = "";
	if (isset($system['hwid'])) $hwid = $system['hwid'];
	$cmd = "discovery";
	$result = super_script($cmd, '');
	if ($result['rv'] == 0) {
		$platform = $result['output_str'];
			if ($platform != $vmplatform) {
				//$vmplatform = $platform;
				$system['platform'] = $platform;
				$ini['system'] = $system;
				write_ini($ini); // update with platform ID and type
			}
	}
	if ($system['platform'] == "amazon") // vmplatform == "vmware" or "" (default - IP address)
	{
		// Dynamically determine instance ID
		$script = "getec2instanceid";
		$result = super_script($script);
		if ($result['rv'] == 0) {
			if ($result['output_str'] != "") // it's amazon EC2
			{
				$old_hwid = $hwid;
				$hwid = $result['output_str']; // EC2 instance ID
				if ($vmplatform != "amazon" || $hwid != $old_hwid) // update platform information
				{
					$system['platform'] = "amazon";
					$system['hwid'] = $hwid;
					$vmplatform = $system['platform'];
					$ini['system'] = $system;
					$rc = write_ini($ini); // update with platform ID and type
					
				}
			}
		}
	}
	if ($system['platform'] != "amazon") // vmplatform == "vmware" or "" (default - IP address)
	{
		global $_config;
		$old_hwid = $hwid;
		// Get primary IP address (default method)
		$script = "getinetaddr";
		$result = super_script($script);
		if ($result['rv'] == 0) {
			foreach ($result['output_arr'] as $arrkey => $aParam) {
				if ($arrkey == "0") {
					preg_match('/addr\:(.*\s)/', $aParam, $preg_out);
					$chunks = preg_split('/\s/', $preg_out[0], -1, PREG_SPLIT_NO_EMPTY);
					$chunks = preg_split('/:/', $chunks[0], -1, PREG_SPLIT_NO_EMPTY);
					$hwid = $chunks[1];
				}
			}
		}
		if ($hwid != $old_hwid) // update platform information
		{
			$system['hwid'] = $hwid;
			$ini['system'] = $system;
			$rc = write_ini($ini); // update with platform ID and type
			
		}
	}
	return $hwid;
}

function cidrSearch($ip, $range) {
	list ($subnet, $bits) = explode('/', $range);
	$ip = ip2long($ip);
	$subnet = ip2long($subnet);
	$mask = -1 << (32 - $bits);
	$subnet &= $mask; # nb: in case the supplied subnet wasn't correctly aligned
	return ($ip & $mask) == $subnet;
}

function cidr2range($cidr) {
        $range = array();
        $cidr = explode('/', $cidr);
        $range[0] = long2ip((ip2long($cidr[0])) & ((-1 << (32 - (int)$cidr[1]))));
        $range[1] = long2ip((ip2long($cidr[0])) + pow(2, (32 - (int)$cidr[1])) - 2);
        return $range;
}

//
// Parses the local interfaces and return interface name (e.g., eth0) corresponding to IP address passed
//
function find_vip_interface($vip) {
	global $log;
	global $_config;
	// Parse 'interface' command response into lines
	// Process interface lines to extract each interface entry
	// Determine if there's an interface with an IP address which corresponds to the VIP
	// If corresponding VIP found, set ethXX to its interface ID; e.g., $ethXX = "eth0"
	// Formulate the interface command
	$netdevices = glob('/sys/class/net/*');
	foreach($netdevices as $id => $device) {
		$cmd = 'ip addr show dev '.$device.' | grep inet | awk \'{ print $2; }\'';
		$result = sudo_execute($cmd);
		if ($result['rv'] != 0) {
			$errorMsg = "interface command failure. Details: " . $result['output_str'];
			$log->LogError($errorMsg);
		}
		$range = cidr2range($result['output_str']);
		$isInRange = cidrSearch($vip, $range);
		if ($isInRange) {
			$short_device = str_replace('/sys/class/net/', '', $device);
			return $short_device;
		}
	}
	return ""; // interface not found, return empty string
}
//
// Returns total system memory in MB
//
function totalMB() {
	$cmd = "memtotal"; // get total memory
	$result = super_script($cmd);
	if ($result['rv'] != 0) {
		return false;
	}
	$outStr = $result['output_str'];
	$chunks = explode(" ", $outStr);
	$totalMB = $chunks[0];
	return $totalMB;
}
function create_uuid() {
	$uuid = "";
	// Generate unique HA id - cat /proc/sys/kernel/random/uuid
	$cmd = "cat /proc/sys/kernel/random/uuid"; // issue hacmd.com job
	$result = sudo_execute($cmd);
	if ($result['rv'] == 0) {
		$uuid = $result['output_str']; // Example output; f3d2d876-f8e0-4a86-9197-aa0c7524b993
		
	}
	return $uuid;
}
//
// Quickly generate a strong key, given a password
//
function quick_keygen($passwd) {
	//We use mt_rand() instead of rand() because it is better for generating random numbers.
	//We use 'true' to get a longer string.
	//See http://www.php.net/mt_rand for a precise description of the function and more examples.
	//  $uniqid = uniqid(mt_rand(), true);
	$key = md5($passwd);
	return $key;
}
//
// Given a password and plaintext, quickly encrypt it
//
function quick_encrypt($passwd, $plaintext) {
	$key = quick_keygen($passwd);
	$crypto = new Encrypt();
	$crypto->set_key($key);
	$crypto->use_xor_crypto(); // creates shorter ciphertexts
	$ciphertext = $crypto->encode($plaintext);
	return $ciphertext;
}
//
// Given password and ciphertext encrypted using quick_encrypt(), decrypt and return original plaintext
//
function quick_decrypt($passwd, $ciphertext) {
	$key = quick_keygen($passwd);
	$crypto = new Encrypt();
	$crypto->use_xor_crypto();
	$crypto->set_key($key);
	$plaintext = $crypto->decode($ciphertext);
	return $plaintext;
}
//
// Given an activation code (original plain text or new encrypted form), decode it into a usable activation code number
//
function decode_actcode($password, $activationCode) {
	$isEncrypted = substr($activationCode, -1) == "="; // if it ends with equal sign, it is encrypted activation code
	if ($isEncrypted) {
		$activationCode = quick_decrypt($password, $activationCode);
	}
	return $activationCode;
}
//
// Given an activation code (encrypted or plain), return encrypted form (convert as needed)
//
function encode_actcode($password, $activationCode) {
	$isEncrypted = substr($activationCode, -1) == "="; // if it ends with equal sign, it is encrypted activation code
	if ($isEncrypted) {
		return $activationCode; // it's already encrypted, so just return the encrypted code
		
	}
	// it's plain text, so encrypt it
	$cipher = quick_encrypt($password, $activationCode);
	return $cipher;
}
function checkPid($pid) {
	global $_config;
	// create our system command
	$cmd = $_config['systemcmd']['sudo'] . ' TERM=dumb ' . "ps $pid";
	// run the system command and assign output to a variable ($output)
	exec($cmd, $output, $result);
	// check the number of lines that were returned
	if (count($output) >= 2) {
		// the process is still alive
		return true;
	}
	// the process is dead
	return false;
}

/**
 *
 *	read aws_iam.ini and decrypt aws access keys
 */
function read_aws_iam_config() {
	set_encryption_key();
	global $log;
	$IAM_KeyConfig = "aws_iam.ini";
	$aws_iam = read_ini($IAM_KeyConfig); // read the S3 configuration file
	//$log->LogDebug( "reading  $IAM_KeyConfig" );
	if ($aws_iam) {
		//$log->LogDebug( "$IAM_KeyConfig content : ". print_r($aws_iam, true) );
		//if (!isset($aws_iam['obfuscated'])) $aws_iam['obfuscated'] = 'false';
		if ($aws_iam['AWSAccessKeyId'] != '' && $aws_iam['AWSSecretKey'] != '') # test 1
		{
			//$log->LogDebug( " # test 1" );
			if (isset($aws_iam['obfuscated']) && $aws_iam['obfuscated'] === 'true') # test 2
			{
				//$log->LogDebug( " # test 2" );
				//$log->LogDebug( " # ENCRYPTION_KEY :".ENCRYPTION_KEY );
				$aws_iam['AWSAccessKeyId'] = quick_decrypt(ENCRYPTION_KEY, $aws_iam['AWSAccessKeyId']);
				$aws_iam['AWSSecretKey'] = quick_decrypt(ENCRYPTION_KEY, $aws_iam['AWSSecretKey']);
				//$log->LogDebug( "aws_iam content : ". print_r($aws_iam, true) );
				//$log->LogDebug( " end # test 2" );
				
			} else
			# test 3
			{
				//$log->LogDebug( " # test 3" );
				$awsAccessKey = $aws_iam['AWSAccessKeyId'];
				$awsSecretKey = $aws_iam['AWSSecretKey'];
				$aws_iam['AWSAccessKeyId'] = quick_encrypt(ENCRYPTION_KEY, $awsAccessKey);
				$aws_iam['AWSSecretKey'] = quick_encrypt(ENCRYPTION_KEY, $awsSecretKey);
				$aws_iam['obfuscated'] = 'true';
				//$log->LogDebug( "aws_iam content 1: ". print_r($aws_iam, true) );
				$rc = write_ini_file($aws_iam, $IAM_KeyConfig, false);
				if (!$rc) {
					$errorMsg = "read_aws_iam_config: Cannot write to iam disk configuration file: $IAM_KeyConfig";
					$log->LogError($errorMsg);
				}
				$aws_iam['AWSAccessKeyId'] = $awsAccessKey;
				$aws_iam['AWSSecretKey'] = $awsSecretKey;
				//$log->LogDebug( "aws_iam content 2: ". print_r($aws_iam, true) );
				//$log->LogDebug( " end # test 3" );
				
			}
		}
	}
	return $aws_iam;
}
/**
 *
 * encrypt aws access keys and override aws_iam.ini
 * @param array $aws_iam
 */
function write_aws_iam_config($aws_iam) {
	set_encryption_key();
	$IAM_KeyConfig = "../config/aws_iam.ini";
	if (isset($aws_iam['AWSAccessKeyId']) && $aws_iam['AWSAccessKeyId'] != '') {
		$awsAccessKey = $aws_iam['AWSAccessKeyId'];
		$aws_iam['AWSAccessKeyId'] = quick_encrypt(ENCRYPTION_KEY, $aws_iam['AWSAccessKeyId']);
	}
	if (isset($aws_iam['AWSSecretKey']) && $aws_iam['AWSSecretKey'] != '') {
		$awsSecretKey = $aws_iam['AWSSecretKey'];
		$aws_iam['AWSSecretKey'] = quick_encrypt(ENCRYPTION_KEY, $aws_iam['AWSSecretKey']);
	}
	$aws_iam['obfuscated'] = 'true';
	if (!file_exists($IAM_KeyConfig)) {
		$mask = umask(0007);
	}
	$rc = write_ini_file($aws_iam, $IAM_KeyConfig, false);
	if (isset($mask)) {
		umask($mask);
	}
	if (isset($aws_iam['AWSAccessKeyId']) && $aws_iam['AWSAccessKeyId'] != '') {
		$aws_iam['AWSAccessKeyId'] = $awsAccessKey;
	}
	if (isset($aws_iam['AWSSecretKey']) && $aws_iam['AWSSecretKey'] != '') {
		$aws_iam['AWSSecretKey'] = $awsSecretKey;
	}
	return $rc;
}

function is_numeric_between($number, $from, $to) {
	return is_numeric($number) && $number >= $from && $number <= $to;
}

function write_shell_config_ini($assoc_arr, $path) {
	global $log;
	$content = "";
	foreach ($assoc_arr as $key => $elem) {
		if ($elem == "") $content.= $key . "=\n";
		else $content.= $key . "=\"" . $elem . "\"\n";
	}
	if (!$handle = fopen($path, 'w')) {
		return false;
	}
	if (flock($handle, LOCK_EX)) {
		if (!fwrite($handle, $content)) {
			flock($handle, LOCK_UN);
			fclose($handle);
			return false;
		}
		fflush($handle); // 1-13-2014 rgb   ensure all data is written before releasing lock
		flock($handle, LOCK_UN);
	}
	fclose($handle);
	return true;
}

/**
 * Safely put content into a file with file locking mechanism
 * @param string $fileName - file path
 * @param string $dataToSave - data to put in file
 * @return bool - true if file has been written, false on failure
 */
function safe_file_rewrite($fileName, $dataToSave) {
	global $log;
	if (!$log) {
		require_once 'KLogger.php';
		require_once 'logging.php';
		$log = init_logging();
	}
	if (file_exists($fileName) && !is_writable($fileName)) {
		sudo_execute("chown apache $fileName");
	}
	$log->LogDebug("safe_file_rewrite: Opening $fileName for writing");
	// Open with 'c' to prevent possible truncation before the file lock is acquired
	$ini_file = new SplFileObject($fileName, 'c');
	$log->LogDebug("safe_file_rewrite: Acquiring lock for $fileName");
	if (!$ini_file->flock(LOCK_EX)) {
		$log->LogError("Unable to acquire lock for $fileName for writing");
		$ini_file = null;
	}
	$log->LogDebug("safe_file_rewrite: Writing data to $fileName");
	$write_start_time = time();
	do {
		// Truncate file manually to write new data to it
		$ini_file->ftruncate(0);
		$file_written = $ini_file->fwrite($dataToSave);
		if ($file_written === 0) {
			$log->LogDebug("safe_file_rewrite: Data was not written to $fileName, retrying...");
			$ini_file->flock(LOCK_UN);
			$ini_file = null;
			sleep(rand(0, 5));
			$ini_file = new SplFileObject($fileName, 'c');
			$ini_file->flock(LOCK_EX);
			$ini_file->ftruncate(0);
		}
	} while ($file_written === 0 && time() - $write_start_time < 60); // retry failed data write for 1 minutes
	// Last check if file has been written
	if ($file_written === FALSE) {
		$ini_file->flock(LOCK_UN);
		$ini_file = null;
		$log->LogError("Unable to write data to $fileName");
		return false;
	}
	$log->LogDebug("safe_file_rewrite: $file_written bytes were written. Releasing lock for $fileName");
	$ini_file->fflush();
	$ini_file->flock(LOCK_UN);
	$log->LogDebug("safe_file_rewrite: Closing $fileName for writing");
	$ini_file = null;
	return true;
}

function set_encryption_key() {
	if (!defined('ENCRYPTION_KEY')) {
		$ini = read_ini('login.ini', '/var/www/softnas/config/'); // reads the config file into PHP associative array
		define('ENCRYPTION_KEY', $ini['login']['encryption_key'] . 'S0ftNa5ky');
	}
}

function read_json_config($path, $assoc = false) {
	
	if (!file_exists($path)) {
		return json_decode('{}', $assoc);
	} else {
		$result = sudo_execute("cat $path"); 
		$contents = $result['output_str'];
		$contents_json = json_decode($contents, $assoc);
		return $contents_json;
	}
}

function write_json_config($path, $config) {
	$config_str = pretty_print_json(json_encode($config));
	
	$tmpFileName = tempnam('/tmp', 'tmp_');
	$result = file_put_contents($tmpFileName, $config_str);
	if($result === false) {
		return "write_json_config: Error while saving config file to /tmp";
	}
	$result = executeCmd("mv $tmpFileName $path");
	if ($result['return_value'] !== 0) {
		return "write_json_config: Error while saving config file";
	}
	return true;
}

function get_masks() {
    return array(
        "●●●●●●●●●●●●●●●●●●●●●●●●●●●●●●",
        "••••••••••••••••••••••••••••••",
        "\u25cf\u25cf\u25cf\u25cf\u25cf\u25cf\u25cf\u25cf\u25cf\u25cf\u25cf\u25cf\u25cf\u25cf\u25cf\u25cf\u25cf\u25cf\u25cf\u25cf\u25cf\u25cf\u25cf\u25cf\u25cf\u25cf\u25cf\u25cf\u25cf\u25cf",
        "\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022",
        "&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
    );
}

function get_default_mask() {
    $masks = get_masks();
    return $masks[0];
}

function is_mask($value) {
    $masks = get_masks();
    return in_array($value, $masks);
}

function pretty_print_json($json) {
	$result = '';
	$level = 0;
	$in_quotes = false;
	$in_escape = false;
	$ends_line_level = NULL;
	$json_length = strlen($json);
	
	for( $i = 0; $i < $json_length; $i++ ) {
		$char = $json[$i];
		$new_line_level = NULL;
		$post = "";
		if( $ends_line_level !== NULL ) {
			$new_line_level = $ends_line_level;
			$ends_line_level = NULL;
		}
		if ( $in_escape ) {
			$in_escape = false;
		} else if( $char === '"' ) {
			$in_quotes = !$in_quotes;
		} else if( ! $in_quotes ) {
			switch( $char ) {
				case '}': case ']':
					$level--;
					$ends_line_level = NULL;
					$new_line_level = $level;
					break;
					
				case '{': case '[':
					$level++;
				case ',':
					$ends_line_level = $level;
					break;
					
				case ':':
					$post = " ";
					break;
					
				case " ": case "\t": case "\n": case "\r":
					$char = "";
					$ends_line_level = $new_line_level;
					$new_line_level = NULL;
					break;
			}
		} else if ( $char === '\\' ) {
			$in_escape = true;
		}
		if( $new_line_level !== NULL ) {
			$result .= "\n".str_repeat( "\t", $new_line_level );
		}
		$result .= $char.$post;
	}
	
	return $result;
}

/**
 * Remove Invisible Characters
 *
 * This prevents sandwiching null characters
 * between ascii characters, like Java\0script.
 *
 * @param   string
 * @param   bool
 * @return  string
 */
function remove_invisible_characters($str, $url_encoded = TRUE) {
	$non_displayables = array();
	// every control character except newline (dec 10),
	// carriage return (dec 13) and horizontal tab (dec 09)
	if ($url_encoded) {
		$non_displayables[] = '/%0[0-8bcef]/'; // url encoded 00-08, 11, 12, 14, 15
		$non_displayables[] = '/%1[0-9a-f]/'; // url encoded 16-31
		
	}
	$non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S'; // 00-08, 11, 12, 14-31, 127
	do {
		$str = preg_replace($non_displayables, '', $str, -1, $count);
	} while ($count);
	return $str;
}

/**
 * Is cli
 *
 * Test to see if a request was made from the command line
 *
 * @return  bool
 */
function is_cli() {
	return (php_sapi_name() === 'cli' OR defined('STDIN'));
}

function read_csv_file($filename) {
	$return = array();
	if (($handle = fopen($filename, "r")) !== FALSE) {
		while (($data = fgetcsv($handle)) !== FALSE) {
			$return = array_merge($return, $data);
		}
		fclose($handle);
	}
	return $return;
}

function isProcessRunning($pidFile = '') {
	global $log;
	if (!file_exists($pidFile) || !is_file($pidFile)) return false;
	$pid = intval(file_get_contents($pidFile));
	return file_exists("/proc/$pid");
}

function trim_value(&$value) {
	$value = trim($value);
}

/**
 * Determines if the current version of PHP is greater then the supplied value
 *
 * Since there are a few places where we conditionally test for PHP > 5
 * we'll set a static variable.
 *
 * @access	public
 * @param	string
 * @return	bool	TRUE if the current version is $version or higher
 */
function is_php($version = '5.0.0') {
	static $_is_php;
	$version = (string)$version;
	if (!isset($_is_php[$version])) {
		$_is_php[$version] = (version_compare(PHP_VERSION, $version) < 0) ? FALSE : TRUE;
	}
	return $_is_php[$version];
}

function iam_check($role = null) {
	global $_CLEAN; // clean POST parameters
	global $_config;
	global $log;
	$log = init_logging(__DIR__.'/../logs/snserv.log');
	if (isset($_config['cache']['iam_info'])) return $_config['cache']['iam_info'];
	$return = FALSE;
	$SCRIPTS = $_config['path']['scripts'];
	$script = 'iam_check.sh';
	$commandLine = __DIR__ . '/../scripts/' . $script;
	if ($role !== NULL) {
		// #5607 - allow override of IAM role name
		$commandLine .= ' '.$role;
	}
	$result = sudo_execute($commandLine);
	if ($result['rv'] == 0) {
		$return = json_decode($result['output_str'], true);
	}
	$_config['cache']['iam_info'] = $return;
	return $return;
}

/**
 * Check from cookie if some path already has fresh files after update,
 * and updates the cookie
 *
 * @author Mihajlo 17.apr.2015
 * @version Mihajlo 25.apr.2015 - disable cache by url as needed
 * @version Mihajlo 12.jun.2015 - disable loading module if update process is active
 *
 * @param string $file_path
 *
 * @return boolean
 */
function is_updated($file_path = null) {
	$applet = "default";
	if ($file_path) {
		$path_arr = explode('/', $file_path);
		$applet = $path_arr[count($path_arr) - 2];
	}
	// If update is active allow access only to update module to see the progress and details
	if ($applet != "update" && $applet != "storagecenter" && is_update_process_active()) {
		header("Location: /buurst/fuusion/");
	}
	if (isset($_GET['nocache'])) {
		return false; // Purposely disable cache (  /applets/example/?nocache  )
		
	}
	require_once "config.php";
	require_once "common.php";
	global $_config;
	$cookie_name = "softnas_version_in_applet_".$applet."_port".$_SERVER['SERVER_PORT'];
	$result = sudo_execute("cat " . $_config['proddir'] . "/version");
	$version = trim($result['output_arr'][0]);
	if (!isset($_COOKIE[$cookie_name]) || $_COOKIE[$cookie_name] != $version) {
		setcookie($cookie_name, $version, time() + (86400 * 365) , "/", $_SERVER['HTTP_HOST'], true, true);
		return false;
	} else {
		return true;
	}
}

function load_quick_help($file_path = null, $quick_help_id = null) {
	$applet = "default";
	if ($file_path) {
		$path_arr = explode('/', $file_path);
		$applet = $path_arr[count($path_arr) - 2];
	}
	
	require_once(__DIR__."/quick_help.php");
	get_quick_help_data($applet, $quick_help_id);
}

function get_applet_data($file_path = null, $quick_help_id = null) {
	$applet = "default";
	
	if ($file_path) {
		$path_arr = explode('/', $file_path);
		if (count($path_arr) > 1) {
			$applet = $path_arr[count($path_arr) - 2];
		} else {
			$applet = $file_path;
		}
	}

	$data = array();
	
	require_once(__DIR__."/quick_help.php");
	$data['quick_help'] = get_quick_help_array($applet, $quick_help_id);
	
	return $data;
}

/**
 * Read /etc/fstab
 *
 * @return array of mountpoints and options
 */
function get_fstab() {
	global $errorMsg;
	global $log;
	$return = array();
	$commandLine = "cat /etc/fstab";
	$result = sudo_execute($commandLine);
	$fstab_raw = "";
	if ($result['rv'] == 0) {
		$fstab_raw = $result['output_str'];
		foreach (preg_split("/((\r?\n)|(\r\n?))/", $fstab_raw) as $fstab_line) {
			if (!ctype_space($fstab_line) && $fstab_line != '' && 0 !== strpos($fstab_line, '#')) {
				$fstab_line_parts = preg_split('/\s+/', $fstab_line);
				$fs_mntops = explode(",", $fstab_line_parts[3]);
				$return[$fstab_line_parts[0]] = array(
					"fs_file" => $fstab_line_parts[1],
					"fs_vfstype" => $fstab_line_parts[2],
					"fs_mntops" => $fs_mntops,
					"fs_freq" => $fstab_line_parts[4],
					"fs_passno" => $fstab_line_parts[5],
				);
			}
		}
		return $return;
	} else {
		$errorMsg = "Unable to read /etc/fstab";
		$log->LogError($errorMsg);
		return null;
	}
}

/**
 * Add new entry to fstab and mount newly added device
 *
 * @param string $fs_dev - device path
 * @param string $fs_mpoint - mount point path
 * @param string $fs_type - filesystem type
 * @param string $fs_mntops - mount options
 * @param int $fs_freq - used by dump. 0 - no, 1 - yes.
 * @param int $fs_passno - fscheck order number
 */
function add_fstab($fs_dev, $fs_mpoint, $fs_type, $fs_mntops = "defaults", $fs_freq = 0, $fs_passno = 0) {
	global $errorMsg;
	global $log;
	# backup /etc/fstab
	$commandLine = "cp -f /etc/fstab /etc/fstab.BAK";
	$result = sudo_execute($commandLine);
	if ($result['rv'] != 0) {
		$errorMsg = "Unable to backup /etc/fstab";
		$log->LogError($errorMsg);
	} else {
		# prepare new fstab
		$fstab = get_fstab();
		$new_fstab_line = sprintf('%s %s %s %s %d %d', $fs_dev, $fs_mpoint, $fs_type, $fs_mntops, $fs_freq, $fs_passno);
		$new_fstab = "";
		foreach ($fstab as $key => $value) {
			$new_fstab.= sprintf('%s %s %s %s %s %s' . PHP_EOL, $key, $value['fs_file'], $value['fs_vfstype'], implode(",", $value['fs_mntops']) , $value['fs_freq'], $value['fs_passno']);
		}
		$new_fstab.= $new_fstab_line . PHP_EOL;
		# check if new mount point exists
		if (!file_exists($fs_mpoint)) {
			$commandLine = "mkdir -p " . $fs_mpoint;
			$result = sudo_execute($commandLine);
			if ($result['rv'] != 0) {
				$errorMsg = "Unable to create directory " . $fs_mpoint;
				$log->LogError($errorMsg);
			}
		}
		# write new fstab
		$new_fstab_file = fopen("/tmp/fstab.new", "w");
		fwrite($new_fstab_file, $new_fstab);
		fclose($new_fstab_file);
		$commandLine = "mv -f /tmp/fstab.new /etc/fstab";
		$result = sudo_execute($commandLine);
		if ($result['rv'] != 0) {
			$errorMsg = "Unable to write /etc/fstab";
			$log->LogError($errorMsg);
		}
		# mount new device
		if (file_exists($fs_dev)) {
			$commandLine = "mount " . $fs_dev;
			$result = sudo_execute($commandLine);
			if ($result['rv'] != 0) {
				$errorMsg = "Unable to mount device " . $fs_dev;
				$log->LogError($errorMsg);
			}
		}
	}
}

/**
 * Remove device from fstab and unmount it
 *
 * @param string $dev - device path
 */
function remove_fstab($dev) {
	global $errorMsg;
	global $log;
	# backup /etc/fstab
	$commandLine = "cp -f /etc/fstab /etc/fstab.BAK";
	$result = sudo_execute($commandLine);
	if ($result['rv'] != 0) {
		$errorMsg = "Unable to backup /etc/fstab";
		$log->LogError($errorMsg);
	} else {
		# prepare new fstab
		$fstab = get_fstab();
		if ($fstab && array_key_exists($dev, $fstab)) {
			# umount removed device
			$commandLine = "umount " . $dev . "/";
			$result = sudo_execute($commandLine);
			if ($result['rv'] != 0) {
				$errorMsg = "Unable to umount device " . $dev;
				$log->LogError($errorMsg);
			}
			unset($fstab[$dev]);
			$new_fstab = "";
			foreach ($fstab as $key => $value) {
				$new_fstab.= sprintf('%s %s %s %s %s %s' . PHP_EOL, $key, $value['fs_file'], $value['fs_vfstype'], implode(",", $value['fs_mntops']) , $value['fs_freq'], $value['fs_passno']);
			}
			# write new fstab
			$new_fstab_file = fopen("/tmp/fstab.new", "w");
			fwrite($new_fstab_file, $new_fstab);
			fclose($new_fstab_file);
			$commandLine = "mv -f /tmp/fstab.new /etc/fstab";
			$result = sudo_execute($commandLine);
			if ($result['rv'] != 0) {
				$errorMsg = "Unable to write /etc/fstab";
				$log->LogError($errorMsg);
			}
		}
	}
}

/**
 * Add NFS pseudo filesystem if not already exists.
 *
 * @param string $path - path to pseudo filesystem
 */
function add_nfs_pseudofs($path) {
	global $errorMsg;
	global $log;
	$commandLine = "cat /etc/exports";
	$result = sudo_execute($commandLine);
	if ($result['rv'] == 0) {
		foreach ($result['output_arr'] as $exports_line) {
			if (strpos($exports_line, $path) !== FALSE) {
				return;
			}
		}
		$exports_file = fopen("/tmp/exports.new", "w");
		fwrite($exports_file, $result['output_str'] . PHP_EOL);
		fwrite($exports_file, $path . " *(ro,fsid=0)" . PHP_EOL);
		$commandLine = "mv -f /tmp/exports.new /etc/exports";
		$result = sudo_execute($commandLine);
		if ($result['rv'] != 0) {
			$errorMsg = "Unable to write to /etc/exports";
			$log->LogError($errorMsg);
		}
	} else {
		$errorMsg = "Unable to read /etc/exports";
		$log->LogError($errorMsg);
	}
}

/**
 * Check some processes to determine if update process is active
 *
 * @author Mihajlo 10.jun.2015
 *
 * @param boolean $strict_check - ignore option' Proceed anyway'
 *
 * @return boolean
 */
function is_update_process_active($strict_check = false) {
	
	//if(session_id() == '') {
	if(!isset($_SESSION)){ // fix warnings and notices
	    session_name('PHPSESSID_port'.$_SERVER['SERVER_PORT']);
	    session_start();
	}
	
	unset($_SESSION['update_in_progress']);
	/*if(!$strict_check){
			if(isset($_REQUEST['proceed_anyway'])){
				$_SESSION['update_proceed_anyway'] = "true";
			}
			
			if(isset($_SESSION['update_proceed_anyway'])){
				return false;
			}
		}*/
	//$file_update = file_get_contents("/tmp/update_in_progress");
	$result = array();
	if (isset($_REQUEST['proceed_anyway'])) {
		exec("sudo rm -f /tmp/update_in_progress", $result);
		return false;
	}
	$file_update = '';
	if (file_exists("/tmp/update_in_progress")) {
		exec("sudo cat /tmp/update_in_progress", $result);
		$file_update = $result[0];
	}
	if (trim($file_update) == "true") {
		$result = array();
		exec("sudo stat -c '%Y' /tmp/update_in_progress", $result);
		$t_now = time();
		$t_update = (int)($result[0]);
		// if file shows 'true' but older than 10 hours
		if ($t_update && ($t_now - $t_update) / 3600 > 10) {
			return false;
		}
		$_SESSION['update_in_progress'] = "true";
		session_commit();
		return true;
	} else {
		return false;
	}
	/*require_once "config.php";
		require_once "common.php";
		
		$result = sudo_execute("ps -eLf | grep [s]oftnas_update_");
		$result2 = sudo_execute("if [ -f /var/run/yum.pid ]; then echo yum; fi");
		$result3 = sudo_execute("ps aux | grep '/usr/sbin/dkms build' | grep -v grep");
		if( 
			(count($result['output_arr']) > 0 && stripos($result['output_str'], "softnas_update_") !== false ) ||
			(count($result2['output_arr']) > 0 && trim($result2['output_str']) == "yum"  )  ||
			(count($result3['output_arr']) > 0 && stripos($result3['output_str'], "/usr/sbin/dkms build") !== false ) 
		) {
			return true;
		}else{
			return false;
		}*/
}

/**
 * Remove build number if there is, to compare versions
 * (to avoid wrong comparison like 3.4.8.1 <-> 3.4.8.521)
 * 
 * @author Mihajlo 03.okt.2016
 * 
 * @param string $version
 * 
 * @return string
 */
function version_remove_build_number($version) {
	$version_arr = explode(".", $version);
	$last = array_pop($version_arr);
	$last_number = (int)$last;
	if ($last_number >= 100) {
		return implode(".", $version_arr);
	} else {
		return $version;
	}
}

/**
 * Determine which version is newer
 * 
 * @author Mihajlo 03.okt.2016
 * 
 * @param string $version1
 * @param string $version2
 * 
 * @return int
 */
function update_version_compare($version1, $version2) {
	global $log;
	if (is_numeric($version1)) {
		$log->LogDebug('update_version_compare: doing integer comparison for '.$version1.' and '.$version2);
		if (is_version_string($version2)) {
			return true; // assume 1536322510 is more fresh then 4.1.0.4445
		}
		return ($version1 > $version2);
	}
	$version1_short = version_remove_build_number($version1);
	$version2_short = version_remove_build_number($version2);
	if ($version1_short !== $version2_short) {
		return strcmp($version1_short, $version2_short);
	} else {
		// if 3.4.9.1 == 3.4.9.1 compare 3.4.9.1.556 vs 3.4.9.1.588
		return strcmp($version1, $version2);
	}
}

/*
 * filter a string for version numbers (4.1, 4.1.1, 1234567890)
 */
function is_version_string($version_string) {
	$result = filter_var($version_string, FILTER_VALIDATE_REGEXP,
		array('options' => array('regexp'=>"/^([0-9]+)(\.[0-9]+)+/")));
	if (!empty($result)) {
		return true;
	}
	return false;
}


/**
 * Check what kind of update is currnetly on instance
 * based on last created log of previous update
 * 
 * @author Mihajlo 03.okt.2016
 * 
 * @return string (dev, test, custom, normal)
 */
function get_installed_update_type() {
	$file = "/tmp/softnas-update.log";
	if (!file_exists($file)) {
		return "normal";
	}
	
	$result = sudo_execute("cat $file | grep 'update in progress'");
	
	if (stripos($result['output_str'], "devupdate in progress") !== false) {
		return "dev";
	}
	if (stripos($result['output_str'], "testupdate in progress") !== false) {
		return "test";
	}
	if (stripos($result['output_str'], "custom update in progress") !== false) {
		return "custom";
	}
	
	return "normal";
}

/**
 * Helper function for userdata.php
 * 
 * @author Mihajlo 28.Aug.2015
 * 
 * @param string $key - name of entry
 * @param data $val - value of entry
 * @param int $expire - how long value will exists
 * 
 * @return value if $val not specified, or else boolean result of setting value
 */
function db_session($key, $val = null, $expire = 0, $require_login = true){
	if(!$key){
		return false;
	}

	require_once("userdata.php");
	global $userdata;
	global $db;
	$log = init_logging(__DIR__.'/../logs/userdata.log');
	$log->LogDebug('db_session('.$key.', '.$val.', '.$expire.', '.var_export($require_login, true).'):');
	if(!isset($userdata) && isset($_SESSION['USERNAME'])){
		$log->LogDebug('Creating userdata object with SESSION username '.$_SESSION['USERNAME']);
		$userdata = new user_data($db, $_SESSION['USERNAME']);
	} elseif (!isset($userdata) && !isset($_SESSION['USERNAME']) && $require_login === false) {
		// use client IP instead of username when we're not logged in
		$log->LogDebug('Creating userdata object without session username for '.$_SERVER['REMOTE_ADDR']);
		global $_CLEAN;
		$log->LogDebug('Other info: '.var_export($_CLEAN, true));
		$userdata = new user_data($db, $_SERVER['REMOTE_ADDR']);
	} elseif (!isset($userdata) && !isset($_SESSION['USERNAME'])) {
		$log->LogError('No userdata, no username, require login: '.var_export($require_login, true));
		return false;
	} elseif (isset($userdata)) {
		$log->LogDebug('userdata object already created');
	} else {
		$log->LogError('Reached undefined position in db_session()');
	}
	$output = NULL;
	if($val === null){
		$output = $userdata->select($key);
	}else{
		$old_val = $userdata->select($key);
		if($old_val != ''){
			$output = $userdata->update($key, $val, $expire);
		}else{
			$output = $userdata->insert($key, $val, $expire);
		}
	}
	$log->LogDebug('output: '.var_export($output, true));
	return $output;
}

/**
 * Check if request is for getting data for software updates
 * 
 * @author Mihajlo 08.Sep.2015
 * 
 * @return boolean
 */
function is_update_request(){
	
	$opcode = isset($_REQUEST['opcode']) ? $_REQUEST['opcode'] : "";
	if($opcode == "statusupdate" || $opcode == "checkupdate" ||
		$opcode == "get_update_log" || $opcode == "get_update_progress" ||
		$opcode == "gettingstarted_update_get_version" || $opcode == "gettingstarted_update_get_progress"){
		
		$host = $_SERVER['HTTP_HOST'];
		$server_name = $_SERVER['SERVER_NAME'];
		$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "";
		$update_url_begin = "https://$host/buurst/applets/update";
		
		if(stripos($referer, $update_url_begin) === 0 ||         // <- https request 
		  $host == 'localhost' || $server_name == 'localhost' ){ // <- softnas-cmd api
			return true;
		}
	}
	
	return false;
}

/**
 * Check if machine needs to reboot
 * 
 * @author Mihajlo 26.Jan.2016
 * 
 * @return Array (Whole info - reboot_text, Detiled with file numbers - reboot_array)
 * 
 */
function get_pendingreboot_info() {
	global $_config;
	$cfgpath = $_config['proddir']."/config";
	$pendingreboot_files = glob("$cfgpath/pendingreboot.*");
	$reboot_text = null;
	$reboot_array = array();
	if ($pendingreboot_files && count($pendingreboot_files) > 0) {
		$reboot_text = "<ul>";
		foreach ($pendingreboot_files as $i => $file) {
			$reboot_number = str_replace("$cfgpath/pendingreboot.", "pendingreboot", $file);
			$reboot_message = file_get_contents($file);
			$reboot_message = preg_replace("/\r|\n/", "<br/>", $reboot_message);
			$reboot_array[$reboot_number] = $reboot_message;
			$reboot_text .= "<li>$reboot_message <br/><br/></li>";
		}
		$reboot_text .= "</ul>";
	}
	return array('reboot_text' => $reboot_text, 'reboot_array' => $reboot_array);
}

/**
 * Measures how long in miliseconds has past between calls of this function
 * 
 * @author Mihajlo 06.Sep.2015
 * 
 * @return int
 */
function measure_time(){
	global $measured_lasted, $measured_time;
	$time = round(microtime(true) * 1000);
	if(!isset($measured_time)){
		$measured_time = $time;
		return 0;
	}
	$measured_lasted = $time - $measured_time;
	$measured_time = $time;
	return $measured_lasted;
}

function get_aws_instance_identity() {
	$reply = false;
	// create curl resource 
	$ch = curl_init(); 
	curl_setopt_array($ch, array(
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_URL => 'http://169.254.169.254/latest/dynamic/instance-identity/document/',
	));
	// $output contains the output string 
	$output = curl_exec($ch); 

	$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	if ($code == '200') {
		$reply = json_decode($output, true);
	}
	return $reply;
}

/**
 * Determine if proxy is used and return file_get_contents via proxy or not
 * @param $url string URL for file_get_contents
 * @return string result of file_get_contents
 */
function file_get_contents_proxy($url) {
	// check if proxy is configured
	$handle = @fopen("/etc/environment", "r");
	if ($handle) {
		while (($buffer = fgets($handle, 4096)) !== false) {
			preg_match('/(?:https?_proxy=https?:)\/\/(?:(?:([^\/:]+):)(?:([^\/@]+)@))?([^\/:]+)(?::(\d+))?/i',
				$buffer, $matches);
			if ($matches) {
				$proxy_user = $matches[1];
				$proxy_pass = $matches[2];
				$proxy_host = $matches[3];
				$proxy_port = $matches[4];
			}
		}
		fclose($handle);
	}

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // read more about HTTPS http://stackoverflow.com/questions/31162706/how-to-scrape-a-ssl-or-https-url/31164409#31164409
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    // With proxy
	if (isset($proxy_host) and isset($proxy_port)) {
		if (isset($proxy_user) and !empty($proxy_user) and isset($proxy_pass) and !empty($proxy_pass)) {
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, "$proxy_user:$proxy_pass");
        }
		curl_setopt($ch, CURLOPT_PROXY, "$proxy_host:$proxy_port");
	}

    $contents = curl_exec($ch);
    curl_close($ch);
    return $contents;
}

/**
 * @param string $eth_name Network interface name to determine IP of
 * @return string IP address of network interface
 */
function get_ip_by_eth($eth_name='eth0') {
	$result = sudo_execute("ip a show $eth_name");
	preg_match('/inet (\d{1,3}.\d{1,3}.\d{1,3}.\d{1,3})\/\d{1,2}/', $result['output_str'], $matches);
	if ($matches) {
		return $matches[1];
	} else {
		return '';
	}
}

/**
 * Get details about network interfaces, connected to this AWS instance
 * @return array|bool Interfaces details or false if failed to retrieve them.
 */
function get_aws_instance_network_interfaces() {
    $interface_parameters = array(
        'device-number',
        'interface-id',
        'local-hostname',
        'local-ipv4s',
        'mac',
        'owner-id',
        'security-group-ids',
        'security-groups',
        'subnet-id',
        'subnet-ipv4-cidr-block',
        'vpc-id',
        'vpc-ipv4-cidr-block',
        'vpc-ipv4-cidr-blocks'
    );

    // get macs
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_URL => 'http://169.254.169.254/latest/meta-data/network/interfaces/macs/',
    ));
    $output = curl_exec($ch);

    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($code == '200') {
        $interfaces = array();
        foreach(preg_split("/((\r?\n)|(\r\n?))/", $output) as $mac){
            $interface_details = array();

            // Get each parameter for interface
            foreach ($interface_parameters as $parameter) {
                curl_setopt_array($ch, array(
                    CURLOPT_URL => "http://169.254.169.254/latest/meta-data/network/interfaces/macs/$mac/$parameter",
                ));
                $output = curl_exec($ch);
                $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if ($code == '200') {
                    $interface_details[$parameter] = $output;
                }
            }
            $interfaces[] = $interface_details;
        }
        return $interfaces;
    } else {
        return false;
    }
}

function restartNifi() {
	global $errorProc;
	global $errorMsg;
	global $_config;
	$logpath = $_config['proddir']."/logs/nifirestart.log";
	$time = date("D M d Y H:i:s").substr((string)microtime() , 1, 4);
	sudo_execute("echo 'Restarting nifi service $time .......' >> $logpath");
	$result = sudo_execute("service nifi restart >>$logpath 2>>$logpath &"); // #4789
	if ($result['rv'] != 0) {
		$errorMsg = $result['output_str'];
		$errorProc = true;
	}
}

/**
 * Check if azure cli is logged in
 * @return bool
 */
function is_azure_cli_logged_in() {
	$cmd = 'account show';
	$result = execAz2Cmds($cmd);
	if ($result['rv'] != 0) {
		return false;
	}
	return true;
}

function isUp($url) {
    /* from http://garridodiaz.com/check-if-url-exists-and-is-online-php/ */
    $url = @parse_url($url);
    if (is_array($url)) {
        $url = array_map('trim', $url);
        $url['port'] = (!isset($url['port'])) ? 80 : (int)$url['port'];
        $path = (isset($url['path'])) ? $url['path'] : '/';
        $path .= (isset($url['query'])) ? "?$url[query]" : '';
        if (isset($url['host']) && $url['host'] != gethostbyname($url['host'])) {
            $fp = fsockopen($url['host'], $url['port'], $errno, $errstr, 3);
            if (!$fp) {
                return false; //socket not opened
            } else {
                fputs($fp, "HEAD $path HTTP/1.1\nHost: $url[host]\n\n"); //socket opened
                $headers = fread($fp, 4096);
                fclose($fp);
                if(strpos($headers, '200 OK')){//matching header
                    return true;
                } else {
                    return false;
                }
            }
        } else {
            return false;
        }
    }
}

function softnas_api_login($address, $username, $password, $api_session_id = null) {
	global $_config;
	global $log;
	if (!$log) {
		$log = init_logging();
	}
	
	$script = $_config['proddir']."/api/softnas-cmd";
	
	if ($api_session_id === null) {
		$api_session_id = $address;
	}
	$cmd = "$script login $username $password -s '$api_session_id' --base_url https://$address/buurst";
	
	$result = sudo_execute($cmd);
	if ($result['rv'] != 0) {
		$errorMsg = "softnas_api_login: error ".$result['output_str'];
		$log->LogError($errorMsg);
		return array("success" => false, "errMsg" => $errorMsg);
	}
	
	$log->LogInfo("softnas_api_login: $username logged in to $address");
	return array("success" => true, "api_session_id" => $api_session_id);
}

function softnas_api_custom_command($api_session_id, $request, $base_url = null) {
	global $_config;
	global $log;
	if (!$log) {
		$log = init_logging();
	}
	
	$temp_file = "/tmp/softnascmd.{$api_session_id}";
	$temp_datafile = "/tmp/softnascmd.{$api_session_id}.data";
	
	if (!file_exists($temp_file) || !file_exists($temp_datafile)) {
		$errorMsg = "softnas_api_custom_command: error - not logged in (tmp files not found)";
		$log->LogWarn($errorMsg);
		return array("success" => false, "errMsg" => $errorMsg);
	}
	
	if ($base_url === null) {
		$result = sudo_execute("cat $temp_datafile | head -1");
		$base_url = trim($result['output_str']);
	}
	
	$options = "--silent -k";
	$cmd = "curl $options --cookie $temp_file --cookie-jar $temp_file --data '$request' $base_url/snserver/snserv.php";
	
	$result = sudo_execute($cmd);
	$result_str = $result['output_str'];
	$result_json = json_decode($result_str);
	if (/*$result['rv'] != 0*/ $result_json === false || $result_json === null) {
		$errorMsg = "softnas_api_custom_command: error ".$result_str;
		if (trim($result_str) == "") {
			$errorMsg.= " - no response from remote host";
		}
		if (stripos($result_str, "302 Found") !== false && stripos($result_str, "login.php") !== false) {
			$errorMsg = "softnas_api_custom_command: error - not logged in";
		}
		$log->LogWarn($errorMsg);
		return array("success" => false, "errMsg" => $errorMsg);
	}
	return array("success" => true, "result" => $result_json);
}

function validate_aws_keys($access_key, $secret_key) {
	if (strlen($access_key) == 0 || strlen($access_key) > 20) {
		return "Access Key length is invalid. Enter a valid Access key.";
	}
	
	if (strlen($secret_key) == 0 || strlen($secret_key) > 50) {
		return "Secret Key length is invalid. Enter a valid Secret key.";
	}
	
	$keys_test = test_aws_keys($access_key, $secret_key);
	if ($keys_test !== true) {
		if ($keys_test == 'wrong_keys') {
			return "Wrong Access or Secret key is entered.";
		}
		if ($keys_test == 'wrong_account') {
			return "Keys for wrong AWS account are entered.";
		}
	}
	return $keys_test;
}

function set_notification_email($email = "admin@example.com", $generate_monit_config = true) {
	
	global $log;
	global $_config;
	
	$log->LogDebug("set_notification_email: $email");
	$monit_settings = read_ini('monitoring.ini');
	
	$monit_settings['NOTIFICATION_EMAIL'] = $email;
	$result = write_shell_config_ini($monit_settings, $_config['proddir']."/config/monitoring.ini");
	if (!$result) {
		return "set_notification_email: Cannot write to softnas configuration file : 'monitoring.ini'";
	}
	
	if ($generate_monit_config) {
		$script = "config-generator-monit";
		$result = super_script($script);
		if ($result['rv'] != 0) {
			$errorMsg = "set_notification_email: monit configuration generator failed";
			$log->LogError("$errorMsg Details: {$result['output_str']}");
			return $errorMsg;
		}
	}
	return true;
}

/**
 * Returns the current nifi home directory path
 * @return String
 */
function get_current_nifihome() {
	global $log;
	$command = "/var/www/softnas/scripts/nifi_tls_utils.sh --getNifiHome";
	$result = sudo_execute($command);
	if ($result['rv'] != 0) {
		$errorMsg = "$command command failed. Unable to get current nifi home directory.";
		$log->LogError($errorMsg);
		return false;
	}
	return $result['output_str'];
}

/**
 * Check if current nifi home directory is in this pool
 * @return bool
 */
function is_nifihome_pool($poolname = "") {
	$nifihome = get_current_nifihome();
	if (!$nifihome) {
		return false;
	}
	$nifihome = realpath($nifihome)."/";
	if ($nifihome == "/") {
		return false;
	}
	$splits = preg_split("/\//", $nifihome, -1, PREG_SPLIT_NO_EMPTY);
	if (!$splits) {
		return false;
	}
	$command = "timeout 2 mountpoint -q /" . $splits[0]."/".$splits[1];
	$result = sudo_execute($command);
	if ($result['rv'] != 0) {
		return false;
	}
	return strpos($nifihome, "/$poolname/") === 0;
}

/**
 * Check if this volume is the current nifi home directory
 * @return bool
 */
function is_nifihome_volume($volname = "") {
	$nifihome = get_current_nifihome();
	if (!$nifihome) {
		return false;
	}
	$nifihome = realpath($nifihome)."/";
	$volname = realpath($volname)."/";
	if ($nifihome == "/" || $volname == "/") {
		return false;
	}
	$splits = preg_split("/\//", $nifihome, -1, PREG_SPLIT_NO_EMPTY);
	if (!$splits) {
		return false;
	}
	$command = "timeout 2 mountpoint -q /" . $splits[0]."/".$splits[1];
	$result = sudo_execute($command);
	if ($result['rv'] != 0) {
		return false;
	}
	return (strpos($nifihome, $volname) === 0);
}

/**
 * Disable Platinum features if they are not allowed
 */
function check_platinum_features() {
	global $log;

	$log->LogDebug("check_platinum_features: Checking if Platinum/Fuusion features are allowed");
	// Check platinum license
	if (!is_platinum_and_fuusion_license_valid()) {
		$log->LogError("Platinum/Fuusion license is expired or invalid. Shutting down Platinum/Fuusion features");
		// disable and stop all platinum features
		$softnas_ini = read_ini();

        $softnas_ini['flexfiles']['enabled'] = "false";
        $result = super_script('flexfiles_services', 'disable');
        if ($result['rv'] != "0") {
            $errorMsg = "Failed to disable flexfiles services: " . $result['output_str'];
            $log->LogError($errorMsg);
        }
        write_ini($softnas_ini);
	}
}

/**
 * Checks if UltraFast is set up and create control path for ssh
 * @param $remotenode string - remote node address
 * @param $remotekey string - private key path
 * @return mixed|bool - returns UltraFast control path which can be used in -S ssh option. Returns false if setup failed or no UltraFast
 */
function get_ultrafast_ssh_control_path($remotenode, $remotekey) {
	global $log;
    if (file_exists("/opt/ultrafast/inc/ultrafast.php")) {
        $log->LogDebug("get_ultrafast_ssh_control_path: Trying to set up UltraFast control path");
        require_once("/opt/ultrafast/inc/ultrafast.php");
        $known_hosts = sudo_execute("ssh-keygen -F " . $remotenode);
        $identity = sudo_execute("cat " . $remotekey);
        try
        {
        	$ultra = new UltraFast();
            $controlPathOutput = $ultra->SshControlPathSetup("root",$remotenode,22,$identity['output_str'],$known_hosts['output_str']);
            $controlPathObj = json_decode($controlPathOutput,true);
            return $controlPathObj['control_path'];
        }
        catch (Exception $ex)
        {
            $log->LogDebug("UltraFast failed control path setup (will continue without UltraFast): " . $ex->getMessage());
        }
    }
    return false;
}

function unmount_storage($mount_path) {
	global $log;
	
	$success = false;
	$result = sudo_execute("umount {$mount_path}");
	$log->LogDebug("Unmounting {$mount_path} ".$result['output_str']);
	if ($result['rv'] == 0) {
		$result_dir = sudo_execute("rm -rf {$mount_path}");
		$log->LogDebug("Removing dir {$mount_path} ".$result_dir['output_str']);
	
		$result_creds = sudo_execute("cat /etc/fstab | grep $mount_path");
		$creds_arr = explode(" cifs credentials=", $result_creds['output_str']);
		if (count($creds_arr) > 1) {
		    $creds_arr = explode(",", $creds_arr[1]);
		    $log->LogDebug("Removing CIFS credentials file ".$creds_arr[0]);
		    sudo_execute("rm -f ".$creds_arr[0]);
		}

		$dir_sed = str_replace(array(" ", "/"), array("\s", "\/"), " $mount_path ");
		$result_fstab = sudo_execute("sed -i \"/$dir_sed/d\" /etc/fstab");
		$log->LogDebug("Removing from fstab {$mount_path}");
		$success = true;
	}
	return $success;
}

/**
 * @param string $processName - The name of the process to check if it is running
 * @return bool - true if process is running
 */
function process_exists($processName) {
    $exists= false;
    exec("ps -A | grep -i $processName | grep -v grep", $pids);
    if (count($pids) > 0) {
        $exists = true;
    }
    return $exists;
}

/**
 * Checks if specified path is a CIFS mountpoint
 * @param string $path - The path to test
 * @return bool - true if path is a CIFS mount
 */
function isCIFSMount($path) {
    $result = executeCmd("mount -t cifs | grep -w \"{$path}\"");
    $retval = ($result && isset($result['return_value']) && $result['return_value'] === 0);
    return $retval;
}

function get_current_softnas_version() {
	global $_config;
    $result = sudo_execute("cat {$_config['proddir']}/version");
    return trim($result['output_str']);
}

function formatSizeValue($value) {
	return str_replace(",", "", $value);
}

function parseToBoolValue($value) {
	if ($value === "false") {
		return false;
	} else {
		return (bool)$value;
	}
}

function parseToYNValue($value) {
	return parseToBoolValue($value) ? 'Y' : 'N';
}

function parseToYYMMDDhhmmTime($time) {
	if (!$time) {
		return "0";
	}
	return date("ymd:Hi", $time);
}

function getDriftParameters($ini = null) {
	global $_config;
	if (!$ini) {
		$ini = read_ini();
	}
	$update_ini = false;
	$data = $ini['support'];
	
	$drift = (object)array(
		'id' => $_config['drift_id'],
		'token' => $_config['drift_token']
	);
	
	if (isset($data['drift_id']) && isset($data['drift_token'])) {
		$drift->id = $data['drift_id'];
		$drift->token = $data['drift_token'];
	}
	$id = $drift->id;
	
	if (!isset($data['live_support_enabled'])) {
		$data['live_support_enabled'] = 'true';
		$old_ini = read_ini('intercom.ini');
		if (isset($old_ini['general']) && isset($old_ini['general']['enabled'])) {
			if (!$old_ini['general']['enabled'] || $old_ini['general']['enabled'] === 'off' || $old_ini['general']['enabled'] === 'false') {
				$data['live_support_enabled'] = 'false';
			}
		}
		$update_ini = true;
	}
	$drift->enabled = $data['live_support_enabled'];
	
	$drift->user_id = isset($data["user_id_$id"]) ? $data["user_id_$id"] : '';
	$drift->external_id = isset($data["external_id_$id"]) ? $data["external_id_$id"] : '';
	if (!$drift->user_id || !$drift->external_id) {
		$drift->user_id = isset($data["live_support_id"]) ? $data["live_support_id"] : '';
		$drift->external_id = isset($data["live_support_external_id"]) ? $data["live_support_external_id"] : '';
		if ($drift->user_id && $drift->external_id) {
			$data["user_id_$id"] = $drift->user_id;
			$data["external_id_$id"] = $drift->external_id;
		} else {
			$drift->user_id = $drift->external_id = $data["user_id_$id"] = $data["external_id_$id"] = '';
		}
		unset($data["live_support_id"]);
		unset($data["live_support_external_id"]);
		$update_ini = true;
	}
	
	if ($update_ini) {
		$ini["support"] = $data;
		write_ini($ini);
	}
	return $drift;
}

function getFlowNamesFromRootProcessGroups() {
    $flowNamesFromPGs = array();
    $nificmd = 'php '.__DIR__.'/nifi/nificmd.php --getrootpgs';
    $result = sudo_execute($nificmd);
    $success = $result['rv'] === 0;
    if (!$success) {
        return $flowNamesFromPGs;
    }
    $pgroups = json_decode($result['output_str']);
    foreach ($pgroups as $pgroup) {
        if (isset($pgroup->component) && isset($pgroup->component->name)) {
            $portCount = 0;
            isset($pgroup->component->inputPortCount) && $portCount += $pgroup->component->inputPortCount;
            isset($pgroup->component->outputPortCount) && $portCount += $pgroup->component->outputPortCount;
            if ($portCount > 0) {
                array_push($flowNamesFromPGs, $pgroup->component->name);
            }
        }
    }
    return $flowNamesFromPGs;
}

function get_live_support_info($override_attr = null) {
	global $_config;
	global $_CLEAN;
	global $errorProc;
	global $errorMsg;
	global $successMsg;
	global $log;
	
	$errorProcOld = $errorProc;
	$errorMsgOld = $errorMsg;
	$successMsgOld = $successMsg;
	
	$external_id = isset($_CLEAN['OP']['drift_external_id']) ? $_CLEAN['OP']['drift_external_id'] : '';
	if (!function_exists('proc_licenseinfo')) require_once __DIR__.'/cmdprocessor.php';
	$licenseinfo = proc_licenseinfo();
	$ini = read_ini();
	
	if (!$log) {
		require_once 'KLogger.php';
		require_once 'logging.php';
	}
	$log = init_logging();
	
	$drift = getDriftParameters($ini);
	
	$drift_url = "https://driftapi.com/contacts";
	$drift_headers = '-H "Authorization: Bearer '.$drift->token.'" -H "Content-Type: application/json"';
	
	$new_chat_user = false;
	
	if ($drift->user_id) {
		$contact_id = str_replace("user", "", $drift->user_id);
		$cmd = 'curl -s '.$drift_headers.' '.$drift_url.'/'.$contact_id;
		$result = exec_command($cmd);
		$result_json = json_decode($result['output_str']);
		if ($result_json === false || $result_json === null || stripos($result['output_str'], 'could not find a contact') !== false) {
			$log->LogInfo("get_live_support_info - could not find a contact - user: $drift->user_id Output:".$result['output_str']);
			$ini['support']['user_id_'.$drift->id] = $drift->user_id = '';
			$ini['support']['external_id_'.$drift->id] = $drift->external_id = '';
			write_ini($ini);
		}
	}
	
	if (!$drift->user_id) {
		$log->LogInfo("get_live_support_info - no user_id");
		if (!$external_id) {
			$log->LogInfo("get_live_support_info - no external_id");
			return array("errMsg" => "get_live_support_info - error while setting contact");
		}
		$chat_name = $external_id;
		$chat_email = "{$external_id}@{$external_id}.com";
		$cmd = 'curl -s '.$drift_headers.' '.$drift_url.'?email='.$chat_email;
		$retries = 6;
		do {
			sleep(5);
			$result = exec_command($cmd);
			$result_json = json_decode($result['output_str']);
			$log->LogInfo("get_live_support_info - creating contact - external_id=$external_id");
			if ($result_json === false || $result_json === null) {
				$errMsg = "get_live_support_info - creating contact: $cmd - rv: ".$result['rv'].", Output: ".$result['output_str'];
				$log->LogInfo($errMsg);
				return array("errMsg" => $errMsg);
			}
			$contacts = $result_json->data;
			
		} while ($retries-- >= 0 && count($contacts) == 0);
		$contact_data = $contacts[0];
		$contact_id = $contact_data->id;
		$log->LogInfo("get_live_support_info - created '$contact_id' contact");
		
		$chat_name = "user{$contact_id}";
		$chat_email = "{$chat_name}@{$chat_name}.com";
		$cmd = 'curl -s '.$drift_headers.' -d \'{"attributes":{"name":"'.$chat_name.'","email":"'.$chat_email.'"}}\' -X PATCH '.$drift_url.'/'.$contact_id;
		$result = exec_command($cmd);
		$result_json = json_decode($result['output_str']);
		if ($result_json === false || $result_json === null) {
			$errMsg = "get_live_support_info - renaming contact: $cmd - rv: ".$result['rv'].", Output: ".$result['output_str'];
			$log->LogInfo($errMsg);
			return array("errMsg" => $errMsg);
		}
		
		$ini['support']['user_id_'.$drift->id] = $drift->user_id = $chat_name;
		$ini['support']['external_id_'.$drift->id] = $drift->external_id = $external_id;
		write_ini($ini);
	}
	$chat_name = $drift->user_id;
	/*$prodreg_ini = read_ini("prodreg_inputs.ini");
	$prodreg = $prodreg_ini['inputs'];
	if (trim($prodreg['prodRegFirstName']) !== '' || trim($prodreg['prodRegLastName']) !== '') {
		$chat_name = $prodreg['prodRegFirstName']." ".$prodreg['prodRegLastName'];
	}*/
	
	$platforms = array('azure' => 'AZ', 'amazon' => 'AWS', 'VM' => 'VMW');
	
	$license_type = '';
	$licenses = getProdCodes();
	$licenses['byol'] = getByolLicenses();
	$byolLicenses = getByolLicenses();
	if (array_key_exists($licenseinfo['product-id'], $licenses['byol'])) {
		$license_type = 'byo';
	} else {
		foreach ($licenses['current'] as $i => $item) {
			if ($licenseinfo['product-id'] == $item['productId'] && $item['consumption'] === true) {
				$license_type = 'mpcons';
				break;
			}
		}
		if ($license_type == '') {
			foreach ($licenses['legacy'] as $i => $item) {
				if ($licenseinfo['product-id'] == $item['productId'] && $item['consumption'] === true) {
					$license_type = 'mpcons';
					break;
				}
			}
		}
	}
	if ($license_type == '') {
		$license_type = 'mpcap';
	}
	
	
	$edition = '';
	if ($licenseinfo['is_platinum']) {
		$edition = 'plat';
	} elseif (stripos($licenseinfo['producttype'], 'essentials') !== false) {
		$edition = 'ess';
	} elseif (stripos($licenseinfo['producttype'], 'enterprise') !== false) {
		$edition = 'ent';
	} elseif (stripos($licenseinfo['producttype'], 'developer') !== false) {
		$edition = 'dev';
	}
	
	$capacity = intval(formatSizeValue($licenseinfo['storage-capacity-GB']));
	$capacity_used = intval(formatSizeValue($licenseinfo['actual-storage-GB']));
	if ($license_type == 'mpcons') {
		$capacity_used_percents = -1;
	} else {
		$capacity_used_percents = intval(($capacity_used / $capacity) * 10000) / 100;
	}
	
	$flexfiles_enabled = false;
	if(isset($ini['flexfiles']) && isset($ini['flexfiles']['enabled']) && $ini['flexfiles']['enabled'] === "true"){
		$flexfiles_enabled = true;
	}
	
	$dcha_configured = false;
	require_once __DIR__.'/sharedPool.php';
	$sharedConfig = SharedPool::read();
	$instance['ha_dcha'] = false;
	if (!empty($sharedConfig)) {
		$dcha_configured = true;
	}
	$snapha_running = false;
	$snapha_configured = false;
	$result_ha = sudo_execute("service softnasha status");
	if (stripos($result_ha['output_str'], "is running...") !== false ) {
		$snapha_running = true;
	}
	if (stripos($result_ha['output_str'], "unrecognized service") === false ) {
		$snapha_configured = true;
	}
	
	$smarttiers_configured = false;
	$btier_config = read_json('btier.json');
	if (is_array($btier_config) && !empty($btier_config)) {
		$smarttiers_configured = true;
	}
	
	require_once __DIR__.'/cmdproc_ultra.php';
	$ultra_configured = false;
	$ultra_connections = proc_ultrafast_connections();
	if (is_array($ultra_connections) && count($ultra_connections) > 0) {
		$ultra_configured = true;
	}
	
	$flexfiles_configured = false;
	if ($flexfiles_enabled) {
		$flex_file = $_config['proddir'].'/config/flexfiles.json';
		$flex_config = file_exists($flex_file) ? json_decode(file_get_contents($flex_file)) : null;
		if (is_object($flex_config) && isset($flex_config->data) && is_array($flex_config->data)) {
			foreach ($flex_config->data as $i => $flow) {
				if (in_array($flow->status, array('Completed', 'Running', 'Paused'))) {
					$flexfiles_configured = true;
					break;
				}
			}
		}
		if (!$flexfiles_configured) { // check if this is target instance
			$flows_arr = getFlowNamesFromRootProcessGroups();
			if (count($flows_arr)) {
				$flexfiles_configured = true;
			}
		}
	}
	
	$saved_info_arr = get_live_support_saved_data($ini);
	
	if (!(int)($saved_info_arr[1])) {
		$pools_arr = proc_pools();
		if (is_array($pools_arr) && count($pools_arr) > 0) {
			$saved_info_arr[1] = time();
		}
	}
	
	$volumes_arr = false;
	if (!(int)($saved_info_arr[2]) || $saved_info_arr[5] == "N") {
		$volumes_arr = proc_volumes();
	}
	if (!(int)($saved_info_arr[2]) && is_array($volumes_arr) && count($volumes_arr) > 0) {
		$saved_info_arr[2] = time();
	}
	if ($saved_info_arr[5] == "N" && is_array($volumes_arr) && count($volumes_arr) > 0) {
		foreach ($volumes_arr as $i => $vol) {
			$result_vol = sudo_execute("zdb -U / -d -e {$vol['pool']}/{$vol['id']} | cut -f9 -d ' '");
			if ($result_vol['rv'] === 0 && intval($result_vol['output_str']) > 6) {
				$saved_info_arr[5] = "Y";
				break;
			}
		}
	}
	
	if (!(int)($saved_info_arr[0]) && ((int)$saved_info_arr[1] || (int)$saved_info_arr[2])) {
		$saved_info_arr[0] = time();
	}
	
	$ha_failover = false;
	$snap_active = false;
	$snaprepstatusini = read_ini("snaprepstatus.ini");
	if ($snaprepstatusini && isset($snaprepstatusini['Relationship1'])) {
		$snap_rel = $snaprepstatusini['Relationship1'];
		$snap_active = isset($snap_rel['Active']) && $snap_rel['Active'] == '1';
		$role = $snap_rel['Role'] ? strtoupper(substr($snap_rel['Role'], 0, 1)) : $saved_info_arr[3];
		if ($saved_info_arr[3] == 'N') {
			$saved_info_arr[3] = $role; // N (Nothing), S (Source), T (Target)
		} else {
			if ($snapha_configured && $saved_info_arr[3] != $role) {
				$ha_failover = true;
			}
		}
	} else {
		$saved_info_arr[3] = 'N';
	}
	
	if (!(int)($saved_info_arr[4])) {
		$born_on_date = "999999"; // if config/born not exist - some older version
		$born_path = $_config['proddir'].'/config/born';
		if (file_exists($born_path)) {
			$born_value = file_get_contents($born_path);
			//$born_on_date = date("ymd", $born_value); // date when instance is first time started
			$born_on_date = date("ymd", time()); // date when user first time logs in
		}
		$saved_info_arr[4] = $born_on_date;
	}
	
	$saved_info_str = implode(",", $saved_info_arr);
	if ($ini['support']['live_support_data'] != $saved_info_str) {
		$ini['support']['live_support_data'] = $saved_info_str;
		write_ini($ini);
	}
	
	$instance_type = 'none';
	$platform = get_system_platform();
	if ($platform == 'amazon') {
		$aws_data = get_aws_instance_identity();
		$instance_type = $aws_data['instanceType'];
	}
	if ($platform == 'azure') {
		require_once __DIR__.'/azure_utils.php';
		$azure_data = getAzureMetadata();
		$instance_type = $azure_data['vmSize'];
	}
	
	require_once __DIR__.'/session_functions.php';
	$logged_in_time_spent = (int)(update_login_time_spent() / 60);
	
	$info = array(
		'softnas_license_type' =>		$license_type,
		'softnas_version' =>			$licenseinfo['version'],
		'softnas_edition' =>			$edition,
		'softnas_max_capacity' =>		$capacity,
		'softnas_capacity_used' =>		$capacity_used_percents,
		'softnas_cloud_provider' =>		$platforms[$licenseinfo['platform']],
		'softnas_registered' =>			parseToYNValue($licenseinfo['registration']->is_registered),
		'softnas_ts' =>					$logged_in_time_spent,
		'softnas_dc' =>					parseToYYMMDDhhmmTime($saved_info_arr[0]),
		'softnas_pc' =>					parseToYYMMDDhhmmTime($saved_info_arr[1]),
		'softnas_vc' =>					parseToYYMMDDhhmmTime($saved_info_arr[2]),
		'softnas_fc' =>					$saved_info_arr[5],
		'softnas_ha' =>					parseToYNValue($dcha_configured || ($snapha_running && $snap_active)),
		'softnas_hf' =>					parseToYNValue($ha_failover),
		'softnas_ivt' =>				$instance_type,
		'softnas_bod' =>				$saved_info_arr[4],
		'softnas_dh' =>					parseToYNValue(!parseToBoolValue($drift->enabled)),
		'softnas_smarttiers' =>			parseToYNValue($smarttiers_configured),
		'softnas_ultrafast' =>			parseToYNValue($ultra_configured),
		'softnas_flexfiles' =>			parseToYNValue($flexfiles_enabled && $flexfiles_configured)
	);
	
	if (is_array($override_attr)) {
		foreach ($override_attr as $i => $attr) {
			$info[$i] = $attr;
		}
	}
	
	$short = array(
		'license_type' => 'lt',
		'version' => 'ver',
		'edition' => 'edi',
		'max_capacity' => 'cap',
		'capacity_used' => 'ccap',
		'free_trial' => 'ft',
		'cloud_provider' => 'cp',
		'registered' => 'gr',
		'ts' => 'ts',
		'dc' => 'dc',
		'pc' => 'pc',
		'vc' => 'vc',
		'fc' => 'fc',
		'ha' => 'ha',
		'hf' => 'hf',
		'ivt' => 'ivt',
		'bod' => 'bod',
		'dh' => 'dh',
		'smarttiers' => 'st',
		'ultrafast' => 'uf',
		'flexfiles' => 'ff'
	);
	$short_info = array();
	foreach ($short as $i => $attr) {
		$short_info[] = $attr."=".$info["softnas_{$i}"];
	}
	$info['title'] = implode("&", $short_info);
	$info['last_time_sent'] = date("Y.m.d H:i:s", time());
	
	$drift_data = (object)array("attributes" => (object)$info);
	$drift_data_str = json_encode($drift_data);
	
	$contact_id = str_replace("user", "", $chat_name);
	$cmd = 'curl -s '.$drift_headers.' -d \''.$drift_data_str.'\' -X PATCH '.$drift_url.'/'.$contact_id;
	$result = exec_command($cmd);
	$result_json = json_decode($result['output_str']);
	
	$errorProc = $errorProcOld;
	$errorMsg = $errorMsgOld;
	$successMsg = $successMsgOld;
	
	if ($result_json === false || $result_json === null) {
		$errMsg = "get_live_support_info - sending info: $cmd - rv: ".$result['rv'].", Output: ".$result['output_str'];
		$log->LogInfo($errMsg);
		return array("errMsg" => $errMsg);
	}
	
}

function get_live_support_saved_data($ini = null) {
	if (!$ini) {
		$ini = read_ini();
	}
	$empty_arr = array("0", "0", "0", "N", "0", "N");
	
	if (!isset($ini['support']['live_support_data'])) {
		$ini['support']['live_support_data'] = implode(",", $empty_arr);
	}
	$saved_info_arr = explode(",", $ini['support']['live_support_data']);
	return array_merge($saved_info_arr, array_slice($empty_arr, count($saved_info_arr)));
}

?>
