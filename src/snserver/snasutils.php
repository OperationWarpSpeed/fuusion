<?php
//
// snasutils.php - SoftNAS integration with ZFS layer
//
//
// Copyright (c) SoftNAS Inc.  All Rights Reserved.
//
//
require_once __DIR__.'/utils.php';

function findProdSKU($sku) {
	$all = getProdCodes();
	foreach($all['current'] as $jCode => $jVal) {
		if (strtoupper($sku) === strtoupper($jVal['product_sku'])) {
			return $jVal;
		}
	}
    foreach($all['legacy'] as $jCode => $jVal) {
        if (strtoupper($sku) === strtoupper($jVal['product_sku'])) {
            return $jVal;
        }
    }
	return false;
}

function prodCodeExists($code) {
	$all = getProdCodes();
	if (array_key_exists($code, $all['current'])) {
		return $all['current'][$code];
	}
    if (array_key_exists($code, $all['legacy'])) {
        return $all['legacy'][$code];
    }
	return false;
}

function getProdCodes() {
	global $newLicensingJSON, $legacyLicensingJSON;
	require_once __DIR__.'/license.php';
	require_once __DIR__.'/license_legacy.php';
	$current = json_decode($newLicensingJSON, true);
	$legacy = json_decode($legacyLicensingJSON, true);
	return array('current' => $current, 'legacy' => $legacy);
}

function getByolLicenses() {
    global $byolLicensingJSON;
    require_once __DIR__.'/license_byol.php';
    return json_decode($byolLicensingJSON, true);
}

// if zpool status returns internal kernel names (dm-...) instead device names for some pools
function get_standard_device_names() {
	global $_config;
	if (isset($_config['cache']['standard_device_disk_names'])){
		return $_config['cache']['standard_device_disk_names'];
	}
	$result = sudo_execute("lsblk -nlo KNAME,NAME | awk {'print $1,$2'}");
	$result_arr = $result['output_arr'];
	$names = array();
	
	foreach ($result_arr as $i => $line) {
		$line_arr = explode(" ", $line);
		if (count($line_arr) > 1) {
			$names[$line_arr[0]] = $line_arr[1]; // $lines["dm-0"] = "xvdi" ...
		}
	}
	
	$_config['cache']['standard_device_disk_names'] = $names;
	return $names;
}
function disk_internal_to_standard_name($name) {
    global $log;
    if (is_link("/dev/$name")) {
        $log->LogDebug("disk_internal_to_standard_name: /dev/$name is a link to device");
        return readlink("/dev/$name");
    }
    return $name;
}
function disk_standard_to_internal_name($name) {
	return $name; // dm- names fixed in #5110 (0e583c6897e7)
	$standard_names = get_standard_device_names();
	$name = str_ireplace("/dev/mapper/", "", $name);
	$name = str_ireplace("/dev/", "", $name);
	
	$dm_names = array();
	foreach ($standard_names as $i => $disk_name) {
		if ($i !== $disk_name) {
			$dm_names[$disk_name] = $i;
		}
	}
	
	return isset($dm_names[$name]) ? $dm_names[$name] : $name;
}

// Check if block device
function snas_is_block_device($name) {
	$name = disk_internal_to_standard_name($name);
	global $_config;
	if (!isset($_config['cache']['block_devices'])) {
		$result_block = sudo_execute("lsblk | grep disk | awk {'print $1'}");
		$blockdev_str = str_replace('/dev/', '', $result_block['output_str']);
		$_config['cache']['block_devices'] = $blockdev_str;
	}
	$vdev_name = str_replace('/dev/', '', $name);
	if (strpos($_config['cache']['block_devices'], $vdev_name) === false) {
		return false;
	} else {
		return true;
	}
}

// Detect filesystems by looking at "zfs list" output
function snas_filesystem_list($fs = '', $arguments = '', $valueFilter = null, $operatorFilter = null, $propertyFilter = null) {
	global $_config;
	$fsarr = array();
    $columns = " -o name,used,avail,refer,mountpoint,volblock ";
	$command = $_config['systemcmd']['zfs'] . ' list ' . $columns . $arguments . ' ' . $fs . ' | grep -vw rpool';
	$result = sudo_execute($command, false, true);
	$snas_raw = $result['output_arr'];
	$snas_count = count($snas_raw);
	$resultFilter = array();

	if ((@count($snas_count) > 0)) {
		// Note that with $i starting at index 1 (not 0) we skip first line, which is the heading
		for ($i = 1;$i <= $snas_count;$i++) {
			$split = preg_split('/[\s]+/m', @$snas_raw[$i], 6);
			$newarr = @array(
				'name' => $split[0],
				'used' => $split[1],
				'avail' => $split[2],
				'refer' => $split[3],
				'mountpoint' => $split[4],
                'volblocksize' => $split[5]
			);
			$fsarr[$newarr['name']] = $newarr;

			$chunks = preg_split('/\//', $newarr['name'], -1, PREG_SPLIT_NO_EMPTY);
			$volname = isset($chunks[1]) ? $chunks[1] : '';

			if($valueFilter && $propertyFilter === 'vol_name') {
				// include filtered and root too
				if(strpos($newarr['name'], '/') === false || ($operatorFilter === 'in' && array_search($volname, $valueFilter) !== false) || ($operatorFilter === 'like' && strpos($volname, $valueFilter) !== false)) {
					$resultFilter[$newarr['name']] = $fsarr[$newarr['name']];
				}
			}

		} //$i = 1; $i <= $snas_count; $i++
		return $valueFilter ? $resultFilter : $fsarr;
	} //( @count( $snas_count ) > 0 )
	else return false;
}
// Filesystem_list returning only one filesystem
function snas_filesystem_list_one($fs = '', $arguments = '') {
	$fsarr = snas_filesystem_list($fs, $arguments);
	return current($fsarr);
}
// Retrieves filesystem properties from 'zfs get' command
function snas_filesystem_getproperties($filesystem = false, $property = false) {
	global $_config;
	$fsdetails = array();
	$fsdetails_info = array();
	$fs = trim($filesystem);
	$allfs = (strlen($fs) < 1) ? true : false;
	// get detailed properties for this filesystem
	if ($property == false) $command = $_config['systemcmd']['zfs'] . ' get all ' . $fs;
	else $command = $_config['systemcmd']['zfs'] . ' get ' . $property . ' ' . $fs;
	$result = sudo_execute($command, false, true);
	$snas_raw = $result['output_arr'];
	$snas_count = count($snas_raw) + 1;
	if ((@count($snas_count) > 0))
	// Create ZFS property array
	$prop = array();
	if (@is_array($snas_raw)) {
		for ($i = 1;$i < count($snas_raw);$i++) {
			$split = preg_split('/[\s]+/m', $snas_raw[$i]);
			$prop[@$split[0]][trim(@$split[1]) ] = $split;
		} //$i = 1; $i < count( $snas_raw ); $i++
		
	} //@is_array( $snas_raw )
	return $prop;
	if ($allfs) $returnarray = $prop;
	if ($property == false) $returnarray = $prop;
	else $returnarray = $prop;
	return $returnarray;
}

