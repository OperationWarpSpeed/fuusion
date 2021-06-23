<?php
//
//  cmdproc_ultra.php - SoftNAS Server Command Processor for UltraFast Commands
//
//  Copyright (c) SoftNAS Inc.  All Rights Reserved.
//
//
function proc_ultrafast_rpc($data=null) {
	global $errorMsg;
	global $errorProc;
	require_once "/opt/ultrafast/inc/ultrafast.php";
	$data = $data ? $data : $_POST;
	$reply = array();

	try {
		$ultra = new UltraFast();
		$reply = json_decode($ultra->RPC($data['ultrafast_opcode'],base64_decode($data['ultrafast_payload'])));
	}
	catch(Exception $err) {
		$errorProc = true;
		$errorMsg = $err->getMessage();
	}

	return $reply;
}

function proc_ultrafast_authorize_onramp() {
	global $errorMsg;
	global $errorProc;
	global $_CLEAN;
	global $extraProperties;

	require_once "/opt/ultrafast/inc/ultrafast.php";

	$reply = array();

    try {
        $ultra = new UltraFast();
        $onramp = new UltraOnramp();
        $onramp->SetUuid($_CLEAN['OP']['onramp_uuid']);
        $onramp->SetPublicIP($_CLEAN['OP']['public_ip']);
        $onramp->SetPrivateIP($_CLEAN['OP']['private_ip']);
        $reply = json_decode($ultra->AddOnramp($onramp->toJson()));

	$extraProperties['needRestart'] = $ultra->IsRestartRequired();
	}
	catch(Exception $err) {
		$errorProc = true;
		$errorMsg = $err->getMessage();
	}

	return $reply;
}

function proc_ultrafast_add_offramp($data=null) {
	global $errorMsg;
	global $errorProc;
	global $extraProperties;

	global $log;

	require_once "/opt/ultrafast/inc/ultrafast.php";
	$data = $data ? $data : $_POST;
	$reply = array();

    try {
        $ultra = new UltraFast();
        $offramp = new UltraOfframp();
        $offramp->SetUuid($data['offramp_uuid']);
        $offramp->SetDns($data['offramp_dns']);
        $offramp->SetUdpPort($data['offramp_udpport']);
        $offramp->SetMaximumUpstream($data['max_upstream']);
        $offramp->SetMaximumDownstream($data['max_downstream']);

		if(isset($data['schedule_uuid'])) {
			$offramp->SetScheduleUuid($data['schedule_uuid']);
		}

		array_push($reply,json_decode($ultra->AddOfframp($offramp->toJson())));

		$extraProperties['needRestart'] = $ultra->IsRestartRequired();
    }
    catch(Exception $err) {
	    $errorProc = true;
	    $errorMsg = $err->getMessage();
    }

    return $reply;
}

function proc_ultrafast_remove_offramp($uuid) {
	global $errorMsg;
	global $errorProc;
	global $successMsg;
	global $extraProperties;
	require_once "/opt/ultrafast/inc/ultrafast.php";
	$reply = array();

	try {
		$ultra = new UltraFast();
		$offramp_uuid = $uuid ? $uuid : $_POST['offramp_uuid'];

		$connection = new UltraConnection();
		$res = $ultra->GetConnection($offramp_uuid);
		$connection->fromJsonString($res);
		$conn_remote_node = $connection->GetRemoteNode();

		if (file_exists('/var/www/softnas/config/snaprepstatus.ini')) {

			$snap_data = read_snaprepstatus();
			$countRep = (int)$snap_data['Configuration']['nRelationships'];

			for($i = 1; $i <= $countRep; $i++) {
				if($snap_data["Relationship$i"]['Active'] && $snap_data["Relationship$i"]['RemoteNode'] === $conn_remote_node) {
					$errorProc = true;
					$errorMsg = 'Cannot remove connection because replication is active. Please disable SnapReplicate and SNAP HA processes and try again';
					return;
				}
			}
		}

		$ultra->CancelSpeedTest($offramp_uuid);
		$ultra->RemoveOfframp($offramp_uuid);

		$extraProperties['needRestart'] = $ultra->IsRestartRequired();
	}
	catch(Exception $err) {
		$errorProc = true;
		$errorMsg = $err->getMessage();
	}
	$successMsg = 'Connection removed.';
	return $reply;
}

