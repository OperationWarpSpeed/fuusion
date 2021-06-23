<?php
require_once(__DIR__."/../integrations/segment/Segment.php");

require_once (__DIR__.'/KLogger.php');
require_once (__DIR__.'/utils.php');
require_once (__DIR__.'/logging.php');
require_once (__DIR__.'/cmdprocessor.php');
require_once (__DIR__.'/cmdproc2.php');
require_once (__DIR__.'/config.php');
require_once (__DIR__.'/snasutils.php');
require_once (__DIR__.'/session_functions.php');

class_alias('Segment', 'Analytics');

//startSegmentTracking();

function getWriteKey() {
	global $_config;
	$write_key = "";
	if (isset($_config['segment_write_key'])) {
		//set_encryption_key();
		//$write_key = quick_decrypt(ENCRYPTION_KEY, $_config['segment_write_key']);
		$write_key = $_config['segment_write_key'];
	}
	return $write_key;
}

/**
 * Initialization of Segment
 * 
 * @author Mihajlo 28.mar.2016
 * 
 */
function startSegmentTracking() {
	global $segment_started;
	global $_config;
	if (!isset($segment_started) || $segment_started !== true) {
		$write_key = getWriteKey();
		if (!$write_key || $write_key === "") {
			global $log;
			$log = init_logging();
			$err_msg = "Error while getting Segment write key.";
			if (isset($_config['segment_write_key'])) {
				$err_msg.= " (encrypted key: ".$_config['segment_write_key'].")";
			}
			$log->LogError($err_msg);
			return;
		}
		Segment::init($write_key);
		$segment_started = true;
	}
}

/**
 * Tracks user's standard requests
 * 
 * @author Mihajlo 28.mar.2016
 * @version Mihajlo 14.apr.2016 - removed detailed tracking
 * 
 */
function trackSnserverActivity() {
	
	global $_CLEAN;
	global $_config;
	$opcode = $_CLEAN['OP']['opcode'];
	$command = isset($_CLEAN['OP']['command']) ? $_CLEAN['OP']['command'] : "";
	
	// count some of requests (exclude repeating requests...):
	$include_requests = array(
		"deletepool", "availabledisks", "pools", "poolsimportabe", "pooldetails", "poolcommand", "createpool",
		"expandpool", "importpool", "readcache", "writelog", "addspare", "volumes", "createvolume",
		"editvolume", "deletevolume", "schedulelist", "schedulecommand", "snapshotlist", "snapcommand",
		"licenseactivate", "newlicense", "internallicense", "executeupdate", "ackagreement",
		"iscsitargetlist", "iscsicommand", "product_registration", "feature_request", "registration_exists",
		"diskdevices", "diskmgmt", "parted_command", "general_settings", "log_settings", "support_settings",
		"email_setup", "active_directory", "iscsicreatetarget", "backup", "samba_users",
		"s3cache_availabledisks", "remote_product_registration","prodreg_inputs",
		"save_license_settings", "get_auth_default_frame", "restart", "enableflexfiles", "acceptbetaagreement", "submit_platinum_license"
	);
	
	if (in_array($opcode, $include_requests)) {
		$requests_path = $_config['proddir']."/logs/segment_api_requests.log";
		sudo_execute("touch $requests_path && sudo chmod 770 $requests_path && chown root:apache $requests_path");
		$requests = file_exists($requests_path) ? (int)(file_get_contents($requests_path)) : 0;
		$requests++;
		file_put_contents($requests_path, $requests);
	}
	return;
}

function trackLogin() {
	global $_config;
	$logins_path = $_config['proddir']."/logs/segment_logins.log";
	$logins = file_exists($logins_path) ? (int)(file_get_contents($logins_path)) : 0;
	$logins++;
	file_put_contents($logins_path, $logins);
}


/**
 * Tracks user's activity and sent it to Segment
 * 
 * @author Mihajlo 22.mar.2016
 * @version Mihajlo 20.apr.2016 - 1 user id for whole instance
 * 
 * @param string $event
 * @param Array $properties
 * @param string $description
 */
function trackCustomActivity($event = "", $properties = array(), $user_id, $description = "") {
    global $log;
    $log = init_logging();
	$reply = Segment::track(array(
		"userId" => $user_id,
		"event" => $event,
		"description" => $description,
		"properties" => $properties
	));
	if ($reply) {
        $log->LogDebug("trackCustomActivity reply: request succeeded");
    } else {
        $log->LogDebug("trackCustomActivity reply: request failed");
    }
	//Segment::flush();
}

