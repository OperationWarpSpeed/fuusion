<?php
//
// cmdprocessor.php - SoftNAS(tm) command processor
//
// Copyright (c) SoftNAS Inc.  All Rights Reserved.
//
//
require_once ('utils.php');
require_once 'session_functions.php';



// Simple server response test (used during Welcome initialization)
//
//
function proc_serverTest() {
	$reply = array();
	return $reply;
}

// AWS Billing Check Status
function proc_meterstatus() {
	global $errorProc, $errorMsg, $successMsg;
	$reply = array();
	$platform = get_system_platform();
	if ($platform !== "amazon") { // #4635
		$reply['not_amazon'] = true;
		return $reply;
	}
	$fcp_config = __DIR__.'/../config/fcp.ini';
	if (!file_exists($fcp_config)) {
		$successMsg = 'No AWS usage meter configuration found.';
		exit(json_encode(array("success" => false, "msg" => $successMsg)));
	}
	$fcp = read_ini('fcp.ini');
	$reply = $fcp;
	return $reply;
}

function proc_islicensedfeature() {
	global $_CLEAN; // clean parameters
	global $errorProc;
	global $errorMsg;
	global $successMsg;
	global $log;
	global $isForm;
	$reply = array();
	$featurename = "";
	if (isset($_CLEAN['OP']['featurename'])) $featurename = $_CLEAN['OP']['featurename'];
	if (strlen($featurename) == 0) {
		$errorProc = true; // pass error back to client
		$errorMsg = "No 'featurename' passed.";
		$log->LogError($errorMsg);
		return $reply;
	}
	$isLicensed = snas_licensed_feature($featurename);
	$reply['isLicensedFeature'] = $isLicensed;
	$reply['featurename'] = $featurename;
	
	return $reply;
}

function proc_islicensedfeatures() {
	global $_CLEAN; // clean parameters
	global $errorProc;
	global $errorMsg;
	global $successMsg;
	global $log;
	global $isForm;
	$reply = array();
	if (isset($_CLEAN['OP']['features'])) $features = json_decode($_REQUEST['features']);
	if (!(is_array($features) && count($features) > 0)) {
		$errorProc = true; // pass error back to client
		$errorMsg = "No 'features' passed.";
		$log->LogError($errorMsg);
		return $reply;
	}
	$licensedFeatures = snas_licensed_features($features);
	//$reply['isLicensedFeature'] = $isLicensed;
	$reply['features'] = $licensedFeatures;
	return $reply;
}