function proc_ultrafast_remove_onramp($uuid) {
	global $errorMsg;
	global $errorProc;
	global $_CLEAN;
	global $extraProperties;

	require_once "/opt/ultrafast/inc/ultrafast.php";
	$reply = array();

	try {
		$ultra = new UltraFast();
		$onramp_uuid = $uuid ? $uuid : $_CLEAN['OP']['onramp_uuid'];
		$ultra->RemoveOnramp($onramp_uuid);

		$extraProperties['needRestart'] = $ultra->IsRestartRequired();
	}
	catch(Exception $err) {
		$errorProc = true;
		$errorMsg = $err->getMessage();
	}

	return $reply;
}

function proc_ultrafast_get_uuid() {
	global $errorMsg;
	global $errorProc;
	global $extraProperties;

	require_once "/opt/ultrafast/inc/ultrafast.php";
	$reply = array();

	try {
		$ultra = new UltraFast();
		$reply['uuid'] = $ultra->GetMyUuid();

		$extraProperties['needRestart'] = $ultra->IsRestartRequired();
	}
	catch(Exception $err) {
		$errorProc = true;
		$errorMsg = $err->getMessage();
	}

	return $reply;
}

function proc_ultrafast_get_channels($connection_uuid="") {
	global $errorMsg;
	global $errorProc;
	global $extraProperties;

	require_once "/opt/ultrafast/inc/ultrafast.php";
	$reply = array();

	try {
		$ultra = new UltraFast();

		$res_channels = $ultra->GetChannels($connection_uuid);
		$chanList = new UltraList($res_channels);

		for ($i = 0;$i < $chanList->size();$i++) {
			$channel = $chanList->getAt($i);
			array_push($reply, $channel);
		}

		$extraProperties['needRestart'] = $ultra->IsRestartRequired();
	}
	catch(Exception $err) {
		$errorProc = true;
		$errorMsg = $err->getMessage();
	}

	return $reply;
}

function proc_ultrafast_get_authorized_onramps() {
	global $errorMsg;
	global $errorProc;
	global $extraProperties;

	require_once "/opt/ultrafast/inc/ultrafast.php";
	$reply = array();

	try {
		$ultra = new UltraFast();
		$res = $ultra->GetOnramps();
		$onramps = new UltraList($res);

		for ($i = 0;$i < $onramps->size();$i++) {
			$onramp = $onramps->getAt($i);
			array_push($reply, $onramp);
		}

		$extraProperties['needRestart'] = $ultra->IsRestartRequired();
	}
	catch(Exception $err) {
		$errorProc = true;
		$errorMsg = $err->getMessage();
	}

	return $reply;
}

function proc_ultrafast_get_configured_offramps() {
	global $errorMsg;
	global $errorProc;
	global $extraProperties;

	require_once "/opt/ultrafast/inc/ultrafast.php";
	$reply = array();

	try {
		$ultra = new UltraFast();
		$res = $ultra->GetOfframps();
		$offramps = new UltraList($res);
		
		for ($i = 0;$i < $offramps->size();$i++) {
			$offramp = $offramps->getAt($i);
			array_push($reply, $offramp);
		}

		$extraProperties['needRestart'] = $ultra->IsRestartRequired();
	}
	catch(Exception $err) {
		$errorProc = true;
		$errorMsg = $err->getMessage();
	}

	return $reply;
}

