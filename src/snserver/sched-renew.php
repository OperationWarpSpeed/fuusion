<?php
//
//  sched-renew.php - SoftNAS automatic renewal processor
//
//  Copyright (c) SoftNAS Inc.  All Rights Reserved.
//
//
require_once 'KLogger.php';
require_once ('utils.php');
require_once 'logging.php';
require_once ('snasutils.php');
require_once ('config.php');
$log = init_logging();
$log->LogDebug("Renewal processing started.");
$licenseInfo = snas_license_info(); // get the licensed capacity info
$valid = $licenseInfo['valid'];
if ($valid == false) // we have an invalid licensing outcome (probably exceeded licensed pool capacity limits or expired license - no auto-renewals when invalid)
{
	$errorMsg = "Renewal processor: License invalid failure - unable to continue. Details: " . $licenseInfo['errMsg'];
	$log->LogError($errorMsg);
	exit(1);
}
// If it's a trial, do not attempt auto-renewal
$isTrial = $licenseInfo['istrial'];
if ($isTrial) {
	$errorMsg = "Renewal processor: Nothing to do - skipping trial license.";
	$log->LogDebug($errorMsg);
	exit(1);
}
// Check to see if in grace period (auto-renewals only take place during grace period, which is after expiration date and before grace period ends)
$graceperiod = $licenseInfo['graceperiod'];
$graceremaining = $licenseInfo['graceremaining'];
if ($graceperiod == "0" || intval($graceremaining) <= 0) {
	$errorMsg = "Renewal processor: Nothing to do - outside grace period or license not expired";
	$log->LogDebug($errorMsg);
	exit(1);
}
$errorMsg = "Renewal processor: Processing license renewal...";
$log->LogDebug($errorMsg);
//
// We have a license that's eligible for renewal during the grace period
//
// Calculate a sleep offset (so all renewal requests do not hit at the same time each hour)
$mctime = microtime(true); // e.g., float(1283846202.89)
$secsOffset = intval($mctime) % 1800; // get up to 30 minute offset, in seconds
if ($secsOffset > 0) {
	$log->LogInfo("Taking a $secsOffset seconds nap before renewal action...");
	sleep($secsOffset); // take a nap for $secsOffset
	$log->LogInfo("Awake from $secsOffset seconds nap.");
}
$log->LogInfo("Attempting online license renewal...");
$regname = $licenseInfo['regname'];
$currentkey = $licenseInfo['currentkey'];
$hwlock = $licenseInfo['hwlock'];
$url = "https://www.softnas.com/apps/activation/softnas/renew.php?";
$url.= "regname=" . urlencode($regname);
$url.= "&";
$url.= "licensekey=$currentkey";
$url.= "&";
$url.= "hwlock=$hwlock";
$log->LogDebug("Request URL: $url");
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 90);
// Set so curl_exec returns the result instead of outputting it.
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// Get the response and close the channel.
$response = curl_exec($ch);
curl_close($ch);
$log->LogDebug("JSON Response from renewal processor");
$log->LogDebug($response);
if (strlen($response) == 0) {
	$errorMsg = "Renewal processor: Renewal attempt failed. Timeout or no response from renewal website (possible network connection issue).";
	$log->LogError($errorMsg);
	//
	// If not connected to Internet, log it and exit.
	// Email admin and support, in case SMTP is flowing on internal network - let them know license will expire after grace period and unable to auto-renew due to no Internet access.
	//
	// ********* Needs to be implemented as error handling once SMTP to admin and support are implemented
	exit(1);
}
$reply = json_decode($response, true);
$log->LogDebug("Decoded renewal reply:");
$log->LogDebug($reply);
//
// Valid renewal response. Process the response in preparation for activation
//
if (!$reply['success']) {
	$errorMsg = "Renewal processor: Renewal attempt failed. Details: " . $reply['msg'];
	$log->LogError($errorMsg);
	exit(1);
}
$log->LogInfo("Renewal processor: successfully retrieved a new license key from renewal site.");
//
// Get the renewal key return parameters
//
$licensekey = $reply['records']['licensekey'];
$regname = $reply['records']['regname'];
$hardware_id = $licenseInfo['hardware_id'];
sleep(1); // take a brief breather to give up CPU for storage processing (and to space out calls to softnas.com)
// Activate the key
//set POST variables
$url = "https://www.softnas.com/apps/activation/softnas/activate.php";
$fields = array(
	'licensekey' => urlencode($licensekey) ,
	'regname' => urlencode($regname) ,
	'hwid' => urlencode($hardware_id) ,
);
$fields_string = "";
//url-ify the data for the POST
foreach ($fields as $key => $value) {
	$fields_string.= $key . '=' . $value . '&';
}
rtrim($fields_string, '&');
//open connection
$ch = curl_init();
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 90);
// Set so curl_exec returns the result instead of outputting it.
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//set the url, number of POST vars, POST data
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, count($fields));
curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
//execute post
$response = curl_exec($ch);
//close connection
curl_close($ch);
$log->LogDebug("JSON Response from activation processor");
$log->LogDebug($response);
if (strlen($response) == 0) {
	$errorMsg = "Renewal processor: Renewal activation attempt failed. Timeout or no response from activation website (possible network connection issue).";
	$log->LogError($errorMsg);
	exit(1);
}
$reply = json_decode($response, true);
$log->LogDebug("Decoded activation reply:");
$log->LogDebug($reply);
if (!$reply['success']) {
	$errorMsg = "Renewal processor: Activation attempt failed. Details: " . $reply['msg'];
	$log->LogError($errorMsg);
	exit(1);
}
$log->LogInfo("Renewal processor: successfully activated new license key.");
sleep(1); // take a brief breather to give up CPU for storage processing (and to space out calls to softnas.com)
$activationCode = $reply['records']['activationCode'];
$hwlockCode = $reply['records']['hwlockCode'];
//
// Install the key
//
$no_hwlock = "";
$reply = snas_license_info($licensekey, $regname, $no_hwlock, $activationCode, $hwlockCode, false); // validate the key
$valid = $reply['valid'];
if ($valid) // we have a valid key, write it to the INI file (if not just testing the key)
{
	$log->LogInfo("Renewal processor: Saving renewed key: $licensekey, registered to: $regname, activation code: $activationCode");
	// Read existing INI contents
	$ini = read_ini();
	$license = $ini['license'];
	$license['key'] = $licensekey;
	if (strlen($regname) > 0) {
		$license['regname'] = $regname;
	}
	if (strlen($activationCode) > 0) {
		$system = $ini['system'];
		if (isset($system['id'])) {
			$system_uuid = $system['id'];
			$encryptedCode = encode_actcode($system_uuid, $activationCode); // encrypt activation code before it's written to softnas.ini
			$license['activationCode'] = $encryptedCode;
		} else
		// just in case there's no system ID for any reason, maintain compatiability
		{
			$license['activationCode'] = $activationCode; // can't use encrypted version yet - not upgraded to system ID (unlikely)
			
		}
	}
	if (strlen($hwlockCode) > 0) {
		$license['hardwareLock'] = $hwlockCode;
	}
	$ini['license'] = $license;
	// Write INI updates
	if (!write_ini_file($ini, "../config/softnas.ini", true)) {
		$errorProc = true; // pass error back to client
		$errorMsg = "Renewal processor: Unable to save license information! (permissions problem)";
		$log->LogError($errorMsg);
		exit(1);
	}
} else { // not valid
	$log->LogError($errorMsg);
	exit(1);
}
$msg = "Renewal processing completed successfully.";
$log->LogDebug($msg);
?>