function proc_licenseinfo() {
	global $_CLEAN; // clean parameters
	global $errorProc;
	global $errorMsg;
	global $successMsg;
	global $log;
	global $_config;
	global $isForm;
	global $extraProperties;

	$reply = array();
	$lickey = $regname = $hwid = "";
	if (isset($_CLEAN['OP']['lickey'])) $lickey = $_CLEAN['OP']['lickey'];
	if (isset($_CLEAN['OP']['regname'])) $regname = $_CLEAN['OP']['regname'];
	if (isset($_CLEAN['OP']['hardware_id'])) $hwid = $_CLEAN['OP']['hardware_id'];
	$fulldetails = false;
	if (isset($_CLEAN['OP']['fulldetails'])) $fulldetails = true;
	$activationCode = "";
	$testkey = false;
	$hardware_lock = - 1;
	//    $reply = snas_license_info( $lickey, $regname, $hwid, $activationCode, $hardware_lock, $testkey, $fulldetails );
	$reply = snas_license_info(); // use built-in license information
	
	if (isset($_CLEAN['OP']['handle_feature'])) {
		$feature = $_CLEAN['OP']['handle_feature'];
		if (snas_licensed_feature($feature, $reply)) {
			if ($feature == 'flexfiles_architect') {
				header("Location: https://{$_SERVER[HTTP_HOST]}/nifi");
				exit;
			}
			
		} else {
			echo "Option not supported by this license!";
			exit;
		}
	}
	
	// determine if DHCP or STATIC  (used during activation, to warn user against activating license keys on DHCP IP's)
	$cmd = "isdhcp";
	$params = "";
	$result = super_script($cmd, $params);
	if ($result['rv'] != 0) {
		$errorMsg = "Error determining whether IP is DHCP or STATIC!";
		$log->LogError($errorMsg);
	} else {
		if ($result['output_str'] == "1") // it's DHCP
		$reply['ipmode'] = "DHCP";
		else
		// it's STATIC
		$reply['ipmode'] = "STATIC";
	}
	// return the platform type
	$ini = read_ini();
	$system = $ini['system'];
	$platform = isset($system['platform']) ? $system['platform'] : get_system_platform();
	$reply['platform'] = $platform;
	$reply['registration'] = get_registration_info($reply);
	$reply['usage_licensing'] = isset($ini['license']) ? $ini['license'] : array();
	$versionStr = $reply['version'];
	$current_year = date("Y");
	$host = "";
	$result = sudo_execute("hostname");
	if ($result['rv'] == 0) {
		$host = trim($result['output_str']);
	} else {
		$log->logInfo("proc_licenseinfo: " . $result['output_str']);
	}

	$reply['currentYear'] = $current_year;		
	$reply['versionStr'] = $versionStr;		
	$reply['host'] = $host;

	$reply['header'] = "
	
<div class='header-wrap'>
	<div>
		<div style='display: inline-block; white-space: nowrap;'>
			<div style='float:left;'>
				<a href='/buurst/'><img id='logo' src='../images/Logo_20.png' 
					alt='Fuusion' /></a>
			</div>
			<div style='float:left; margin-left:4px; color: #030299'>
				<h2>Fuusion&reg;</h2>
			</div>
		</div>
		<div style='margin: -9px 0px 0px 12px;'>
			<span style='color:#707070; font-size: 11px; white-space: nowrap;'>
				Copyright &copy; 2012-$current_year Buurst Inc. All Rights Reserved.
			</span>
		</div>
	</div>
	
	<div>
		<div class='softnas-title main-area'>
			Buurst Fuusion&reg;, version $versionStr
		</div>
		<div class='softnas-title bottom-area'>
			host <span id='systemHost'>$host</span>
		</div>
	</div>
	
	<div style='float:right; text-align: right;white-space: nowrap;'>
		<span id='btnProdReg' class='btn-prod' onclick='javascript:iFrame.prodRegW.show();'>
			Product Registration
		</span> 
		<span id='btnFeatureReq' class='btn-prod' onclick=
			'javascript:window.open(\"mailto:features@buurst.com\")'>
			Feature Request
		</span>
	</div>
</div>
	";
		
	// #3025
	$pendingreboot_info = get_pendingreboot_info();
	$reply['reboot_array'] = $pendingreboot_info['reboot_array'];
	$reply['pendingreboot'] = $pendingreboot_info['reboot_text'];
	
	$drift = getDriftParameters($ini);
	$reply['drift_id'] = $drift->id;
	$reply['live_support_enabled'] = $drift->enabled;
	$reply['drift_user_id'] = $drift->external_id;
	
	$timestampInstance = sudo_execute('date -r /etc/softnas/firstinit.completed +%s');
	$timestampInstance = (int) $timestampInstance['output_str'];

	if($timestampInstance) {
		$diffSecounds = time() - $timestampInstance;
		$track30DaysOld = floor((($diffSecounds / 60) / 60) / 24) >= 30;

		// $track30DaysOld = floor($diffSecounds / 60) >= 30; // temporary: 30 minutes for tests

		//$ini = read_ini();
		$ini['track'] = isset($ini['track']) ? $ini['track'] : array();

		if($track30DaysOld && ((isset($ini['track']['tracked30DaysOld']) && $ini['track']['tracked30DaysOld'] !== 'true') || !isset($ini['track']['tracked30DaysOld']))) {
			$ini['track']['tracked30DaysOld'] = 'true';
		
			if (!write_ini_file($ini, "../config/softnas.ini", true)) {
				$errorProc = true;
				$errorMsg = 'Unable to save tracked 30 Days Old information!';
				$log->LogError($errorMsg);
			}

			$reply['track30DaysOld'] = $track30DaysOld;
			$reply['fingerprint'] = isset($ini['track']['fingerprint']) ? $ini['track']['fingerprint'] : null;
		}
	}

	if(isset($_CLEAN['OP']['authentication']) && $_CLEAN['OP']['authentication'] === 'true') {
		$extraProperties = getAuthInfo($reply);

		if(!$extraProperties) {
			$errorProc = true;
			$extraProperties = array();
		}

		return;
	}

	return $reply;
}

	function getAuthInfo($licenseInfo) {
		$logged = check_logged_in();

		if(!$logged) {
			return false;
		}

		$iniContent = read_ini();
		$iniLoginContent = read_ini('login.ini');
		$now = new DateTime();
		$dateExpiration = new DateTime(strtotime($licenseInfo['expiration']));
		$dateExpiration->add(new DateInterval('PT1S')); // 1 second after midnight on expiration date
		$expired = ($now >= $dateExpiration) && !$licenseInfo['is_perpetual'];
		$settings = proc_gettingstarted('getsettings');
		$username = isset($_SESSION) ? $_SESSION['USERNAME'] : null;
		$registration = json_decode(json_encode($licenseInfo['registration']), true);
		$registration['platform'] = $licenseInfo['platform'];
		$productId = $licenseInfo['product-id'];
		$licenseIsValid = $licenseInfo['valid'];
		$proModules = array(
			'snapreplicate',
			'iscsitarget',
			'iscsiinitiator',
			'schedsnapshots',
			'kerberos'
		);
		$menu = array(array(
			'text' => 'Storage',
			'qtip' => 'Storage administration',
			'iconCls' => 'x-fa fa-database',
			'expanded' => true,
			'data' => array(array(
				'text' => 'Volumes and LUNs',
				'qtip' => 'Administer Volumes, LUNs and Snapshots',
				'iconCls' => 'x-fa fa-cube',
				'module' => 'volumes',
				'leaf' => true
			),array(
				'text' => 'Storage Pools',
				'qtip' => 'Administer Storage Pools, aggregates of disk devices',
				'iconCls' => 'x-fa fa-cubes',
				'module' => 'pools',
				'leaf' => true
			),array(
				'text' => 'CIFS Shares',
				'qtip' => 'Share a Volume with Windows file sharing using CIFS/SMB protocol',
				'iconCls' => 'x-fa fa-share-alt-square',
				'module' => 'cifs',
				'leaf' => true
			),array(
				'text' => 'NFS Exports',
				'qtip' => 'Share a Volume with Network File System NFS protocol',
				'iconCls' => 'x-fa fa-file-text-o',
				'module' => 'nfsexports',
				'uri' => '../html/webadmin.php?path=/exports',
				'leaf' => true
			),array(
				'text' => 'AFP Volumes',
				'qtip' => 'Share a Volume with AFP protocol',
				'iconCls' => 'x-fa fa-cubes',
				'module' => 'afpvolumes',
				'uri' => '../html/webadmin.php?path=/netatalk3',
				'leaf' => true
			),array(
				'text' => 'Disk Devices',
				'qtip' => 'Disk device partitioning and configuration',
				'iconCls' => 'x-fa fa-hdd-o',
				'module' => 'diskdevices',
				'leaf' => true
			),array(
				'text' => 'iSCSI LUN Targets',
				'qtip' => 'Share block-device LUN Volumes as iSCSI targets using iSCSI protocol',
				'iconCls' => 'x-fa fa-hdd-o',
				'module' => 'iscsitarget',
				'leaf' => true
			),array(
				'text' => 'iSCSI SAN Initiators',
				'qtip' => 'Configure iSCSI initiators to connect remote SAN disks as iSCSI devices',
				'iconCls' => 'x-fa fa-hdd-o',
				'module' => 'iscsisaninitiators',
				'uri' => '../html/webadmin.php?path=/iscsi-client/',
				'leaf' => true
			))
		),array(
			'text' => 'SnapReplicate&trade; / SNAP HA',
			'qtip' => 'Configure block replication and mirroring',
			'iconCls' => 'x-fa fa-refresh',
			'module' => 'snapreplicate',
			'leaf' => true
		),array(
			'text' => 'dSphere',
			'iconCls' => 'x-fa fa-exchange',
			'module' => 'flexfiles',
			'expanded' => true,
			'needEnableFlexfiles' => true,
			'expired' => false,
			'data' => array(array(
				'text' => 'UltraFast Storage Accelerator',
				'tooltip' => 'UltraFast Storage Accelerator',
				'iconCls' => 'x-fa fa-dashboard',
				'module' => 'ultrafast',
				'leaf' => true,
				'needEnableFlexfiles' => true,
				'expired' => false
			),array(
				'text' => 'Lift and Shift',
				'iconCls' => 'x-fa fa-cloud',
				'module' => 'flexfilesliftshift',
				'leaf' => true,
				'needEnableFlexfiles' => true,
				'expired' => false
			),array(
				'text' => 'FlexFiles Architect',
				'iconCls' => 'x-fa fa-object-group',
				'module' => 'nifi',
				'uri' => '../html/nifi.php',
				'leaf' => true,
				'isBeta' => false,
				'expired' => false
			),array(
				'text' => 'FlexFiles Settings',
				'iconCls' => 'x-fa fa-gear',
				'module' => 'flexfilessettings',
				'leaf' => true,
				'needEnableFlexfiles' => true,
				'expired' => false
			))
		),array(
			'text' => 'Settings',
			'qtip' => 'Configure SoftNAS settings and options',
			'iconCls' => 'x-fa fa-cog',
			'data' => array(array(
				'text' => 'Administrator',
				'qtip' => 'General settings for the SoftNAS admin',
				'iconCls' => 'x-fa fa-user',
				'module' => 'administrator',
				'leaf' => true
			),array(
				'text' => 'Schedules',
				'qtip' => 'Configure and administer Schedules, which control when repetitive, automated tasks are carried out',
				'iconCls' => 'x-fa fa-clock-o',
				'module' => 'schedules',
				'leaf' => true
			),array(
				'text' => 'Change Password',
				'qtip' => 'Change user account passwords',
				'iconCls' => 'x-fa fa-lock',
				'module' => 'changepassword',
				'uri' => '../html/webadmin.php?path=/passwd/',
				'leaf' => true
			),array(
				'text' => 'Identity and Access Control',
				'qtip' => 'Configure identity management settings',
				'iconCls' => 'x-fa fa-cogs',
				'data' => array(array(
					'text' => 'idmapd daemon',
					'qtip' => 'Mapping of UID/GID in NFS Exports',
					'iconCls' => 'x-fa fa-file',
					'module' => 'idmapddaemon',
					'uri' => '../html/webadmin.php?path=/idmapd/',
					'leaf' => true
				),array(
					'text' => 'LDAP Server',
					'qtip' => 'Configuring the LDAP Server',
					'iconCls' => 'x-fa fa-server',
					'module' => 'ldapserver',
					'uri' => '../html/webadmin.php?path=/ldap-server/',
					'leaf' => true
				),array(
					'text' => 'LDAP Client',
					'qtip' => 'Configuring LDAP Client',
					'iconCls' => 'x-fa fa-server',
					'module' => 'ldapclient',
					'uri' => '../html/webadmin.php?path=/ldap-client/',
					'leaf' => true
				),array(
					'text' => 'Kerberos',
					'qtip' => 'Configure Kerberos for Active Directory',
					'iconCls' => 'x-fa fa-lock',
					'module' => 'kerberos',
					'uri' => '../html/webadmin.php?path=/krb5/',
					'leaf' => true
				))
			),array(
				'text' => 'Firewall',
				'qtip' => 'Configure firewall settings',
				'iconCls' => 'x-fa fa-shield',
				'module' => 'firewall',
				'uri' => '../html/webadmin.php?path=/firewall/',
				'leaf' => true
			),array(
				'text' => 'Licensing',
				'qtip' => 'View and administer SoftNAS licensing and enter license keys',
				'iconCls' => 'x-fa fa-key',
				'module' => 'license',
				'leaf' => true
			),array(
				'text' => 'Network Settings',
				'qtip' => 'View and configure SoftNAS networking options',
				'iconCls' => 'x-fa fa-sitemap',
				'module' => 'netowrksettings',
				'uri' => '../html/webadmin.php?path=/net/',
				'leaf' => true
			),array(
				'text' => 'General System Settings',
				'qtip' => 'Access all Linux system administration functions',
				'iconCls' => 'x-fa fa-cogs',
				'module' => 'generalsystemsettings',
				'uri' => '../html/webadmin.php',
				'leaf' => true
			),array(
				'text' => 'System Services',
				'iconCls' => 'x-fa fa-desktop',
				'qtip' => 'View, start and stop system services',
				'module' => 'systemservices',
				'uri' => '../html/webadmin.php?path=/init/',
				'leaf' => true
			),array(
				'text' => 'System Time',
				'qtip' => 'Set system time, timezone and NTP settings',
				'iconCls' => 'x-fa fa-clock-o',
				'module' => 'systemtime',
				'uri' => '../html/webadmin.php?path=/time/',
				'leaf' => true
			),array(
				'text' => 'Software Updates',
				'qtip' => 'View current version and apply available software updates',
				'iconCls' => 'x-fa fa-cloud-download',
				'module' => 'update',
				'leaf' => true
			),array(
				'text' => 'User Accounts',
				'qtip' => 'Create and manage user accounts',
				'iconCls' => 'x-fa fa-users',
				'module' => 'usermanagement',
				'leaf' => true
			),array(
				'text' => 'Backup/Restore',
				'qtip' => 'Create and restore configuration backup archives',
				'iconCls' => 'x-fa fa-users',
				'module' => 'backuprestore',
				'leaf' => true
			))
		),array(
			'text' => 'Documentation',
			'qtip' => 'Product documentation and help files',
			'iconCls' => 'x-fa fa-folder',
			'data' => array(array(
				'text' => 'Online Forum Help',
				'qtip' => 'Get online help from the SoftNAS Forums community',
				'iconCls' => 'x-fa fa-support',
				'module' => 'onlineforumhelp',
				'uri' => 'http://www.softnas.com/forums/forum.php',
				'leaf' => true
			),array(
				'text' => 'Getting Started',
				'qtip' => 'Click here for help getting started',
				'iconCls' => 'x-fa fa-book',
				'module' => 'gettingstarted',
				'leaf' => true
			),array(
				'text' => 'Planning Your Instance',
				'qtip' => 'Click here for help planning instance sizes',
				'iconCls' => 'x-fa fa-question-circle',
				'module' => 'planninginstance',
				'uri' => 'https://docs.softnas.com/display/SD/Planning+Your+Instance',
				'leaf' => true
			),array(
				'text' => 'Creating Your Instance',
				'qtip' => 'Click here for help creating instances',
				'iconCls' => 'x-fa fa-question-circle',
				'module' => 'creatinginstance',
				'uri' => 'https://docs.softnas.com/display/SD/Creating+Your+Instance',
				'leaf' => true
			),array(
				'text' => 'General Navigation',
				'qtip' => 'Click here for help with the user interface',
				'iconCls' => 'x-fa fa-question-circle',
				'module' => 'generalnavigation',
				'uri' => 'https://docs.softnas.com/display/SD/General+Navigation',
				'leaf' => true
			),array(
				'text' => 'Adding Storage',
				'qtip' => 'Click here for help expanding capacity',
				'iconCls' => 'x-fa fa-question-circle',
				'module' => 'addingstorage',
				'uri' => 'https://docs.softnas.com/display/SD/Adding+Storage',
				'leaf' => true
			),array(
				'text' => 'Creating and Managing Pools',
				'qtip' => 'Click here for help creating storage pools',
				'iconCls' => 'x-fa fa-question-circle',
				'module' => 'creatingpools',
				'uri' => 'https://docs.softnas.com/display/SD/Creating+and+Managing+Pools',
				'leaf' => true
			),array(
				'text' => 'Sharing Volumes',
				'qtip' => 'Click here for help sharing volumes',
				'iconCls' => 'x-fa fa-question-circle',
				'module' => 'sharingvolumes',
				'uri' => 'https://docs.softnas.com/display/SD/Sharing+Volumes',
				'leaf' => true
			),array(
				'text' => 'High Availability',
				'qtip' => 'Click here for help configuring multiple nodes for high availability',
				'iconCls' => 'x-fa fa-question-circle',
				'module' => 'highavailability',
				'uri' => 'https://docs.softnas.com/display/SD/High+Availability',
				'leaf' => true
			),array(
				'text' => 'Administration',
				'qtip' => 'Click here for help managing StorageCenter',
				'iconCls' => 'x-fa fa-question-circle',
				'module' => 'administrationguide',
				'uri' => 'https://docs.softnas.com/display/SD/Administration',
				'leaf' => true
			),array(
				'text' => 'Best Practices',
				'qtip' => 'Click here for best practices and general tips on managing StorageCenter',
				'iconCls' => 'x-fa fa-question-circle',
				'module' => 'bestpractices',
				'uri' => 'https://docs.softnas.com/display/SD/Best+Practices',
				'leaf' => true
			),array(
				'text' => 'SoftNAS Cloud(R) on CenturyLink',
				'qtip' => 'Click here for best practices and general tips on managing StorageCenter on the CenturyLink platform',
				'iconCls' => 'x-fa fa-question-circle',
				'module' => 'cloudcenturylink',
				'uri' => 'https://docs.softnas.com/pages/viewpage.action?pageId=65745',
				'leaf' => true
			),array(
				'text' => 'Installation Guide (HTML)',
				'qtip' => 'View installation guide documentation',
				'iconCls' => 'x-fa fa-question-circle',
				'module' => 'installationguide',
				'uri' => 'https://www.softnas.com/docs/softnas/v3/html/index.html',
				'leaf' => true
			),array(
				'text' => 'High Availability Guide (HTML)',
				'qtip' => 'View High Availability documentation',
				'iconCls' => 'x-fa fa-question-circle',
				'module' => 'highavailabilityguide',
				'uri' => 'https://www.softnas.com/docs/softnas/v3/snapha-html/index.htm',
				'leaf' => true
			),array(
				'text' => 'API Guide (HTML)',
				'qtip' => 'View API documentation',
				'iconCls' => 'x-fa fa-question-circle',
				'module' => 'apiguide',
				'uri' => 'https://www.softnas.com/docs/softnas/v3/api-html/',
				'leaf' => true
			),array(
				'text' => 'User Reference Guide (HTML)',
				'qtip' => 'View user reference guide documentation',
				'iconCls' => 'x-fa fa-question-circle',
				'module' => 'userreferenceguide',
				'uri' => 'https://www.softnas.com/docs/softnas/v3/html-reference-guide/index.html',
				'leaf' => true
			),array(
				'text' => 'SoftNAS CLI',
				'qtip' => 'Download SoftNAS CLI',
				'iconCls' => 'x-fa fa-file-o',
				'module' => 'softnascli',
				'uri' => 'https://www.softnas.com/docs/softnas/v3/api/softnas-cmd.zip',
				'leaf' => true
			),array(
				'text' => 'PVM CloudFormation Template',
				'qtip' => 'Download PVM CloudFormation Template',
				'iconCls' => 'x-fa fa-file-o',
				'module' => 'pvmcloudformationtemplate',
				'uri' => 'https://www.softnas.com/docs/softnas/v3/api/SoftNAS-AWSCloudTemplate-Basic.json',
				'leaf' => true
			),array(
				'text' => 'HVM Cloud Formation Template',
				'qtip' => 'Download HVM Cloud Formation Template',
				'iconCls' => 'x-fa fa-file-o',
				'module' => 'hvmcloudformationtemplate',
				'uri' => 'https://www.softnas.com/docs/softnas/v3/api/Softnas-AWSCloudTemplateHVM.json',
				'leaf' => true
			))
		));

		if(!$licenseIsValid || array_search($productId, array('1', '3', '4', '5')) === false) {
			array_unshift($menu, array(
				'text' => 'Upgrade to Pro',
				'qtip' => 'Click here to Upgrade to SoftNAS&reg; Professional Edition',
				'iconCls' => 'x-fa fa-level-up',
				'module' => 'upgradepro',
				'uri' => 'https://www.softnas.com/wp/products/softnas-pro-upgrade/',
				'leaf' => true
			));
		}

		switch($registration['platform']) {
		    case 'azure':
		        $registration['platformDescription'] = 'Microsoft Azure';
		        break;
		    case 'VM':
		        $registration['platformDescription'] = 'VMware';
		        break;
		    case 'amazon':
		        $registration['platformDescription'] = 'AWS';
		        break;
		    default:
		        $registration['platformDescription'] = 'Unknown';
		}

		$agreement = $settings['agreement'] === true;
		$registration = array_merge($registration, proc_prodreg_inputs('get'), array(
			'agreement' => $agreement,
			'productType' => $licenseInfo['producttype']
		));

		$showHome = $settings['showWelcomeOnStartup'] === '1';
		$showGettingStarted = isset($settings['showOnStartup']) && $settings['showOnStartup'] === '1';
		$support = $iniContent['support'];

		if(get_system_platform() !== 'amazon') {
			$meterStatus = array(
				'not_amazon' => true
			);
		}
		else {
			$meterStatus = file_exists(__DIR__.'/../config/fcp.ini') ? read_ini('fcp.ini') : array(
				'success' => false,
				'msg' => 'No AWS usage meter configuration found.'
			);
		}

		$storageCapacity = $licenseInfo['storage-capacity-GB'];
		$showGoldWelcomeStartup = isset($iniContent['gettingstarted']['showGoldWelcomeStartup']) ? $iniContent['gettingstarted']['showGoldWelcomeStartup'] : 'true';

		return array(
			'name' => $username,
			'username' => $username,
			'timeout' => $iniLoginContent['login']['timeout'],
			'email' => isset($support['useremail']) ? $support['useremail'] : null,
			'version' => $licenseInfo['version'],
			'host' => $licenseInfo['host'],
			'showHomeStartup' => $showHome,
			'showHome' => $showHome || !$agreement,
			'showGettingStarted' => $agreement && $showGettingStarted,
			'gettingStartedContent' => $settings['gettingStartedContent'],
			'showGoldWelcomeStartup' => ($storageCapacity >= 20000 && $storageCapacity <= 1000000) && $showGoldWelcomeStartup !== 'false',
			'updateInProgress' => is_update_process_active(),
			'rebootInProgress' => file_exists('/tmp/softnas-rebooting'),
			'success' => $logged,
			'registration' => $registration,
			'proModules' => $proModules,
			'licenseIsEssentials' => ($licenseIsValid && $productId === '2'),
			'licenseIsUtility' => $licenseInfo['model'] === 'utility',
			'meterStatus' => $meterStatus,
			//'intercom' => proc_get_intercom(),
			'schedSnapshotsIsLicensed' => snas_licensed_feature('schedsnapshots'),
			'license' => array(
				'info' => $licenseInfo,
				'isValid' => $licenseIsValid,
				'status' => $licenseInfo['status'],
				'isActivated' => $licenseInfo['is_activated'],
				'expired' => $expired,
				'today' => $licenseInfo['today'],
				'gracePeriod' => $licenseInfo['graceperiod'],
				'graceInEffect' => $licenseInfo['graceperiod'] === '1' && !$licenseInfo['is_perpetual']
			),
			'menu' => $menu
		);
	}

	function proc_newlicense() {
		global $_CLEAN; // clean parameters
		global $errorProc;
		global $errorMsg;
		global $successMsg;
		global $log;
		global $isForm;
		$isForm = true; // use a form-response ("data" contains fields)
		$reply = array();
		$testkey = ""; // set to non-null if only testing key validity
		if (isset($_CLEAN['OP']['testkey'])) {
			$testkey = $_CLEAN['OP']['testkey'];
			$log->LogDebug("testkey is: $testkey");
		}
		$newkey = $newreg = "";
		if (isset($_CLEAN['OP']['newkey'])) {
			$newkey = $_CLEAN['OP']['newkey'];
			$log->LogInfo("User entered new license key: $newkey");
		}
		if (isset($_CLEAN['OP']['regnew'])) {
			$regnew = $_CLEAN['OP']['regnew'];
			$log->LogInfo("User entered new registered owner: $regnew");
		}
		if (strlen($newkey) == 0 || strlen($regnew) == 0) $log->LogError("Missing required parameters!  newkey: $newkey  regnew: $regnew");
		$hwid = "";
		if (isset($_CLEAN['OP']['hwid'])) {
			$hwid = $_CLEAN['OP']['hwid'];
			$log->LogInfo("User entered new HWID: $hwid");
		}
		$activationCode = "";
		if (isset($_CLEAN['OP']['activationCode'])) {
			$activationCode = $_CLEAN['OP']['activationCode'];
			$ini = read_ini();
			$system = $ini['system'];
			$system_id = $system['id'];
			$encryptedActivationCode = encode_actcode($system_id, $activationCode); // encrypt the activation code using system id as the password
			$log->LogDebug("Activation code: $activationCode");
		}
		$hardwareLock = "";
		if (isset($_CLEAN['OP']['hardwareLock'])) {
			$hardwareLock = $_CLEAN['OP']['hardwareLock'];
			$log->LogInfo("Hardware lock: $hardwareLock");
		}
		$activationType = "";
		if (isset($_CLEAN['OP']['activationType'])) {
			$activationType = $_CLEAN['OP']['activationType'];
		}
		$log->LogDebug("Calling snas_license_info with: newkey: $newkey, regnew: $regnew, hwid: $hwid, activationCode: $activationCode, hardwareLock: $hardwareLock, testkey: $testkey");
		$log->LogDebug("activationType: $activationType");
		$testingOnly = $testkey != "";
		$testStr = $testingOnly ? "Testing key validity only." : "New license key addition (not testing)";
		$log->LogDebug("Type: $testStr");
		$fullDetails = true;
		$reply = snas_license_info($newkey, $regnew, $hwid, $activationCode, $hardwareLock, $testingOnly, $fullDetails, $activationType == 'manual');
		$log->LogDebug("Return from snas_license_info:");
		$log->LogDebug($reply);
		$valid = $reply['valid'];
		if ($valid) // we have a valid key, write it to the INI file (if not just testing the key)
		{
			if ($testkey == "") // not just testing the key - install the new key
			{
				$log->LogDebug("Saving new key: $newkey, registered to: $regnew, activation code: $activationCode");
				// Read existing INI contents
				$ini = read_ini();
				$license = $ini['license'];
				$license['key'] = $newkey;
				if (strlen($regnew) > 0) {
					$license['regname'] = $regnew;
				}
				if (strlen($activationCode) > 0) {
					$license['activationCode'] = $encryptedActivationCode;
				}
				if (strlen($hardwareLock) > 0) {
					$license['hardwareLock'] = $hardwareLock;
				}
				$ini['license'] = $license;
				// Write INI updates
				if (!write_ini_file($ini, "../config/softnas.ini", true)) {
					$errorProc = true; // pass error back to client
					$errorMsg = "Unable to save license information! (permissions problem)";
					$log->LogError($errorMsg);
				}
			} else {
				$log->LogDebug("Testing... NOT Saving new key: $newkey, registered to: $regnew, activation code: $encryptedActivationCode");
			}
		} else { // not valid
			$log->LogError($errorMsg);
		}
		return $reply;
	}
	//
	// Revert to using built-in license
	//
	function proc_internallicense() {
		global $_CLEAN; // clean parameters
		global $errorProc;
		global $errorMsg;
		global $successMsg;
		global $log;
		$reply = array();
		// Read existing INI contents
		$ini = read_ini();
		$license = $ini['license'];
		unset($license['key']);
		unset($license['regname']);
		unset($license['hwid']);
		unset($license['activationCode']);
		unset($license['hardwareLock']);
		$ini['license'] = $license;
		// Write INI updates
		if (!write_ini_file($ini, "../config/softnas.ini", true)) {
			$errorProc = true; // pass error back to client
			$errorMsg = "Unable to save license information! (permissions problem)";
			$log->LogError($errorMsg);
			return $reply;
		}
		$reply = snas_license_info();
		$valid = $reply['valid'];
		if (!$valid) // should not happen
		{ // not valid
			$log->LogError("Invalid license, not accepted!");
		}
		return $reply;
	}
	function proc_licenseactivate() {
		global $_CLEAN; // clean parameters
		global $errorProc;
		global $errorMsg;
		global $log;
		$newkey = $newreg = "";
		if (isset($_CLEAN['OP']['licensekey'])) {
			$newkey = $_CLEAN['OP']['licensekey'];
		}
		if (isset($_CLEAN['OP']['regname'])) {
			$regnew = $_CLEAN['OP']['regname'];
		}
		$hwid = get_hardware_id();
		$log->LogInfo("Received activation proxy request");
		$log->LogInfo("newkey: $newkey, regnew: $regnew, hwid: $hwid");

		$activation_result = activate_license_key($newkey, $regnew, $hwid);

		if (!is_array($activation_result)) {
			$errorProc = true;
			$errorMsg = $activation_result;
			return array();
		}
        $log->LogInfo("Activation proxy: successfully activated new license key.");
		return $activation_result;
	}

	function proc_save_license_settings() {
		global $_CLEAN; // clean parameters
		global $errorProc;
		global $errorMsg;
		global $successMsg;
		global $log;
		global $isForm;
		$reply = array();
		$server_id = '';
		$activation_code = '';
		$server_dns = '';
		if (isset($_CLEAN['OP']['server_id']) && $_CLEAN['OP']['server_id'] != ''
		  && isset($_CLEAN['OP']['activation_code']) && $_CLEAN['OP']['activation_code'] != '') {
			$server_id = $_CLEAN['OP']['server_id'];
			$activation_code = $_CLEAN['OP']['activation_code'];
		} else {
			$errorProc = true;
			$errorMsg = "Server ID and Activation code are required";
			$log->LogError($errorMsg);
			$reply['errMsg'] = $errorMsg;
			return $reply;
		}
		if (isset($_CLEAN['OP']['server_dns'])) {
			$server_dns = $_CLEAN['OP']['server_dns'];
		}
		
		$valid_msg = validate_flexnet_inputs($server_id, $activation_code, $server_dns);
		if($valid_msg !== "OK"){
			$errorProc = true;
			$errorMsg = $valid_msg;
			$log->LogError($errorMsg);
			$reply['errMsg'] = $errorMsg;
			return $reply;
		}
		
		$ini = read_ini();
		$ini['license']['instanceid'] = $server_id;
		$ini['license']['rightsid'] = $activation_code;
		$ini['license']['server_dns'] = $server_dns;
		write_ini($ini);
		//$log->LogInfo( "Received activation proxy request" );
		//$log->LogInfo( "newkey: $newkey, regnew: $regnew, hwid: $hwid" );
		return $reply;
	}

	function proc_registration_setnotshowagain() {
		global $_CLEAN;
		global $errorProc;

		$ini = read_ini('prodreg_inputs.ini');
		$ini['inputs']['prodRegCheckNotShowAgain'] = $_CLEAN['OP']['notShowAgain'];
				
		$errorProc = !write_ini($ini, 'prodreg_inputs.ini');
	}

	function proc_registration_exists($emailParam = null, $companyParam = null) {
		global $_CLEAN;
		global $errorProc;
		global $errorMsg;
		global $successMsg;
		global $log;
		$reply = array();
		$email = isset($_CLEAN['OP']['prodRegBusinessEmail']) ? $_CLEAN['OP']['prodRegBusinessEmail'] : $emailParam;
		$company = isset($_CLEAN['OP']['prodRegCompany']) ? $_CLEAN['OP']['prodRegCompany'] : $companyParam;
		$email = trim($email);
		$company = trim($company);
		require_once ('../integrations/kayako/index.php');
		$k = new KayakoAPI();
		$reply['user_exisits'] = $k->userExists(array(
			'query' => $email
		));
		$reply['company_exisits'] = $k->organizationExists(array(
			'name' => $company
		));

		// repeat because have typo in word "exisits"		
		$reply['user_exists'] = $k->userExists(array(		
			'query' => $email		
		));		
		$reply['company_exists'] = $k->organizationExists(array(		
			'name' => $company		
		));

		//$reginfo->testUser = $k->userExists(array('query' => 'calvinfroedge@gmail.com'));
		//$reginfo->testOrg = $k->organizationExists(array('name' => 'test'));
		return $reply;
	}
	function proc_product_registration() {
		global $_CLEAN;
		global $errorProc;
		global $errorMsg;
		global $successMsg;
		global $log;
		$reply = array();
		$reply['warning'] = "";
		//$req = (object)$_REQUEST;
		$req = (object)array();
		$data = isset($_CLEAN['OP']['data']) ? json_decode($_CLEAN['OP']['data'], true) : $_REQUEST;

		foreach ($data as $id => $val) {
			//$req->$id = $val;
			$req->$id = str_ireplace('&', '%26', htmlspecialchars_decode($val));
		}
		//$prodRegFullname = str_ireplace(' ', '%20', "$req->prodRegFirstName $req->prodRegLastName");
		$prodRegFullname = str_ireplace(array(
			' ',
			'&'
		) , array(
			'%20',
			'%26'
		) , htmlspecialchars_decode($data['prodRegFirstName'] . " " . $data['prodRegLastName']));
		/*$req = (object)array(
		'prodRegFirstName' =>		'M12',
		'prodRegLastName' =>		'Z',
		'prodRegJobFunction' =>		'IT - Data Architect',
		'prodRegJobTitle' =>		'x',
		'prodRegBusinessPhone' =>	'063',
		'prodRegBusinessEmail' =>	'nekarkiraj12@linki.co',
		//'prodRegCreateAccount' =>	'true',
		//'prodRegPassword' =>		'Pass4W0rd',
		//'prodRegPasswordConfirm' =>	'Pass4W0rd',
		'prodRegAccountId' =>		'892064206063',
		'prodRegInstanceId' =>		'i-e6af8107',
		'prodRegCompany' =>			'test12.20.12',
		'prodRegIndustry' =>		'Healthcare',
		'prodRegAddress1' =>		'PK27',
		'prodRegAddress2' =>		'...',
		'prodRegCity' =>			'Novi Sad',
		'prodRegZip' =>				'21000',
		'prodRegCountry' =>			'California',
		'prodRegCheckUpgrades' =>	'true',
		'prodRegCheckPromotions' =>	'true'
		);*/
		$sendNotifications = ($req->prodRegCheckUpgrades == 'true') ? "yes" : "no";
		$sendPromotions = ($req->prodRegCheckPromotions == 'true') ? "yes" : "no";
		$createAccount = ($req->prodRegCreateAccount == 'true') ? true : false;
		
		// #2628 - adding validation to prevent "Submit error..."
		$valid_msg = "OK";
		if( (isset($req->prodRegFirstName) && $req->prodRegFirstName != "" && strlen($req->prodRegFirstName) > 50) ||
			(isset($req->prodRegLastName) && $req->prodRegLastName != "" && strlen($req->prodRegLastName) > 50) ||
			(isset($req->prodRegJobFunction) && $req->prodRegJobFunction != "" && strlen($req->prodRegJobFunction) > 50) ||
			(isset($req->prodRegBusinessPhone) && $req->prodRegBusinessPhone != "" && strlen($req->prodRegBusinessPhone) > 20) ||
			(isset($req->prodRegBusinessEmail) && $req->prodRegBusinessEmail != "" && strlen($req->prodRegBusinessEmail) > 100) ||
			(isset($req->prodRegCompany) && $req->prodRegCompany != "" && strlen($req->prodRegCompany) > 100) ||
			(isset($req->prodRegAddress1) && $req->prodRegAddress1 != "" && strlen($req->prodRegAddress1) > 50) ||
			(isset($req->prodRegAddress2) && $req->prodRegAddress2 != "" && strlen($req->prodRegAddress2) > 50) ||
			(isset($req->prodRegCity) && $req->prodRegCity != "" && strlen($req->prodRegCity) > 50) ||
			(isset($req->prodRegZip) && $req->prodRegZip != "" && strlen($req->prodRegZip) > 15)
		) {
			$valid_msg = "Submit error: Some of fields are not entered correctly.";
		}
		
		if (isset($req->prodRegBusinessEmail) && !filter_var($req->prodRegBusinessEmail, FILTER_VALIDATE_EMAIL)) {
			$valid_msg = "Submit error: Email is not in valid format";
		}
		if ($valid_msg != "OK") {
			$errorProc = true;
			$errorMsg = $valid_msg;
			$log->LogError('product_registration - ' . $errorMsg);
			$reply['errMsg'] = $errorMsg;
			return $reply;
		}
		
		$licenseinfo = proc_licenseinfo();
		$platforms = array('azure' => 'Azure', 'amazon' => 'AWS', 'VM' => 'VMware');
		$platform_name = $platforms[$licenseinfo['platform']];
		$capacity = intval(formatSizeValue($licenseinfo['storage-capacity-GB']));
		$capacity_tb = intval($capacity/1024).'TB';

		$edition = '';
		if ($licenseinfo['is_platinum']) {
			$edition = 'Platinum';
			if ((int)($licenseinfo['product-id']) == 31) {
				$edition = 'Fuusion';
			}
		} elseif (stripos($licenseinfo['producttype'], 'essentials') !== false) {
			$edition = 'Essentials';
		} elseif (stripos($licenseinfo['producttype'], 'enterprise') !== false) {
			$edition = 'Enterprise';
		} elseif (stripos($licenseinfo['producttype'], 'developer') !== false) {
			$edition = 'Developer';
		}

		$license_type = 'Marketplace';
		$byol_licenses = getByolLicenses();
		if (array_key_exists($licenseinfo['product-id'], $byol_licenses)) {
			$license_type = 'BYOL';
		}

		$instance_type = 'unknown';
		if ($licenseinfo['platform'] == 'amazon') {
			$aws_data = get_aws_instance_identity();
			$instance_type = $aws_data['instanceType'];
		}
		if ($licenseinfo['platform'] == 'azure') {
			require_once __DIR__.'/azure_utils.php';
			$azure_data = getAzureMetadata();
			$instance_type = $azure_data['vmSize'];
		}

		$msg = "
	<style>	* { font-family: 'Tahoma', 'Arial', sans-serif; }</style>
	<h2>New Product Registration request</h2>
	
	<h3>User info:</h3>
	First Name:		<b>$req->prodRegFirstName</b><br/>
	Last Name:		<b>$req->prodRegLastName</b><br/>
	Job Function:	<b>$req->prodRegJobFunction</b><br/>
	Job Title:		<b>$req->prodRegJobTitle</b><br/>
	Business Phone:	<b>$req->prodRegBusinessPhone</b><br/>
	Business Email:	<b>$req->prodRegBusinessEmail</b><br/>
	<br/>
	
	<h3>Business information:</h3>
	AWS Account ID:		<b>$req->prodRegAccountId</b><br/>
	Instance ID:		<b>$req->prodRegInstanceId</b><br/>
	Edition:			<b>$edition</b><br/>
	Capacity:			<b>$capacity_tb</b><br/>
	License Type:		<b>$license_type</b><br/>
	Cloud:				<b>$platform_name</b><br/>
	Instance/VM:		<b>$instance_type</b><br/>
	<br/>
	
	Company:			<b>$req->prodRegCompany</b><br/>
	Industry:			<b>$req->prodRegIndustry</b><br/>
	Address 1:			<b>$req->prodRegAddress1</b><br/>
	Address 2:			<b>$req->prodRegAddress2</b><br/>
	City:				<b>$req->prodRegCity</b><br/>
	Zip or Postal Code:	<b>$req->prodRegZip</b><br/>
	Country:			<b>$req->prodRegCountry</b><br/>
	<br/>
	
	<h3>Send me emails:</h3>
	Notifications:	<b>$sendNotifications</b><br/>
	Promotions:		<b>$sendPromotions</b><br/>
	<br/>
	";
		//exit($msg);
		require_once ('../integrations/kayako/index.php');
		$k = new KayakoAPI();
		$kayako_user = $k->userExists(array(
			'query' => trim($req->prodRegBusinessEmail)
		));
		$kayako_company = $k->organizationExists(array(
			'name' => trim($req->prodRegCompany)
		));
		$user_id = null;
		if ($kayako_user) {
			$user_id = (string)$kayako_user->id;
			$reply['user_exists'] = 'yes';
			$log->LogDebug("product_registration - user exists: $user_id , $req->prodRegBusinessEmail");
		}
		$state = "";
		$country = $req->prodRegCountry;
		if ($req->prodRegCountryIndex <= 50) {
			$state = $req->prodRegCountry;
			$country = 'United States';
		}
		$company_id = null;
		if ($kayako_company) {
			$company_id = (string)$kayako_company->id;
		} else {
			$reply['creating_company'] = 'yes';
			$kayako_company = $k->createOrganization(array(
				'name' => trim($req->prodRegCompany) ,
				'address' => "$req->prodRegAddress1 , $req->prodRegAddress2",
				'city' => $req->prodRegCity,
				'state' => $state,
				'postalcode' => $req->prodRegZip,
				'country' => $country,
				'phone' => $req->prodRegBusinessPhone
				/*,
				'fax' =>			"",
				'website' =>		""*/
			));
			if ($kayako_company) {
				$company_id = (string)$kayako_company->userorganization->id;
			}
		}
		if (($req->prodRegPassword != $req->prodRegPasswordConfirm || $req->prodRegPassword == "") && $createAccount) {
			$errorProc = true;
			$errorMsg = "Submit error, wrongly entered or empty password";
			$log->LogError('product_registration - ' . $errorMsg . ' - ' . $msg);
			$reply['errMsg'] = $errorMsg;
			return $reply;
		}
		if (!$createAccount && !$kayako_user) {
			$errorProc = true;
			$errorMsg = "Submit error, you have to create Support Account";
			$log->LogError('product_registration - ' . $errorMsg . ' - ' . $msg);
			$reply['errMsg'] = $errorMsg;
			return $reply;
		}
		if ($createAccount && !$kayako_user) {
			$reply['creating_account'] = 'yes';
			$kayako_user = $k->createUser(array(
				//'fullname' =>				"$req->prodRegFirstName%20$req->prodRegLastName",
				'fullname' => $prodRegFullname,
				'password' => $req->prodRegPassword,
				'email' => $req->prodRegBusinessEmail,
				'userorganizationid' => $company_id,
				'salutation' => $req->prodRegJobFunction, //???
				'designation' => $req->prodRegJobTitle,
				'phone' => $req->prodRegBusinessPhone,
			));
			if ($kayako_user) {
				$user_id = (string)$kayako_user->user->id;
			}
		}
		/*if(!$user_id)
		{
		// Account for recieving messages from unregistered users
		$user_id = '782'; 
		}*/
		$ticket = $k->createTicket(array(
			'subject' => "New product registration ($prodRegFullname)",
			//'fullname' =>	"$req->prodRegFirstName%20$req->prodRegLastName",
			'fullname' => $prodRegFullname,
			'email' => $req->prodRegBusinessEmail,
			'contents' => "$msg",
			'userid' => $user_id
		));
		if (!$ticket) {
			$errorProc = true;
			$errorMsg = "Submit error, ticket not sent";
			$log->LogError('product_registration - ' . $errorMsg . ' - ' . $msg);
			$reply['errMsg'] = $errorMsg;
			$reply['ticket'] = $ticket;
			return $reply;
		}
		$softnas_ini = read_ini();
		$softnas_ini['registration']['registered'] = 'true';
		$softnas_ini['registration']['business_email'] = $req->prodRegBusinessEmail;
		write_ini($softnas_ini);
		$successMsg = "Submit successful";
		$log->LogInfo('product_registration - success - ' . $msg);
		prodreg_save_monit_email($req->prodRegBusinessEmail, $reply);
		/*   Remote registration:           */
		$snaprepini = read_ini('snaprepstatus.ini');
		$remote = $snaprepini['Relationship1'];
		if ($remote && $remote['RemoteNode']) {
			if (!$softnas_ini['registration']['remote_account'] || !$softnas_ini['registration']['remote_pwd']) {
				$errorMsg = "Error while registering remote node - no remote credentials";
				$log->LogError('Remote prod.reg. - ' . $errorMsg);
				$reply['errMsg'] = $errorMsg;
				$reply['remote_registration'] = 'error';
				$reply['warning'].= "Warning: $errorMsg ";
			} else {
				$remotenode = $remote['RemoteNode'];
				//$remoteuser = $remote['RemoteUserEntered'];
				//$remotepass = $remote['RemotePasswordEntered'];
				set_encryption_key();
				$remoteuser = quick_decrypt(ENCRYPTION_KEY, $softnas_ini['registration']['remote_account']);
				$remotepass = quick_decrypt(ENCRYPTION_KEY, $softnas_ini['registration']['remote_pwd']);
				$src_node = $_SERVER['SERVER_ADDR'];
				$src_account_id = $req->prodRegAccountId;
				$src_instance_id = $req->prodRegInstanceId;
				$url = "https://$remotenode/buurst/snserver/snserv.php" . "?opcode=remote_product_registration";
				//$url.= "&fullname={$req->prodRegFirstName}%20{$req->prodRegLastName}&user_id={$user_id}";
				$url.= "&fullname={$prodRegFullname}&user_id={$user_id}";
				$url.= "&email={$req->prodRegBusinessEmail}&src_node={$src_node}";
				$url.= "&src_account_id={$src_account_id}&src_instance_id={$src_instance_id}";
				$response = https_request($url, $remoteuser, $remotepass);
				$log->LogDebug("Remote response: $response");
				$decodedItems = json_decode($response, true);
				if (!$response || !$decodedItems || !$decodedItems['records']) {
					$errorMsg = "Error while registering remote node";
					$log->LogError('Remote prod.reg. - ' . $errorMsg);
					$reply['errMsg'] = $errorMsg;
					$reply['remote_registration'] = 'error';
					$reply['warning'].= " Warning: $errorMsg ";
				}
				/*$reply['TEST1'] = $response;
				$reply['TEST2'] = json_decode($response);
				$reply['TEST3'] = $url;*/
				if ($decodedItems['records']['result'] == 'success') {
					$reply['remote_registration'] = 'success';
					$log->LogDebug("Remote prod.reg. success");
				}
				if ($decodedItems['records']['result'] == 'already') {
					$reply['remote_registration'] = 'already';
					$log->LogDebug("Remote product already registered");
				}
			}
		}
		/*   (Remote registration)          */
		return $reply;
	}

	function proc_remote_product_registration() {
		global $_CLEAN; // clean parameters
		global $errorProc;
		global $errorMsg;
		global $successMsg;
		global $log;
		global $isForm;
		$fullname =			isset($_CLEAN['OP']['fullname']) ? trim($_CLEAN['OP']['fullname']) : "";
		$email =			isset($_CLEAN['OP']['email']) ? trim($_CLEAN['OP']['email']) : "";
		$src_account_id =	isset($_CLEAN['OP']['src_account_id']) ? trim($_CLEAN['OP']['src_account_id']) : "";
		$src_instance_id =	isset($_CLEAN['OP']['src_instance_id']) ? trim($_CLEAN['OP']['src_instance_id']) : "";
		$src_node =			isset($_CLEAN['OP']['src_node']) ? trim($_CLEAN['OP']['src_node']) : "";
		$user_id =			isset($_CLEAN['OP']['user_id']) ? trim($_CLEAN['OP']['user_id']) : "";
		$reply = array();
		$reply['warning'] = "";
		$ini = read_ini();
		$registered = $ini['registration']['registered'];
		if ($registered === 'true') {
			$reply['result'] = "already";
			$reply['message'] = "Already registered";
		} else {
			// return the platform type
			$ini = read_ini();
			$system = $ini['system'];
			$platform = $system['platform'];
			$reg_info = get_registration_info(array(
				'platform' => $platform,
				'hardware_id' => get_hardware_id()
			));
			$account_id = $reg_info->prodreg_account;
			$instance_id = $reg_info->prodreg_instance_id;
			if (!$account_id) {
				$mail_split = explode('@', $email);
				$account_id = $mail_split[1];
			}
			$ticket = (object)array(
				'user_fullname' => $fullname,
				'email' => $email,
				'account_id' => $src_account_id,
				'instance_id' => $src_instance_id,
				'localnode' => $src_node,
				'remote_account_id' => $account_id,
				'remote_instance_id' => $instance_id,
				'remotenode' => $_SERVER['SERVER_ADDR'],
				'user_id' => $user_id
			);
			$result = remote_registration_create_ticket($ticket);
			$reply['remote_ticket'] = $result;
			if (!$result->success) {
				$errorMsg = "Submit error, ticket not sent (msg: $result->msg)";
				$log->LogError('Remote prod.reg. - ' . $errorMsg);
				$reply['errMsg'] = $errorMsg;
				$reply['remote_registration'] = 'ticket not sent';
			} else {
				$reply['remote_registration'] = 'success';
				$log->LogDebug("Remote prod.reg. success");
				$ini['registration']['registered'] = 'true';
				$ini['registration']['business_email'] = $email;
				write_ini($ini);
				$reply['result'] = "success";
				$reply['message'] = "Registered successfully";
				prodreg_save_monit_email($email, $reply);
			}
		}
		$reply['registered'] = $ini['registration']['registered'];
		return $reply;
	}

	function remote_registration_create_ticket($params) {
		global $log;
		foreach ($params as $id => $val) {
			$params->$id = str_ireplace('&', '%26', htmlspecialchars_decode($val));
		}
		$prodRegFullname = str_ireplace(' ', '%20', $params->user_fullname);
		$msg = "
		<style>	* { font-family: 'Tahoma', 'Arial', sans-serif; }</style>
		<h2>Remote instance product registration</h2>
		
		<h3>User info:</h3>
		User:						<b>$params->user_fullname</b><br/>
		Business Email:				<b>$params->email</b><br/>
		<br/>
		Local (source) machine:<br/>
		Local Account ID:			<b>$params->account_id</b><br/>
		Local Instance ID:			<b>$params->instance_id</b><br/>
		Local IP:					<b>$params->localnode</b>
		<br/>
		Remote (target) machine:<br/>
		Remote Account ID:			<b>$params->remote_account_id</b><br/>
		Remote Instance ID:			<b>$params->remote_instance_id</b><br/>
		Remote IP:					<b>$params->remotenode</b>
		<br/>
	";
		$log->LogDebug($msg);
		$log->LogDebug($params);
		require_once ('../integrations/kayako/index.php');
		$k = new KayakoAPI();
		sleep(1);
		$ticket = $k->createTicket(array(
			'subject' => "Remote instance product registration ($prodRegFullname)",
			'fullname' => $prodRegFullname,
			'email' => $params->email,
			'contents' => "$msg",
			'userid' => $params->user_id
		));
		$result = (object)array(
			'success' => true
		);
		if (!$ticket) {
			$result->success = false;
			$result->msg = $msg;
		}
		$result->ticket = $ticket;
		return $result;
	}

	function prodreg_save_monit_email($email, &$reply) {
		global $_CLEAN;
		global $log;
		$command_tmp = isset($_CLEAN['OP']['command']) ? $_CLEAN['OP']['command'] : "";
		$_CLEAN['OP']['command'] = 'update';
		$_CLEAN['OP']['email'] = $email;
		$email_reply = proc_email_setup();
		if ($email_reply['errMsg']) {
			$errorMsg = "Error while saving monitoring email: " . $email_reply['errMsg'];
			$log->LogError('Prod.reg. - ' . $errorMsg);
			$reply['errMsg'] = $errorMsg;
			$reply['email_setup'] = 'error';
			$reply['warning'].= "Warning: $errorMsg ";
		}
		$_CLEAN['OP']['command'] = $command_tmp;
	}

	function proc_feature_request() {
		global $_CLEAN;
		global $errorProc;
		global $errorMsg;
		global $successMsg;
		global $log;
		$reply = array();
		$summary = isset($_CLEAN['OP']['featureRequestSummary']) ? $_CLEAN['OP']['featureRequestSummary'] : "";
		$details = isset($_CLEAN['OP']['featureRequestDetails']) ? $_CLEAN['OP']['featureRequestDetails'] : "";
		$details = nl2br(" $details ");
		$msg = "
	<style>	* { font-family: 'Tahoma', 'Arial', sans-serif; }</style>
	<h2>New feature request</h2>
	<h3>Summary:</h3>
	<p>
		$summary
	</p>
	<h3>Details:</h3>
	<p>
		$details
	</p>
	";
		//exit($msg);
		$headers = "MIME-Version: 1.0" . "\r\n";
		$headers.= "Content-type:text/html;charset=UTF-8" . "\r\n";
		//$headers .= 'From: <webmaster@example.com>' . "\r\n";
		//$headers .= 'Cc: office@example.com'. "\r\n";
		if (
		//mail('neparkiraj@gmail.com', 'Feature request', $msg, $headers)
		mail('ideas-FEEDBACK-softnas@mailer.aha.io', 'Feature request', $msg, $headers)) {
			$successMsg = "Submit successful";
			$log->LogInfo('feature_request - success - ' . $msg);
			//return true;
			
		} else {
			$errorProc = true;
			$errorMsg = "Submit error, please try again later";
			$log->LogError('feature_request - ' . $errorMsg . ' - ' . $msg);
			//return false;
			
		}
		return $reply;
	}

	function proc_prodreg_inputs($cmd = null) {
		global $_CLEAN;
		global $errorProc;
		global $errorMsg;
		global $successMsg;
		global $log;
		global $_config;
		$reply = array();
		$inipath = "prodreg_inputs.ini";
		$command = isset($_CLEAN['OP']['command']) ? $_CLEAN['OP']['command'] : $cmd;
		if ($command == 'get') {
			$reply['is_registered'] = check_product_registered();
			if (!$reply['is_registered']) {
				$ini = read_ini($inipath);
				if ($ini['inputs']) {
					foreach ($ini['inputs'] as $id => $val) {
						$ini['inputs'][$id] = htmlspecialchars_decode($val);
					}
				}
				$reply = ($ini['inputs'] ? $ini['inputs'] : array(
					'prodRegCheckUpgrades' => 'true',
					'prodRegCheckPromotions' => 'true'
				));
			}
		}
		if ($command == 'save') {
			$inputs = array();
			foreach ($_CLEAN['OP'] as $key => $input) {
				if (stripos($key, 'prodReg') === 0) {
					$inputs[$key] = $input;
				}
			}
			$result = write_ini(array(
				'inputs' => $inputs
			) , $inipath);
			if (!$result) {
				$errorMsg = "update $inipath: Cannot write to file.";
				$errorProc = true;
				$reply['errMsg'] = $errorMsg;
				$log->LogError($errorMsg);
				return $reply;
			}else{
				// #2099 - repeat popup no more often than once per week (if inputs saved by user closing the window)
				if(isset($_CLEAN['OP']['reset_uptime']) && $_CLEAN['OP']['reset_uptime'] == "true"){
					$uptime_path = $_config['proddir'] . "/config/uptime";
					file_put_contents($uptime_path, "0"); // resetting time counter
				}
			}
			$successMsg = "$command completed successfully.";
			$reply['msg'] = $successMsg;
		}
		return $reply;
	}

	function delete_status_file() {
		global $log;
		global $_config;
		$statusfile = "/tmp/softnas-update.status";
		$cmd = $_config['systemcmd']['unlink'] . " " . $statusfile;
		$result = sudo_execute($cmd);
		//sudo_execute( "rm -f /tmp/update_in_progress" );
		return $result;
	}

	//
	// Checks for available software updates (for user notification purposes)
	//
	function proc_checkupdate($overrideURL = "", $customupdate = "") {
		global $_CLEAN; // clean parameters
		global $errorProc;
		global $errorMsg;
		global $successMsg;
		global $log;
		global $_config;
		global $extraProperties;

		$called_function = false;

		if(isset($_CLEAN['OP']['updateType'])) {
			$updateType = $_CLEAN['OP']['updateType'];
			$version = $_CLEAN['OP']['version'];

			file_put_contents("/tmp/updatetype", $updateType);

			if($updateType === 'customupdate') {
				$_CLEAN['OP']['customupdate'] = $version;
			}
		}

		if ($customupdate != "") {
			$called_function = true;
		}
		
		$updateURL = $_config['urlupdate']; // path to update folder on server
		$update_type = file_get_contents("/tmp/updatetype");
		if ($update_type === "devupdate") {
			$updateURL = $_config['urldevupdate'];
		} else if ($update_type === "devnextupdate" || isset($_REQUEST['devnextupdate'])) {
			$updateURL = $_config['urldevnextupdate'];
		} else if ($update_type === "stableupdate" || isset($_REQUEST['stableupdate'])) {
			$updateURL = $_config['urlstableupdate'];
		} else if ($update_type === "testupdate") {
			$updateURL = $_config['urltestupdate'];
		}
		$log->LogDebug('update url is 1st '.$updateURL);
		if (isset($_CLEAN['OP']['customupdate'])) {
			$customupdate_arg = trim($_CLEAN['OP']['customupdate']);
		} else {
			$customupdate_arg = '';
		}
		if ($customupdate_arg != '') {
			$customupdate = $customupdate_arg;
		}
		$reply = array();
		// Check current status:
		$statusupdate = "";
		if (is_update_process_active(true)) {
			$statusupdate = file_get_contents_proxy("/tmp/softnas-update.status");
			/*if(!$statusupdate){
			$statusupdate = "updating...";
			}*/
		}
		$reply['statusupdate'] = $statusupdate ? $statusupdate : "";
		//delete_status_file();                          // delete prior update status file for earlier updates (if any)
		$newversion = "";
		if ($overrideURL != "") $updateURL = $overrideURL; // for testupdate / devupdate to get version from testupdate or devupdate path instead
		$log->LogDebug('url is now '.$updateURL);
		$params = $updateURL . "/version";
		$log->LogDebug('update check params: '.var_export($params, true));
		if ( /*$overrideURL != "" &&*/ $customupdate != "") {
			$updateURL = $overrideURL;
			//if($called_function){
			$cmd = 'echo "' . $customupdate . '" > /tmp/version';
			$result = sudo_execute("chmod 666 /tmp/version");
			$result = sudo_execute($cmd);
			$result = sudo_execute("chmod 644 /tmp/version");
			//}
			$newversion = $customupdate;
		}
		$cmd = $_config['systemcmd']['cat'] . " ../version";
		$result = sudo_execute($cmd);
		if ($result['rv'] != 0) {
			$errorProc = true;
			$errorMsg = "Current version file read failed (error 100.3)";
			$reply['errMsg'] = $errorMsg;
			$log->LogError($errorMsg);
			return $reply;
		}
		$version = $result['output_str'];
		$log->LogDebug("Current version is: " . $version);
		$installed_now = get_installed_update_type();
		$versions_found = false;
		// #1613 - list of possible versions in file version_list
		$recent_versions_html = file_get_contents_proxy("$updateURL/version_list");
		$recent_versions_file_arr = explode(PHP_EOL, $recent_versions_html);
		$log->LogDebug('recent versions: '.var_export($recent_versions_file_arr, true));
		//$recent_versions = array("&nbsp;");
		$recent_versions = array();
		foreach ($recent_versions_file_arr as $i => $line) {
			$version_str = trim($line);
			if ($version_str !== "") {
				$versions_found = true;
				if ($customupdate !== "" || $installed_now == "custom" ||
				  update_version_compare($version_str, $version) > 0) { // #4668
					$recent_versions[] = $version_str;
				}
			}
		}
		if ($customupdate == ""){
			if (count($recent_versions) > 0) {
				$newversion = $recent_versions[count($recent_versions) - 1];
			} else {
				if ($versions_found === false) {
					$errorProc = true;
					$errorMsg = "Recent versions list read failed (error 100.4)";
					$reply['errMsg'] = $errorMsg;
					$log->LogError($errorMsg);
					return $reply;
				} else {
					$recent_versions = array($version); // #4711 - if empty, show only current version
					$newversion = $version; // #4712 - $reply['msg'] = "You are running the latest version"
					$reply['recent_versions'] = $recent_versions;
					$errorMsg = "No new versions available (error 100.5)";
					$log->LogInfo($errorMsg);
				}
			}
		} else {
			$recent_versions = array($newversion);
		}
		
		/*if (count($recent_versions) == 0) {
			$recent_versions = array($newversion);
		}*/
		
		$reply['recent_versions'] = $recent_versions;
		$log->LogDebug("New version is: " . $newversion);
		$result = sudo_execute("chmod 666 /tmp/version");
		$result = sudo_execute("echo ".escapeshellarg($newversion)." > /tmp/version");
		$result = sudo_execute("chmod 644 /tmp/version");
		
		$update_available = $version != $newversion; // a version difference has been detected
		$reply['version'] = $version;
		$reply['newversion'] = $newversion;
		$reply['updateavailable'] = $update_available;
		$successMsg = $update_available ? "A newer version is available" : "You are running the latest version";
		$reply['msg'] = $successMsg;

		$extraProperties = array(	
			'status' => $reply['msg'],
			'currentVersion' => $reply['version'],
			'latestVersion'=> $reply['newversion']
		);

		return $reply;
	}

	//
	// Checks at homepage of StorageCenter if update is still running (#1690)
	//
	function proc_checkupdate_homepage() {
		//session_name('PHPSESSID_port'.$_SERVER['SERVER_PORT']); session_start();
		check_logged_in(); // Keep user logged in during the update
		if (is_update_process_active()) {
			exit("Update is still running.");
		} else {
			exit("Update is finished.");
		}
	}

	//
	// Executes software updates
	//
	function proc_executeupdate() {
		global $_CLEAN; // clean parameters
		global $errorProc;
		global $errorMsg;
		global $successMsg;
		global $log;
		global $_config;
		$reply = array();

		delete_status_file(); // delete prior update status file for earlier updates (if any)
		$updatetype = "regular update.";
		$testupdate = '';
		$devupdate = '';
		$customupdate = '';
		$selectedupdate = '';

		if(isset($_CLEAN['OP']['updateType'])) {
			$updateTypeParam = $_CLEAN['OP']['updateType'];
			$version = $_CLEAN['OP']['version'];

			$testupdate = $updateTypeParam === 'testupdate' ? $version : "";
			$devupdate = $updateTypeParam === 'devupdate' ? $version : "";
			$devnextupdate = $updateTypeParam === 'devnextupdate' ? $version : "";
			$stableupdate = $updateTypeParam === 'stableupdate' ? $version : "";
			$customupdate = $updateTypeParam === 'customupdate' ? $version : "";
			$selectedupdate = $version;
			$update_type = $updateType;
		}
		else {
			isset($_CLEAN['OP']['testupdate']) ? $testupdate = $_CLEAN['OP']['testupdate'] : "";
			isset($_CLEAN['OP']['devupdate']) ? $devupdate = $_CLEAN['OP']['devupdate'] : "";
			isset($_CLEAN['OP']['devnextupdate']) ? $devnextupdate = $_CLEAN['OP']['devnextupdate'] : "";
			isset($_CLEAN['OP']['stableupdate']) ? $stableupdate = $_CLEAN['OP']['stableupdate'] : "";
			isset($_CLEAN['OP']['customupdate']) ? $customupdate = $_CLEAN['OP']['customupdate'] : "";
			isset($_CLEAN['OP']['recentVersions']) ? $selectedupdate = $_CLEAN['OP']['recentVersions'] : "";
			isset($_CLEAN['OP']['update_type']) ? $update_type = $_CLEAN['OP']['update_type'] : "";
		}

		if ($testupdate != "" || $update_type === 'testupdate') {
			$updateURL = $_config['urltestupdate']; // path to test update folder on server
			proc_checkupdate($updateURL); // run version check with testupdate path to get the testupdate version instead of production version
			$updatetype = 'test update.';
			$testupdate = $selectedupdate;
		} else if ($devupdate != "" || $update_type === 'devupdate') {
			$updateURL = $_config['urldevupdate']; // path to dev update folder on server
			proc_checkupdate($updateURL); // run version check with devupdate path to get the devupdate version instead of production version
			$updatetype = 'devel update.';
			$devupdate = $selectedupdate;
		} else if ($devnextupdate != "" || $update_type === 'devnextupdate') {
			$updateURL = $_config['urldevnextupdate']; // path to dev update folder on server
			proc_checkupdate($updateURL); // run version check with devnextupdate path to get the devnextupdate version instead of production version
			$updatetype = 'devel-next update.';
			$devnextupdate = $selectedupdate;
		} else if ($stableupdate != "" || $update_type === 'stableupdate') {
			$updateURL = $_config['urlstableupdate']; // path to dev update folder on server
			proc_checkupdate($updateURL); // run version check with stableupdate path to get the stableupdate version instead of production version
			$updatetype = 'stable update.';
			$stableupdate = $selectedupdate;
		} else if ($customupdate != "" || $update_type === 'customupdate') {
			$updateURL = $_config['urlcustomupdate']; // path to custom update folder on server
			proc_checkupdate($updateURL, $customupdate); // run version check with customupdate path to get the customupdate version instead of production version
			$updatetype = 'custom update.';
			$customupdate = $selectedupdate;
		} else {
			proc_checkupdate();
			$updateURL = $_config['urlupdate']; // path to regular update folder on server
			$updatetype = 'update.';
		}
		$log->LogInfo("Starting " . $updatetype);
		if ($customupdate != "") {
			$newversion = trim($customupdate);
			file_put_contents('/tmp/version', $newversion);
		} else {
			$cmd = $_config['systemcmd']['cat'] . " /tmp/version";
			$result = sudo_execute($cmd);
			if ($result['rv'] != 0) {
				$errorProc = true;
				$errorMsg = "Update version file read failed (error 100.10)";
				$reply['errMsg'] = $errorMsg;
				$log->LogError($errorMsg);
				return $reply;
			}
			$newversion = $result['output_str'];
			if (/*$testupdate == "" && $devupdate == "" &&*/ $selectedupdate != "" && $newversion != $selectedupdate) {
				$log->LogInfo("Selected update: " . $selectedupdate);
				$newversion = $selectedupdate;
				$currentversion = file_get_contents($_config['proddir'] . "/version");
				if (!$currentversion) {
					$errorProc = true;
					$errorMsg = "Current version file read failed (error 100.14)";
					$reply['errMsg'] = $errorMsg;
					$log->LogError($errorMsg);
					return $reply;
				} else {
					$installed_now = get_installed_update_type(); // don't compare it updating from custom
					if ($installed_now !== "custom" && update_version_compare($newversion, $currentversion) <= 0) {
						$errorProc = true;
						$errorMsg = "Current version is newer than selected (error 100.15)";
						$reply['errMsg'] = $errorMsg;
						$log->LogError($errorMsg);
						return $reply;
					}
				}
				$result = sudo_execute("chmod 666 /tmp/version");
				$result = sudo_execute("echo ".escapeshellarg($selectedupdate)." > /tmp/version");
				$result = sudo_execute("chmod 644 /tmp/version");
			}
		}
		$log->LogDebug("Update version is: " . $newversion);
		if ($newversion == get_current_softnas_version()) {
			$errorProc = true;
			$errorMsg = "Version is already $newversion";
            $reply['errMsg'] = $errorMsg;
			return $reply;
		}
		$updateName = "softnas_update_" . $newversion; // update name command
		$params = escapeshellarg($updateURL . "/" . $updateName . ".sh"); // update script filename
		$log->LogDebug("Update cmd: " . $cmd . " params: " . $params);
		$cmd = "getupdate";
		$result = super_script($cmd, $params);
		if ($result['rv'] != 0) {
			$errorProc = true;
			$errorMsg = "Update command failed (error 100.11)";
			$reply['errMsg'] = $errorMsg;
			$log->LogError($errorMsg);
			return $reply;
		}
		if (strpos($result['output_str'], '404.shtml') !== FALSE) {
			$errorProc = true;
			$errorMsg = 'That update version was not found, update aborting.';
			$reply['errMsg'] = $errorMsg;
			$log->LogError($errorMsg);
			return $reply;
		}
		$log->LogDebug($result);
		$pos = strpos($result['output_str'], "200 OK");
		if ($pos === false) {
			$errorProc = true;
			$errorMsg = "Update command failed (error 100.12)";
			$reply['errMsg'] = $errorMsg;
			$log->LogError($errorMsg);
			return $reply;
		}
		//
		// Apply the update.  This last phase of the update process must take place asynchonously, since
		// the entire SoftnAS UI tree (/var/www/softnas) can be replaced and the Apache webserver restarted
		// by the update process.  During the update process, the client will be unable to contact the
		// server.  To signfify how the update procecss completes, an update status file will be created
		// in var/www/html/update.status. This file is only written once the update process completes.
		// /var/www/html/update.log will contain the detailed log of the update results.
		//
		$arglist = "";
		if ($testupdate != "") {
			$arglist = " " . "testupdate"; // trigger a test update instead of regular update (dev/test)
			
		} else if ($devupdate != "") {
			$arglist = " " . "devupdate"; // trigger a dev update instead of regular update (dev/test)
			
		} else if ($customupdate != "") {
			$arglist = " " . "customupdate $newversion"; // trigger a custom update instead of regular update (dev/test)
			
		} elseif ($devnextupdate != "") {
			$arglist = " " . "devnextupdate";
		} elseif ($stableupdate != "") {
			$arglist = " " . "stableupdate";
		}
		$tmpdir = $_config['tempdir'];
		$proddir = $_config['proddir'];
		$nohup = $_config['systemcmd']['nohup'];
		$sh = $_config['systemcmd']['bash'];
		$cmd = $nohup . " " . $tmpdir . "/" . $updateName . ".sh" . $arglist . " > /tmp/softnas-update.log 2>&1 &";
		// this nohup command runs in the background and returns immediately, which allows this process to exit prior to
		// the update execution process completion.
		$log->LogInfo("Update Command: " . $cmd);
		sudo_execute("chmod +x " . $tmpdir . "/" . $updateName . ".sh");
		$result = sudo_execute($cmd);
		$log->LogDebug("Update Launch Results: " . $result['output_str']);
		if ($result['rv'] != 0) {
			$errorProc = true;
			$errorMsg = "Unable to execute final update phase - execution failed (error 100.13)";
			$reply['errMsg'] = $errorMsg;
			$log->LogError($errorMsg);
			return $reply;
		}
		$reply['newversion'] = $newversion;
		$successMsg = "Update was started successfully. Version '$newversion' installation is underway...";
		$reply['msg'] = $successMsg;
		return $reply;
	}
	//
	// Returns status updates on an update that's in-progress
	//
	function proc_statusupdate() {
		session_name('PHPSESSID_port'.$_SERVER['SERVER_PORT']);
		if(session_id() == '') {
			session_start();
		}
		check_logged_in(); // Keep user logged in during the update (#1130)
		global $_CLEAN; // clean parameters
		global $errorProc;
		global $errorMsg;
		global $successMsg;
		global $log;
		global $_config;
		$reply = array();
		$cmd = $_config['systemcmd']['cat'] . " /tmp/softnas-update.status";
		$result = sudo_execute($cmd);
		if ($result['rv'] != 0) {
			$errorProc = true;
			$errorMsg = "Update status unavailable";
			$reply['errMsg'] = $errorMsg;
			$log->LogError($errorMsg);
			return $reply;
		}
		$updatestatus = $result['output_str'];
		$successMsg = $updatestatus;
		$reply['msg'] = $successMsg;
		// Mihajlo 31.may.2015
		/*if(stripos($updatestatus, "OK.") !== false){
			sudo_execute("echo '' > /tmp/softnas-update.status");
		}*/
		return $reply;
	}

	//
	// Returns ini file contents
	//
	function proc_getini() {
		global $_CLEAN; // clean parameters
		global $errorProc;
		global $errorMsg;
		global $successMsg;
		global $log;
		$reply = array();
		$ini = read_ini();
		$reply['ini'] = $ini; // return INI file object
		$successMsg = "Ini read okay";
		$reply['msg'] = $successMsg;
		return $reply;
	}

	//
	// Acknowledge the license agreement
	//
	function proc_ackagreement() {
		global $_CLEAN; // clean parameters
		global $errorProc;
		global $errorMsg;
		global $successMsg;
		global $log;
		$reply = array();
		$params = "";
		$cmd = "discovery";
		$result = super_script($cmd, $params);
		if ($result['rv'] != 0) {
			$msg = "Discovery script failed";
			$log->LogError($msg);
		}
		$log->LogDebug("System discovered platform type: $result");
		$platform = $result['output_str']; // VM, EC2, ... or ?? if unrecognized
		$ini = read_ini();
		$reg = $ini['registration'];
		$sys = $ini['system'];
		$reg['agreement'] = "true";
		$sys['platform'] = $platform;
		$ini['registration'] = $reg;
		$ini['system'] = $sys;

		$ini['track'] = isset($ini['track']) ? $ini['track'] : array();
		$ini['track']['trackedFirstTime'] = "true";
		$ini['track']['fingerprint'] = $_POST['fingerprint'];

		if (!write_ini_file($ini, "../config/softnas.ini", true)) {
			$errorProc = true; // pass error back to client
			$errorMsg = "Unable to save agreement status information!";
			$log->LogError($errorMsg);
		}
		$log->LogInfo("Customer agreed to license agreement");
		$successMsg = "Agreement accepted.";
		$reply['msg'] = $successMsg;
		return $reply;
	}
	
	function proc_accept_beta_agreement() {
		global $_CLEAN; // clean parameters
		global $errorProc;
		global $errorMsg;
		global $successMsg;
		global $log;

		$reply = array();
		
		$enableFlexFiles = true;

		$params = "";
		$cmd = "discovery";
		$result = super_script($cmd, $params);
		if ($result['rv'] != 0) {
			$msg = "Discovery script failed";
			$log->LogError($msg);
		}
		$log->LogDebug("System discovered platform type: $result");
		$platform = $result['output_str']; // VM, EC2, ... or ?? if unrecognized
		$ini = read_ini();

		$sys = $ini['system'];
		$sys['platform'] = $platform;
		$ini['system'] = $sys;

		if ( !isset($ini['beta']) ) {
			$ini['beta'] = array();
		}
		$ini['beta']['accepted'] = "true";

		if ( !isset($ini['flexfiles']) ) {
			$ini['flexfiles'] = array();
		}

		if( isset($ini['flexfiles']['enabled']) && $ini['flexfiles']['enabled'] === "true" ) {
			$enableFlexFiles = false;
		}

		// if flexfiles services already were enabled, does not call the script to enable
		if($enableFlexFiles) {
			$ini['flexfiles']['enabled'] = "true";

			$result = sudo_execute("/var/www/softnas/scripts/flexfiles_services.sh enable");
			if ($result['rv'] != "0") {
				$errorMsg = "Failed to enable beta services: " . $result['output_str'];
				$errorProc = true;
				$reply['errMsg'] = $errorMsg;
				$log->LogError($errorMsg);
				return $reply;
			}
		}

		if (!write_ini_file($ini, "../config/softnas.ini", true)) {
            $errorProc = true; // pass error back to client
            $errorMsg = "Unable to save beta agreement status information!";
            $log->LogError($errorMsg);
        }
        $log->LogInfo("Customer agreed to beta info agreement");
        $successMsg = "Beta information agreement accepted.";
        $reply['msg'] = $successMsg;
        return $reply;
	}

	// update enabled property
	function proc_enableflexfiles() {
		global $_CLEAN; // clean parameters
		global $errorProc;
		global $errorMsg;
		global $successMsg;
		global $log;

		$reply = array();
		$params = "";
		$cmd = "discovery";
		$result = super_script($cmd, $params);
		if ($result['rv'] != 0) {
			$msg = "Discovery script failed";
			$log->LogError($msg);
		}
		$log->LogDebug("System discovered platform type: $result");
		$platform = $result['output_str']; // VM, EC2, ... or ?? if unrecognized
		$ini = read_ini();

		$sys = $ini['system'];
		$sys['platform'] = $platform;
		$ini['system'] = $sys;

		if ( !isset($ini['flexfiles']) ) {
			$ini['flexfiles'] = array();
		}

		$ini['flexfiles']['enabled'] = "true";
		$result = sudo_execute("/var/www/softnas/scripts/flexfiles_services.sh enable");
		if ($result['rv'] != "0") {
			$errorMsg = "Failed to enable flexfiles services: " . $result['output_str'];
			$errorProc = true;
			$reply['errMsg'] = $errorMsg;
			$log->LogError($errorMsg);
			return $reply;
		}

		if (!write_ini_file($ini, "../config/softnas.ini", true)) {
			$errorProc = true; // pass error back to client
			$errorMsg = "Unable to save flexfiles enable status information!";
			$log->LogError($errorMsg);
		}

		$log->LogInfo("Customer enabled FlexFiles services");
		$successMsg = "FlexFiles services enabled.";
		$reply['msg'] = $successMsg;
		return $reply;
	}

		function proc_general_settings() {
			global $_CLEAN; // clean POST parameters
			global $_config;
			global $errorProc;
			global $errorMsg;
			global $successMsg;
			global $log;
			global $isForm;
			$reply = array();
			$licenseInfo = snas_license_info(); // get the licensed capacity info
			$valid = $licenseInfo['valid'];
			if ($valid == false) // we have an invalid licensing outcome (probably exceeded licensed pool capacity limits or expired license)
			{
				$error = true;
				$errorMsg = "License failure - unable to continue. Details: " . $licenseInfo['errMsg'];
				$errorProc = true; // pass error back to client
				$log->LogError($errorMsg);
				return $reply;
			}
			$command = isset($_CLEAN['OP']['command']) ? $_CLEAN['OP']['command'] : 'get_general_settings';
			if ($command == 'update_fuusion_settings') {
				$login_ini = 'login.ini';
				$login_config = read_ini($login_ini);
				$login_settings = $login_config['login'];
				$login_settings['timeout'] = isset($_CLEAN['OP']['timeout']) ? $_CLEAN['OP']['timeout'] : "";
				// option for user IP-based session hash
				/*if (isset($_CLEAN['OP']['ipHash'])) {
					$login_settings['ipHash'] = 1;
				} else {
					$login_settings['ipHash'] = "0";
				}*/
				//$login_settings['session_folder']    = $_CLEAN['OP']['session_folder'];
				//$login_settings['encryption_key']    = $_CLEAN['OP']['encryption_key'];
				$login_config['login'] = $login_settings;
				$result = write_ini($login_config, $login_ini);
				if (!$result) {
					$errorMsg = "Saving Fuusion settings: Cannot write to login configuration file: $login_ini";
					$errorProc = true;
					$reply['errMsg'] = $errorMsg;
					$log->LogError($errorMsg);
					return $reply;
				}
				$successMsg = "Settings were saved successfully";
				$reply['msg'] = $successMsg;
			}
			if ($command == 'get_general_settings') {
				$isForm = true;
				$general_settings = read_ini('general_settings.ini');
				/*$reply['smtp_use_auth'] = 'off';
				if ($general_settings && isset($general_settings['smtp']) && is_array($general_settings['smtp'])) {
					unset($reply['smtp_use_auth']);
					$reply+= $general_settings['smtp'];
					$reply['smtp_use_auth'] = $reply['smtp_use_auth'] ? 'on' : 'off';
				}*/
				// Mihajlo 08.may.2015 - Get dual authentication data:
				$auth = isset($general_settings['authentication']) ? $general_settings['authentication'] : false;
				if (!$auth || !isset($auth["auth_type"]) || !isset($auth["auth_user"]) || !$auth["auth_type"] || !$auth["auth_user"]) {
					$auth = array(
						"auth_type" => "not_using",
						"auth_user" => ""
					);
				}
				$auth['url_auth_google'] = $_config['url_auth_google'];
				$auth['url_auth_facebook'] = $_config['url_auth_facebook'];
				$reply["dual_auth"] = $auth;
				$login_settings = read_ini('login.ini');
				if ($login_settings) {
					$reply+= $login_settings['login'];
				}
				/*$s3config_settings = read_s3_config();//ini('s3config.ini');
				    $reply['s3AwsAccessKey'] = '';
				    $reply['s3AwsSecretKey'] = '';
				    $reply['s3_obfuscated']  = 'off';
				    if($s3config_settings)
				    {
				            $global = $s3config_settings['global'];
				            //$reply[] = print_r($global,true);
				            $reply['s3AwsAccessKey'] = $global['awsAccessKey'];
				            $reply['s3AwsSecretKey'] = $global['awsSecretKey'];
				            $reply['s3_obfuscated']  = $global['obfuscated'] === 'true' ? 'on' : 'off';
				    }
				    
				    
				    $aws_iam_settings  = read_aws_iam_config();//read_ini('aws_iam.ini');
				    $reply['haAwsAccessKey'] = '';
				    $reply['haAwsSecretKey'] = '';
				    $reply['ha_obfuscated']  = 'off';
				    if($aws_iam_settings)
				    {
				           $reply['haAwsAccessKey'] = $aws_iam_settings['AWSAccessKeyId'];
				           $reply['haAwsSecretKey'] = $aws_iam_settings['AWSSecretKey'];
				           $reply['ha_obfuscated']  = $aws_iam_settings['obfuscated'] === 'true' ? 'on' : 'off';
				    }
				    else
				    {
				            
				    }*/
				// reading proxy settings:
				// Mihajlo 15.10.2014 , change #4044 ($proxy_exclude)
				$reply['proxy_enabled'] = 'off';
				$proxy_ip = $proxy_port = $proxy_username = $proxy_password = $proxy_exclude = "";
				$proxy_part = $proxy_exclude_part = "";
				$result = sudo_execute("cat /etc/environment");
				
				$proxy_arr = $result['output_arr'];
				foreach ($proxy_arr as $i => $line) {
					$proxy_data = trim($line);
					$proxy_str = 'https_proxy=http://';
					$proxy_exclude_str = 'no_proxy=';
					
					if (stripos($proxy_data, $proxy_str) === 0) {
						$proxy_part = substr($proxy_data, strlen($proxy_str));
					}
					if (stripos($proxy_data, $proxy_exclude_str) === 0) {
						$proxy_exclude_part = substr($proxy_data, strlen($proxy_exclude_str));
					}
				}
				if ($proxy_part !== "") {
					$reply['proxy_enabled'] = 'on';
					$proxy_all = explode('@', $proxy_part);
					
					if (count($proxy_all) == 2) {
						// usr:pwd@addr:port   :
						$proxy_user = explode(':', $proxy_all[0]);
						$proxy_addr = explode(':', $proxy_all[1]);
						$proxy_username = $proxy_user[0];
						$proxy_password = $proxy_user[1];
						$proxy_ip = $proxy_addr[0];
						$proxy_port = $proxy_addr[1];
					} else {
						// addr:port   :
						$proxy_addr = explode(':', $proxy_all[0]);
						$proxy_ip = $proxy_addr[0];
						$proxy_port = $proxy_addr[1];
						$proxy_username = "";
						$proxy_password = "";
					}
				}
				if ($proxy_exclude_part !== "") {
					$reply['proxy_enabled'] = 'on';
					$proxy_exclude = $proxy_exclude_part;
				}

				$reply['proxy_ip'] = $proxy_ip;
				$reply['proxy_port'] = $proxy_port;
				$reply['proxy_username'] = $proxy_username;
				$reply['proxy_password'] = $proxy_password;
				$reply['proxy_exclude'] = $proxy_exclude;
				$softnas_settings = read_ini('softnas.ini');
				$get_st = isset($softnas_settings['gettingstarted']) ? $softnas_settings['gettingstarted'] : array();
				$reply['showonstartup'] = (isset($get_st['showonstartup']) && $get_st['showonstartup'] === "0") ? 'off' : 'on';
				$reply['showWelcomeOnStartup'] = (isset($get_st['showWelcomeOnStartup']) && $get_st['showWelcomeOnStartup'] === "0") ? 'off' : 'on';
				
				// #2576 - Segment
				if (!isset($softnas_settings['system'])) {
					$softnas_settings['system'] = array();
				}
				if (!isset($softnas_settings['system']['track_activity'])) {
					$softnas_settings['system']['track_activity'] = true;
				}
				$reply['track_activity'] = $softnas_settings['system']['track_activity'];
				
				/*if (isset($softnas_settings['gettingstarted'])) {
					$reply['showonstartup'] = $softnas_settings['gettingstarted']['showonstartup'] ? 'on' : 'off';
					$reply['showWelcomeOnStartup'] = $softnas_settings['gettingstarted']['showWelcomeOnStartup'] ? 'on' : 'off';
				}*/
				$reply['platform'] = get_system_platform();
				
				$reply['support_chat'] = isset($softnas_settings['support']['live_support_enabled']) ? $softnas_settings['support']['live_support_enabled'] : 'true';
		
			} elseif ($command == 'update_general_settings') {
				// smtp settings
				/*$general_settings_ini = 'general_settings.ini';
				$general_settings = read_ini($general_settings_ini);
				$smtp_settings = isset($general_settings['smtp']) ? $general_settings['smtp'] : array();
				$smtp_settings['smtp_mailserver'] =	isset($_CLEAN['OP']['smtp_mailserver']) ? $_CLEAN['OP']['smtp_mailserver'] : "";
				$smtp_settings['smtp_port'] =		isset($_CLEAN['OP']['smtp_port']) ? $_CLEAN['OP']['smtp_port'] : "";
				$smtp_settings['smtp_use_auth'] =	isset($_CLEAN['OP']['smtp_use_auth']) ? $_CLEAN['OP']['smtp_use_auth'] : "";
				$smtp_settings['smtp_username'] =	isset($_CLEAN['OP']['smtp_username']) ? $_CLEAN['OP']['smtp_username'] : "";
				$smtp_settings['smtp_password'] =	isset($_CLEAN['OP']['smtp_password']) ? $_CLEAN['OP']['smtp_password'] : "";
				$general_settings['smtp'] = $smtp_settings;
				$result = write_ini($general_settings, $general_settings_ini);
				if (!$result) {
					$errorMsg = "update general settings: Cannot write to general settings configuration file: $general_settings_ini";
					$errorProc = true;
					$reply['errMsg'] = $errorMsg;
					$log->LogError($errorMsg);
					return $reply;
				}*/
				// session && encryption settings
				$login_ini = 'login.ini';
				$login_config = read_ini($login_ini);
				$login_settings = $login_config['login'];
				$login_settings['timeout'] = isset($_CLEAN['OP']['timeout']) ? $_CLEAN['OP']['timeout'] : "";
				// option for user IP-based session hash
				if (isset($_CLEAN['OP']['ipHash'])) {
					$login_settings['ipHash'] = 1;
				} else {
					$login_settings['ipHash'] = "0";
				}
				//$login_settings['session_folder']    = $_CLEAN['OP']['session_folder'];
				//$login_settings['encryption_key']    = $_CLEAN['OP']['encryption_key'];
				$login_config['login'] = $login_settings;
				$result = write_ini($login_config, $login_ini);
				if (!$result) {
					$errorMsg = "session && encryption settings: Cannot write to login configuration file: $login_ini";
					$errorProc = true;
					$reply['errMsg'] = $errorMsg;
					$log->LogError($errorMsg);
					return $reply;
				}
				// s3config
				/*$s3config_settings = read_s3_config();//ini('s3config.ini');
				    $global = $s3config_settings['global'];
				    $global['awsAccessKey']              = $_CLEAN['OP']['s3AwsAccessKey'];
				    $global['awsSecretKey']              = $_CLEAN['OP']['s3AwsSecretKey'];
				    $global['obfuscated']                = $_CLEAN['OP']['s3_obfuscated'] ? 'true' : 'false';
				    $s3config_settings['global']         = $global;
				    $result = write_s3_config($s3config_settings);
				    if (!$result) 
				    {
				          $errorMsg = "s3 config: Cannot write to s3 configuration file : s3config.ini";
				          $errorProc = true;  
				          $reply['errMsg'] = $errorMsg;
				          $log->LogError( $errorMsg );
				          return $reply;
				    }*/
				// s3 ha config
				/*$aws_iam_settings  = read_aws_iam_config();//read_ini('aws_iam.ini');
				    
				    $aws_iam_settings['AWSAccessKeyId']  = $_CLEAN['OP']['haAwsAccessKey'];
				    $aws_iam_settings['AWSSecretKey']    = $_CLEAN['OP']['haAwsSecretKey'];
				    $aws_iam_settings['obfuscated']      = $_CLEAN['OP']['ha_obfuscated'] ? 'true' : 'false';
				    
				    $result = write_aws_iam_config($aws_iam_settings);
				    if (!$result) 
				    {
				          $errorMsg = "s3 ha config: Cannot write to ha configuration file : aws_iam.ini";
				          $errorProc = true;  
				          $reply['errMsg'] = $errorMsg;
				          $log->LogError( $errorMsg );
				          return $reply;
				    }*/
				// saving proxy settings
				// Mihajlo 15.10.2014
				$proxy_ip = isset($_CLEAN['OP']['proxy_ip']) ? trim($_CLEAN['OP']['proxy_ip']) : "";
				$proxy_port = isset($_CLEAN['OP']['proxy_port']) ? trim($_CLEAN['OP']['proxy_port']) : "";
				$proxy_username = isset($_CLEAN['OP']['proxy_username']) ? trim($_CLEAN['OP']['proxy_username']) : "";
				$proxy_password = isset($_CLEAN['OP']['proxy_password']) ? trim($_CLEAN['OP']['proxy_password']) : "";
				$proxy_exclude = isset($_CLEAN['OP']['proxy_exclude']) ? trim($_CLEAN['OP']['proxy_exclude']) : "";
				$proxy_enabled = isset($_CLEAN['OP']['proxy_enabled']) ? trim($_CLEAN['OP']['proxy_enabled']) : "";
				$SCRIPTS = $_config['path']['scripts'];
				$script = "proxy.sh";
				// removing old proxy settings:
				// (to prevent duplicate variables in /etc/enviropement)
				$args = " remove ";
				$commandLine = $SCRIPTS . '/' . $script . " " . $args;
				$result = sudo_execute($commandLine /*, true*/);
				if ($result['rv'] != "0") {
					$errorMsg = "update general settings: Saving proxy settings. Message: " . $result['output_str'];
					$errorProc = true;
					$reply['errMsg'] = $errorMsg;
					$log->LogError($errorMsg);
					return $reply;
				}
				// saving new proxy settings
				//(if address field is not empty and checkbox selected):
				if ($proxy_ip != "" && $proxy_enabled == 'on') {
					if ($proxy_port == "") {
						$errorMsg = "update general settings: Proxy port is not entered";
						$errorProc = true;
						$reply['errMsg'] = $errorMsg;
						$log->LogError($errorMsg);
						return $reply;
					}
					$args = " -h $proxy_ip -p $proxy_port";
					if ($proxy_username != "" && $proxy_password != "") {
						$args.= " -u $proxy_username -w $proxy_password";
					}
					if ($proxy_exclude) {
						$proxy_exclude = preg_replace('/\s+/', '', $proxy_exclude);
						$proxy_exclude_arr = explode(",", $proxy_exclude);
						$proxy_exclude = implode(" ", $proxy_exclude_arr);
						$args.= " -e '$proxy_exclude'";
					}
					$commandLine = $SCRIPTS . '/' . $script . " " . $args;
					$result = sudo_execute($commandLine /*, true*/);
					if ($result['rv'] != "0") {
						$errorMsg = "update general settings: Cannot save proxy settings. Message: " . $result['output_str'];
						$errorProc = true;
						$reply['errMsg'] = $errorMsg;
						$log->LogError($errorMsg);
						return $reply;
					}
					// Adding SnapReplicate nodes if SnapReplicate is enabled
					if (file_exists($_config['proddir'] . "/config/snaprepstatus.ini")) {
						$snaprepstatus = read_ini("snaprepstatus.ini");
						$remotenode = $snaprepstatus['Relationship1']['RemoteNode'];
						super_script("proxy", "noproxy $remotenode");
					}
				}
				// show gettings started
				$softnasini = 'softnas.ini';
				$softnas_settings = read_ini($softnasini);
				$gettingstarted = isset($softnas_settings['gettingstarted']) ? $softnas_settings['gettingstarted'] : '';
				$gettingstarted['showonstartup'] = (isset($_CLEAN['OP']['showonstartup']) && $_CLEAN['OP']['showonstartup'] == 'on') ? '1' : '0';
				$gettingstarted['showWelcomeOnStartup'] = (isset($_CLEAN['OP']['showWelcomeOnStartup']) && $_CLEAN['OP']['showWelcomeOnStartup'] == 'on') ? '1' : '0'; // 10.10.2014
				
				// #2576 - Segment
				$track_activity = isset($_CLEAN['OP']['track_activity']) ? trim($_CLEAN['OP']['track_activity']) : false;
				if (!isset($softnas_settings['system'])) {
					$softnas_settings['system'] = array();
				}
				$softnas_settings['system']['track_activity'] = ($track_activity == "on" ? "true" : "false");

				$softnas_settings['gettingstarted'] = $gettingstarted;
				
				// Live support settings
				/*$live_support_new = isset($_CLEAN['OP']['support_chat']) ? ($_CLEAN['OP']['support_chat'] === 'on' ? 'true' : 'false') : 'false';
				if ($softnas_settings['support']['live_support_enabled'] != $live_support_new) {
					get_live_support_info();
				}
				$softnas_settings['support']['live_support_enabled'] = $live_support_new;
				*/
				$result = write_ini($softnas_settings, $softnasini);
				if (!$result) {
					$errorMsg = "softnas settings: Cannot write to softnas configuration file : $softnasini";
					$errorProc = true;
					$reply['errMsg'] = $errorMsg;
					$log->LogError($errorMsg);
					return $reply;
				}
				// #6045 - Send report when Segment is disabled to notify that tracking was disabled
				if ($softnas_settings['system']['track_activity'] === 'false') {
					exec('php '.__DIR__.'/segment_utils.php send_usage_report');
				}
				$successMsg = "Settings were saved successfully";
				$reply['msg'] = $successMsg;
				
			} elseif ($command == 'update_auth_settings') {
				$general_settings = read_ini('general_settings.ini');
				if (!isset($general_settings['authentication'])) {
					$general_settings['authentication'] = array();
				}
				$general_settings['authentication']['auth_type'] = isset($_CLEAN['OP']['auth_type']) ? $_CLEAN['OP']['auth_type'] : "";
				$general_settings['authentication']['auth_user'] = isset($_CLEAN['OP']['auth_user']) ? $_CLEAN['OP']['auth_user'] : "";
				$result = write_ini($general_settings, 'general_settings.ini');
				if (!$result) {
					$errorMsg = "softnas settings: Cannot write to softnas configuration file : 'general_settings.ini'";
					$errorProc = true;
					$reply['errMsg'] = $errorMsg;
					$log->LogError($errorMsg);
					return $reply;
				}
				$successMsg = "Settings were saved successfully";
				$reply['msg'] = $successMsg;
	} elseif ($command == 'set_notification_email') {
		$monit_settings = read_ini('monitoring.ini');
		$log->LogInfo($monit_settings);

		$monit_settings['NOTIFICATION_EMAIL'] = isset($_CLEAN['OP']['email']) ? $_CLEAN['OP']['email'] : "admin@example.com";
		$result = write_shell_config_ini($monit_settings, "../config/monitoring.ini");
		if (!$result) {
			$errorMsg = "softnas settings: Cannot write to softnas configuration file : 'monitoring.ini'";
			$errorProc = true;
			$reply['errMsg'] = $errorMsg;
			$log->LogError($errorMsg);
			return $reply;
		}

		// regenerate monit config
		$script = "config-generator-monit";
		$result = super_script($script);
		if ($result['rv'] != 0) {
			$errorProc = true;
			$errorMsg = "monit configuration generator failed";
			$reply['errMsg'] = $errorMsg;
			$log->LogError("$errorMsg Details: {$result['output_str']}");
		}
		$successMsg = "Settings were saved successfully";
		$reply['msg'] = $successMsg;
	}
	return $reply;
}