function makeDailyReport() {
	
	global $log;
	global $_config;
	if (!$log) {
		$log = init_logging();
	}
	$log->LogDebug("makeDailyReport: making daily segment report");
	// Instance info:
	$instance = array();
	global $getting_instance_info;
	$getting_instance_info = true;
	$softnas_config = read_ini();
	$track_activity = 'true';
	if (is_array($softnas_config) && is_array($softnas_config['system']) && isset($softnas_config['system']['track_activity'])) {
		$track_activity = $softnas_config['system']['track_activity'];
	}
	$licenseinfo = proc_licenseinfo();
	$instance_type = "";
	
	$result = sudo_execute("nproc --all");
	$number_of_cpus = trim($result['output_str']);
	$result = sudo_execute("cat /proc/meminfo | grep 'MemTotal:'");
	$amount_of_ram = $result['output_str'];
	$amount_of_ram = trim(str_ireplace("MemTotal:", "", $amount_of_ram));
	$result = sudo_execute("cat /proc/uptime | awk {'print $1'}");
	$system_uptime = trim($result['output_str']);
	$nfs_connection_count = `netstat -an | grep ":2049" | grep ESTABLISHED | wc -l`;
	$cifs_connection_count = `netstat -an | grep ":445" | grep ESTABLISHED | wc -l`;
	$requests_path =		$_config['proddir']."/logs/segment_api_requests.log";
	$logins_path =		$_config['proddir']."/logs/segment_logins.log";
	$ha_failover_path =	$_config['proddir']."/logs/segment_ha_failovers.log";
	$api_requests =	file_exists($requests_path) ? (int)(file_get_contents($requests_path)) : 0;
	$logins =		file_exists($logins_path) ? (int)(file_get_contents($logins_path)) : 0;
	$failovers =	file_exists($ha_failover_path) ? (int)(file_get_contents($ha_failover_path)) : 0;
	file_put_contents($requests_path, 0);
	file_put_contents($logins_path, 0);
	file_put_contents($ha_failover_path, 0);
	if (isset($licenseinfo['sku_code'])) {
		$instance['sku_code'] = $licenseinfo['sku_code'];
	} else {
		$instance['sku_code'] = NULL;
	}
	$instance['sku_name'] = $licenseinfo['sku_name'];
	$instance['customer_id'] = $licenseinfo['registration']->prodreg_account;
	$instance['platform'] = $licenseinfo['platform'];
    $instance['ip_global'] = trim(file_get_contents_proxy("https://softnas.com/ip.php"));
	$instance['identifier'] = $licenseinfo['registration']->prodreg_instance_id;
	$instance['type'] = $instance_type;
	$instance['location'] = "";
	$instance['product_id'] = $licenseinfo['product-id'];
	$instance['version'] = $licenseinfo['version'];
	$instance['actual_storage_gb'] = intval(formatSizeValue($licenseinfo['actual-storage-GB']));
	$instance['capacity_gb'] = intval(formatSizeValue($licenseinfo['capacityGB']));
	$instance['used_storage_gb'] = 0;
	$instance['license_actual_expiration'] = $licenseinfo['actual_expiration'];
	$instance['license_expiration'] = $licenseinfo['expiration'];
	$instance['is_activated'] = $licenseinfo['is_activated'];
	$instance['is_perpetual'] = $licenseinfo['is_perpetual'];
	$instance['using_ultrafast'] = file_exists('/var/www/softnas/config/.using_ultrafast');
	$softnasINI = read_ini('softnas.ini');
	$instance['using_liftandshift'] = false;
	if (isset($softnasINI['flexfiles']['enabled'])) {
		$instance['using_liftandshift'] = true;
	}
	$instance['is_subscription'] = $licenseinfo['is_subscription'];
	$instance['is_trial'] = parseToBoolValue($licenseinfo['istrial']);
	$instance['license_version'] = $licenseinfo['license-version'];
	$instance['license_type'] = $licenseinfo['licensetype'];
	$instance['mixed_pool_types'] = var_export(detectMixedPoolTypes(), true);
	// $instance['mixed_pool_types_in_use'] = poolTypes();
	$instance['regname'] = $licenseinfo['regname'];
	$instance['ha_failover_events'] = $failovers;
	$instance['system_uptime'] = $system_uptime;
	$instance['number_of_cpus'] = $number_of_cpus;
	$instance['amount_of_ram'] = intval(formatSizeValue($amount_of_ram));
	// $instance['api_requests'] = $api_requests;
	$instance['number_of_logins'] = $logins;
	$instance['cifs_connection_count'] = (int) $cifs_connection_count;
	$instance['nfs_connection_count'] = (int) $nfs_connection_count;
	// $instance['quickhelp_journey'] = array();
	// $tracking_file = __DIR__.'/../logs/quickhelp_journey.json';
	// if (file_exists($tracking_file)) {
	// 	$raw_data = json_decode(file_get_contents($tracking_file));
	// 	if (is_array($raw_data)) {
	// 		$instance['quickhelp_journey'] = $raw_data;
	// 		unset($raw_data);
	// 		file_put_contents($tracking_file, "[]");
	// 	}
	// }

	require_once __DIR__.'/Tier.php';
	$T = new Tier();
	$raw_tier_data = $T->segment_report();
	$instance['tieredstorage'] = $raw_tier_data['in_use'];

	if ($licenseinfo['platform'] == 'azure') {
		require_once (__DIR__.'/azure_utils.php');
		$azure_data = getAzureMetadata();
		$instance['identifier'] = $azure_data['vmId'];
		$instance['type'] = $azure_data['vmSize'];
		$instance['location'] = $azure_data['location'];
	}
	
	if ($licenseinfo['platform'] == 'amazon') {
		$aws_data = get_aws_instance_data();
		$instance['type'] = $aws_data->instanceType;
		$instance['location'] = $aws_data->region;
	}
	
	// ha
	$result = sudo_execute("service softnasha status");
	if (stripos($result['output_str'], "is running...") !== false ) {
		$instance['ha_active'] = true;
	} else {
		$instance['ha_active'] = false;
	}
	require_once __DIR__.'/sharedPool.php';
	$sharedConfig = SharedPool::read();
	$instance['ha_dcha'] = false;
	if (!empty($sharedConfig)) {
		$instance['ha_dcha'] = true;
	}
	$hastatus = read_ini('HA.ini');
	if (is_array($hastatus)) {
		$instance['ha_snapha'] = true;
	}
	
	// Volumes info:
	$volumes = proc_volumes();
	$instance['numbervols'] = count($volumes);
	
	// $snaprep_volumes = array();
	// $result = sudo_execute("cat ".$_config['proddir']."/config/snapvol-* | grep 'Volume = '");
	// if ($result['rv'] == 0) {
	// 	foreach ($result['output_arr'] as $i => $line) {
	// 		$snaprep_volumes[] = str_replace(array('Volume = ', '"'), '', $line);
	// 	}
	// }

	exec('sudo zpool list -H -o name', $pool_names);
	$instance['numberpools'] = count($pool_names);

	$instance['obj'] = false;
    foreach ($pool_names as $pool_name) {
        $device_type = poolDevicesType($pool_name);
        if ($device_type === 'object') {
            $instance['obj'] = true;
            break;
        }
	}

	$disks = proc_diskdevices();
	$instance['numberdisks'] = count($disks);

	$disk_pools = snas_get_disk_pools_simple();
	
	$nfs_volumes = segmentGetNfsShares();
	$cifs_volumes = segmentGetCifsShares();
	$afp_volumes = segmentGetAfpShares();
	$iscsi_targets = proc_iscsitargetlist();
	$iscsi_volumes = array();
	foreach ($iscsi_targets as $i => $target) {
		if (stripos($target['dev_path'], '(EMPTY TARGET)') === false) {
			$iscsi_volumes[] = $target['dev_path'];
		}
	}
	$instance['changed_dedup_default'] = false;
	$instance['changed_compression_default'] = false;
	foreach ($volumes as $i => $volume) {
		
	// 	$volumes[$i]['snapreplicate_enabled'] = in_array($volume['vol_name'], $snaprep_volumes);
	// 	$volumes[$i]['nfs'] = in_array("/export".$volume['vol_path'], $nfs_volumes);
	// 	$volumes[$i]['cifs'] = in_array($volume['vol_path'], $cifs_volumes);
	// 	$volumes[$i]['afp'] = in_array($volume['vol_path'], $afp_volumes);
	// 	$volumes[$i]['iscsi'] = in_array("/dev/zvol/".$volume['pool']."/".$volume['vol_name'], $iscsi_volumes);
		
	// 	$volumes[$i]['free_space'] = formatSizeValue($volumes[$i]['free_space']);
	// 	$volumes[$i]['total_space'] = formatSizeValue($volumes[$i]['total_space']);
	// 	$volumes[$i]['used_space'] = formatSizeValue($volumes[$i]['used_space']);
	// 	// #5991 - display total used storage space
		$instance['used_storage_gb'] += $volumes[$i]['used_space'];
		if (stripos(poolStorageType($volumes[$i]['pool']), 'Tiered') !== FALSE) {
			$instance['used_tieredstorage_gb'] += $volumes[$i]['used_space'];
		} else {
			$instance['used_regularstorage_gb'] += $volumes[$i]['used_space'];
		}
		$instance['logical_storage_gb'] += $volumes[$i]['used_logical'];
		if ($volume['dedup'] !== 'off') {
			$instance['changed_dedup_default'] = true;
		}
		if ($volume['compression'] !== 'on') {
			$instance['changed_compression_default'] = true;
		}
	// 	$volumes[$i]['usedbydataset'] = formatSizeValue($volumes[$i]['usedbydataset']);
	// 	$volumes[$i]['usedbysnapshots'] = formatSizeValue($volumes[$i]['usedbysnapshots']);
		
	// 	$volumes[$i]['pool_structure'] = array();
	// 	foreach ($disk_pools as $key => $disk_pool) {
	// 		$device = str_replace("/dev/", "", $key);
	// 		if ($volume['pool'] == $disk_pool && $device != $disk_pool) {
	// 			$device_type = segmentGetDeviceType($device);
	// 			$volumes[$i]['pool_structure'][$device] = $device_type;
	// 		}
	// 	}
		
	// 	unset($volumes[$i]['total_numeric']);
	// 	unset($volumes[$i]['free_numeric']);
	// 	unset($volumes[$i]['used_numeric']);
	// 	unset($volumes[$i]['Available']);
	// 	unset($volumes[$i]['Used']);
	// 	unset($volumes[$i]['Snapshots']);
	// 	unset($volumes[$i]['pct_used']);
	// 	unset($volumes[$i]['nfs_export']);
	// 	unset($volumes[$i]['hourlysnaps']);
	// 	unset($volumes[$i]['dailysnaps']);
	// 	unset($volumes[$i]['weeklysnaps']);
	// 	unset($volumes[$i]['reserve_units']);
	// 	unset($volumes[$i]['reserve_space']);
	}
	
	// // Network info:
	// $network = array();
	// $result = sudo_execute("cat /proc/net/dev | grep eth | awk {'print $1,$2,$10'}");
	// $interfaces = $result['output_arr'];
	// foreach ($interfaces as $i => $interface) {
	// 	$interface_arr = explode(' ', $interface);
	// 	$interface_name = $interface_arr[0];
	// 	if (substr($interface_name, -1, 1) === ":") {
	// 		$interface_name = substr($interface_name, 0, -1);
	// 	}
	// 	$network[$interface_name] = array(
	// 		'name' => $interface_name,
	// 		'bytes_in' => formatSizeValue($interface_arr[1]),
	// 		'bytes_out' => formatSizeValue($interface_arr[2])
	// 	);
	// }
	
	$monitoring_email = `grep NOTIFICATION_EMAIL /var/www/softnas/config/monitoring.ini | cut -d '=' -f 2 | sed 's/\"//g'`;
	$monitoring_email = trim($monitoring_email); // remove extra linebreak
	$instance['report_version'] = 'v2.5';
	$instance['currentkey'] = $licenseinfo['currentkey'];
	$instance['monitoring_email'] = 'none';
	if (!empty($monitoring_email) && $monitoring_email !== 'admin@example.com') {
		$instance['monitoring_email'] = $monitoring_email;
	}
    $log->LogDebug("makeDailyReport: report array:");
    $log->LogDebug($instance);
	return $instance;
}