// Setting Azure disks - names needs be fixed to it's serial numbers
function snas_refresh_raw_rules_file() {
	global $log;
	
	if (get_system_platform() != 'azure') {
		return true;
	}
	
	$file_raw_rules = '/etc/udev/rules.d/60-raw.rules';
	
	$result = sudo_execute("ls -l /dev/disk/by-id/");
	if ($result['rv'] != 0) {
		$log->LogInfo("refresh_raw_rules: Error while listing /dev/disk/by-id/.  ". $result['output_str']);
		return false;
	}
	$disk_list = $result['output_arr'];
	
	$result = sudo_execute("cat $file_raw_rules");
	if ($result['rv'] != 0) {
		$log->LogInfo("refresh_raw_rules: Error while reading file 60-raw.rules.  ". $result['output_str']);
		return false;
	}
	$file_arr = $result['output_arr'];
	
	$disk_arr = array();
	foreach ($disk_list as $i => $line) {
		$line_arr = explode(" -> ../../", trim($line));
		$line_arr_left = explode("scsi-", $line_arr[0]);
		$serial_arr = explode("-part", $line_arr_left[1]);
		$serial = $serial_arr[0];
		$disk_name = substr($line_arr[1], 0, 3);
		
		if (substr($disk_name, 0, 2) === 'sd' && $disk_name != 'sda' && $disk_name != 'sdb') {
			$disk_arr[$disk_name] = $serial;
		}
	}
	
	// Replace old KERNEL=="sd*", ..... parts with the new ones:
	$new_file_arr = array();
	foreach ($file_arr as $i => $line) {
		if (stripos($line, 'KERNEL=="sd*", BUS=="scsi", ENV{ID_SERIAL}==') === false) {
			$new_file_arr[] = $line;
		}
	}
	foreach ($disk_arr as $disk_name => $disk_serial) {
		$line_str = 'KERNEL=="sd*", BUS=="scsi", ENV{ID_SERIAL}=="'.$disk_serial;
		$line_str.= '", NAME+="'.$disk_name.'%n"';
		$new_file_arr[] = $line_str;
	}
	$new_file_str = implode(chr(10), $new_file_arr);
	
	$result = sudo_execute("sudo chmod 666 $file_raw_rules; sudo echo '$new_file_str' > $file_raw_rules; sudo chmod 644 $file_raw_rules; ");
	if ($result['rv'] != 0) {
		$log->LogInfo("refresh_raw_rules: Error while writing to file 60-raw.rules.  ". $result['output_str']);
		return false;
	}
	return true;
}

function handle_license_feature_request($feature) {
	
	global $_CLEAN;
	$command = isset($_CLEAN['OP']['command']) ? $_CLEAN['OP']['command'] : "";
	$set_header = true;
	
	if (stripos($feature, 'tier_') === 0 || $feature == 'createtier') {
		$feature = 'smarttiers';
	}
	if (stripos($feature, 'ultrafast_') === 0) {
		$feature = 'ultrafast';
	}
	if (stripos($feature, 'flex_') === 0) {
		$feature = 'flexfiles';
	}
	if ($feature == 'diskmgmt') {
		if ($command == 'createEBSdisk' || $command == 'createAzureDisk' ) {
			$feature = 'block_storage';
		}
		if ($command == 'createDRBDDisk' || $command == 'deleteDRBDDisk') {
			$feature = 'dualha';
		}
	}
	if ($feature == 'createpool') {
		if (isset($_CLEAN['OP']['sharedStorage']) && $_CLEAN['OP']['sharedStorage'] === 'on') {
			$feature = 'dualha';
		}
	}
	if ($feature == 'snaprepcommand' || $feature == 'hacommand') {
		$feature = 'snapreplicate';
		if ($command == 'snapreplicatestatus') {
			$set_header = false;
		}
	}
	
	$features_arr = array(
		'enableflexfiles', 'acceptbetaagreement', 'block_storage', 'snapreplicate', 'deltasync', 'smarttiers', 'readcache', 'writelog',
		'ultrafast', 'flexfiles', 'flexfiles_architect', 'dualha'
	);
	
	if (in_array($feature, $features_arr)) {
		return snas_licensed_feature($feature, null, $set_header);
	} else {
		return true;
	}
}