function proc_kms_settings() {
	global $_CLEAN;
	global $_config;
	global $errorProc;
	global $errorMsg;
	global $successMsg;
	global $log;
	global $isForm;
	$reply = array();
	
	$command = isset($_CLEAN['OP']['command']) ? $_CLEAN['OP']['command'] : 'get_kms_settings';
	
	if ($command == "get_kms_settings") {
		
	}
	
	if ($command == "update_kms_custom_settings") {

		$kms_data = read_ini('kms.ini');
		$custom_data = (isset($kms_data['custom']) ? $kms_data['custom'] : array());
		
		if(isset($_CLEAN['OP']['kmsPassword']) && $_CLEAN['OP']['kmsPassword'] !== '') {
			if($_CLEAN['OP']['kmsPassword'] == $_CLEAN['OP']['kmsPasswordConfirm']) {
				$custom_data['kmsPassword'] = $_CLEAN['OP']['kmsPassword'];
			} else {
				$errorMsg = "Softnas KMS custom settings: Password mismatch";
				$errorProc = true;
				$reply['errMsg'] = $errorMsg;
				$log->LogError($errorMsg);
				return $reply;
			}
		}
		
		$custom_data['kmsHost'] = isset($_CLEAN['OP']['kmsHost']) ? $_CLEAN['OP']['kmsHost'] : '';
		$custom_data['kmsPort'] = isset($_CLEAN['OP']['kmsPort']) ? $_CLEAN['OP']['kmsPort'] : '';
		$custom_data['kmsKeyFilePath'] = isset($_CLEAN['OP']['kmsKeyFilePath']) ? $_CLEAN['OP']['kmsKeyFilePath'] : '';
		$custom_data['kmsCertFilePath'] = isset($_CLEAN['OP']['kmsCertFilePath']) ? $_CLEAN['OP']['kmsCertFilePath'] : '';
		$custom_data['kmsCertRequired'] = isset($_CLEAN['OP']['kmsCertRequired']) ? $_CLEAN['OP']['kmsCertRequired'] : '';
		$custom_data['kmsSSLVersion'] = isset($_CLEAN['OP']['kmsSSLVersion']) ? $_CLEAN['OP']['kmsSSLVersion'] : '';
		$custom_data['kmsCaFilePath'] = isset($_CLEAN['OP']['kmsCaFilePath']) ? $_CLEAN['OP']['kmsCaFilePath'] : '';
		$custom_data['kmsHandshake'] = isset($_CLEAN['OP']['kmsHandshake']) ? $_CLEAN['OP']['kmsHandshake'] : true;
		$custom_data['kmsSuppressEofs'] = isset($_CLEAN['OP']['kmsSuppressEofs']) ? $_CLEAN['OP']['kmsSuppressEofs'] : true;
		$custom_data['kmsUsername'] = isset($_CLEAN['OP']['kmsUsername']) ? $_CLEAN['OP']['kmsUsername'] : '';
		
		$kms_data['custom'] = $custom_data;
		write_ini($kms_data, 'kms.ini');
		
	}
	
	if ($command == "update_kms_amazon_settings") {

		$kms_data = read_ini('kms.ini');
		$amazon_data = (isset($kms_data['amazon']) ? $kms_data['amazon'] : array());
		
		$access_key = isset($_CLEAN['OP']['amazonKmsAccess']) ? $_CLEAN['OP']['amazonKmsAccess'] : '';
		$secret_key = isset($_CLEAN['OP']['amazonKmsSecret']) ? $_CLEAN['OP']['amazonKmsSecret'] : '';
		$master_key = isset($_CLEAN['OP']['amazonKmsMasterKey']) ? $_CLEAN['OP']['amazonKmsMasterKey'] : '';
		
		if ($master_key == '') {
			$errorProc = true;
			$errorMsg = "AWS Master Key is required!";
			$reply['errMsg'] = $errorMsg;
			$log->LogError($errorMsg);
			return $reply;
		}
		
		$iam = iam_check();
		$S3ini = false;
		if ($iam !== FALSE) {
			$access_key = $iam['AccessKeyId'];
			$secret_key = $iam['SecretAccessKey'];
		} elseif ($access_key == '' || $secret_key == '') {
			$errorProc = true;
			$errorMsg = "AWS Keys are required!";
			$reply['errMsg'] = $errorMsg;
			$log->LogError($errorMsg);
			return $reply;
		} elseif ($access_key == '●●●●●●●●●●●●●●●●●●●●●●●●●●●●●●' && $secret_key == '●●●●●●●●●●●●●●●●●●●●●●●●●●●●●●') {
			$S3ini = read_s3_config();
			$keys = $S3ini['global'];
			if (isset($keys['awsAccessKey']) && isset($keys['awsSecretKey'])) {
				$access_key = $keys['awsAccessKey'];
				$secret_key = $keys['awsSecretKey'];
			}
			if ($access_key == '●●●●●●●●●●●●●●●●●●●●●●●●●●●●●●' && $secret_key == '●●●●●●●●●●●●●●●●●●●●●●●●●●●●●●') {
				$errorProc = true;
				$errorMsg = "AWS Keys is required!";
				$reply['errMsg'] = $errorMsg;
				$log->LogError($errorMsg);
				return $reply;
			}
		}
		// Validation of keys:
		//set_aws_keys_to_shell($access_key, $secret_key);
		set_aws_profile_keys('kmskeys', $access_key, $secret_key);
		$instance_data = get_aws_instance_data();
		$region = $instance_data->region;
		$result = sudo_execute("aws kms generate-data-key --key-id $master_key --number-of-bytes 10 --profile kmskeys --region $region");
		if ( stripos($result['output_str'], "The security token included in the request is invalid") !== false ) {
				$errorProc = true;
				$errorMsg = "Wrong AWS Access/Secret Keys!";
				$reply['errMsg'] = $errorMsg;
				$log->LogError($errorMsg);
				return $reply;
		}
		if ( stripos($result['output_str'], "Invalid keyId") !== false ) {
				$errorProc = true;
				$errorMsg = "Wrong Master Key!";
				$reply['errMsg'] = $errorMsg;
				$log->LogError($errorMsg);
				return $reply;
		}
		///   #959: /////////////////////////
		if ($S3ini === false) {
			$S3ini = read_s3_config();
			$S3ini['global']['awsAccessKey'] = $access_key;
			$S3ini['global']['awsSecretKey'] = $secret_key;
			write_s3_config($S3ini);
		}
		$amazon_data['keyId'] = $master_key;
		$kms_data['amazon'] = $amazon_data;
		write_ini($kms_data, 'kms.ini');
	}
	
	if ($command == "update_kms_azure_settings") {
		
		$kms_data = read_ini('kms.ini');
		$azure_data = (isset($kms_data['azure']) ? $kms_data['azure'] : array());
		
		$azure_username = isset($_CLEAN['OP']['azureKmsUsername']) ? $_CLEAN['OP']['azureKmsUsername'] : '';
		$azure_password = isset($_CLEAN['OP']['azureKmsPassword']) ? $_CLEAN['OP']['azureKmsPassword'] : '';
		$azure_tenant = isset($_CLEAN['OP']['azureKmsTenant']) ? $_CLEAN['OP']['azureKmsTenant'] : '';
		$azure_key_vault = isset($_CLEAN['OP']['azureKmsKeyVault']) ? $_CLEAN['OP']['azureKmsKeyVault'] : '';
		$azure_key = isset($_CLEAN['OP']['azureKmsKey']) ? $_CLEAN['OP']['azureKmsKey'] : '';
		
		if ($azure_key_vault == '') {
			$errorProc = true;
			$errorMsg = "Azure Key Vault is required!";
			$reply['errMsg'] = $errorMsg;
			$log->LogError($errorMsg);
			return $reply;
		}
		
		if ($azure_key == '') {
			$errorProc = true;
			$errorMsg = "Azure Key is required!";
			$reply['errMsg'] = $errorMsg;
			$log->LogError($errorMsg);
			return $reply;
		}
		
		if ($azure_username == '' || $azure_password == '') {
			$errorProc = true;
			$errorMsg = "Azure credentials are required!";
			$reply['errMsg'] = $errorMsg;
			$log->LogError($errorMsg);
			return $reply;
		} else {
			$azure_ini = azure_login_and_get_info($azure_username, $azure_password, false, $azure_tenant);
			
			if (isset($azure_ini['errMsg'])) {
				$errorProc = true;
				$errorMsg = "update_kms_azure_settings: Login failed: ".$azure_ini['errMsg'];
				$reply['errMsg'] = $errorMsg;
				$log->LogError( $errorMsg );
				return $reply;
			}
		}
		
		// Validation of keys:
		$azure_key_list = azure_key_list($azure_key_vault);
		if (isset($azure_key_list['errMsg'])) {
			$errorProc = true;
			$errorMsg = "update_kms_azure_settings: Setting Azure Key Vault: ".$azure_key_list['errMsg'];
			$reply['errMsg'] = $errorMsg;
			$log->LogError( $errorMsg );
			return $reply;
		}
		$key_found = false;
		foreach ($azure_key_list as $i => $item) {
			if ($item[0] == $azure_key) {
				$key_found = true;
			}
		}
		if (!$key_found) {
			$errorProc = true;
			$errorMsg = "Wrong Azure Key!";
			$reply['errMsg'] = $errorMsg;
			$log->LogError($errorMsg);
			return $reply;
		}
		
		$azure_data['keyVaultId'] = $azure_key_vault;
		$azure_data['keyId'] = $azure_key;
		$kms_data['azure'] = $azure_data;
		write_ini($kms_data, 'kms.ini');
	}
	
	if ($command == "get_azure_kms_keys") {
		$keyvault = isset($_CLEAN['OP']['key_vault']) ? $_CLEAN['OP']['key_vault'] : null;
		if (!$keyvault) {
			$error = true;
			$errorMsg = "get_azure_kms_keys: No Key Vault specified";
			$errorProc = true;
			$log->LogError($errorMsg);
			return $reply;
		}
		$azure_key_list = azure_key_list($keyvault);
		if (isset($azure_key_list['errMsg'])) {
			$errorProc = true;
			$errorMsg = "update_kms_azure_settings: ".$azure_key_list['errMsg'];
			$reply['errMsg'] = $errorMsg;
			$log->LogError( $errorMsg );
			return $reply;
		}
		return array("azure_keys" => $azure_key_list);
	}
}

		function proc_monit_settings() {
			global $_CLEAN; // clean POST parameters
			global $_config;
			global $errorProc;
			global $errorMsg;
			global $successMsg;
			global $log;
			global $isForm;
			$reply = array();
			$licenseInfo = snas_license_info(); // get the licensed capacity info
			$valid = $licenseInfo['valid'];
			if ($valid == false) // we have an invalid licensing outcome (probably exceeded licensed pool capacity limits or expired license)
			{
				$error = true;
				$errorMsg = "License failure - unable to continue. Details: " . $licenseInfo['errMsg'];
				$errorProc = true; // pass error back to client
				$log->LogError($errorMsg);
				return $reply;
			}
			$command = isset($_CLEAN['OP']['command']) ? $_CLEAN['OP']['command'] : 'get_monit_settings';
			$monit_ini = 'monitoring.ini';
			if ($command == 'get_monit_settings') {
				$isForm = true;
				$path = $_config['proddir'] . "../config/$monit_ini";
				if (!is_readable($path)) {
					exec_command("bash -c 'chown root.apache $path; chmod 660 $path;'");
				}
				$monit_settings = read_ini($monit_ini);
				//$reply += $monit_settings;
				if ($monit_settings) {
					if ($monit_settings['NOTIFICATION_EMAIL'] == "admin@example.com") {
						$monit_settings['NOTIFICATION_EMAIL'] = "";
					}
					$monit_settings['SMTP_PASSWORD'] = base64_decode($monit_settings['SMTP_PASSWORD']);
					$monit_settings['SUPRESS_KEYWORDS_SNSERV'] = base64_decode($monit_settings['SUPRESS_KEYWORDS_SNSERV']);
					$monit_settings['SUPRESS_KEYWORDS_SNAPREPLICATE'] = base64_decode($monit_settings['SUPRESS_KEYWORDS_SNAPREPLICATE']);
					if (isset($monit_settings['SMTP_ENCRYPTION']) && $monit_settings['SMTP_ENCRYPTION'] == "") {
						$monit_settings['SMTP_ENCRYPTION'] = "tlsv11"; // #4043 (set default combo box value)
					}
					if ($monit_settings['USE_EXT_SMTP'] == 'yes') {
						if (!isset($monit_settings['SMTP_FROM']) || !$monit_settings['SMTP_FROM']) {
							$monit_settings['SMTP_FROM'] = 'softnas@'.gethostname();
						}
					} else {
						$monit_settings['SMTP_FROM'] = '';
					}
					$reply+= $monit_settings;
				} else {
					$errorMsg = "monit settings: Enable to load monit configuration file: $monit_ini";
					$errorProc = true;
					$reply['errMsg'] = $errorMsg;
					$log->LogError($errorMsg);
					return $reply;
				}
			} elseif ($command == 'test_smtp_settings') {
				require_once 'Email/Mail.php';
				$to = $_CLEAN['OP']['notification_email'];
				if (empty($to)) {
                    $errorMsg = "Email address is empty";
                    $errorProc = true;
                    $reply['msg'] = $errorMsg;
                    return $reply;
				}
				if (empty($_CLEAN['OP']['smtp_mailserver'])) {
					$driver = 'mail';
                    $params = array();
				} else {
                    $driver = 'smtp';
                    $smtp_host = $_CLEAN['OP']['smtp_mailserver'];
                    $smtp_port = $_CLEAN['OP']['smtp_port'];
                    $smtp_from = (isset($_CLEAN['OP']['smtp_from']) && $_CLEAN['OP']['smtp_from']) ? $_CLEAN['OP']['smtp_from'] : 'softnas@'.gethostname();
                    $params = array(
                        'host' => $smtp_host,
                        'port' => $smtp_port,
                        'timeout' => 25
                    );

                    if (!empty($_CLEAN['OP']['smtp_username']) || !empty($_CLEAN['OP']['smtp_password'])) {
                        $params = array_merge($params, array(
                            'auth' => true,
                            'username' => $_CLEAN['OP']['smtp_username'],
                            'password' => $_CLEAN['OP']['smtp_password'],
                        ));
                    }

				}
				$headers = array(
					'From' => $smtp_from,
					'To' => $to,
					'Subject' => "Softnas Monitoring Test Mail",
					'Reply-To' => $smtp_from
				);
				$body = "Test Mail from Softnas";
				$smtp = Mail::factory($driver, $params);
				$mail = $smtp->send($to, $headers, $body);
				if (PEAR::isError($mail)) {
					$error = true;
                    $errorMsg = "SMTP Error : " . $mail->getMessage();
                    $errorProc = true;
                    $log->LogError($errorMsg);
                    $reply['msg'] = $errorMsg;
                    return $reply;
				} else {
					return true;
				}
            } elseif ($command == 'update_monit_settings') {
				$isForm = true;
				// read monit settings
				$path = $_config['proddir'] . "../config/$monit_ini";
				if (!is_readable($path)) {
					exec_command("bash -c 'chown root.apache $path; chmod 660 $path;'");
				}
				$monit_settings = read_ini($monit_ini);
				## Basic Monit Settings
				$monit_settings['POLLING_INTERVAL'] =	isset($_CLEAN['OP']['POLLING_INTERVAL']) ? $_CLEAN['OP']['POLLING_INTERVAL'] : "";
				## Services to monitor. Valid options "on" and "off"
				$monit_settings['MONITOR_HTTPD'] =		(isset($_CLEAN['OP']['MONITOR_HTTPD']) && $_CLEAN['OP']['MONITOR_HTTPD'] == 'on') ? 'on' : 'off';
				$monit_settings['MONITOR_SSHD'] =		(isset($_CLEAN['OP']['MONITOR_SSHD']) && $_CLEAN['OP']['MONITOR_SSHD'] == 'on') ? 'on' : 'off';
				$monit_settings['MONITOR_SENDMAIL'] =	(isset($_CLEAN['OP']['MONITOR_SENDMAIL']) && $_CLEAN['OP']['MONITOR_SENDMAIL'] == 'on') ? 'on' : 'off';
				$monit_settings['MONITOR_WINBIND'] =	(isset($_CLEAN['OP']['MONITOR_WINBIND']) && $_CLEAN['OP']['MONITOR_WINBIND'] == 'on') ? 'on' : 'off';
				$monit_settings['MONITOR_NFS'] =		(isset($_CLEAN['OP']['MONITOR_NFS']) && $_CLEAN['OP']['MONITOR_NFS'] == 'on') ? 'on' : 'off';
				$monit_settings['MONITOR_ULTRAFAST'] =	(isset($_CLEAN['OP']['MONITOR_ULTRAFAST']) && $_CLEAN['OP']['MONITOR_ULTRAFAST'] == 'on') ? 'on' : 'off';
				$monit_settings['MONITOR_SMB'] =		(isset($_CLEAN['OP']['MONITOR_SMB']) && $_CLEAN['OP']['MONITOR_SMB'] == 'on') ? 'on' : 'off';
				$monit_settings['MONITOR_NMB'] =		(isset($_CLEAN['OP']['MONITOR_NMB']) && $_CLEAN['OP']['MONITOR_NMB'] == 'on') ? 'on' : 'off';
				$monit_settings['MONITOR_HOTSPARE'] =	(isset($_CLEAN['OP']['MONITOR_HOTSPARE']) && $_CLEAN['OP']['MONITOR_HOTSPARE'] == 'on') ? 'on' : 'off';
				$monit_settings['MONITOR_NTPD'] =		(isset($_CLEAN['OP']['MONITOR_NTPD']) && $_CLEAN['OP']['MONITOR_NTPD'] == 'on') ? 'on' : 'off';
				$monit_settings['MONITOR_NETATALK'] =	(isset($_CLEAN['OP']['MONITOR_NETATALK']) && $_CLEAN['OP']['MONITOR_NETATALK'] == 'on') ? 'on' : 'off';
				$monit_settings['MONITOR_NIFI'] =		(isset($_CLEAN['OP']['MONITOR_NIFI']) && $_CLEAN['OP']['MONITOR_NIFI'] == 'on') ? 'on' : 'off';
				## Thresholds for system resources Valid options are "XX%"
				$monit_settings['CPU_WARNING_USER'] =	((isset($_CLEAN['OP']['CPU_WARNING_USER']) && is_numeric_between($_CLEAN['OP']['CPU_WARNING_USER'], 0, 100)) ? $_CLEAN['OP']['CPU_WARNING_USER'] : 80) . '%';
				$monit_settings['CPU_WARNING_SYSTEM'] =	((isset($_CLEAN['OP']['CPU_WARNING_SYSTEM']) && is_numeric_between($_CLEAN['OP']['CPU_WARNING_SYSTEM'], 0, 100)) ? $_CLEAN['OP']['CPU_WARNING_SYSTEM'] : 20) . '%';
				$monit_settings['CPU_WARNING_WAIT'] =	((isset($_CLEAN['OP']['CPU_WARNING_WAIT']) && is_numeric_between($_CLEAN['OP']['CPU_WARNING_WAIT'], 0, 100)) ? $_CLEAN['OP']['CPU_WARNING_WAIT'] : 20) . '%';
				$monit_settings['MEMORY_WARNING'] =		((isset($_CLEAN['OP']['MEMORY_WARNING']) && is_numeric_between($_CLEAN['OP']['MEMORY_WARNING'], 0, 100)) ? $_CLEAN['OP']['MEMORY_WARNING'] : 80) . '%';
				$monit_settings['DISK_SPACE_WARNING'] =	((isset($_CLEAN['OP']['DISK_SPACE_WARNING']) && is_numeric_between($_CLEAN['OP']['DISK_SPACE_WARNING'], 0, 100)) ? $_CLEAN['OP']['DISK_SPACE_WARNING'] : 80) . '%';
				## Monitor log files
				## Keywords to suppress notifications should be in regular expression form. For example to supress alerts for all error log entries containing the words foo or bar enter "foo|bar"
				$monit_settings['MONITOR_SNSERV'] =				(isset($_CLEAN['OP']['MONITOR_SNSERV']) && $_CLEAN['OP']['MONITOR_SNSERV'] == 'yes') ? 'yes' : 'no';
				$monit_settings['SUPRESS_KEYWORDS_SNSERV'] =	isset($_CLEAN['OP']['SUPRESS_KEYWORDS_SNSERV']) ? base64_encode(html_entity_decode($_CLEAN['OP']['SUPRESS_KEYWORDS_SNSERV'], ENT_QUOTES, "UTF-8")) : "";
				$monit_settings['MONITOR_SNAPREPLICATE'] =		(isset($_CLEAN['OP']['MONITOR_SNAPREPLICATE']) && $_CLEAN['OP']['MONITOR_SNAPREPLICATE'] == 'yes') ? 'yes' : 'no';
				$monit_settings['SUPRESS_KEYWORDS_SNAPREPLICATE'] = isset($_CLEAN['OP']['SUPRESS_KEYWORDS_SNAPREPLICATE']) ? base64_encode(html_entity_decode($_CLEAN['OP']['SUPRESS_KEYWORDS_SNAPREPLICATE'], ENT_QUOTES, "UTF-8")) : "";
				## Notifications
				## If you want to use gmail SMTP set the following
				## USE_EXT_SMTP="yes"
				## SMTP_MAILSERVER="smtp.gmail.com"
				## SMTP_PORT="587"
				## SMTP_USERNAME="user@gmail.com"
				## SMTP_PASSWORD="password"
				## SMTP_ENCRYPTION="tlsv11"
				## Valid values for SMTP_ENCRYPTION are SSLV2, SSLV3 and TLSV1.1
				$monit_settings['NOTIFICATION_EMAIL'] =	(isset($_CLEAN['OP']['NOTIFICATION_EMAIL']) && $_CLEAN['OP']['NOTIFICATION_EMAIL']) ? $_CLEAN['OP']['NOTIFICATION_EMAIL'] : 'admin@example.com';
				$monit_settings['USE_EXT_SMTP'] =		(isset($_CLEAN['OP']['USE_EXT_SMTP']) && $_CLEAN['OP']['USE_EXT_SMTP'] == 'yes') ? 'yes' : 'no';
				$monit_settings['SMTP_MAILSERVER'] =	isset($_CLEAN['OP']['SMTP_MAILSERVER']) ? $_CLEAN['OP']['SMTP_MAILSERVER'] : "";
				$monit_settings['SMTP_PORT'] =			isset($_CLEAN['OP']['SMTP_PORT']) ? $_CLEAN['OP']['SMTP_PORT'] : "";
				$monit_settings['SMTP_USERNAME'] =		isset($_CLEAN['OP']['SMTP_USERNAME']) ? $_CLEAN['OP']['SMTP_USERNAME'] : "";
				$monit_settings['SMTP_PASSWORD'] =		isset($_CLEAN['OP']['SMTP_PASSWORD']) ? base64_encode(html_entity_decode($_CLEAN['OP']['SMTP_PASSWORD'], ENT_QUOTES, "UTF-8")) : "";
				$monit_settings['SMTP_FROM'] =			isset($_CLEAN['OP']['SMTP_FROM']) ? $_CLEAN['OP']['SMTP_FROM'] : 'softnas@'.gethostname();
				$monit_settings['SMTP_ENCRYPTION'] =	isset($_CLEAN['OP']['SMTP_ENCRYPTION']) ? $_CLEAN['OP']['SMTP_ENCRYPTION'] : "";
				$monit_settings['MONITOR_S3_ERROR'] =		(isset($_CLEAN['OP']['MONITOR_S3_ERROR']) && $_CLEAN['OP']['MONITOR_S3_ERROR'] == 'on') ? 'on' : 'off';
				$monit_settings['MONITOR_S3_WARN'] =		(isset($_CLEAN['OP']['MONITOR_S3_WARN']) && $_CLEAN['OP']['MONITOR_S3_WARN'] == 'on') ? 'on' : 'off';
				$monit_settings['MONITOR_HA_ERROR'] =		(isset($_CLEAN['OP']['MONITOR_HA_ERROR']) && $_CLEAN['OP']['MONITOR_HA_ERROR'] == 'on') ? 'on' : 'off';
				$monit_settings['MONITOR_HA_WARN'] =		(isset($_CLEAN['OP']['MONITOR_HA_WARN']) && $_CLEAN['OP']['MONITOR_HA_WARN'] == 'on') ? 'on' : 'off';
				$monit_settings['MONITOR_DELTA_SYNC_ERROR'] =		(isset($_CLEAN['OP']['MONITOR_DELTA_SYNC_ERROR']) && $_CLEAN['OP']['MONITOR_DELTA_SYNC_ERROR'] == 'on') ? 'on' : 'off';
				$monit_settings['MONITOR_DELTA_SYNC_WARN'] =		(isset($_CLEAN['OP']['MONITOR_DELTA_SYNC_WARN']) && $_CLEAN['OP']['MONITOR_DELTA_SYNC_WARN'] == 'on') ? 'on' : 'off';
				$monit_settings['MONITOR_HA_WARN'] =		(isset($_CLEAN['OP']['MONITOR_HA_WARN']) && $_CLEAN['OP']['MONITOR_HA_WARN'] == 'on') ? 'on' : 'off';
				$result = write_shell_config_ini($monit_settings, "../config/$monit_ini");
				if (!$result) {
					$errorMsg = "monit settings:: Cannot write to monit settings configuration file: $monit_ini";
					$errorProc = true;
					$reply['errMsg'] = $errorMsg;
					$log->LogError($errorMsg);
					return $reply;
				}
				$script = "config-generator-monit"; // scan for new disks attached since Linux was last booted
				$result = super_script($script); // detects dynamically-added disks in VMware
				if ($result['rv'] != 0) {
					$errorProc = true;
					$errorMsg = "monit configuration generator failed.Deatils : " . $result['output_str'];
					$reply['errMsg'] = $errorMsg;
					$log->LogError($errorMsg);
					return $reply;
				}
				// save SMTP to general_settings.ini
				$general_settings_ini = 'general_settings.ini';
				$general_settings = read_ini($general_settings_ini);
				$smtp_settings = isset($general_settings['smtp']) ? $general_settings['smtp'] : array();
				$smtp_settings['smtp_mailserver'] =	$monit_settings['SMTP_MAILSERVER'];
				$smtp_settings['smtp_port'] =		$monit_settings['SMTP_PORT'];
				$smtp_settings['smtp_use_auth'] =	$monit_settings['SMTP_USERNAME'] ? "on" : "off";
				$smtp_settings['smtp_username'] =	$monit_settings['SMTP_USERNAME'];
				$smtp_settings['smtp_password'] =	$monit_settings['SMTP_PASSWORD'];
				$smtp_settings['smtp_from'] =		$monit_settings['SMTP_FROM'];
				$smtp_settings['smtp_encryption'] =	$monit_settings['SMTP_ENCRYPTION'];
				$general_settings['smtp'] = $smtp_settings;
				$result = write_ini($general_settings, $general_settings_ini);
				if (!$result) {
					$errorMsg = "update general settings: Cannot write to general settings configuration file: $general_settings_ini";
					$errorProc = true;
					$reply['errMsg'] = $errorMsg;
					$log->LogError($errorMsg);
					return $reply;
				}
				
				$successMsg = "Settings were saved successfully";
				$reply['msg'] = $successMsg;
				//$reply['print_r'] = $result;
				
			}
			return $reply;
		}
		function proc_log_settings() {
			global $_CLEAN; // clean POST parameters
			global $_config;
			global $errorProc;
			global $errorMsg;
			global $successMsg;
			global $log;
			global $isForm;
			global $pageTotal;
			$reply = array();
			$licenseInfo = snas_license_info(); // get the licensed capacity info
			$valid = $licenseInfo['valid'];
			if ($valid == false) // we have an invalid licensing outcome (probably exceeded licensed pool capacity limits or expired license)
			{
				$error = true;
				$errorMsg = "License failure - unable to continue. Details: " . $licenseInfo['errMsg'];
				$errorProc = true; // pass error back to client
				$log->LogError($errorMsg);
				return $reply;
			}
			$entry = $i = 0;
			$command = isset($_CLEAN['OP']['command']) ? $_CLEAN['OP']['command'] : 'log_list';
			$log_ini = 'log_settings.ini';
			$log_settings = read_ini($log_ini);
			switch ($command) {
				case 'log_list':
					$start = isset($_CLEAN['OP']['start']) ? $_CLEAN['OP']['start'] : 0; // starting entry (paging toolbar)
					$limit = isset($_CLEAN['OP']['limit']) ? $_CLEAN['OP']['limit'] : 25; // number of entries to return
					$pageTotal = count($log_settings);
					$settings = array();
					if ($log_settings) {
						foreach ($log_settings as $key => $value) {
							if ($i >= $start && $entry < $limit) // within the page limit
							{
								$value['appName'] = $key;
								$settings[] = $value; //$log_settings["$key"]["appName"] = $key;
								//$reply[] = $value;
								$entry++; // increment entries returned
								
							} elseif ($entry == $limit) {
								break;
							}
							$i++;
						}
					}
					$reply+= $settings;
				break;
				case 'create':
					$app_name = isset($_CLEAN['OP']['app_name']) ? $_CLEAN['OP']['app_name'] : '';
					$app_name = trim($app_name); // avoid accidental spaces
					if ($_CLEAN['OP']['app_name'] == '') {
						$errorProc = true;
						$errorMsg = "The application $app_name is required!";
					} elseif (isset($log_settings["$app_name"])) {
						$errorProc = true;
						$errorMsg = "The application $app_name already exists!";
					}
					if ($errorProc) {
						$reply['errMsg'] = $errorMsg;
						$log->LogError($errorMsg);
						return $reply;
					}
					$log_file = isset($_CLEAN['OP']['log_file']) ? $_CLEAN['OP']['log_file'] : '';
					$log_file = trim($log_file); // avoid accidental spaces
					if ($_CLEAN['OP']['log_file'] == '') {
						$errorProc = true;
						$errorMsg = "The log file is required!";
					} elseif (!file_exists($log_file)) {
						$result = sudo_execute("test -f \"$log_file\" && echo \"exits\" || (echo \"not exists\" && exit 1)");
						//$log->LogDebug(print_r($result, true));
						if ($result['rv'] != 0) {
							$errorProc = true;
							$errorMsg = "The log file $log_file does not exist!";
						}
					} elseif (!is_readable($log_file)) {
						$result = sudo_execute("test -r \"$log_file\" -a -f \"$log_file\" && echo \"readable\" || (echo \"not readable\" && exit 1)");
						//$log->LogDebug("test -r \"$log_file\" -a -f \"$log_file\" && echo \"readable\" || (echo \"not readable\" && exit 1) : ".print_r($result, true));
						if ($result['rv'] != 0) {
							$errorProc = true;
							$errorMsg = "The log file $log_file is not readable!";
						}
					}
					if ($errorProc) {
						$reply['errMsg'] = $errorMsg;
						$log->LogError($errorMsg);
						return $reply;
					}
					$log_settings["$app_name"] = array(
						'logFile' => $log_file
					);
					$result = write_ini($log_settings, 'log_settings.ini');
					if (!$result) {
						$errorMsg = "log settings: Cannot write to log settings configuration file: $log_ini";
						$errorProc = true;
						$reply['errMsg'] = $errorMsg;
						$log->LogError($errorMsg);
						return $reply;
					}
				break;
				case 'delete':
					$app_name = isset($_CLEAN['OP']['appName']) ? $_CLEAN['OP']['appName'] : '';
					if (!isset($log_settings["$app_name"])) {
						$errorProc = true;
						$errorMsg = "The application $app_name does not exist!";
						$reply['errMsg'] = $errorMsg;
						$log->LogError($errorMsg);
						return $reply;
					}
					unset($log_settings["$app_name"]);
					$result = write_ini($log_settings, 'log_settings.ini');
					if (!$result) {
						$errorMsg = "log settings: Cannot write to log settings configuration file: $log_ini";
						$errorProc = true;
						$reply['errMsg'] = $errorMsg;
						$log->LogError($errorMsg);
						return $reply;
					}
				break;
				case 'log_content':
					global $pageTotal;
					$application = isset($_CLEAN['OP']['application']) ? $_CLEAN['OP']['application'] : ""; //
					if (!isset($log_settings["$application"])) {
						$errorProc = true;
						$errorMsg = "The application $application does not exist!";
						$reply['errMsg'] = $errorMsg;
						$log->LogError($errorMsg);
						return $reply;
					}
					$log->LogDebug("The application $application exists");
					$file = $log_settings["$application"]['logFile'];
					//$reply['file'] = $file;
					$start = isset($_CLEAN['OP']['start']) ? $_CLEAN['OP']['start'] : 0; // starting entry (paging toolbar)
					$limit = isset($_CLEAN['OP']['limit']) ? $_CLEAN['OP']['limit'] : 25; // number of entries to return
					$logsize = get_logsize($file);
					//$reply["logsize"] = $logsize;
					$result = get_log_content($file, $start, $limit); // get inverted log entries in array
					$pageTotal = $logsize;
					$reply['content'] = $result['output_str'];
					/*if ($lines !== FALSE)
					     {
					       foreach ($lines as $key => $value) {
					         $reply[] = array('content' => $value);
					       }
					       //$reply["lines"] = print_r($lines, true);
					       //$reply += $lines;
					       $pageTotal = $logsize;
					     }*/
					/*foreach ( $members as $member )
					     {
					         if( $i >= $start && $entry < $limit )           // within the page limit
					         {
					           $reply[$entry]['entry']          = $member;
					           $reply[$entry]['logsize'] = $logsize;         // include the total log size
					           $entry++;                                     // increment entries returned
					         }
					         $i++;
					     }
					     $pageTotal = $i;                                    // return 'total' records actually available (for paging)
					*/
					return $reply;
				break;
				case 'application_list':
					$settings = array();
					foreach ($log_settings as $key => $value) {
						$settings[] = array(
							'appName' => $key
						); //$log_settings["$key"]["appName"] = $key;
						
					}
					$reply+= $settings;
				break;
				case 'update_loglevel':
					$loglevels = array(
						'Debug',
						'Info',
						'Warn',
						'Error',
						'Fatal',
						'Off'
					);
					$softnas_level = isset($_CLEAN['OP']['softnas_ini']) ? $_CLEAN['OP']['softnas_ini'] : ""; // storagecenter log level
					$snapreplicate_level = isset($_CLEAN['OP']['snapreplicate_ini']) ? $_CLEAN['OP']['snapreplicate_ini'] : ""; // snapreplicate log level
					if (!in_array($softnas_level, $loglevels)) $softnas_level = 'Info';
					if (!in_array($snapreplicate_level, $loglevels)) $snapreplicate_level = 'Info';
					$softnasini = 'softnas.ini';
					$softnas_settings = read_ini($softnasini);
					$softnas_settings['support']['loglevel'] = $softnas_level;
					$result = write_ini($softnas_settings, $softnasini);
					if (!$result) {
						$errorMsg = "softnas settings: Cannot write to softnas configuration file : $softnasini";
						$errorProc = true;
						$reply['errMsg'] = $errorMsg;
						$log->LogError($errorMsg);
						return $reply;
					}
					$snapreplicateini = 'snapreplicate.ini';
					$snapreplicate_settings = read_ini($snapreplicateini);
					$snapreplicate_settings['support']['loglevel'] = $snapreplicate_level;
					$result = write_ini($snapreplicate_settings, $snapreplicateini);
					if (!$result) {
						$snapini_path = $_config['proddir'] . '/config/' . $snapreplicateini;
						$result = sudo_execute("chmod g+w $snapini_path");
						if ($result['rv'] != 0) {
							$errorMsg = "softnas settings: change permission snapreplicate configuration file : $snapreplicateini. Details : $result[output_str]";
							$errorProc = true;
							$reply['errMsg'] = $errorMsg;
							$log->LogError($errorMsg);
						}
						$result = write_ini($snapreplicate_settings, $snapreplicateini);
						if (!$result) {
							$errorMsg = "softnas settings: Cannot write to snapreplicate configuration file : $snapreplicateini";
							$errorProc = true;
							$reply['errMsg'] = $errorMsg;
							$log->LogError($errorMsg);
						}
					}
					$softnas_settings = read_ini($softnasini);
					$snapreplicate_settings = read_ini($snapreplicateini);
					// $msg_debug = "After Save Configuration\n";
					// $msg_debug .= "Softnas Loglevel :{$softnas_settings['support']['loglevel']}\n";
					// $msg_debug .= "Softnas Loglevel :{$snapreplicate_settings['support']['loglevel']}";
					$successMsg = "Settings were saved successfully";
					$reply['msg'] = $successMsg;
					return $reply;
					break;
				case 'get_loglevel':
					global $isForm;
					$isForm = true; // use a form-response ("data" contains fields)
					$softnasini = 'softnas.ini';
					$softnas_settings = read_ini($softnasini);
					if (!$softnas_settings) {
						$errorMsg = "softnas settings: Cannot read  softnas configuration file : $softnasini";
						$errorProc = true;
						$reply['errMsg'] = $errorMsg;
						$log->LogError($errorMsg);
						return $reply;
					}
					$reply['snserv_loglevel'] = $softnas_settings['support']['loglevel'];
					$snapreplicateini = 'snapreplicate.ini';
					$snapreplicate_settings = read_ini($snapreplicateini);
					if (!$snapreplicate_settings) {
						$errorMsg = "softnas settings: Cannot read snapreplicate configuration file : $snapreplicateini";
						$errorProc = true;
						$reply['errMsg'] = $errorMsg;
						$log->LogError($errorMsg);
					}
					$reply['snapreplicate_loglevel'] = $snapreplicate_settings['support']['loglevel'];
					break;
				default:
					# code...
					break;
				}
				//$successMsg = "Settings wa s saved successfully";
				//$reply['msg'] = $successMsg;
				return $reply;
			}
			function get_logsize($file) {
				global $log;
				//$log->LogDebug("function get_logsize : line ".__LINE__);
				$return = FALSE;
				$result = sudo_execute("test -f \"$file\" && echo \"exits\" || (echo \"not exists\" && exit 1)");
				//$log->LogDebug(print_r($result, true));
				if (file_exists($file) || $result['rv'] == 0) {
					//$log->LogDebug("command : wc -l \"$file\" | cut -f1 -d' '");
					$result = sudo_execute("wc -l \"$file\" | cut -f1 -d' '");
					//$log->LogDebug('command result : ' . print_r($result, true));
					if (is_array($result) && $result[0] != 1) {
						$return = intval($result['output_str']);
					}
				}
				return $return;
			}
			//
			// Returns snapreplicate log file entries as inverted array
			//
			function get_log_content($file, $start, $length) {
				global $log;
				//$log->LogDebug("function ".__FUNCTION__." : line ".__LINE__);
				$return = FALSE;
				$result = sudo_execute("test -f \"$file\" && echo \"exits\" || (echo \"not exists\" && exit 1)");
				//$log->LogDebug(print_r($result, true));
				if (file_exists($file) || $result['rv'] == 0) {
					$lines = $start + $length;
					//$log->LogDebug("command : tail -$lines $file | head -n$length");
					$result = sudo_execute("tail -$lines $file | head -n$length");
					//$log->LogDebug('command result : ' . print_r($result, true));
					if (is_array($result) && $result[0] != 1) {
						$return = $result;
					}
				}
				return $return;
			}
			function proc_get_update_log() {
				session_name('PHPSESSID_port'.$_SERVER['SERVER_PORT']);
				if(session_id() == '') {
					session_start();
				}
				check_logged_in(); // Keep user logged in during the update (#1130)
				global $log;
				global $_CLEAN; // clean POST parameters
				global $_config;
				global $errorProc;
				global $errorMsg;
				global $successMsg;
				global $log;
				global $isForm;
				global $pageTotal;
				$reply = array();
				//$log->LogInfo("Begin function ".__FUNCTION__." : line ".__LINE__);
				$file = $_config['tempdir'] . '/softnas-update.log';
				$lines = isset($_CLEAN['OP']['lineCount']) ? (int)($_CLEAN['OP']['lineCount']) : 0;
				//$length = 10;
				$result = sudo_execute("test -f \"$file\" && echo \"exits\" || (echo \"not exists\" && exit 1)");
				//$log->LogDebug(print_r($result, true));
				//$log->LogInfo("Line count  : {$_CLEAN['lineCount']}");
				if (file_exists($file) || $result['rv'] == 0) {
					//$lines = $start + $length;
					//$log->LogDebug("command : tail -$lines $file | head -n$length");
					$result = sudo_execute("tail -n+{$lines} $file ");
					//$log->LogInfo("command : tail -n+{$lines} $file ");
					if (is_array($result) && $result['rv'] != 1) { // fixing notices and warnings
						$reply['content'] = $result['output_str'];
						$reply['lineCount'] = $lines + count($result['output_arr']);
					} else {
						$errorMsg = "get update log: unable to read log";
						$reply['content'] = "$errorMsg : ".$result['output_str'];
						$reply['lineCount'] = $lines;
						//$errorProc = true;
						$reply['errMsg'] = $errorMsg;
						$log->LogError($errorMsg);
						return $reply;
					}
				}
				//$log->LogDebug("End function ".__FUNCTION__." : line ".__LINE__);
				return $reply;
			}
			function proc_get_update_progress() {
				session_name('PHPSESSID_port'.$_SERVER['SERVER_PORT']);
				if(session_id() == '') {
					session_start();
				}
				check_logged_in(); // Keep user logged in during the update (#1130)
				global $log;
				global $_CLEAN; // clean POST parameters
				global $_config;
				global $errorProc;
				global $errorMsg;
				global $successMsg;
				global $log;
				global $isForm;
				global $pageTotal;
				global $extraProperties;

				$reply = array();
				//$log->LogInfo("Begin function ".__FUNCTION__." : line ".__LINE__);
				$file = $_config['tempdir'] . '/progress.json';
				//$length = 10;
				$result = sudo_execute("test -f \"$file\" && echo \"exits\" || (echo \"not exists\" && exit 1)");
				//$log->LogDebug(print_r($result, true));
				//$log->LogInfo("Line count  : {$_CLEAN['lineCount']}");
				if (file_exists($file) || $result['rv'] == 0) {
					//$lines = $start + $length;
					//$log->LogDebug("command : tail -$lines $file | head -n$length");
					$result = sudo_execute("cat $file ");
					//$log->LogInfo("command : cat $file ");
					if (is_array($result) && $result['rv'] != 1) { // fixing notices and warnings
						$reply['content'] = $result['output_str'];
					} else {
						$errorMsg = "get update progress: enable to read progress.json";
						$errorProc = true;
						$reply['errMsg'] = $errorMsg;
						$log->LogError($errorMsg);
						return $reply;
					}
				}
				//$log->LogDebug("End function ".__FUNCTION__." : line ".__LINE__);

				if(isset($_CLEAN['OP']['updateType'])) {
					$resultProgress = json_encode($reply);
					$resultProgress = str_replace('"[{', '[{', $resultProgress);
					$resultProgress = str_replace('}]"', '}]', $resultProgress);
					$resultProgress = str_replace("'", '"', $resultProgress);
					$resultProgress = str_replace('total', '"total"', $resultProgress);
					$resultProgress = json_decode(str_replace('current', '"current"', $resultProgress), true);
					$contentProgress = $resultProgress['content'][0];
					$resultLog = proc_get_update_log();
					$errMsgProgress = isset($resultProgress['errMsg']) ? $resultProgress['errMsg'] : null;
					$errMsgLog = isset($resultLog['errMsg']) ? $resultLog['errMsg'] : null;
					$extraProperties = array(
						'progressValue' => $contentProgress['current'] / $contentProgress['total'],
						'details' => $resultLog['content']
					);		
					$errorProc = isset($errMsgProgress) || isset($errMsgLog);
					$errorMsg = $errMsgProgress ? $errMsgProgress : ($errMsgLog ? $errMsgLog : null);
				}

				return $reply;
			}
			function proc_support_settings() {
				global $_CLEAN; // clean POST parameters
				global $_config;
				global $errorProc;
				global $errorMsg;
				global $successMsg;
				global $log;
				global $isForm;
				$reply = array();
				$command = isset($_CLEAN['OP']['command']) ? $_CLEAN['OP']['command'] : 'get_settings';
				$softnas_settings = read_ini();
				switch ($command) {
					case 'get_settings':
					break;
					case 'send':
						$email_user = isset($_CLEAN['OP']['email_user']) ? $_CLEAN['OP']['email_user'] : ''; // Grab email_user POST
						$support_ticket = isset($_CLEAN['OP']['support_ticket']) ? $_CLEAN['OP']['support_ticket'] : '';// Grab support_ticket POST
						if (!filter_var($email_user, FILTER_VALIDATE_EMAIL)) {
							$error = true;
							$errorMsg = "Sending report failed - User email is not in valid format";
							$errorProc = true; // pass error back to client
							$log->LogError($errorMsg);
							return $reply;
						}
						$command = "{$_config['proddir']}/scripts/getsupport.sh {$email_user} {$support_ticket} 2>&1";
						$result = sudo_execute($command);
						if ($result['rv'] != 0) {
							$error = true;
							$errorMsg = $result['ouput_str'];
							$errorProc = true; // pass error back to client
							$log->LogError($errorMsg);
							return $reply;
						}
						if (1 != preg_match('/https:.*/',$result['output_str'],$url_matches)) {
							$error = true;
							$errorMsg = "Unable to locate the URL for accessing the final support report";
							$errorProc = true; // pass error back to client
							$log->LogError($errorMsg);
							return $reply;
						}
						if (1 != preg_match('/.* cat (.*)/',$result['output_str'],$log_matches)) {
							$error = true;
							$errorMsg = "Unable to locate the log file for the support report generation and submission process";
							$errorProc = true; // pass error back to client
							$log->LogError($errorMsg);
							return $reply;
						}
						$reply['msg'] = $successMsg = 'Support report is now generating while logging to ' . $log_matches[1] .'. The support report can be downloaded for seven days from' . "\n" . $url_matches[0];
						$reply['url'] = $url_matches[0];
						$reply['log'] = $log_matches[1];
						break;
					}
					return $reply;
				}
				function proc_email_setup() {
					global $_CLEAN; // clean POST parameters
					global $_config;
					global $errorProc;
					global $errorMsg;
					global $successMsg;
					global $log;
					global $isForm;
					$reply = array();
					$licenseInfo = snas_license_info(); // get the licensed capacity info
					$valid = $licenseInfo['valid'];
					if ($valid == false) // we have an invalid licensing outcome (probably exceeded licensed pool capacity limits or expired license)
					{
						$error = true;
						$errorMsg = "License failure - unable to continue. Details: " . $licenseInfo['errMsg'];
						$errorProc = true; // pass error back to client
						$log->LogError($errorMsg);
						return $reply;
					}
					$command = isset($_CLEAN['OP']['command']) ? $_CLEAN['OP']['command'] : 'check';
					$softnas_settings = read_ini();
					switch ($command) {
						case 'check':
							if (isset($softnas_settings['support']['useremail']) && $softnas_settings['support']['useremail'] != '') {
								$reply['useremail'] = $softnas_settings['support']['useremail'];
								$reply['show_email_setup'] = false;
							} else {
								$reply['useremail'] = '';
								$reply['show_email_setup'] = true;
							}
						break;
						case 'update':
							//? isset($_CLEAN['OP']['email_support']) : '';
							$softnas_settings['support']['useremail'] = isset($_CLEAN['OP']['email']) ? $_CLEAN['OP']['email'] : '';
							$result = write_ini($softnas_settings);
							if (!$result) {
								$errorMsg = "softnas settings: Cannot write to softnas configuration file";
								$errorProc = true;
								$reply['errMsg'] = $errorMsg;
								$log->LogError($errorMsg);
								return $reply;
							}
							$monit_ini = 'monitoring.ini';
							$monit_settings = read_ini($monit_ini);
							//$reply += $monit_settings;
							if (!$monit_settings) {
								$errorMsg = "monit settings: Enable to load monit configuration file: $monit_ini";
								$errorProc = true;
								$reply['errMsg'] = $errorMsg;
								$log->LogError($errorMsg);
								return $reply;
							}
							$monit_settings['NOTIFICATION_EMAIL'] = $softnas_settings['support']['useremail'];
							$result = write_shell_config_ini($monit_settings, "../config/$monit_ini");
							if (!$result) {
								$errorMsg = "monit settings:: Cannot write to monit settings configuration file: $monit_ini";
								$errorProc = true;
								$reply['errMsg'] = $errorMsg;
								$log->LogError($errorMsg);
								return $reply;
							}
							$script = "config-generator-monit"; // scan for new disks attached since Linux was last booted
							$result = super_script($script); // detects dynamically-added disks in VMware
							if ($result['rv'] != 0) {
								$errorProc = true;
								$errorMsg = "monit configuration generator failed.Deatils : " . $result['output_str'];
								$reply['errMsg'] = $errorMsg;
								$log->LogError($errorMsg);
								return $reply;
							}
							$successMsg = "Settings were saved successfully";
							$reply['msg'] = $successMsg;
					}
					return $reply;
				}