function proc_ultrafast_target_config() {
	global $extraProperties;

	require_once "/opt/ultrafast/inc/ultrafast.php";
	$data = array();

	try {
		$ultra = new UltraFast();
		$data = array(
			'region_name' => $ultra->GetRegionName(),
			'upd_port' => $ultra->GetUdpPort()
		);

		$extraProperties['needRestart'] = $ultra->IsRestartRequired();

		$ini = read_ini();
		$system = $ini['system'];
		$extraProperties['isAws'] = $system['platform'] === 'amazon';
	}
	catch(Exception $err) {
		$errorProc = true;
		$errorMsg = $err->getMessage();
	}

	return $data;
}

function proc_ultrafast_save_target_config() {
	global $extraProperties;

	require_once "/opt/ultrafast/inc/ultrafast.php";

	try {
		$ultra = new UltraFast();
		$data = json_decode($_POST['records']);

		$ultra->SetRegionName($data->region_name);
		$ultra->SetUdpPort($data->upd_port);

		$extraProperties['needRestart'] = $ultra->IsRestartRequired();
	}
	catch(Exception $err) {
		$errorProc = true;
		$errorMsg = $err->getMessage();
	}

	return $data;
}

function proc_ultrafast_delete_connection() {
	if(isset($_POST['records'])) {
		$data = json_decode($_POST['records']);
		proc_ultrafast_remove_offramp($data->uuid);
	}
}

function proc_ultrafast_save_connection() {
	// ini_set('display_errors', 1);
	global $_SERVER;
	global $log;
	global $errorMsg;
	global $errorProc;
	global $successMsg;
	global $extraProperties;

	require_once "/opt/ultrafast/inc/ultrafast.php";

	try {

		$log = init_logging();

		if(!isset($_POST['records'])) {
			$errorProc = true;
			$errorMsg = 'The "records" property is expected and was not sent.';
			$log->LogDebug('proc_ultrafast_save_connection: The "records" property is expected and was not sent.');
		}

		if(!$errorProc) {

			$data = json_decode($_POST['records'], true);

			$ultra = new UltraFast();

            if ($_POST['opcode'] === 'ultrafast_edit_connection') {
				$offramp = new UltraOfframp();
				$res = $ultra->GetOfframp($data['uuid']);
				$offramp->fromJsonString($res);
				$offramp->SetMaximumUpstream($data['max_upstream']);
				$offramp->SetMaximumDownstream($data['max_downstream']);
				$offramp->SetScheduleUuid($data['schedule_uuid']);
				$data = json_decode($ultra->EditOfframp($offramp->toJson()));
            }
            else {
                $data['onramp_uuid'] = $ultra->GetMyUuid();
                $dns = $data['host'];
                $userid = $data['admin_user'];
                $password = $data['admin_pass_verify'];
                $url = "https://$dns/buurst/snserver/snserv.php";

                $url_args = "?opcode=ultrafast_authorize_onramp";
                foreach ($data as $key => $val) {
                    $url_args.= "&{$key}={$val}";
                }

                $log->LogDebug("proc_ultrafast_save_connection: url: $url$url_args, userid: $userid, password: $password");
                $response = https_request("{$url}{$url_args}", $userid, $password);
                $resAuthOnramp = json_decode($response, true);
                $log->LogDebug($response);

				if (!$resAuthOnramp) {
					$errorProc = true;
					$errorMsg = $response;
					if (stripos($errorMsg, "Remote Login Failed. Invalid username/password") !== false) {
						$errorMsg.= " <br>- Verify if IP/DNS of target is correct";
						$errorMsg.= " <br>- Verify if username and password used to authenticate to the target are correct";
						$errorMsg.= " <br>- Open HTTPS/443 inbound port of target for the source to communicate";
					}
				}
				else if(!$resAuthOnramp['success']) {
					$errorProc = true;
					$errorMsg = $resAuthOnramp['msg'];
				}
				else {

					$skip_test = !isset($_POST['skip_port_test']) ? false : $_POST['skip_port_test'];
					if ($_POST['runSpeedTest'] === 'true' && (!$skip_test || $skip_test === 'false')) {
						$http_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null;
						$port_result = test_ultrafast_port($dns, $ultra->GetUdpPort(), $http_host, get_global_ip(), $userid, $password);
						if ($port_result !== true) {
							$port_result.= " Testing speed for TCP connection might fail. Continue speed test?";
							$errorProc = true;
							$errorMsg = "Warning: $port_result";
							$extraProperties['warning'] = true;
						}
					}

					if (!$errorProc) {
						$data['offramp_uuid'] = $resAuthOnramp['records']['offramp_uuid'];
						$data['uuid'] = $data['offramp_uuid'];
						$data['offramp_udpport'] = $resAuthOnramp['records']['offramp_udp_port'];
						$data['offramp_dns'] = $dns;
						$data_uuid = $data['onramp_uuid'];
						$data = proc_ultrafast_add_offramp($data);

						if($_POST['runSpeedTest'] == 'true') {
							$dataObj = $data[0];
							$ultra->StartSpeedTest($dataObj->uuid);
							touch('/var/www/softnas/config/.using_ultrafast');
						}
					}
				}
			}

			$extraProperties['needRestart'] = $ultra->IsRestartRequired();
		}
	}
	catch(Exception $err) {
		$errorProc = true;
		$errorMsg = $err->getMessage();
	}
	$successMsg = "Connection created.";
	return $data;
}