function snas_licensed_feature($featurename, $license = null, $set_header = true) {
	global $log;
	global $errorMsg;
	global $errorProc;
	
	if (!is_object($log)) {
		$log = init_logging();
	}
	$log_file_tmp = $log->GetLogFile();
	$log = init_logging(__DIR__.'/../logs/license.log');
	
	$log->LogDebug("Query feature $featurename ...");
	$isLicensed = false;
	if ($license === null) {
		$license = snas_license_info(); // get license information
	}
	
	$product_id = $license['product-id'];
	if (isset($license['cloud_essentials_testing']) && $license['cloud_essentials_testing'] === true) {
		$product_id = '22';
	}
	
	$product_arr = array(22, 23, 24, 51, 52, 53, 54);
	if (!in_array((int)$product_id, $product_arr)) {
		// Do not check other types (#6796)
		$log = init_logging($log_file_tmp);
		return true;
	}
	
	if (!$license['valid']) {
		$errorProc = true;
		$errorMsg = $license['errMsg'];
		$log = init_logging($log_file_tmp);
		return false;
	}
	
	$profeatures = array(
		'snapreplicate',
		'iscsitarget',
		'iscsiinitiator',
		'activedirectory',
		'readcache',
		'writelog',
		'schedsnapshots',
	);
	$enterprise_features = array(
		'smarttiers',
		'dualha'
	);
	
	$cloud_essentials_blocked = array(
		'enableflexfiles',
		'acceptbetaagreement',
		'block_storage',
		'snapreplicate',
		'deltasync',
		'smarttiers',
		'writelog',
		'dualha'
	);
	
	$cloud_essentials_dcha_blocked = array(
		'enableflexfiles',
		'acceptbetaagreement',
		'block_storage',
		'deltasync',
		'smarttiers',
		'writelog'
	);

	$platinum_features = array(
		'enableflexfiles',
        'ultrafast',
		'flexfiles',
		'smarttiers',
		'flexfiles_architect'
	);

	if (in_array($featurename, $platinum_features)) {
        return is_platinum_and_fuusion_license_valid();
	}
	
	$log->LogDebug("Checking to see if feature '$featurename' is licensed...");
	if ($license['valid']) {
		
		switch ($product_id) {
			case '1': // SoftNAS Cloud Enterprise
				$isLicensed = true;
                break;
			case '3': // SoftNAS Cloud
				$isLicensed = true;
				foreach ($enterprise_features as $feature) {
					if ($feature == $featurename) {
						$isLicensed = false; // exclude Enterprise features from SoftNAS Cloud
                        break;
                    }
				}
                break;
			case '4': // SoftNAS Cloud/Enterprise Max
				$isLicensed = true;
                break;
			case '5': // SoftNAS Cloud Express
				$isLicensed = true;
				foreach ($enterprise_features as $feature) {
					if ($feature == $featurename) {
						$isLicensed = false; // exclude Enterprise features from SoftNAS Cloud Express
                        break;
                    }
				}
                break;
			case '6': // SoftNAS Cloud FCP
				$isLicensed = true;
				break;
			case '2': // SoftNAS Essentials
				$isLicensed = true;
				foreach ($profeatures as $feature) {
					if ($feature == $featurename) {
						$isLicensed = false; // exclude Pro features from SoftNAS Essentials
						break;
					}
				}
				foreach ($enterprise_features as $feature) {
					if ($feature == $featurename) {
						$isLicensed = false; // exclude Enterprise features from SoftNAS Essentials
						break;
					}
				}
				break;
			case '22': // SoftNAS Cloud Essentials
			case '24': // SoftNAS Cloud Essentials 1TB
			case '50': // SoftNAS Cloud Essentials MMS
			case '51': // SoftNAS Cloud Essentials 10TB
            case '52': // SoftNAS Cloud Essentials 20TB
            case '53': // SoftNAS Cloud Essentials 50TB
            case '54': // SoftNAS Cloud Essentials BYOL
				$isLicensed = true;
				foreach ($cloud_essentials_blocked as $feature) {
					if ($feature == $featurename) {
						$isLicensed = false; // exclude Enterprise features from SoftNAS Cloud Essentials
						break;
					}
				}
				break;
			case '23': // SoftNAS Cloud Essentials DCHA
				$isLicensed = true;
				foreach ($cloud_essentials_dcha_blocked as $feature) {
					if ($feature == $featurename) {
						$isLicensed = false; // exclude Enterprise features from SoftNAS Cloud Essentials
						break;
					}
				}
				break;
			default:
				break;
		}
		$strMsg = $isLicensed ? "Licensed" : "NOT Licensed";
		$log->LogDebug("snas_is_licensed: Feature " . $featurename . " is $strMsg.");
	}
	if (!$isLicensed && $set_header) {
		header('HTTP/1.0 401 Unauthorized');
	}
	$log = init_logging($log_file_tmp);
	return $isLicensed;
}
function snas_licensed_features($features, $license = null) {
	if ($license === null) {
		$license = snas_license_info(); // get license information
	}
	
	foreach ($features as $key => $feature) {
		$return[] = array(
			'isLicensed' => snas_licensed_feature($feature, $license),
			'featurename' => $feature
		);
	}
	return $return;
}
//
// Returns license information
//
function snas_license_info($lickey = "", $regname = "", $hardware_id = "", $activationCode = "", $hardware_lock = - 1, $testkey = true, $fulldetails = true, $manualActivate = false) {
	global $log;
	global $_config;
	global $errorMsg;
	global $errorProc;
	global $response;
	
	if (!is_object($log)) {
		$log = init_logging();
	}
	$log_file_tmp = $log->GetLogFile();
	$log = init_logging(__DIR__.'/../logs/license.log');
	
	$using_default = false;
	$license_info = array();
	$license_info['smarttiers_override'] = false;
	if (file_exists('/.smarttiers_override')) {
		$license_info['smarttiers_override'] = true;
	}
	$license_info['valid'] = false;
	$license_info['errMsg'] = "Invalid License";
	$license_info['status'] = $license_info['errMsg'];
	$license_info['capabilities'] = "";
	$license_info['model'] = "";
	$license_info['sig'] = "";
	$today = date("m/d/Y");
	$license_info['today'] = $today; // get server's date today
	$license_info['date'] = time(); // store timestamp for cache
	$license_info['hardware_id'] = get_hardware_id(); // always return hardware_id (even when there's an error) - it's required to activate a new license!!
	$log->LogDebug("snas_license_info: testkey: $testkey, activationCode: $activationCode, lickey: $lickey, regname: $regname");
	if ($testkey) {
		$log->LogDebug("snas_license_info: testkey is set so this pass is just for testing purposes");
	}
	$actType = $manualActivate ? "Manual activation." : "Online activation.";
	$log->LogDebug("snas_license_info: $actType");
	$log->LogDebug("snas_license_info: Ready to validate license");
	// Read the local INI file settings and process any overrides
	$ini = read_ini();
	$license = array();
	if (isset($ini['license'])) {
		$license = $ini['license'];
		$license_info['cloud_essentials_testing'] = (isset($license['cloud_essentials_testing']) && (int)($license['cloud_essentials_testing']) === 1);
		$system = $ini['system'];
		// Check to see if this system has a unique licensing system hash code - if not, upgrade it now so that it does
		if (!isset($system['id'])) // unique system guid is used as master encryption key for this system (among other things)
		{
			$system_uuid = create_uuid(); // create a guid for this system
			$system['id'] = $system_uuid;
			$ini['system'] = $system;
			// upgrade activation code from plaintext mode to ciphertext mode
			if (isset($license['activationCode']) && $license['activationCode'] != "") {
				$tmpActivationCode = $license['activationCode']; // could be plain (original form) or encrypted format
				$encryptedCode = encode_actcode($system_uuid, $tmpActivationCode); // ensure we're working with a decoded activation code
				$license['activationCode'] = $encryptedCode;
			}
			$ini['license'] = $license;
			write_ini($ini);
		}
		$system_uuid = $system['id'];
		if (strlen($lickey) == 0 && isset($license['key'])) // no override passed in to this function, so use INI key
		{
			$lickey = $license['key']; // read customer-entered license key (if any)
			
		}
		if (strlen($regname) == 0 && isset($license['regname'])) // no override passed in to this function, so use INI key
		{
			$regname = $license['regname'];
		}
		if (strlen($hardware_id) == 0) // no override passed in to this function, so use INI key
		{
			if (isset($license['hardware_id'])) $$hardware_id = $license['hardware_id']; // NOTE: this would ONLY be used if we generate a hardware-locked key (which we do not today)
			
		}
		if (strlen($activationCode) == 0) // no override passed in to this function, so use INI key (if any)
		{
			if (isset($license['activationCode'])) {
				$log->LogDebug("snas_license_info: Using activation code from INI file.");
				$activationCode = $license['activationCode']; // could be plain (original form) or encrypted format
				decode_actcode($system_uuid, $activationCode); // ensure we're working with a decoded activation code
				
			}
		}
		if ($hardware_lock == - 1) // no override passed in
		{
			if (isset($license['hardwareLock'])) {
				$log->LogDebug("snas_license_info: Using harwareLock from INI file.");
				$hardware_lock = $license['hardwareLock'];
				$license_info['hwlock'] = $hardware_lock;
			}
		}
	}
	$activationRequired = true;
	$key = $lickey;
	if ($key == "") { // use the default config license key
		$key = $_config['license']; // no customer key or passed-in key argument, so use built-in default key
		$log->LogDebug("snas_license_info: Using built-in license");
		$using_default = true;
		$log->LogDebug("snas_license_info: Setting to no activation required for built-in license.");
		$activationRequired = false; // no activation is required for EAP or SoftNAS.com (buit-in) licenses (whether shipped in product or add-ons)
		
	}
	if (strlen($regname) == 0) {
		$log->LogDebug("snas_license_info: Using built-in registration name.");
		$regname = "SoftNAS.com";
	}
	if ($regname == "SoftNAS.com" && $using_default) { // only allow SoftNAS.com to be used with built-in licenses without activation
		$log->LogDebug("snas_license_info: Setting to no activation required for SoftNAS.com");
		$activationRequired = false; // no activation is required for EAP or SoftNAS.com (buit-in) licenses (whether shipped in product or add-ons)
		
	}
	//
	// Check to see if on-demand, utility billing model is used instead of our licensing
	//
	$utilityModel = false; // cloud "utility" model is true iff billing and licensing is handled by cloud provider instead of license keys
	$system = $ini['system']; // get system section
	$platform = isset($system['platform']) ? $system['platform'] : false; // get platform type
	if (!$platform) {
		$platform = get_system_platform();
	} // 16.10.2014
	if ($platform == "amazon") // it's Amazon EC2 - see if we were launched from AWS Marketplace
	{
		$cmd = $_config['systemcmd']['wget'] . " -qO- http://169.254.169.254/latest/meta-data/product-codes/";
		$result = sudo_execute($cmd);
		if ($result['rv'] != 0) {
			$log->LogDebug("snas_license_info: Amazon EC2 platform metadata not detected: ".var_export($result, true));
		} else
		// got a product code for AWS Marketplace
		{
			$prodCode = $result['output_str'];
			$e = prodCodeExists($prodCode);
		}
	} elseif ($platform == "azure") {
		$sku_code = get_azure_sku();
		if (file_exists("../config/prod")) {
			$prodCode = trim(file_get_contents("../config/prod"));
		}
		if (isset($prodCode) && !empty($prodCode)) {
			$e = prodCodeExists($prodCode);
		} elseif (isset($sku_code) && !empty($sku_code)) {
			$e = findProdSKU($sku_code);
		}
	} elseif ($platform == 'google') {
		//TODO: Update license here

	}
	if (isset($e) && is_array($e)) {
		$license_info['model'] = 'utility';
		$utilityProduct = $e['productId'];
		$utilityModel = true;
		$activationRequired = false;
		$utilityCapacityGB = $e['capacityGB'];
		$license_info['sku_name'] = $e['product_name'];
	}
	if (!$utilityModel) {
		$log->LogDebug("snas_license_info: Licensing is traditional license Key model.");
		$license_info['model'] = "key"; // indicates a license key is required
		
	}
	// Now process the license key using the new snvalidate for longer keys
	$license_key_parsed = parseLicenseKey($key, $regname, $hardware_id);
	if ($license_key_parsed == FALSE) {
		$errorMsg = "License validation failure (error 13.8)";
		$license_info['status'] = $errorMsg;
		$license_info['errMsg'] = $errorMsg;
		$log->LogError($errorMsg);
		$log = init_logging($log_file_tmp);
		return $license_info;
	}
    $license_info['status'] = $license_key_parsed['status'];
    $license_info['capabilities'] = $license_key_parsed['capabilities'];
    $license_info['sig'] = $license_key_parsed['sig'];

	$errorFlag = false; // this flag is used to indicate an error has occured that will be returned (to continue processing and return license info w/ error)
	if ($license_info['status'] == "OK") {
		// if it's a "default" license from config, verify it's authentic
		if ($using_default && $license_info['sig'] != $_config['sig'] && !$errorFlag) {
			$errorMsg = "License validation failure (error 13.9)"; // default key and signature mismatch
			$license_info['status'] = $errorMsg;
			$log->LogError($errorMsg);
			$errorFlag = true; // flag as error and continue
			
		}
		$sigCheck = $_config['sig'];
		$strMsg = "Valid License";
		$license_info['errMsg'] = $strMsg;
		$license_info['status'] = $strMsg;

		if ($license_key_parsed['capabilities']['storage-capacity-GB'] < 250) {
            $license_key_parsed['capabilities']['storage-capacity-GB'] = 250;
		}
		$license_info['product-id'] = $license_key_parsed['capabilities']['product-id'];
		$license_info['productID'] = $license_key_parsed['capabilities']['product-id']; // variable named so can be accessed in Javascript
		$license_info['license-version'] = $license_key_parsed['capabilities']['license-version']; // 0 == perpetual vers 1 license, 1=subscription vers 1 license
		$license_info['storage-capacity-GB'] = $license_key_parsed['capabilities']['storage-capacity-GB'];
		$license_info['actual_expiration'] = $license_key_parsed['capabilities']['expiration']; // this is the actual expiration (before grace period)
		$license_info['expiration'] = $license_key_parsed['capabilities']['expiration']; // this is the effective expiration (including grace period)
		$license_info['gracedays'] = $license_key_parsed['capabilities']['gracedays'];
		$license_info['istrial'] = $license_key_parsed['capabilities']['istrial'];
		$license_info['graceperiod'] = "0";
		$license_info['graceremaining'] = $license_info['gracedays'];
		$licenseVersion = $license_info['license-version'];
		$isPerpetual = $licenseVersion == "0";
		$license_info['is_perpetual'] = $isPerpetual;
		$license_info['is_subscription'] = !$isPerpetual;
		$license_info['maint_expired'] = false;
		$license_info['maint_expiration'] = "No maintenance expiration";
		if ($utilityModel) {
			$license_info['product-id'] = $utilityProduct; // override product ID for utility model
			$license_info['productID'] = $utilityProduct; // override product ID for utility model
            $license_info['storage-capacity-GB'] = $utilityCapacityGB;
			$license_info['actual_expiration'] = $license_key_parsed['capabilities']['expiration']; // this is the actual expiration (before grace period)
            $license_info['istrial'] = false;
			$license_info['is_perpetual'] = true;
			$license_info['is_subscription'] = !$isPerpetual;
			$license_info['maint_expired'] = false;
			$license_info['maint_expiration'] = "No maintenance expiration";
			if ($using_default) {
				if ($platform == "amazon") {
					$regname = "Your AWS Marketplace Account";
				} elseif ($platform == "azure") {
					$regname = "Your Microsoft Azure Store Account";
				}
			}
			// use the built-in default key
			
		}
		if ($isPerpetual) {
			$license_info['maint_expiration'] = $license_info['expiration']; // expiration date is when maintenance expires for perpetual license
			$license_info['expiration'] = "00/00/0000"; // perpetual licenses do not expire
			$datearr = explode("/", $license_info['maint_expiration'], 3); // check for maintenance expiration
			$mon = $datearr[0];
			$day = $datearr[1];
			$year = $datearr[2];
			$expirestime = strtotime("$mon/$day/$year");
			$todaytime = strtotime($today);
			if ($todaytime >= $expirestime) // maintenance period has expired for perpetual license
			{
				$license_info['maint_expired'] = true; // flag maintenance as expired
				
			}
		}
		if ($license_info['expiration'] == "00/00/0000") {
			if ($utilityModel) $license_info['expiration'] = "On-demand license does not expire"; // no expiration for on-demand licenses
			else $license_info['expiration'] = "License does not expire";
			$license_info['actual_expiration'] = $license_info['expiration']; // actual expiration is same - non-expiring
			
		} else
		// check for expired license
		{
			$datearr = explode("/", $license_info['expiration'], 3);
			$mon = $datearr[0];
			$day = $datearr[1];
			$year = $datearr[2];
			$expirestime = strtotime("$mon/$day/$year");
			$graceexpire = $expirestime + 86400 * intval($license_info['gracedays']); // add grace period (in seconds) for final expiration date
			$todaytime = strtotime($today);
			$log->LogDebug("snas_license_info: Expires time: $expirestime, final expire time: $graceexpire, today time: $todaytime");
			$log->LogDebug("snas_license_info: Expires: " . date('m/d/Y', $expirestime) . ", grace expiration: " . date('m/d/Y', $graceexpire) . ", today: " . date('m/d/Y', $todaytime));
			if ($todaytime >= $expirestime) // flag grace period is in-effect as regular license period has expired
			{
				$license_info['graceperiod'] = "1";
				$graceremaining = ($graceexpire - $todaytime) / 86400; // days remaining
				$license_info['graceremaining'] = $graceremaining;
				$log->LogInfo("Grace period is in effect.  $graceremaining days remaining in grace period.");
			}
			if (intval($license_info['gracedays']) > 0) // update the final expiration date sent back to caller
			{
				$license_info['expiration'] = date('m/d/Y', $graceexpire); // revised expiration including grace period
				
			}
			$license_info['maint_expiration'] = $license_info['expiration']; // expiration date is when maintenance grace period expires for subscription licenses
			if ($todaytime >= $graceexpire && !$errorFlag) // license expired
			{
				$license_info['maint_expired'] = true; // flag maintenance as expired
				$errorMsg = "License validation failure - license and grace period have both expired (error 14.2)";
				$license_info['status'] = $errorMsg;
				$license_info['errMsg'] = $errorMsg;
				$log->LogError($errorMsg);
				$errorFlag = true; // flag as error and continue
				
			}
		}
		if ($testkey && !$manualActivate) $activationRequired = false; // do not test for activation if online activation and we're only testing the key / regname
		$log->LogDebug("snas_license_info: Activation status: " . var_export($activationRequired, true));
		if ($activationRequired) // verify activation code is valid and license was activated properly
		{
			$log->LogDebug("snas_license_info: Activation is required.");
			if (validate_license_activation_code($key, $license_key_parsed['sig'], $activationCode) !== true) // only return an error if we're testing/validating the key (if we're installing new key, ignore old codes)
			{
				$log->LogError("License is not properly activated.");
				if ($manualActivate) {
					$errorMsg = "License Failure. Invalid activation code. You must use a valid activation code (error 13.4)";
					sleep(10); // add time to thwart brute force attacks on manual activation via the API
					
				} else {
					$errorMsg = "License Failure. License is not properly activated. Activate and validate your license. (error 13.5)";
				}
				$license_info['status'] = $errorMsg;
				$license_info['errMsg'] = $errorMsg;
				$log->LogError($errorMsg);
				$log = init_logging($log_file_tmp);
				return $license_info;
			}
			$log->LogInfo("License is properly activated.");
			//
			// Check the hardware code's validity (prevents using license on multiple or different hardware/IP addresses)
			//
			$local_hardware_id = get_hardware_id(); // always compute fresh from local hardware to prevent tampering/license re-use
			if ($local_hardware_id != "127.0.0.1") // skip hwlock check if localhost (VM network is disconnected currently)
			{
				$hwid_hash = crc32($local_hardware_id); // create hardware ID hash from hardware ID string
                $ac1 = decode_actcode($system_uuid, $activationCode);
				$code = $hwid_hash / $ac1; // encrypt with activation code
				$hwlock = floor($code) % 9999; // create 4-digit hardware lock check code
				if ($hardware_lock != $hwlock && !$testkey) // see if calculated hwid matches the one on file (only return error if testing the key - not when installing new one)
				{
					$log->LogDebug("snas_license_info: local_hardware_id: $local_hardware_id, hwlock: $hwlock, hardware_lock: $hardware_lock, code: $code, hwid_hash: $hwid_hash");
					$errorMsg = "License Failure. License is not for this hardware ID: $local_hardware_id (error 13.6)";
					$license_info['status'] = $errorMsg;
					$license_info['errMsg'] = $errorMsg;
					$log->LogError($errorMsg);
					$errorFlag = true; // flag as error and continue
					$log = init_logging($log_file_tmp);
					return $license_info;
				}
				$license_info['hwlock'] = $hardware_lock;
			}
		} else
		// no activation is required
		{
			$log->LogDebug("snas_license_info: No activation is required.");
			if ($testkey) $log->LogDebug("snas_license_info: No activation is required when testing key for regname: $regname, key: $key");
			elseif ($utilityModel) $log->LogDebug("snas_license_info: No activation required with utility model.");
			elseif ($using_default) $log->LogDebug("snas_license_info: No activation required using default regname: $regname, key: $key");
			else $log->LogDebug("snas_license_info: No activation is required on regname: $regname, key: $key");
		}
		$skipCapacityCheck = false;

		if (!$utilityModel) {
			if ($license_key_parsed['capabilities']['product-id'] == 6) {
				// #6716 - SoftNAS Essentials (object filer) snkeygen bug workaround, key with product-id 6 is invalid and should be 22
				$log->LogDebug('snas_license_info: snkeygen workaround engaged, remap 6 to 22');
				$license_info['productID'] = $license_info['product-id'] = 22;
			} else {
				$log->LogDebug('snas_license_info: No SoftNAS Essentials (object filer) license found');
			}
		} else {
			$log->LogDebug('snas_license_info: not engaging snkeygen workaround');
		}
		if (!isset($productType)) {
			$productType = 'Unknown SoftNAS License Type';
		}

		// Resolve BYOL license details
		$byolLicenses = getByolLicenses();
		if (array_key_exists($license_info['product-id'], $byolLicenses)) {
            $log->LogDebug("snas_license_info: found BYOL license: " . var_export($byolLicenses[$license_info['product-id']], true));
            $productType = $byolLicenses[$license_info['product-id']]['product_name'];
		} else {
            if (isset($license_info['sku_name'])) {
                $log->LogDebug('snas_license_info: sku_name was found, overriding productType: '.$license_info['sku_name']);
                $productType = $license_info['sku_name'];
            } else {
                // invalid product type - error!
                $log->LogDebug("snas_license_info: Invalid product type, product-id: " . $license_info['product-id']);
                $errorMsg = "License Failure. License is not for valid product type (error 13.7)";
                $license_info['status'] = $errorMsg;
                $license_info['errMsg'] = $errorMsg;
                $log->LogError($errorMsg);
                $errorFlag = true; // flag as error and continue
				$log = init_logging($log_file_tmp);
				return $license_info;
            }
		}

		if (!isset($license_info['sku_name'])) {
			$license_info['producttype'] = $productType;
			if (!$utilityModel) {
				$license_info['sku_name'] = $productType.' (BYOL)';
			} else {
				$license_info['sku_name'] = $productType.' (legacy)';
			}
		} else {
			$license_info['producttype'] = $license_info['sku_name'];
			if (!$utilityModel) {
				$license_info['producttype'] .= ' (BYOL)';
			}
		}
		// Check licensed capacity vs. actual capacity
		$storageFree = $storageUsed = $storageTotal = 0;
		if (strpos($license_info['producttype'], 'Buurst&trade; Fuusion') !== false) {
			// #18734 - Ignore capacity in case of Fuusion license so return 0
			$license_info['storage-capacity-GB'] = 0;
			$skipCapacityCheck = true;
		}
		$capacityGB = $license_info['storage-capacity-GB']; // licensed capacity allowed
		$power = 3; // GB
		$sizeGB = ($storageTotal / pow(1024.0, $power));
		$sizeGB_log = number_format($sizeGB, 0, '.', ',');
		if (!$skipCapacityCheck) $log->LogDebug("snas_license_info: Licensed capacity: " . $capacityGB . ", GB Actual Storage Capacity: " . $sizeGB_log . " GB");
		$license_info['actual-storage-GB'] = number_format($sizeGB, 2); // actual storage capacity currently
		if ($sizeGB >= $capacityGB && !$skipCapacityCheck && !$errorFlag) // capacity exceeded
		{
			$errorMsg = "License validation failure - aggregate storage pool actual size '$sizeGB GB' exceeds licensed '$capacityGB GB' capacity maximum. Please upgrade your licensed capacity and try again. (error 14.3)";
			$license_info['status'] = $errorMsg;
			$license_info['errMsg'] = $errorMsg;
			$log->LogError($errorMsg);
			$errorFlag = true; // flag as error and continue
			$log = init_logging($log_file_tmp);
			return $license_info;
		}
		$licenseModel = $isPerpetual ? "Perpetual" : "Subscription";
		$licenseType = $license_info['istrial'] == "1" ? "TRIAL" : $licenseModel;
		// Create user-friendly values (some come from .INI file)
		if (strlen($regname) == 0 || $regname == "SoftNAS.com") // it's unregistered
		$regUser = "Unregistered, Built-in License";
		else $regUser = $regname;
		$license_info['regname'] = $regUser;
		// Get the system's unique hardware ID (if one wasn't passed in)
		if (strlen($hardware_id) == 0) {
			$hardware_id = get_hardware_id();
		}
		$license_info['hardware_id'] = $hardware_id; // hwid=192.168.146.100
		$license_info['licensetype'] = $licenseType;
		$nbr = $license_info['storage-capacity-GB'];
		$human_string = number_format($nbr, 0, '.', ',');
		$license_info['capacityGB'] = $human_string . " GB"; //#1893
		$nbr = str_replace(",", "", $license_info['actual-storage-GB']);
		$license_info['totalStorageGB'] = number_format($nbr, 0, '.', ',')." GB";
		$license_info['is_activated'] = $activationCode != ""; // non-null activation code indicates product is activated
		$license_info['currentkey'] = $key;
		if ($using_default) {
			if ($utilityModel) $license_info['currentkey'] = "Cloud License";
			else $license_info['currentkey'] = "Built-in License";
		}
		$log->LogDebug("snas_license_info: Check if Platinum/Fuusion License valid");
        $license_info['is_platinum'] = is_platinum_and_fuusion_license_valid();
        $license_info['is_fuusion'] = $license_info['is_platinum'];
		if ($fulldetails) // return "full details" about license and current version
		{
			$cmd = $_config['systemcmd']['cat'] . " ../version";
			$result = sudo_execute($cmd);
			if ($result['rv'] == 0) {
				$versionString = $result['output_str']; // e.g., 1.2.0.el6.x86_64
				$chunks = explode(".", $versionString);
				$majorversion = $chunks[0];
				$minorversion = $chunks[1];
				$updateversion = $chunks[2];
				$os_release = (count($chunks) > 3) ? $chunks[3] : "";
				$license_info['version'] = $versionString;
				$license_info['majorversion'] = $majorversion;
				$license_info['minorversion'] = $minorversion;
				$license_info['updateversion'] = $updateversion;
				$license_info['os_release'] = $os_release;
				$license_info['os_architecture'] = "x64";
			}
			$cmd = $_config['systemcmd']['hostname'] . ' --all-ip-addresses | head -1';
			$result = sudo_execute($cmd);
			if ($result['rv'] == 0) {
				$license_info['local_ip'] = $result['output_str'];
			}
		}
		$log->LogDebug("snas_license_info: Valid license response returned.");
	} else
	// Invalid License
	{
		$errorProc = true;
		$errorMsg = "License validation failure - Invalid Key or Registration Name (error 14.1)";
		$license_info['status'] = $errorMsg;
		$license_info['errMsg'] = $errorMsg;
		$log->LogError($errorMsg);
		$log = init_logging($log_file_tmp);
		return $license_info;
	}
	if ($errorFlag == false) {
		$license_info['valid'] = true;
	}
	$log = init_logging($log_file_tmp);
	return $license_info;
}
function get_registration_info($reply) {
	global $_config;
	$reginfo = (object)array();
	$uptime_path = $_config['proddir'] . "/config/uptime";
	$uptime = (int)(file_get_contents($uptime_path));
	$uptime_days = (int)($uptime / 86400);
	$softnas_ini = read_ini();
	// potential fix for warning
	if (isset($softnas_ini['registration']['registered'])) {
		$is_registered = $softnas_ini['registration']['registered'];
	} else {
		$is_registered = false;
	}
	$reginfo->is_registered = $is_registered;
	//$reginfo->is_registered = true;
	$reginfo->days_unregistered_count = $uptime_days;
	
	if(isset($softnas_ini['gettingstarted']['showonstartup'])
		&& $softnas_ini['gettingstarted']['showonstartup'] === "0"){
		$reginfo->show_getting_started = false;
	}else{
		$reginfo->show_getting_started = true;
	}
	
	//$result = sudo_execute("curl -s http://icanhazip.com");
	//$reginfo->ip_global = $result['output_str'];
	$reginfo->ip_local = get_local_ip();
	$reginfo->prodreg_instance_id = $reginfo->ip_local; // IP address or AWS Instance ID
	$reginfo->prodreg_account_label = "";
	$reginfo->prodreg_account = ""; // email or ASW Account ID
	if ($reply['platform'] == 'amazon') {
		$reginfo->prodreg_instance_id = $reply['hardware_id'];
		$reginfo->prodreg_account_label = "AWS Account ID";
		$cmd = "curl http://169.254.169.254/latest/dynamic/instance-identity/document";
		$result = sudo_execute($cmd);
		$result_str = $result['output_str'];
		$result_begin = stripos($result_str, '{');
		$result_str_json = substr($result_str, $result_begin);
		$result_obj = json_decode($result_str_json);
		$reginfo->prodreg_account = $result_obj->accountId;
	}
	if ($reply['platform'] == 'azure') {
		$reginfo->prodreg_account_label = "Azure Subscription ID";
		//$reginfo->prodreg_account = "";
		
	}
	if ($reply['platform'] == 'VM') {
		$reginfo->prodreg_account_label = "Account ID";
		$reginfo->prodreg_account = $reginfo->ip_local;
		//$result = sudo_execute("dmidecode -s system-uuid");
		$result = sudo_execute("dmidecode -s system-serial-number");
		$reginfo->prodreg_instance_id = $result['output_str'];
	}
	
	$prodreg_ini = read_ini("prodreg_inputs.ini");
	$reginfo->prodreg_not_show_again = false;
	if(isset($prodreg_ini['inputs']) && isset($prodreg_ini['inputs']['prodRegCheckNotShowAgain']) &&
	  $prodreg_ini['inputs']['prodRegCheckNotShowAgain'] === "true"){
		$reginfo->prodreg_not_show_again = true;
	}
	
	//require_once ('../integrations/kayako/index.php');
	//$k = new KayakoAPI();
	return $reginfo;
}