function proc_log_js_error() {
	global $_CLEAN; // clean POST parameters
	global $_config;
	global $errorProc;
	global $errorMsg;
	global $successMsg;
	global $log;
	if (isset($_CLEAN['OP']['error']) && $error = $_CLEAN['OP']['error']) {
		$log->LogError('JAVASCRIPT ERROR : '.$error);
	}
	return $reply;
}

function proc_log_js_errors() {
	global $_CLEAN;
	global $_config;
	global $errorProc;
	global $errorMsg;
	global $successMsg;
	global $log;
	$reply = array();
	$log_path = __DIR__.'/../logs/javascript.log';
	//$js_log = init_logging($log_path);
	$remote_addr = $_SERVER['REMOTE_ADDR'];
	
	if(isset($_CLEAN['OP']['info'])) {
		$info = $_CLEAN['OP']['info'];
		$error_str = html_entity_decode(str_replace('[ip]', "[$remote_addr]", $info));
	}
	else {
		$errors_param = isset($_CLEAN['OP']['js_errors']) ? $_CLEAN['OP']['js_errors'] : false;
		$browser = isset($_CLEAN['OP']['browser']) ? $_CLEAN['OP']['browser'] : "";
		$browser_version = isset($_CLEAN['OP']['browser_version']) ? $_CLEAN['OP']['browser_version'] : "";
		$os = isset($_CLEAN['OP']['os']) ? $_CLEAN['OP']['os'] : "";
		$errors_param =	html_entity_decode($errors_param);
		$errors	= json_decode($errors_param);
		
		if (!$errors) {
			$errorProc = true;
			$errorMsg = "proc_log_js_errors - Fail to get the JS errors list. json_last_error: ".json_last_error();
			$reply['errMsg'] = $errorMsg;
			$log->LogError($errorMsg);
			return $reply;
		}
		
		$client_info = trim("$os $browser $browser_version");
		if ($client_info != "") {
			$client_info = "[$client_info]";
		}
		
		$errors = (object)$errors;
		$time = ""; $count = ""; $msec = "";
		$error_messages = array();
		foreach ($errors as $file => $file_parts) {
			foreach ($file_parts as $position => $error) {
				$error_obj = (object)$error;
				$time = $error->time;
				$msec = "000";
				if ((int)$time > 1000000000000000) {
					$time = ((int)$time)/1000;
				}
				if ((int)$time > 1000000000000) {
					$msec = ((int)$time) % 1000;
					$time = ((int)$time)/1000;
				}
				$time = date('Y-m-d H:i:s', $time);
				$position_arr = explode('_', $position); // pos_line_column
				$line = $position_arr[1];
				$column = $position_arr[2];
				$message = $error->message;
				$count = ($error->count > 1 ? " ($error->count times)" : "");
				$error_messages[] = "$time.$msec [$remote_addr] $client_info --> $file:$line:$column: $error->message $count";
			}
		}
		if (count($error_messages) == 0) {
			return $reply;
		}
		$error_str = implode(chr(10), $error_messages);
	}

	$result = sudo_execute("echo '$error_str' >> $log_path");
	if ($result['rv'] != 0) {
		$errorProc = true;
		$errorMsg = "proc_log_js_errors - saving to $log_path failed - Details: ". $result['output_str'];
		$reply['errMsg'] = $errorMsg;
		$log->LogError($errorMsg);
	}
	return $reply;
	
}