function test_ultrafast_port($target_ip, $port_to_check, $source_http_host, $source_global_ip, $userid = null, $password = null) {
	global $log;

	$log = init_logging();

	$url = "https://$target_ip/buurst/snserver/snserv.php";
	$ultra_ini = read_ini("ultrafast.ini");
	set_encryption_key();
	if ($userid !== null && $password !== null) {
		$ultra_ini["connection-$target_ip"] = array();
		$ultra_ini["connection-$target_ip"]["userid"] = quick_encrypt(ENCRYPTION_KEY, $userid);
		$ultra_ini["connection-$target_ip"]["password"] = quick_encrypt(ENCRYPTION_KEY, $password);
		write_ini($ultra_ini, "ultrafast.ini");
	} else {
		$userid = quick_decrypt(ENCRYPTION_KEY, $ultra_ini["connection-$target_ip"]["userid"]);
		$password = quick_decrypt(ENCRYPTION_KEY, $ultra_ini["connection-$target_ip"]["password"]);
	}

	$port_result = true;

	// open target port for testing with is_address_reachable() :
	$log->LogDebug("test_ultrafast_port: proc_iperf_port open: url: $url, userid: $userid, password: $password");
	$response = https_request("{$url}?opcode=iperf_port&port={$port_to_check}&action=open", $userid, $password);

	if (!is_address_reachable($target_ip, $port_to_check)) {
		$port_result = "proc_ultrafast_save_connection: Can not connect from {$source_global_ip}/{$source_http_host} to {$target_ip}:{$port_to_check}";
	} else {

		// open source port for testing with opcode=test_remote_address :
		proc_iperf_port($port_to_check, 'open');

		$log->LogDebug("proc_test_remote_address: url: $url, userid: $userid, password: $password");
		$response = https_request("{$url}?opcode=test_remote_address&remote_http_host={$source_http_host}&remote_global_ip={$source_global_ip}&remote_port={$port_to_check}", $userid, $password);
		$resTestRemote = json_decode($response);

		if ($resTestRemote) {
			if ($resTestRemote->success == false) {
				$port_result = "proc_test_remote_address: Can not connect from {$target_ip} to {$source_http_host}/{$source_global_ip}:{$port_to_check} ";
				$log->LogError($resTestRemote->msg);
			}
		} else {
			$log->LogError("proc_test_remote_address: Can't parse response from target!");
		}

		// closing source port :
		proc_iperf_port($port_to_check, 'close');
	}

	// closing target port :
	$log->LogDebug("proc_iperf_port close: url: $url, userid: $userid, password: $password");
	$response = https_request("{$url}?opcode=iperf_port&port={$port_to_check}&action=close", $userid, $password);

	return $port_result;
}