//
// Use CURL library to issue HTTPS request with basic authentication (if username and password are supplied)
//
function https_request($url, $username = "", $password = "", $custom_options = array(), $operation = "") {
	global $log;
	// Initialize session and set URL.
	$errorResponse = 'Remote Login Failed. Invalid username/password, incorrect remote hostname/IP address, or a network communications issue has occurred.<br /><br />Please confirm the login information, verify network connectivity, and try again.';
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	if (strlen($username) > 0 && strlen($password) > 0) {
		curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password); // in case Basic authentication supported by remote node (legacy 1.x support)
		
	}
	$cookiePath = "/tmp/session_cookie.txt";
	$fp = fopen($cookiePath, "w");
	fclose($fp);
	curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiePath);
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	// Construct a login request URL
	$urlPieces = parse_url($url);
	$log->LogDebug("https_request: urlPieces:");
	$log->LogDebug($urlPieces);
	$host = $urlPieces['host']; // get hostname or IP address component
	$urlLogin = "https://" . $host . "/buurst/login.php";
	//
	// ***** The following was disabled on 12-5-2013 in order to ship 2.0.0. SnapReplicate was not compatible
	//       with this form check, and we ran out of time to resolve this issue for the release.
	//       THIS NEEDS TO BE RE-ENABLED TO PREVENT HACKER ATTACKS AT NEXT AVAILABLE OPPORTUNITY.  rgb
	//     $validIP = ip2long($host);
	//      if( $validIP === false )  // could be a hostname
	//          $ipV4 = gethostbyname($host);
	//      else
	//          $ipV4 = $host;
	//      // set "form cookie" for remote login to work
	//      $log->LogDebug( "Generating formkey for ip: $ipV4..." );
	//      $formKey = generateFormKey( $ipV4 );               // original form way: setcookie('KEY_SS', $formKey, 0, "/");
	//      $cookie = "KEY_SS=$formKey";
	//      $log->LogDebug( "formkey cookie is: $cookie" );
	//      curl_setopt($ch, CURLOPT_COOKIE, $cookie);
	// *****
	// Formulate a login request
	curl_setopt($ch, CURLOPT_URL, $urlLogin); // attempt a login request
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POST, true);
	$data = array(
		'username' => $username,
		'password' => $password
	);
	// This is workaround added to skip dual auth login in remote server
	if ($operation) {
		$salt = 'P@ss4W0rd$';
		$pass = quick_keygen('S0ftn@s');
		$hash = quick_encrypt($pass.$salt, $operation);
		$data['hash'] = "$pass:$hash";
	}
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	$log->LogDebug("https_request: Sending login request to remote system's login.php...");
	foreach ($custom_options as $key => $val) {
		curl_setopt($ch, $key, $val);
	}
	$output = curl_exec($ch);
	$info = curl_getinfo($ch);
	// Set so curl_exec returns the result instead of outputting it.
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	// Get the response
	$log->LogDebug("https_request: Sending original request with login.php form post authentication...");
	foreach ($custom_options as $key => $val) {
		curl_setopt($ch, $key, $val);
	}
	$response = curl_exec($ch);
	$info = curl_getinfo($ch);
	$log->LogDebug("https_request: getinfo:");
	$log->LogDebug($info);
	$log->LogDebug("https_request: curl_exec response:");
	$log->LogDebug($response);
	// Check for 302 redirect to login.php (new session authentication)
	$redirectFound = "<title>302 Found</title>";
	$loginFound = "/buurst/login.php";
	if ($info['http_code'] == 401 || $info['http_code'] == 404) // basic auth or login.php not found
	{
		$log->LogDebug("\nhttps_request: Received 401 authentication challenge. Try legacy 401 authenticate...\n");
		curl_setopt($ch, CURLOPT_POST, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_URL, $url);
		$log->LogDebug("https_request: Resending original request directly to URL with basic auth...");
		foreach ($custom_options as $key => $val) {
			curl_setopt($ch, $key, $val);
		}
		$response = curl_exec($ch);
		$info = curl_getinfo($ch);
		$log->LogDebug("https_request: reply getinfo:");
		$log->LogDebug($info);
		$log->LogDebug("https_request: curl_exec response:");
		$log->LogDebug($response);
		if ($info['http_code'] != 200) // request failed
		{
			$log->LogDebug("https_request: Remote request failed.");
			$response = $errorResponse;
		}
	} else if (stripos($response, "captcha_iframe") !== false)
	{
		$log->LogDebug("https_request: Login attempt failed too many times.");
		$response = "Remote Login failed too many times. Please logout and login again from your browser to solve Captcha protection and then continue.";
	} else if ($info['http_code'] != 302) // login failed
	{
		$log->LogDebug("https_request: Login attempt failed.");
		$response = $errorResponse;
	} else
	// login succeeded, re-send original request
	{
		$log->LogDebug("\nhttps_request: Login succeeded.\n");
		curl_setopt($ch, CURLOPT_POST, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		// This is workaround added to skip dual auth login in remote server
		if (isset($data['hash'])) {
			require_once 'http_build_url.php';
			$url = http_build_url($url,array("query" => http_build_query(array('hash' => $data['hash']))), HTTP_URL_JOIN_QUERY );
		}
		curl_setopt($ch, CURLOPT_URL, $url);
		$log->LogDebug("https_request: Logged in. Resending original request...");
		foreach ($custom_options as $key => $val) {
			curl_setopt($ch, $key, $val);
		}
		$response = curl_exec($ch);
		$info = curl_getinfo($ch);
		$log->LogDebug("https_request: getinfo logged in:");
		$log->LogDebug($info);
		$log->LogDebug("https_request: curl_exec logged in response:");
		$log->LogDebug($response);
		if ($info['http_code'] != 200) // request failed
		{
			$log->LogDebug("https_request: Remote request failed.");
			$response = "Remote request failed. Details: $output";
		}
	}
	curl_close($ch);
	unlink($cookiePath); // get rid of cookie file that's no longer required
	return $response;
}

