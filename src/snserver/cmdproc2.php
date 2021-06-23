<?php
//
//  cmdproc2.php - SoftNAS Server Command Processor 2
//
//  Copyright (c) SoftNAS Inc.  All Rights Reserved.
//
//

//
// Handles GettingStarted command-processing
//
// JSON parameters:  command (and optional params per command handlers)
//
function proc_gettingstarted($command = null) {
global $_CLEAN; // clean POST parameters
global $_config;
global $errorProc;
global $errorMsg;
global $successMsg;
global $log;
global $isForm;
$reply = array();
$command = $command ? $command : (isset($_CLEAN['OP']['command']) ? $_CLEAN['OP']['command'] : "");
$log->LogDebug("gettingstarted command: " . $command);
switch ($command) {
	case 'getsettings':
		$isForm = true; // use a form-response ("data" contains fields)
		$utilityModel = "0";
		$showonstartup = "0";
		$showwelcome = "1";
		$completedsteps = "0,0,0,0,0,0,0,0,0,0"; // preset to no steps completed yet
		$config = read_ini();
		if (isset($config['gettingstarted'])) {
			$gettingstarted = $config['gettingstarted'];
			$showwelcome = (isset($gettingstarted['showWelcomeOnStartup']) && $gettingstarted['showWelcomeOnStartup'] === "0") ? "0" : "1";
			$completedsteps = isset($gettingstarted['completedsteps']) ? $gettingstarted['completedsteps'] : "0,0,0,0,0,0,0,0,0,0";
			// return licensing info
			$licenseInfo = snas_license_info(); // get the licensed capacity info
			if ($licenseInfo['model'] == "utility") // use SoftNAS Cloud utility model for support
			{
				$utilityModel = "1";
			}
		}
		$reply['utilityModel'] = $utilityModel;
		$reply['showOnStartup'] = "0";
		$reply['showWelcomeOnStartup'] = $showwelcome;
		$reply['show_password_warning'] = false;
		if (file_exists("/tmp/default_password_warning")) {
			$default_pwd = trim(file_get_contents("/tmp/default_password_warning"));
			if ($default_pwd === "yes") {
				$reply['show_password_warning'] = true;
			}
		}
		$steps = explode(",", $completedsteps); // e.g., completedsteps = "1,0,0,0,0,0,0,0,0,0"
		$n = 1;
		$stepsGettingStarted = array();

		foreach ($steps as $step) {
			$stepName = "step" . strval($n);
			$reply[$stepName] = $step == "1" ? "true" : "false";
			$n++;

			$stepsGettingStarted[$stepName] = $step === '1';
		}
		
		$reply['stepsGettingStarted'] = $stepsGettingStarted;
		$reply['agreement'] = false;
		if(isset($config['registration']) && isset($config['registration']['agreement'])
			&& $config['registration']['agreement'] === "true"){
			$reply['agreement'] = true;
		}

		$reply['flexfilesEnabled'] = false;
		if(isset($config['flexfiles']) && isset($config['flexfiles']['enabled'])
			&& $config['flexfiles']['enabled'] === "true"){
			$reply['flexfilesEnabled'] = true;
		}

		$reply['betaAgreementAccepted'] = false;
		if(isset($config['beta']) && isset($config['beta']['accepted'])
			&& $config['beta']['accepted'] === "true"){
			$reply['betaAgreementAccepted'] = true;
		}
		
		// #2099 - Change Pop-up behavior for Product Reg and support (6)
		$reply['registered'] = false;
		$reply['monitoring_email'] = '';
		if(isset($config['registration']) && isset($config['registration']['registered'])
			&& $config['registration']['registered'] === "true"){
			$reply['registered'] = true;
			
			$prodreg_ini = read_ini("prodreg_inputs.ini");
			if($prodreg_ini && isset($prodreg_ini['inputs'])
			&& isset($prodreg_ini['inputs']['prodRegBusinessEmail'])){
				$reply['monitoring_email'] = $prodreg_ini['inputs']['prodRegBusinessEmail'];
			}
		}
		
		////////////////       Help Content       ///////////////////////////
		$reply['platform'] = get_system_platform();
		
		$url_firewall = "https://vimeo.com/289947809/2a92fe0231";
		if ($reply['platform'] === 'azure') {
			$url_firewall = "https://vimeo.com/290753353/802e1a352a";
		}
		
		$licensing_text = 'Enter your license key and activate your installation. You can get a free license key '.
				'<a href="https://www.softnas.com/wp/try-softnas-cloud-now-storagecenter-registration/" target=_blank">by registering here</a>.'.
				' You will find your license key in <a href="https://www.softnas.com/amember/login/index" target="_blank">your customer portal</a>.  '.
				'Please copy and paste the license key from the customer portal into the License Key field, along with your registered customer name provided with the key.';
		
		if ($utilityModel === "1") { // it's SoftNAS Cloud utility computing model - use proper Support link for activation
			$licensing_text = 'Enter your license key and activate the free support agreement that is included. You can get the free support key ' .
					'<a href="https://www.softnas.com/wp/adding-softnas-cloud-support/" target=_blank">by registering here</a>. ' .
					'You will find your license key in <a href="https://www.softnas.com/amember/login/index" target="_blank">your customer portal</a> after registering.  ' .
					'Please copy and paste the license key from the customer portal into the License Key field, along with your registered customer name provided with the key.';
		}
		
		$monitoring_mail_info = "";
		if ($reply['registered'] === true) {
			$monitoring_mail_info = " <br/><br/><u><a id='btnMonitoringEmail' style='cursor:pointer;'>Click here</a></u> to configure monitoring email";
		}
		
		$reply['help_content'] = array(
			array(
				'1',
				'Verify your network settings. If you have more than one network interface, go ahead and configure it '.
				'using the Network Interfaces menu. Remember that communication is key - if you have a firewall, it must be configured to allow all '.
				'required (and only the required) avenues of communication between your instances and home servers, if applicable. '.
				'<br><br>Here you can also configure the hostname for your SoftNAS node.'.
				'<br><br>For more information on configuring your firewall or security group for SoftNAS Cloud (r), '.
				'see the <a href="' . $url_firewall . '" target="_blank">following video</a>.',
				'https://docs.softnas.com/display/SD/Configuring+Network+Settings',
				'Network Settings',
				'Configure Network Settings and Hostname',
				'netowrksettings'
			),
			array(
				'2',
				'Change the administrator passwords for <b>root</b> and <b>softnas</b> accounts to secure your storage and installation.',
				'https://docs.softnas.com/display/SD/Changing+Default+Passwords',
				'Change Password',
				'Set Administrator Passwords',
				'changepassword'
			),
			array(
				'3',
				'Apply the latest software updates to ensure your system has the latest software installed.',
				'https://docs.softnas.com/display/SD/Updating+to+Latest+Version',
				'Software Updates',
				'Apply Software Updates',
				'update'
			),
			array(
				'4',
				$licensing_text,
				'https://docs.softnas.com/pages/viewpage.action?pageId=65702',
				'Licensing',
				'Activate License Key',
				'license'
			),
			array(
				'5',
				'Add disk storage devices. Disk Devices are added to the SoftNAS virtual machines as either Block or Object devices.',
				'https://docs.softnas.com/display/SD/Allocating+Disk+Storage+Devices',
				'Disk Devices',
				'Add Storage Devices',
				'diskdevices'
			),
			array(
				'6',
				'Partition disk devices. Disk devices must be partitioned to make them avaialble and ready to use in storage pools.',
				'https://docs.softnas.com/display/SD/Partitioning+Disks',
				'Disk Devices',
				'Partition Storage Devices',
				'diskdevices'
			),
			array(
				'7',
				'Create Storage Pools that provide aggregated storage created from multiple disk devices, which can be combined together using software RAID.',
				'https://docs.softnas.com/display/SD/Create+a+Storage+Pool',
				'Storage Pools',
				'Create Storage Pool',
				'pools'
			),
			array(
				'8',
				'Create Volumes (filesystems) for sharing via NFS, AFP and CIFS/SMB and LUNS (block devices) for sharing via iSCSI.',
				'https://docs.softnas.com/pages/viewpage.action?pageId=65712',
				'Volumes and LUNs',
				'Create Volumes and LUNs',
				'volumes'
			),
			array(
				'9',
				'Share storage over the network to clients using NFS, AFP, CIFS/SMB and iSCSI. Use the menus on the left to select '.
				'"NFS Exports", "AFP Volumes", "CIFS Shares", and "iSCSI LUN Targets" to configure each sharing protocol.'.
				'<br><br>Then configure client devices to connect using NFS (VMware/Linux/UNIX), AFP (Apple devices), CIFS/SMB '.
				'(Windows clients) and iSCSI initiators to use shared storage.<br><br>Click on the "?" icon for more details on share configuration.',
				'https://docs.softnas.com/display/SD/Sharing+Volumes+over+a+Network',
				'',
				'Share Volumes (NFS, AFP, CIFS, iSCSI)'
			),
			array(
				'10',
				'Setting up a notification email is a vital step in protecting your data. Without a notification email set up, you will not be notified '.
				'of warnings or failures. By ensuring you are alerted of potential issues on your softnas instance, you ensure an opportunity to '.
				'pre-emptively fix any issue before it becomes critical.' . $monitoring_mail_info,
				'https://docs.softnas.com/display/SD/Monitoring',
				'Administrator.Monitoring',
				'Set up your notification email',
				'administrator/monitoring'
			)
		);
		
		// content with better format and full content (with steps)
		$reply['gettingStartedContent'] = array();

		foreach ($reply['help_content'] as $key => $value) {
			$reply['gettingStartedContent'][$key] = array(
				'id' => (int) $value[0],
				'title' => $value[4],
				'description' => $value[1],
				'url' => $value[2],
				'checked' => $steps[(int) $value[0] - 1] === '1',
				'moduleRef' => isset($value[5]) ? $value[5] : ''
			);
		}

		$reply['option'] = isset($gettingstarted['option']) ? $gettingstarted['option'] : '';

		$successMsg = "Getting started status returned okay.";
		$reply['msg'] = $successMsg;
	break;
	case 'modifysettings':
		$showonstartup = isset($_CLEAN['OP']['showOnStartup']) ? "1" : "0";
		if (isset($_CLEAN['OP']['showWelcomeOnStartup'])) {
			// clicked from Welcome panel
			$showwelcomeonstartup = $_CLEAN['OP']['showWelcomeOnStartup'];
		} else {
			$showwelcomeonstartup = - 1;
			// will not be changed (modifysettings called from 'Getting started panel')
			
		}
		$steps = "";
		for ($n = 0;$n < 10;$n++) {
			$stepName = "step" . strval($n + 1);
			$step = isset($_CLEAN['OP'][$stepName]) ? "1" : "0";
			$steps.= "$step";
			if ($n < 9) $steps.= ",";
		}
		$config = read_ini();
		$gettingStartedTmp = isset($config['gettingstarted']) ? $config['gettingstarted'] : array(); // removing warnings and notices
		if ($showwelcomeonstartup == - 1) { // setting to be without changes
			$showwelcomeonstartup = isset($gettingStartedTmp['showWelcomeOnStartup']) ? $gettingStartedTmp['showWelcomeOnStartup'] : "1";
		}
		$gettingstarted = array();
		$gettingstarted['showonstartup'] = $showonstartup;
		$gettingstarted['showWelcomeOnStartup'] = $showwelcomeonstartup;
		$gettingstarted['completedsteps'] = $steps;
		$config['gettingstarted'] = $gettingstarted;
		if (!write_ini_file($config, $_config['proddir'] . "/config/softnas.ini", true)) {
			$errorProc = true; // pass error back to client
			$errorMsg = "gettingstarted - unable to save license information! (permissions problem)";
			$log->LogError($errorMsg);
		} else $successMsg = "gettingstarted settings updated.";
		return $reply;
		break;
	case 'modifysettings_welcome':
		$showWelcomeOnStartup = isset($_CLEAN['OP']['showWelcomeOnStartup']) ? $_CLEAN['OP']['showWelcomeOnStartup'] : "0";
		$ini = read_ini("softnas.ini");
		$welcome_ini = "1";
		if(!$showWelcomeOnStartup || $showWelcomeOnStartup == "0" || $showWelcomeOnStartup == "false"){
			$welcome_ini = "0";
		}
		$ini['gettingstarted']['showWelcomeOnStartup'] = $welcome_ini;
		write_ini($ini, "softnas.ini");
		$successMsg = "welcome settings updated.";
		return $reply;
		break;
	case 'modifyspecificsettings':
		$ini = read_ini('softnas.ini');	

		if(isset($_CLEAN['OP']['showOnStartup'])) {
			$ini['gettingstarted']['showonstartup'] = ($_CLEAN['OP']['showOnStartup'] === 'true' ? '1' : '0');
		}
		
		if(isset($_CLEAN['OP']['steps'])) {
			$values = json_decode($_POST['steps'], true);
			$steps = array();

			for($i = 1; $i < 11; $i++) {
				$steps[$i - 1] = isset($values["step{$i}"]) ? '1' : '0';
			}

			$ini['gettingstarted']['completedsteps'] = implode(',', $steps);
		}

		if(isset($_CLEAN['OP']['email'])) {
			$ini['support']['useremail'] = $_CLEAN['OP']['email'];	
		}

		if(isset($_CLEAN['OP']['showGoldWelcomeStartup'])) {
			$ini['gettingstarted']['showGoldWelcomeStartup'] = $_CLEAN['OP']['showGoldWelcomeStartup'];
		}

		if(isset($_CLEAN['OP']['rebootWarningSeen'])) {
			$ini['gettingstarted']['rebootWarningSeen'] = $_CLEAN['OP']['rebootWarningSeen'];
		}

		if(isset($_CLEAN['OP']['option'])) {
			$ini['gettingstarted']['option'] = $_CLEAN['OP']['option'];
		}

		$errorProc = !write_ini($ini, 'softnas.ini');

		return $reply;

		break;
	default:
		$errorMsg = "gettingstarted: Invalid command received: $command";
		$errorProc = true;
		$reply['errMsg'] = $errorMsg;
		$log->LogError($errorMsg);
		return $reply;
		break;
	} // end switch
	return $reply;
}