function segmentGetDeviceType($device) {
	
	if (stripos($device, "sd") === 0) {
		$platform = get_system_platform();
		if ($platform == "VM") {
			return "VMware Virtual disk";
		}
		if ($platform == "azure") {
			return "Azure MSFT disk";
		}
	}
	if (stripos($device, "xvd") === 0) {
		return "Amazon EBS Xen Virtual Disk";
	}
	if (stripos($device, "s3-") === 0) {
		global $s3disks;
		if (!isset($s3disks)) {
			$s3disks = read_ini("s3config.ini");
		}
		$type = "";
		if (isset($s3disks[$device]) && isset($s3disks[$device]['type'])) {
			$type = $s3disks[$device]['type'] !== "" ? " (".$s3disks[$device]['type'].")" : " (amazon)";
		}
		return "Object Storage$type";
	}
	if (stripos($device, "artisan-") === 0) {
		return "Artisan OBS Cloud Disk";
	}
	if (stripos($device, "drbd") === 0) {
		return "DRBD Disk";
	}
	return "unknown";
}

function segmentGetNfsShares() {
	$nfs_shares = array();
	$result = sudo_execute("cat /etc/exports | grep '/export/'");
	$result_arr = $result['output_arr'];
	foreach ($result_arr as $i => $line) {
		$line_arr = explode(' ', $line);
		if (strpos($line_arr[0], "/export/") === 0) {
			$nfs_shares[] = $line_arr[0];
		}
	}
	return $nfs_shares;
}
function segmentGetCifsShares() {
	$cifs_shares = array();
	$result = sudo_execute("cat /etc/samba/smb.conf | grep 'path = '");
	$result_arr = $result['output_arr'];
	foreach ($result_arr as $i => $line) {
		$line_arr = explode('path = ', trim($line));
		if (count($line_arr) > 0 && $line_arr[0] === "") {
			$cifs_shares[] = $line_arr[1];
		}
	}
	return $cifs_shares;
}
function segmentGetAfpShares() {
	$afp_shares = array();
	$result = sudo_execute("cat /etc/netatalk/afp.conf | grep 'path = '");
	$result_arr = $result['output_arr'];
	foreach ($result_arr as $i => $line) {
		$line_arr = explode('path = ', trim($line));
		if (count($line_arr) > 0 && $line_arr[0] === "") {
			$afp_shares[] = $line_arr[1];
		}
	}
	return $afp_shares;
}