//
// Validate Flexnet before saving it to softnas.ini
//
function validate_flexnet_inputs($server_id, $activation_code, $server_dns){
	
	$server_id = trim($server_id);
	$activation_code = trim($activation_code);
	$server_dns = trim($server_dns);
	
	// Activatioon Code:
	$valid_activation = true;
	$activation_arr = explode("-", $activation_code);
	// checking if input is in format e.g.: ce66-8530-799b-4ac1-7f5d-7259-fca4-0427
	if(count($activation_arr) != 8){
		$valid_activation = false;
	}else{
	  	foreach($activation_arr as $i => $part){
			if (strlen("$part") != 4 || !ctype_xdigit($part)){
				$valid_activation = false;
				break;
			}
		}
	}
	if(!$valid_activation){
		return "Activation code is not valid";
	}

	// Local License Server DNS:
	if($server_dns != '' && !filter_var($server_dns, FILTER_VALIDATE_IP)) {
		return "Local License Server DNS is not valid";
	}
	
	// Hosted License Server ID:
	$url_flexnet = "https://softnas.compliance.flexnetoperations.com/instances/$server_id/request";
	$result = sudo_execute("/opt/softnas/bin/usage_capture_client -server $url_flexnet -feature x -count x");
	if(stripos($result['output_str'], "Server instance not found") !== false){
		return "Hosted License Server ID is not valid";
	}
	
	return "OK";
}