function proc_ultrafast_test_connection() {
	global $_SERVER;
	global $errorMsg;
	global $errorProc;
	global $extraProperties;
	require_once "/opt/ultrafast/inc/ultrafast.php";

	$speedTestStarted = false;
	try {
		$ultra = new UltraFast();

		$skip_test = !isset($_POST['skip_port_test']) ? false : $_POST['skip_port_test'];
		if (!$skip_test || $skip_test === 'false') {
			$offramp = new UltraOfframp();
			$res = $ultra->GetOfframp($_POST['uuid']);
			$offramp->fromJsonString($res);
			$http_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null;
                        $port_result = test_ultrafast_port($offramp->GetDns(), $ultra->GetUdpPort(), $http_host, get_global_ip());
			if ($port_result !== true) {
				$port_result.= " Testing speed for TCP connection might fail. Continue speed test?";
				$errorProc = true;
				$errorMsg = "Warning: $port_result";
				$extraProperties['warning'] = true;
			}
		}

		if (!$errorProc) {
			$ultra->StartSpeedTest($_POST['uuid']);
			$speedTestStarted = true;
		}
	}
	catch(Exception $err) {
		if (!$speedTestStarted && stripos($err->getMessage(), "Item not found") !== false) {
			global $log;
			$log->LogDebug("proc_ultrafast_test_connection: ".$err->getMessage());
			$ultra->StartSpeedTest($_POST['uuid']);
		} else {
			$errorProc = true;
			$errorMsg = $err->getMessage();
		}
	}
}

function proc_ultrafast_stop_test_connection() {
	global $errorMsg;
	global $errorProc;
	require_once "/opt/ultrafast/inc/ultrafast.php";

	try {
		$ultra = new UltraFast();
		$ultra->CancelSpeedTest($_POST['uuid']);
	}
	catch(Exception $err) {
		$errorProc = true;
		$errorMsg = $err->getMessage();
	}
}

function proc_ultrafast_connections() {
	global $errorMsg;
	global $errorProc;
	global $extraProperties;

	require_once "/opt/ultrafast/inc/ultrafast.php";
	$result = array();

	try {
		$ultra = new UltraFast();
		$connString = $ultra->GetConnections();
		$ultraList = new UltraList($connString);

		for ($i = 0;$i < $ultraList->size();$i++) {
			$connection = $ultraList->getAt($i);
			$connAttributes = $connection;
			$connAttributes['source_status'] = proc_ultrafast_get_channels($connAttributes['uuid']);
			$connAttributes['isEnabled'] = $connAttributes['conn_status'] === 1;
			array_push($result, $connAttributes);
		}

		$extraProperties['needRestart'] = $ultra->IsRestartRequired();
	}
	catch(Exception $err) {
		$errorProc = true;
		$errorMsg = $err->getMessage();
	}

	return $result;
}

function proc_ultrafast_delete_scheduling() {
	global $errorMsg;
	global $errorProc;
	global $successMsg;
	global $extraProperties;

	require_once "/opt/ultrafast/inc/ultrafast.php";
	$reply = array();

	try {

		if(!isset($_POST['records'])) {
			$errorProc = true;
			$errorMsg = 'The "records" property is expected and was not sent.';
			$log->LogDebug('proc_ultrafast_delete_scheduling: The "records" property is expected and was not sent.');
		}

		$data = json_decode($_POST['records']);

		$ultra = new UltraFast();
		$ultra->RemoveSchedule($data->uuid);

		$extraProperties['needRestart'] = $ultra->IsRestartRequired();
	}
	catch(Exception $err) {
		$errorProc = true;
		$errorMsg = $err->getMessage();
	}

	if(!$errorProc) {
		$successMsg = 'Bandwidth Scheduler removed.';
	}

	return $reply;
}