//
// proc_resetsessiontimer - resets the timer
//
function proc_resetsessiontimer() {
	global $log;
	$log->LogDebug("User activity detected. Resetting session timeout");
	check_logged_in();
	return array(
		'is_registered' => check_product_registered()
	);
}

function proc_userpassword() {
	global $log, $_CLEAN, $errorProc, $errorMsg, $successMsg;
	$args = $_CLEAN['OP'];
	/* Check for required arguments */
	if (!isset($args['user'])) {
		$errorProc = true;
		$errorMsg = 'No user was given';
	}
	if (!isset($args['oldpassword'])) {
		$errorProc = true;
		$errorMsg = 'No old password was provided';
	}
	if (!isset($args['newpassword'])) {
		$errorProc = true;
		$errorMsg = 'No new password was provided';
	}
	/* Verify old password is correct */
	exec(dirname(dirname(__FILE__)) . '/scripts/login.sh '.escapeshellarg($args['user']).' '.escapeshellarg($args['oldpassword']), $userpassword_output, $userpassword_return);
	if ($userpassword_return !== 0) {
		$errorProc = true;
		$errorMsg = 'Old password ('.$args['oldpassword'].') was incorrect';
	}
	if ($errorProc) {
		return array('errMsg' => $errorMsg);
	}
	/* Set new password for user */
	exec('echo '.escapeshellarg($args['user'].':'.$args['newpassword']).' | sudo chpasswd', $chpasswd_output, $chpasswd_return);
	if ($chpasswd_return !== 0) {
		$errorProc = true;
		$errorMsg = 'Could not change user password, unknown system error: '.print_r($chpasswd_output, true);
		return false;
	}
	$successMsg = 'Changed user password';
	return true;
}