function proc_applet_data() {
	global $_CLEAN;
	$reply = array();
	$applet = $_CLEAN['OP']['applet'];
	$quick_help_id = $_CLEAN['OP']['quickhelpid'];
	$reply['applet_data'] = get_applet_data($applet, $quick_help_id);
	return $reply;
}

// Enable / disable SSH authentication for root without password
function proc_ssh_auth() {
	$reply = array();
	$S = new SSHConfig();
	if (isset($_REQUEST['root_login'])) {
		$S->set('PermitRootLogin', 'yes');
	} else {
		$S->set('PermitRootLogin', 'without-password');
	}
	if (isset($_REQUEST['password_auth'])) {
		$S->set('PasswordAuthentication', 'yes');
	} else {
		$S->set('PasswordAuthentication', 'no');
	}
	return array('result' => $S->save());
}

function proc_set_status_live_support() {
	$ini = read_ini();
	$new_status = $_POST['enabled'] ? 'true' : 'false';
	if ($new_status != $ini['support']['live_support_enabled']) {
		$ini['support']['live_support_enabled'] = $new_status;
		write_ini($ini);
		get_live_support_info();
	}
}
function proc_get_live_support_info() {
	global $_CLEAN;
	global $log;
	global $errorProc;
	global $errorMsg;
	$info_result = get_live_support_info();
	if (isset($info_result['errMsg'])) {
		$errorProc = true;
		$errorMsg = $info_result['errMsg'];
		$log->LogError($errorMsg);
	}
}
function proc_gold_support_welcome($method) {
    $reply = array();
    $softnas_config = read_ini("softnas.ini");
	if ($method == 'GET') {
		if (array_key_exists('hideGoldSupportWelcome', $softnas_config['support'])) {
			if ($softnas_config['support']['hideGoldSupportWelcome'] == 'true') {
				$reply['hideGoldSupportWelcome'] = true;
			} else {
                $reply['hideGoldSupportWelcome'] = false;
			}
		} else {
            $reply['hideGoldSupportWelcome'] = false;
		}
		return $reply;
	} else {
        global $_CLEAN;
        $hideGoldSupportWelcome = isset($_CLEAN['OP']['hideGoldSupportWelcome']) ? $_CLEAN['OP']['hideGoldSupportWelcome'] : "false";
        $softnas_config['support']['hideGoldSupportWelcome'] = $hideGoldSupportWelcome;
        write_ini($softnas_config, 'softnas.ini');
        $reply['hideGoldSupportWelcome'] = $softnas_config['support']['hideGoldSupportWelcome'];
        return $reply;
	}
}