function proc_ultrafast_save_scheduling() {
	global $_CLEAN;
	global $errorMsg;
	global $errorProc;
	global $successMsg;
	global $extraProperties;

	// ini_set('display_errors', 1);
	require_once "/opt/ultrafast/inc/ultrafast.php";
	$isEdit = $_CLEAN['OP']['opcode'] === 'schedule-ch';

	try {

		if(!isset($_POST['records'])) {
			$errorProc = true;
			$errorMsg = 'The "records" property is expected and was not sent.';
			$log->LogDebug('proc_ultrafast_save_scheduling: The "records" property is expected and was not sent.');
		}

		$data = json_decode($_POST['records'], true);

		$ultra = new UltraFast();
		$schedule = new UltraSchedule();
		$schedule->fromJsonArray($data);

		if($isEdit) {
			$ultra->EditSchedule($schedule->toJson());
		}
		else {
			$schedule->SetUuid($ultra->AddSchedule($schedule->toJson()));
		}

		$extraProperties['needRestart'] = $ultra->IsRestartRequired();
	}
	catch(Exception $err) {
		$errorProc = true;
		$errorMsg = $err->getMessage();
	}

	if(!$errorProc) {
        $successMsg = $isEdit ? 'Bandwidth Scheduler updated.' : 'Bandwidth Scheduler added.';
    }
	return json_decode($schedule->toJson());
}

function proc_ultrafast_scheduling() {
	global $errorMsg;
	global $errorProc;
	global $extraProperties;

	require_once "/opt/ultrafast/inc/ultrafast.php";
	$result = array();

	try {
		$ultra = new UltraFast();
		$res_sched = $ultra->GetSchedules();
		$list = new UltraList($res_sched);

		for ($i = 0;$i < $list->size();$i++) {
			$sched = $list->getAt($i);
			array_push($result, $sched);
		}

		$extraProperties['needRestart'] = $ultra->IsRestartRequired();
	}
	catch(Exception $err) {
		$errorProc = true;
		$errorMsg = $err->getMessage();
	}

	return $result;
}

function proc_ultrafast_peak_speed() {
	// ini_set('display_errors', 1);
	global $extraProperties;

	require_once "/opt/ultrafast/inc/ultrafast.php";

	$timeRange = $_GET['timeRange'];
	$uuid = $_GET['connectionId'];

    $upload = 0;
    $download = 0;
    $lastWeekDownload = 0;
    $lastWeekUpload = 0;
    $lastDayDownload = 0;
    $lastDayUpload = 0;

    try {
        $ultra = new UltraFast();
        $data = json_decode($ultra->GetTunnelPerformances($uuid, 'MAX', "s5", 0, 1), true);
        $data = $data['data'];

        foreach ($data['bandwithtx'] as $timestamp => $value) {
            $upload = $value;
            $download = $data['bandwithrx'][$timestamp];
            break;
        }

        $data = json_decode($ultra->GetTunnelPerformances($uuid, 'MAX', "w1", 0, 1), true);
        $data = $data['data'];

        foreach ($data['bandwithtx'] as $timestamp => $value) {
            $lastWeekUpload = ($value == '-nan' ? 0 : $value);
            $lastWeekDownload = ($data['bandwithrx'][$timestamp] == '-nan' ? 0 : $data['bandwithrx'][$timestamp]);
        }

        $data = json_decode($ultra->GetTunnelPerformances($uuid, 'MAX', 'd1', 0, 1), true);
        $data = $data['data'];

        foreach ($data['bandwithtx'] as $timestamp => $value) {
            $lastDayUpload = ($value == '-nan' ? 0 : $value);
            $lastDayDownload = ($data['bandwithrx'][$timestamp] == '-nan' ? 0 : $data['bandwithrx'][$timestamp]);
        }

        $extraProperties['needRestart'] = $ultra->IsRestartRequired();
    }
    catch(Exception $err) {
        $errorProc = true;
        $errorMsg = $err->getMessage();
    }

	return array(
		array(
			'download' => ($download == '-nan') ? 0 : round($download, 2),
			'upload' => ($upload == '-nan') ? 0 : round($upload, 2),
			'last_week_download' => ($lastWeekDownload == '-nan') ? 0 : round($lastWeekDownload, 2),
			'last_week_upload' => ($lastWeekUpload == '-nan') ? 0 : round($lastWeekUpload, 2),
			'last_day_download' => ($lastDayDownload == '-nan') ? 0 : round($lastDayDownload, 2),
			'last_day_upload' => ($lastDayUpload == '-nan') ? 0 : round($lastDayUpload, 2)
		)
	);
}