function check_product_registered() {
	$ini = read_ini();
	return (isset($ini['registration']['registered']) && $ini['registration']['registered'] == 'true');
}

function proc_change_pwd_warning() {
	echo "
		<h2>Password needs to be changed</h2>
		<div>You are using initial password, it is recommended to change it! </div>
		<div></div>
		<div><a href='/buurst/html/changepass.php'>Change Password for 'buurst' user</a></div>
	";
	file_put_contents("/tmp/default_password_warning", "seen");
	exit;
}

function proc_test_remote_address() {
	global $_CLEAN;
	global $_config;
	global $errorProc;
	global $errorMsg;
	global $successMsg;
	global $log;

	$remote_http_host = (isset($_CLEAN['OP']['remote_http_host']) && $_CLEAN['OP']['remote_http_host'] !== "") ?
		$_CLEAN['OP']['remote_http_host'] : null;
	$remote_global_ip = (isset($_CLEAN['OP']['remote_global_ip']) && $_CLEAN['OP']['remote_global_ip'] !== "") ?
		$_CLEAN['OP']['remote_global_ip'] : null;
	if (!$remote_http_host || !$remote_global_ip) {
		$errorProc = true;
		$errorMsg = "test_remote_address: Empty remote address (http_host=$remote_http_host, global_ip=$remote_global_ip)";
		$log->LogError($errorMsg);
		return;
	}
	$port = (isset($_CLEAN['OP']['remote_port']) && $_CLEAN['OP']['remote_port'] !== "") ? $_CLEAN['OP']['remote_port'] : null;

	$is_reachable = is_address_reachable($remote_http_host, $port);
	if (!$is_reachable) {
		//$is_reachable = is_address_reachable($remote_http_host, $port, 15); // 5764
	}
	if (!$is_reachable && ($remote_http_host != $remote_global_ip)) {
		$is_reachable = is_address_reachable($remote_global_ip, $port);
		if (!$is_reachable) {
			//$is_reachable = is_address_reachable($remote_global_ip, $port, 15); //5764
		}
	}
	if (!$is_reachable) {
		$errorProc = true;
		$errorMsg = "Failed to reach {$remote_http_host}/{$remote_global_ip} at port {$port}";
		$log->LogError($errorMsg);
	}
}