function sendSegmentData() {
	global $_config;
	global $log;
	$write_key = getWriteKey();
	if (!$write_key || $write_key === "") {
		return;
	}
	$file_name = $_config['proddir']."/logs/segment_report.log";
	$cmd = "php ".$_config['proddir']."/send.php --secret $write_key --file $file_name";
	$result = sudo_execute($cmd);
	if ($result['rv'] != 0) {
		$log = init_logging();
		$err_msg = "Error while sending data to Segment. command: $cmd";
		$log->LogError($err_msg);
		return;
	}
}

// calling sendSegmentData() from cron:
if (isset($argv[1]) && $argv[1] == "send_usage_report") {
	
	global $log;
	global $_config;
	if (!$log) {
		$log = init_logging();
	}
	
	$ini = read_ini("softnas.ini", $_config['proddir']."/config/");
	if (!isset($ini['system'])) {
		$ini['system'] = array();
	}
	if (isset($ini['system']['track_activity']) &&
		$ini['system']['track_activity'] == "false") {
		exit;
	}
	if (file_exists('/.no_segment_reporting')) {
        $log->LogDebug("Segment send_usage_report: flag exists, skipping report");
		exit;
	}
	if (!isset($ini['system']['track_user_id'])) {
		$ini['system']['track_user_id'] = create_uuid();
		write_ini($ini, "softnas.ini", $_config['proddir']."/config/");
	}
	
	startSegmentTracking();
	$info = makeDailyReport();
	trackCustomActivity("daily_report", $info, $ini['system']['track_user_id']);
	Segment::flush();
}