function proc_ultrafast_performance() {
	// ini_set('display_errors', 1);
	global $extraProperties;

	require_once "/opt/ultrafast/inc/ultrafast.php";

    $timeRange = $_GET['timeRange'];
    $bar = "s5";
    $numBars = 12;
    $uuid = $_GET['connectionId'];
    $result = array();

    try {
        $ultra = new UltraFast();

        switch ($timeRange) {
            case 'hour':
                $bar = "m2";
                $numBars = 30;
                break;
            case 'day':
                $bar = "h1";
                $numBars = 24;
                break;
            case 'week':
                $bar = "h12";
                $numBars = 14;
                break;
            case 'month':
                $bar = "d1";
                $numBars = 30;
                break;
        }

        $data = json_decode($ultra->GetTunnelPerformances($uuid, 'AVERAGE', $bar, 0, $numBars), true);
        $data = $data['data'];
        $extraProperties['needRestart'] = $ultra->IsRestartRequired();
    }
    catch(Exception $err) {
        $errorProc = true;
        $errorMsg = $err->getMessage();
    }

    foreach ($data['confBwTx'] as $timestamp => $write) {

        $read = $data['confBwRx'][$timestamp];
        $upload = $data['avgpendwrite'][$timestamp];
        $rtTime = $data['millisrtt'][$timestamp];
        $retransmit_abs = $data['packetretrans'][$timestamp];
        $sent_abs = $data['packettx'][$timestamp];
        if ($sent_abs == 0){
            $retransmit = 0;
        } else {
            $retransmit = $retransmit_abs / $sent_abs;
        }

		array_push($result, array(
			'name' => $timestamp,
			'read' => ($read == '-nan') ? 0 : round($read, 2),
			'write' => ($write == '-nan') ? 0 : round($write, 2),
			'upload' => ($upload == '-nan') ? 0 : round($upload, 2),
			'rt_time' => ($rtTime == '-nan') ? 0 : round($rtTime, 2),
			'retransmit' => ($retransmit == '-nan') ? 0 : round($retransmit, 2)
		));
	}

	return $result;
}

function proc_ultrafast_restart() {
	global $extraProperties;

	require_once "/opt/ultrafast/inc/ultrafast.php";

	try {
		$ultra = new UltraFast();
		$extraProperties['needRestart'] = $ultra->IsRestartRequired();
	}
	catch(Exception $err) {
		$errorProc = true;
		$errorMsg = $err->getMessage();
	}

	$result = sudo_execute('systemctl restart ultrafast');

	if ($result['rv'] !== 0) {
		$errorProc = true;
		$errorMsg = 'Restart UltraFast Failed!';
	}
}

function proc_ultrafast_services() {
	global $errorMsg;
	global $errorProc;
	global $extraProperties;

	require_once "/opt/ultrafast/inc/ultrafast.php";
	$result = array();

	try {
		$ultra = new UltraFast();

		$res_serv = $ultra->GetServices();
		$list = new UltraList($res_serv);
		for ($i = 0;$i < $list->size();$i++) {
			$record = $list->getAt($i);
			$recAttributes = $record;

			$recAttributes['service_type'] = array('service_type' => $recAttributes['service_type'] === Enums::OBJECT ? 'dns' : 'cidr');

			array_push($result, $recAttributes);
		}

		$extraProperties['needRestart'] = $ultra->IsRestartRequired();
	}
	catch(Exception $err) {
		$errorProc = true;
		$errorMsg = $err->getMessage();
	}

	return $result;
}