function is_address_reachable($address, $port = null, $maximum_time = 2, $connect_timeout = 5) {
	if ($port !== null) {
		$port = ":$port";
	}
	$result = sudo_execute("curl -v -I --connect-timeout $connect_timeout -m $maximum_time $address$port");
	if (stripos($result['output_str'], "Connected to $address") === false) {
		global $log;
		$log->LogInfo("is_address_reachable: Warning: $address$port not reachable");
		return false;
	}
	return true;
}
// return true if hostname exists, false if not (used for pre-check during bucket creation / mount)
function dns_check($hostname) {
	exec('nslookup '.$hostname, $nslookup_output, $nslookup_return);
	if ($nslookup_return === 0) {
		return true;
	} else {
		return false;
	}
}

function get_local_ip() {
	$result = sudo_execute("ifconfig | awk -F\"[ :]+\" '/inet / && !/127.0/ {print $3}' | head -n1");
	return $result['output_str'];
}

function get_global_ip() {
	return trim(file_get_contents("http://icanhazip.com"));
}

function get_azure_sku() {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'http://169.254.169.254/metadata/instance/compute/sku?api-version=2017-04-02&format=text');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$headers = array(
	    'Metadata:true'
	);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	$server_output = curl_exec($ch);
	curl_close($ch);
	return strtoupper($server_output);
}