/**
 * Identify user and sent info to Segment
 * 
 * @author Mihajlo 22.mar.2016
 * @version Mihajlo 11.apr.2016 - create_uuid()
 * 
 */
function identifyUser($username) {
	global $log;
	global $_config;
	$log = init_logging();
	$user_id = "";
	$user_info = array();
	$user_random_id = null;
	
	$ini = read_ini("softnas.ini", $_config['proddir']."/config/");
	if (!isset($ini['identified_users'])) {
		$ini['identified_users'] = array();
	}
	if (!isset($ini['identified_users'][$username])) {
		$user_random_id = create_uuid();
		$sys_info = proc_licenseinfo();
		$reg_info = $sys_info['registration'];
		$user_info['name'] = $username;
		$user_info['instance_id'] = $reg_info->prodreg_instance_id;
		$user_info['account_id'] = $reg_info->prodreg_account;
		$user_info['ip_local'] = $reg_info->ip_local;
		$user_info['ip_global'] = trim(file_get_contents("http://icanhazip.com"));
		$segment_user = array("userId" => $user_random_id, "traits" => $user_info);
		
		Segment::identify($segment_user);
		if (Segment::flush()) {
			$ini['identified_users'][$username] = $user_random_id;
			write_ini($ini);
			db_session('segm_id', $user_random_id);
			session_commit();
		} else {
			$log->LogError("identifyUser: Error while sending identity");
		}
	} else {
		db_session('segm_id', $ini['identified_users'][$username]);
		session_commit();
	}
}