function proc_iperf_port($port = null, $action = null) {
	global $_CLEAN;
	global $_config;
	global $errorProc;
	global $errorMsg;
	global $successMsg;
	global $log;
	global $_config;
	
	$reply = array();
	if ($port === null) {
		$port = (isset($_CLEAN['OP']['port']) && $_CLEAN['OP']['port'] !== "") ? (int)($_CLEAN['OP']['port']) : null;
	}
	if ($action === null) {
		$action = (isset($_CLEAN['OP']['action']) && $_CLEAN['OP']['action'] !== "") ? $_CLEAN['OP']['action'] : null;
	}
	
	if ($port === null) {
		$errorMsg = "proc_iperf_port: No port entered";
		$errorProc = true;
		$reply['errMsg'] = $errorMsg;
		$log->LogError($errorMsg);
		return $reply;
	}
	if ($action == 'open') {
		$nohup = $_config['systemcmd']['nohup'];
		$result = sudo_execute("$nohup iperf -s -p {$port} > /dev/null 2>&1 &");
		$log->LogDebug("iperf opening port - ".$result['output_str']);
		$reply['records'] = $result['output_str'];
	} elseif ($action == 'close') {
		$result = sudo_execute("kill -9 $(ps aux | grep [i]perf | awk '{ print $2 }')");
		$log->LogDebug("iperf closing port - ".$result['output_str']);
		$reply['records'] = $result['output_str'];
	} else {
		$errorMsg = "proc_iperf_port: arg. 'action' not correct";
		$errorProc = true;
		$reply['errMsg'] = $errorMsg;
		$log->LogError($errorMsg);
	}
	return $reply;
}

?>