function proc_ultrafast_delete_service() {
	global $errorMsg;
	global $errorProc;
	global $successMsg;
	global $extraProperties;

	require_once "/opt/ultrafast/inc/ultrafast.php";
	$reply = array();

	try {

		if(!isset($_POST['records'])) {
			$errorProc = true;
			$errorMsg = 'The "records" property is expected and was not sent.';
			$log->LogDebug('proc_ultrafast_delete_service: The "records" property is expected and was not sent.');
		}

		if(!$errorProc) {

			$data = json_decode($_POST['records'], true);

			$ultra = new UltraFast();
			$ultra->RemoveService($data['uuid']);

			$extraProperties['needRestart'] = $ultra->IsRestartRequired();
		}
	}
	catch(Exception $err) {
		$errorProc = true;
		$errorMsg = $err->getMessage();
	}

	if(!$errorProc) {
		$successMsg = 'Service removed.';
	}
	return $reply;
}

function proc_ultrafast_save_service() {
	global $errorMsg;
	global $errorProc;
	global $successMsg;
	global $extraProperties;

	require_once "/opt/ultrafast/inc/ultrafast.php";

	try {

		if(!isset($_POST['records'])) {
			$errorProc = true;
			$errorMsg = 'The "records" property is expected and was not sent.';
			$log->LogDebug('proc_ultrafast_delete_service: The "records" property is expected and was not sent.');
		}

		if(!$errorProc) {

			$data = json_decode($_POST['records'], true);

			$ultra = new UltraFast();
			$record = new UltraService();
			$dataTmpType = $data['service_type'];
			$data['service_type'] = $dataTmpType['service_type'] === 'dns' ? Enums::OBJECT : Enums::CIDR;

			$record->fromJsonArray($data);
			$serv_uuid = $ultra->AddService($record->toJson());

			$data['uuid'] = $serv_uuid;
			$data['service_type'] = $dataTmpType;

			$extraProperties['needRestart'] = $ultra->IsRestartRequired();
		}
	}
	catch(Exception $err) {
		$errorProc = true;
		$errorMsg = $err->getMessage();
	}

	if(!$errorProc) {
		$successMsg = 'Service added.';
	}
	return $data;
}

function proc_ultrafast_notifications() {
	global $errorMsg;
	global $errorProc;
	global $extraProperties;

	require_once "/opt/ultrafast/inc/ultrafast.php";
	$data = '';
	$resExecute = sudo_execute('systemctl --no-pager status ultrafast');
	$isRunning = $resExecute['rv'] === 0;

	$extraProperties['ultraIsRunning'] = $isRunning;

	try {
		$ultra = new UltraFast();
		$data = $ultra->PollNotifications($_POST['uuid'], (int) $_POST['timeout']);
	}
	catch(Exception $err) {
		$errorProc = true;
		$errorMsg = $err->getMessage();
	}

	return json_decode($data);
}

function proc_ultrafast_scheduling_timezone() {
	global $errorMsg;
	global $errorProc;
	global $extraProperties;

	require_once "/opt/ultrafast/inc/ultrafast.php";

	try {
		$ultra = new UltraFast();
		$data = $ultra->GetTimezone();
	}
	catch(Exception $err) {
		$errorProc = true;
		$errorMsg = $err->getMessage();
	}

	return $data;
}

function proc_ultrafast_manage_connection() {
	global $errorMsg;
	global $errorProc;
	global $successMsg;
	global $extraProperties;

	require_once "/opt/ultrafast/inc/ultrafast.php";

	try {
		$conn_uuid = isset($_POST['uuid']) ? $_POST['uuid'] : '';
		$disable = isset($_POST['disable']) ? (($_POST['disable'] === 'true') ? true : false) : false;

		$ultra = new UltraFast();
		$ultra->ManageConnection($conn_uuid, $disable);
		$extraProperties['needRestart'] = $ultra->IsRestartRequired();
	}
	catch(Exception $err) {
		$errorProc = true;
		$errorMsg = $err->getMessage();
	}

	if(!$errorProc) {
		$successMsg = 'Connection is ' . ($disable === true ? 'disabled.' : 'enabled.');
	}
}