/**
 * Validate and record platinum license
 * @param $license_key - Platinum license key
 * @param $reg_name - License owner
 * @return bool|array - array with license info if validated, false otherwise
 */
function validate_platinum_license($license_key = null, $reg_name = null) {
	global $log;

	if (!is_object($log)) {
		$log = init_logging();
	}
	$log_file_tmp = $log->GetLogFile();
	$log = init_logging(__DIR__.'/../logs/license.log');
	
	$existing_license = false;

	// If key and reg name isn't provided, check saved license
    $softnas_ini = read_ini();
	if ($license_key === null || $reg_name === null) {
		if (array_key_exists('license', $softnas_ini) && array_key_exists('platinum_key', $softnas_ini['license']) && array_key_exists('platinum_reg_name', $softnas_ini['license'])) {
            $existing_license = true;
            $license_key = $softnas_ini['license']['platinum_key'];
            $reg_name = $softnas_ini['license']['platinum_reg_name'];
		} else {
			$log->LogDebug("validate_platinum_license: Failed to validate saved Platinum license: No saved Platinum license.");
			$log = init_logging($log_file_tmp);
			return false;
		}
	}

    $cmd = __DIR__."/snvalidate-new '$license_key' '$reg_name'";
    $log->LogDebug("validate_platinum_license: Validating license $cmd");
    $result = sudo_execute($cmd);
    $log->LogDebug("validate_platinum_license: Validating result: {$result['output_str']}");
    if (stripos($result['output_str'], 'Invalid License')) {
    	$log = init_logging($log_file_tmp);
    	return false;
	}

	if (count($result['output_arr']) < 3) {
    	$log->LogError("Invalid output from validation command: {$result['output_str']}");
    	$log = init_logging($log_file_tmp);
    	return false;
	}

    $sig = '';
    foreach ($result['output_arr'] as $arrkey => $licenseParam) {
        if ($arrkey == "0") {
            preg_match('/^Status\: (.*)$/m', $licenseParam, $preg_out);
            $status = $preg_out[1];
            $log->LogDebug("validate_platinum_license: License status: $status");
            if (stripos($status, 'ok') === FALSE) {
            	$log = init_logging($log_file_tmp);
            	return false;
			}
        }
        if ($arrkey == "1") {
            preg_match('/^Capabilities\: (.*)$/m', $licenseParam, $preg_out);
            $capabilities = $preg_out[1];
            $log->LogDebug("validate_platinum_license: License capabilities: $capabilities");
            $capabilities_exploded = explode(':', $capabilities);
            if ($capabilities_exploded[0] != 30 && $capabilities_exploded[0] != 31) {
            	$log->LogError("Platinum/Fuusion check: License has wrong product ID");
            	$log = init_logging($log_file_tmp);
            	return false;
			}

            // parse capabilities string and find expiration date
            $expires_time = strtotime($capabilities_exploded[3]);
            $grace_expire = $expires_time + 86400 * $capabilities_exploded[4]; // add grace period (in seconds) for final expiration date
            $today = date("m/d/Y");
            $today_time = strtotime($today);
            $log->LogDebug("validate_platinum_license: Expires time: $expires_time, final expire time: $grace_expire, today time: $today_time");
            $log->LogDebug("validate_platinum_license: Expires: " . date('m/d/Y', $expires_time) . ", grace expiration: " . date('m/d/Y', $grace_expire) . ", today: $today");
            if ($today_time >= $grace_expire) {
                $log->LogError("Platinum license is expired");
                $log->LogDebug("validate_platinum_license: removing license from softnas.ini");
                $softnas_ini = read_ini();
                if (array_key_exists('platinum_key', $softnas_ini['license'])) unset($softnas_ini['license']['platinum_key']);
                if (array_key_exists('platinum_reg_name', $softnas_ini['license'])) unset($softnas_ini['license']['platinum_reg_name']);
                if (array_key_exists('platinum_activation_code', $softnas_ini['license'])) unset($softnas_ini['license']['platinum_activation_code']);
                // disable and stop all platinum features
                $log->LogDebug("validate_platinum_license: Disabling platinum features");
                $softnas_ini['flexfiles']['enabled'] = "false";
                super_script('flexfiles_services', 'disable');
                write_ini($softnas_ini);
                $log = init_logging($log_file_tmp);
                return false;
            } elseif ($today_time >= $expires_time) {
                // Grace period in progress
                $days_remaining = ($grace_expire - $today_time) / 86400; // days remaining
                $log->LogWarn("Platinum/Fuusion license is about to expire. Days until expiration: $days_remaining");
            }
        }
        if ($arrkey == "2") {
            preg_match('/^Sig\: (.*)$/m', $licenseParam, $preg_out);
            $sig = $preg_out[1];
            $log->LogDebug("validate_platinum_license: License sig: $sig");
        }
    }

	if ($existing_license && $reg_name !== "SoftNAS.com" && array_key_exists('platinum_activation_code', $softnas_ini['license'])) {
		$activation_code_result = validate_license_activation_code($license_key, $sig, $softnas_ini['license']['platinum_activation_code']);
    	if ($activation_code_result !== true) {
    		$log->LogError("Activation code validation failed: $activation_code_result");
    		$log = init_logging($log_file_tmp);
    		return false;
		}
	}

	$log = init_logging($log_file_tmp);
	return array(
		'status' => $status,
		'capabilities' => $capabilities,
		'sig' => $sig
	);
}

/**
 * @param $license_key - License key
 * @param $reg_name - Account name
 * @param $hwid - Hardware ID
 * @return array|string - Array with activation code and lock code on success, string with error otherwise
 */