function proc_submit_platinum_license() {
	global $_CLEAN;
	global $log;
	global $errorProc;
	global $errorMsg;
	
	$log = init_logging(__DIR__.'/../logs/license.log');

    $license_key = isset($_CLEAN['OP']['license_key']) ? $_CLEAN['OP']['license_key'] : null;
    $reg_name = isset($_CLEAN['OP']['reg_name']) ? $_CLEAN['OP']['reg_name'] : null;
    $activation_code = isset($_CLEAN['OP']['activation_code']) ? $_CLEAN['OP']['activation_code'] : null;
    $softnas_ini = read_ini();

    if ($license_key === null || $reg_name === null) {
    	$errorProc = true;
    	$errorMsg = 'License key is empty';
    	return array();
	}

    $log->LogInfo("Testing Platinum license");
    $validation_license_result = validate_platinum_license($license_key, $reg_name);
    if (!is_array($validation_license_result)) {
    	$errorProc = true;
    	$errorMsg = 'Provided license is invalid';
    	return array();
	}
    $log->LogInfo("Test OK. Activating platinum license");

    // Skip activation for SoftNAS.com reg name keys
	if ($reg_name !== "SoftNAS.com") {
        if ($activation_code === null) {
            $log->LogInfo("Activating Platinum license online");
            $activation_result = activate_license_key($license_key, $reg_name, get_hardware_id());
            if (!is_array($activation_result)) {
                $errorProc = true;
                $errorMsg = $activation_result;
                return array();
            }
        } else {
            $log->LogInfo("Validating activation code");
            // Bump up amount of digits to 4 with zeros in the beginning if activation code is less that 4 digits
            $activation_code_length = (string) strlen($activation_code);
            if ($activation_code_length < 4) {
                for ($num = 0; $num < 4 - $activation_code_length; $num++) {
                    $activation_code = "0$activation_code";
                }
            }
            $validation_activation_code_result = validate_license_activation_code($license_key, $validation_license_result['sig'], $activation_code);
            if ($validation_activation_code_result !== true) {
                $errorProc = true;
                $errorMsg = $validation_activation_code_result;
                return array();
            }
            // Re-read softnas.ini to make sure system uuid is present after validating activation key
			if (!array_key_exists('system', $softnas_ini) || !array_key_exists('id', $softnas_ini['system'])) {
                $softnas_ini = read_ini();
			}
            $softnas_ini['license']['platinum_activation_code'] = encode_actcode($softnas_ini['system']['id'], $activation_code);
        }
	}

	// Save Platinum license to ini
	$softnas_ini['license']['platinum_key'] = $license_key;
	$softnas_ini['license']['platinum_reg_name'] = $reg_name;
	write_ini($softnas_ini);
    return array();
}

function proc_get_platinum_license() {
	$softnas_ini = read_ini();
	$reply = array(
		'status' => '',
		'license_key' => '',
		'reg_name' => ''
	);
	if (is_platinum_and_fuusion_license_valid()) {
		if (array_key_exists('license', $softnas_ini) && array_key_exists('platinum_key', $softnas_ini['license'])) {
            $reply['status'] = 'Valid';
            $reply['license_key'] = $softnas_ini['license']['platinum_key'];
            $reply['reg_name'] = $softnas_ini['license']['platinum_reg_name'];
		} else {
            $reply['status'] = 'Included with cloud license';
		}
	} else {
        $reply['status'] = 'No License';
	}

	return $reply;
}

?>