function activate_license_key($license_key, $reg_name, $hwid) {
	global $log;
    // Activate the key
    //set POST variables
    $url = "https://www.softnas.com/apps/activation/softnas/activate.php";
    if (!isUp($url)) {
        $log->LogError("License activation may fail because isUp() says the update server (".$url.") is currently unavailable");
    }
    $fields = array(
        'licensekey' => urlencode($license_key) ,
        'regname' => urlencode($reg_name) ,
        'hwid' => urlencode($hwid) ,
    );
    $fields_string = "";
    //url-ify the data for the POST
    foreach ($fields as $key => $value) {
        $fields_string.= $key . '=' . $value . '&';
    }
    rtrim($fields_string, '&');
    //open connection
    $ch = curl_init();

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
    if (isset($proxy_host) and isset($proxy_port)) {
        curl_setopt($ch, CURLOPT_PROXY, "$proxy_host:$proxy_port");
        if (isset($proxy_user) and isset($proxy_pass)) {
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, "$proxy_user:$proxy_pass");
        }
    }

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
    $log->LogDebug("activate_license_key: JSON Response from activation processor");
    $log->LogDebug("activate_license_key: $response");
    if (strlen($response) == 0) {
        return "Activation proxy: Renewal activation attempt failed. Timeout or no response from activation website (possible network connection issue).";
    }
    $reply = json_decode($response, true);
    $log->LogInfo("Decoded activation reply:");
    $log->LogInfo($reply);
    if (!isset($reply['success']) || !$reply['success']) {
        $log->LogError("Activation proxy: Activation attempt failed. Details: " . $reply['msg']);
        return "Activation proxy: Activation attempt failed. Details: {$reply['msg']}";
    }
    return array(
    	'activationCode' => $reply['records']['activationCode'],
		'hwlockCode' => $reply['records']['hwlockCode']
	);
}

/**
 * @param $license_key - License key
 * @param $sig - key signature
 * @param $activation_code - Activation code
 * @return bool|string - true if successfully verified, string with error msg otherwise
 */
function validate_license_activation_code($license_key, $sig, $activation_code) {
	global $log;

	$softnas_ini = read_ini();
    // Check to see if this system has a unique licensing system hash code - if not, upgrade it now so that it does
    if (!isset($softnas_ini['system']['id'])) // unique system guid is used as master encryption key for this system (among other things)
    {
        $system_uuid = create_uuid(); // create a guid for this system
        $softnas_ini['system']['id'] = $system_uuid;
        write_ini($softnas_ini);
    }
    $system_uuid = $softnas_ini['system']['id'];

    $log->LogDebug("validate_license_activation_code: Verifying activation code.");
    $log->LogDebug("validate_license_activation_code: Key $license_key; Signature $sig; Activation code $activation_code.");
    $decSig = hexdec($sig);
    $keyval = 21058; // seed value
    $chunks = preg_split("/-/", $license_key); // get the elements of the key
    foreach ($chunks as $piece) {
        $aVal = hexdec($piece);
        $keyval^= $aVal; // XOR the key pieces together

    }
    $code = $decSig ^ $keyval;
    $activation_test = $code % 9999; // create 4-digit activation code

    // Bump up amount of digits to 4 with zeros in the beginning if activation test is less that 4 digits
    $activation_test_length = (string) strlen($activation_test);
    if ($activation_test_length < 4) {
        for ($num = 0; $num < 4 - $activation_test_length; $num++) {
            $activation_test = "0$activation_test";
        }
    }
    $license_info['is_activated'] = false;
    $ac1 = decode_actcode($system_uuid, $activation_code); // ensure we're comparing a decrypted activation code
    $log->LogDebug("validate_license_activation_code: $activation_test, ac1 = $ac1 (activationCode: $activation_code)");
    if ($activation_test != $ac1)
    {
        return "License Failure. Invalid activation code. You must use a valid activation code (error 13.4)";
    }
    $log->LogDebug("validate_license_activation_code: License is properly activated.");

    return true;
}

/**
 * Check if Platinum license is expired
 * @return bool - true if license is expired. false otherwise
 */
function is_platinum_and_fuusion_license_valid() {
    global $log;
    $log = init_logging(__DIR__.'/../logs/license.log');
    $log_file_tmp = $log->GetLogFile();

	// Check if Platinum is included with cloud license
	$prodCode = getCurrentProductCode();
    $cloud_license = prodCodeExists($prodCode);
	if (is_array($cloud_license) && ($cloud_license['platinum'] || $cloud_license['fuusion'])) {
        $log = init_logging($log_file_tmp);
		return true;
	}

	$platinum_license = validate_platinum_license();

	// Check if there is add-on license installed
	if (!is_array($platinum_license)) {
		// Check if license is included with BYOL license
		$softnas_ini = read_ini();
		if (array_key_exists('license', $softnas_ini) && array_key_exists('key', $softnas_ini['license']) && array_key_exists('regname', $softnas_ini['license'])) {
			$license_key_parsed = parseLicenseKey($softnas_ini['license']['key'], $softnas_ini['license']['regname']);
			if ($license_key_parsed !== FALSE) {
				if ($license_key_parsed['status'] == 'OK') {
					$byolLicenses = getByolLicenses();
					if ($byolLicenses[$license_key_parsed['capabilities']['product-id']]['platinum']) {
                        $log->LogDebug("is_platinum_and_fuusion_license_valid: Platinum license is included with installed BYOL license");
                        $log = init_logging($log_file_tmp);
                        return true;
					}
					if ($byolLicenses[$license_key_parsed['capabilities']['product-id']]['fuusion']) {
						$log->LogDebug("is_platinum_and_fuusion_license_valid: Fuusion license is included with installed BYOL license");
						$log = init_logging($log_file_tmp);
						return true;
					}
				}
			}
		}
        $log = init_logging($log_file_tmp);
		return false;
	}
    $log = init_logging($log_file_tmp);
	return true;
}

function getCurrentProductCode() {
	global $_config, $log;
	$platform = get_system_platform();

    if ($platform == "amazon")
    {
        $cmd = $_config['systemcmd']['wget'] . " -qO- http://169.254.169.254/latest/meta-data/product-codes/";
        $result = sudo_execute($cmd);
        if ($result['rv'] != 0) {
			$log->LogDebug("Amazon EC2 platform metadata not detected: ".var_export($result, true));
        } else {
            return $result['output_str'];
        }
    } elseif ($platform == "azure") {
        $sku_code = get_azure_sku();

        if (file_exists("../config/prod")) {
            return trim(file_get_contents("../config/prod"));
        }
        if (isset($sku_code) && !empty($sku_code)) {
            $e = findProdSKU($sku_code);
            return $e['productCode'];
        }
    } elseif ($platform == 'google') {
		return '';
	}
    return '';
}

function parseLicenseKey($key, $regname, $hardware_id='') {
    global $log;
    $log = init_logging(__DIR__.'/../logs/license.log');
    $log_file_tmp = $log->GetLogFile();

    $cmd = __DIR__."/snvalidate-new '$key' '$regname' '$hardware_id'";
    $result = sudo_execute($cmd);
    $log->LogDebug("parseLicenseKey: snvalidate-new RESULT: {$result['output_str']}");
    if ($result['rv'] != 0) {
        $cmd = __DIR__."/snvalidate '$key' '$regname' '$hardware_id'";
        $result = sudo_execute($cmd);
        $log->LogDebug("parseLicenseKey: snvalidate RESULT: {$result['output_str']}");
        if ($result['rv'] != 0) {
            $log = init_logging($log_file_tmp);
            return FALSE;
        }
    }

    $license = array();
    foreach ($result['output_arr'] as $arrkey => $licenseParam) {
        if ($arrkey == "0") {
            preg_match('/^Status\: (.*)$/m', $licenseParam, $preg_out);
            $license['status'] = $preg_out[1];
        }
        if ($arrkey == "1") {
            preg_match('/^Capabilities\: (.*)$/m', $licenseParam, $preg_out);
            // Parse the capabilities
            $pieces = explode(":", $preg_out[1]);
            // <product-id>:<lickey-version>:<max-storage-gb>:<days-to-expire>:<grace-days>:<is-trial>
            $license['capabilities']['product-id'] = $pieces[0];
            $license['capabilities']['license-version'] = $pieces[1]; // 0 == perpetual vers 1 license, 1=subscription vers 1 license
            $license['capabilities']['storage-capacity-GB'] = $pieces[2];
            $license['capabilities']['expiration'] = $pieces[3]; // this is the effective expiration (including grace period)
            $license['capabilities']['gracedays'] = $pieces[4];
            $license['capabilities']['istrial'] = $pieces[5];
        }
        if ($arrkey == "2") {
            preg_match('/^Sig\: (.*)$/m', $licenseParam, $preg_out);
            $license['sig'] = $preg_out[1];
        }
    }
    $log->LogDebug("parseLicenseKey: License: " . var_export($license, true));
    $log = init_logging($log_file_tmp);
    return $license;
}

?>
