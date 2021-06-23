<?php
//
//  cmdproc_flexfiles.php - SoftNAS Server Command Processor for UltraFast Commands
//
//  Copyright (c) 2016 SoftNAS, LLC.  All Rights Reserved.
//
//
//
//

//require_once __DIR__.'/nifi.php';
//require_once __DIR__.'/curl.php';
require_once __DIR__.'/KLogger.php';
require_once __DIR__.'/utils.php';
require_once __DIR__.'/config.php';
require_once __DIR__.'/snasutils.php';
require_once __DIR__.'/cmdproc2.php';
require_once __DIR__.'/cmdprocessor.php';

function initConfig() {
    global $fileConfig;
    global $cmdPath;
    global $log;
    global $logPath;
    global $logPathFlexFiles;
    global $fileLogInitializeInstance;
    global $nifiCmdPhpFile;
    global $nifiPath;
    global $scriptsPath;
    global $_config;

    $fileConfig = __DIR__ . '/../config/flexfiles.json';
    $cmdPath = __DIR__ . '/../api/softnas-cmd';
    $logPath = __DIR__ . '/../logs/';
    $scriptsPath = __DIR__ . '/../scripts/';
    $logPathFlexFiles = "{$logPath}flexfiles/";
    $fileLogInitializeInstance = __DIR__ . '/../logs/tawscf.progress';
    $nifiCmdPhpFile = "{$_config['systemcmd']['php']} {$_config['proddir']}/snserver/nifi/nificmd.php";

    $nifiPath = get_nifi_home().'/';
}

function executeCmd($command, $asSudo = true, $rawOutput = false, $redirectOutput = true, $user = null) {
    $sudo = $asSudo ? "/usr/bin/sudo " : '';
    
    if($user) {
        $sudo .= "-u $user ";
    }

    if($rawOutput === false) {
        $cmd = "{$sudo}TERM=dumb $command";

        if($redirectOutput)  {
            $cmd .= ' 2>&1';
        }

        exec($cmd, $result, $returnValue);
        $result_str = implode(chr(10), $result);

        return array(
            'return_value' => $returnValue,
            'output_arr' => $result,
            'output_str' => $result_str
        );
    }
    else {
        $cmd = "{$sudo}$command";

        if($redirectOutput)  {
            $cmd .= ' 2>&1';
        }

        system($cmd, $returnValue);

        return $returnValue;
    }
}

function connect_remote($flow) {
    global $errorProc;
    global $errorMsg;
    global $cmdPath;
    global $scriptsPath;
    global $avoidMonit;
    global $flex_log;

    initConfig();

    $result = softnas_api_custom_command($flow->publicDns , "opcode=resetsessiontimer" , "https://{$flow->publicDns}/softnas");
    if ($result['success'] === true) {
        $result['return_value'] = 0;
    } else {
        if (stripos($result['errMsg'], "no response from remote host") !== false) {
            $errorProc = true;
            $errorMsg = "flexfiles connect_remote: no response from remote host";
            return;
        }
        if (!isset($flow->username) || !isset($flow->password) || !$flow->username || !$flow->password) {
            $errorProc = true;
            $errorMsg = "flexfiles connect_remote: username or password is missing";
            return;
        }
        $pwd_log = str_repeat("*", strlen($flow->password));
        $flex_log->LogInfo("connect_remote: not logged in yet - trying to login now ([$flow->username], [$pwd_log])");
        $result = executeCmd("$cmdPath login {$flow->username} '{$flow->password}' -s {$flow->publicDns} --base_url https://{$flow->publicDns}/softnas");
    }

    $success = $result['return_value'] === 0;

    if(!$success) {
        $errorProc = true;
        $errorMsg = json_decode($result['output_str']);
        $errorMsg = $errorMsg ? $errorMsg->err_msg : $result['output_str'];
        if (stripos($errorMsg, "An error has occured during login cmd") !== false) { // #5312 - cleaning unusable details
            $errorMsg = "An error has occured during login cmd";
            $avoidMonit = true;
        }
        return;
    }
}

function get_nifi_home() {
    global $errorProc;
    global $errorMsg;
    global $log;

    $log->LogDebug("Getting nifi home from /etc/init.d/nifi...");
    $result = sudo_execute("grep NIFI_HOME= /etc/init.d/nifi | awk -F '=' '{print $2}'");
    $nifi_dir = $result['output_str'];
    if ($result['rv'] !== 0 || !file_exists($nifi_dir) || !is_dir($nifi_dir) ) {
        $log->LogDebug("Getting nifi home...");
        // get path of nifi.properties:
        $info = get_nifi_process_info();
        if ($info['path'] === "") {
            $errorMsg = "Error while getting nifi home. Nifi status: ".$info["status"];
            $errorProc = true;
            return false;
        }
        $nifi_dir = $info['path'];

        $log->LogDebug("Found nifi home: $nifi_dir");
    }
    return $nifi_dir;

}

function get_nifi_process_info() {
    $result = sudo_execute("service nifi status");
    $info = array(
        "status" => "",
        "path" => ""
    );
    if (stripos($result['output_str'], 'nifi: unrecognized service') !== false) {
        $info["status"] = "not installed";
        return $info;
    }
    if (stripos($result['output_str'], 'NiFi is currently running') !== false) {
        $info["status"] = "running";
    }
    if (stripos($result['output_str'], 'NiFi is not running') !== false) {
        $info["status"] = "not running";
    }

    foreach ($result['output_arr'] as $i => $line) {
        if (stripos($line, 'NiFi home: ') !== false) {
            $info["path"] = str_ireplace('NiFi home: ', '', $line);
        }
    }
    return $info;
}

function get_conf_property($property) {
    $nifi_dir = get_nifi_home();
    $properties_path = "{$nifi_dir}/conf/nifi.properties";
	
	$result = sudo_execute("cat $properties_path | tr -d ' \t' | grep '^$property='");
	return str_replace("$property=", "", $result['output_str']);
}

function check_target_repositorylocation($flow) {
    global $errorProc;
    global $errorMsg;

    $success = false;
    connect_remote($flow);
    if($errorProc) {
        return array("success" => $success, "check_msg" => $errorMsg." <br/>");
    }
    
    $request_cmd = "opcode=flex_get_nificonfig";
    $result_request = softnas_api_custom_command($flow->publicDns, $request_cmd, "https://{$flow->publicDns}/softnas");
    $check_msg = "Failed to retrieve FlexFiles config of target node: ";
    if ($result_request['success'] === true) {
        $result_json = $result_request['result'];
        if ($result_json->success === true) {
            $check_msg = "";
            if ($result_json->records->repository->exists !== true) {
                $check_msg = "FlexFiles repository location of target node does not exists. <br/>";
            } else if ($result_json->records->repository->valid !== true) {
                $check_msg = "FlexFiles repository location of target node is not on a cloud-backed disk. <br/>";
            } else {
                $success = true;
            }
        } else {
            $check_msg .= $result_json->msg . ". <br/>";
        }
    } else {
        $check_msg .= $result_request['errMsg'] . ". <br/>";
    }
    return array("success" => $success, "check_msg" => $check_msg);
}

function proc_check_nificonfig() {
    global $errorProc;
    global $errorMsg;
    global $cmdPath;
    global $_config;
    global $scriptsPath;

    initConfig();

    $flow = getFlowById($_POST['flowId']);
    $publicDns = $flow->publicDns;
    
    if (is_local_address($publicDns)) {
        return;
    }
    
    // #4746
    $http_port = get_conf_property("nifi.web.https.port");  // default 8080
    $socket_port = get_conf_property("nifi.remote.input.socket.port"); //default 8081
    
    $verify_msg = "";
    
    // #6318 - Make sure target nifi repo is in a cloud-backed disk
    $check_result = check_target_repositorylocation($flow);
    if ($check_result['success'] !== true) {
        $verify_msg = $check_result['check_msg'];
    }

    if ($verify_msg === "") {
        $is_reachable_http = is_address_reachable($publicDns, $http_port);
        if (!$is_reachable_http) {
            $is_reachable_http = is_address_reachable($publicDns, $http_port, 15);
        }
        if (!$is_reachable_http) {
            $verify_msg .= "Can not connect to $publicDns:$http_port. </br>Please check firewall or network security group settings. </br>";
        }
        $is_reachable_socket = is_address_reachable($publicDns, $socket_port);
        if (!$is_reachable_socket) {
            $is_reachable_socket = is_address_reachable($publicDns, $socket_port, 15);
        }
        if (!$is_reachable_socket) {
            $verify_msg .= "Can not connect to $publicDns:$socket_port. </br>Please check firewall or network security group settings. </br>";
	}
	$cmd = "{$scriptsPath}nifi_tls_utils.sh --checkPending";
	$result = executeCmd($cmd);
	$success = $result['return_value'] === 0;
	if(!$success) {
	    $verify_msg .= "Error in local: " . $result['output_str'];
        } else {
	    $cmd = "/usr/bin/ssh root@$publicDns {$scriptsPath}nifi_tls_utils.sh --checkPending";
	    $result = executeCmd($cmd);
	    $success = $result['return_value'] === 0;
	    if(!$success) {
	         $verify_msg .= "Error in target: " . $result['output_str'];
	    }
	}
    }
    
    if ($verify_msg !== "") {
        $errorProc = true;
        $errorMsg = $verify_msg;
        return;
    }
}

function getConfigFileForLocalSettings() {
    $timer_config = array(
        "localPort" => get_conf_property("nifi.web.https.port"),
        "localPublicHost" => get_conf_property("nifi.remote.input.host")
    );
    $configFile = "/tmp/softnas_flexfiles_settings.json";
    file_put_contents($configFile, json_encode($timer_config));
    return $configFile;
}

function proc_get_nificonfig($configName = null) {
    global $errorProc;
    global $errorMsg;
    global $scriptsPath;
    global $nifiPath;
    global $nifiCmdPhpFile;

    initConfig();
    $config = array();
    $is_target = false;
    $is_source = false;

    if(!$configName || $configName === 'flexfiles') {
        $result = executeCmd("{$scriptsPath}getnificonf.sh");
        $success = $result['return_value'] === 0;
        if(!$success) {
            $errorProc = true;
            $errorMsg = $result['output_str'];

            return;
        }
        $values = array();
        foreach ($result['output_arr'] as $configValue) {
            $configValueArray = explode('=', $configValue);
            $values[$configValueArray[0]] = $configValueArray[1];
        }
        // check if there are existing flows/components
        $result = executeCmd("$nifiCmdPhpFile --getPgFlow --pgId=root");
        $success = $result['return_value'] === 0;
        if(!$success) {
            $errorProc = true;
            $errorMsg = $result['output_str'];
            return;
        }
        $rootPgFlows = json_decode($result['output_str']);
        $hasFlows = isset($rootPgFlows) && isset($rootPgFlows->flow) &&
            ((isset($rootPgFlows->flow->processGroups) && count($rootPgFlows->flow->processGroups) >= 1) || 
             (isset($rootPgFlows->flow->remoteProcessGroups) && count($rootPgFlows->flow->remoteProcessGroups) >= 1) ||
             (isset($rootPgFlows->flow->processors) && count($rootPgFlows->flow->processors) >= 1) ||
             (isset($rootPgFlows->flow->inputPorts) && count($rootPgFlows->flow->inputPorts) >= 1) ||
             (isset($rootPgFlows->flow->outputPorts) && count($rootPgFlows->flow->outputPorts) >= 1) ||
             (isset($rootPgFlows->flow->connections) && count($rootPgFlows->flow->connections) >= 1) ||
             (isset($rootPgFlows->flow->funnels) && count($rootPgFlows->flow->funnels) >= 1)
            );

        $config['flexfiles'] = array(
            'host' => $values['nifi.remote.input.host'],
            'ui_port' => $values['nifi.web.https.port'],
            'data_port' => $values['nifi.remote.input.socket.port'],
            'admin_username' => 'admin',
            'count_threads' => 10,
            'configured' => is_dir("{$nifiPath}ssl") && file_exists("{$nifiPath}ssl/keystore.jks") && file_exists("{$nifiPath}ssl/truststore.jks"),
            'readOnly' => false,
            'hasFlows' => $hasFlows,
            'is_source' => $is_source,
            'is_target' => $is_target
        );
        
        $use_ultra = false;
        $result = executeCmd("crudini --get {$nifiPath}conf/bootstrap.conf '' use.ultrafast 2>/dev/null");
	$success = $result['return_value'] === 0;
        if($success) {
            $use_ultra = $result['output_str'];
        }
        $config['flexfiles']['fuusion_use_ultrafast'] = $use_ultra;
        
        $config['flexfiles']['site_to_site_username'] = 'buurst';
        $config['flexfiles']['site_to_site_ui_port'] = $config['flexfiles']['ui_port'];
        $config['flexfiles']['site_to_site_data_port'] = $config['flexfiles']['data_port'];

        if($configName) {
            return $config['flexfiles'];
        }
    }

    if(!$configName || $configName === 'repository') {
        $location = get_nifi_home();
        $isValidLocation = proc_is_validlocationforrepository($location);

        $config['repository'] = array(
            'location' => $location,
            'valid' => $isValidLocation['valid'],
            'exists' => $isValidLocation['exists']
        );

        if($configName) {
            return $config['repository'];
        }
    }

    if(!$configName || $configName === 'runtime') {

        $configfile = getConfigFileForLocalSettings();
        $result = executeCmd("$nifiCmdPhpFile --get_maxtimer_threadcount");
        $success = $result['return_value'] === 0;

        if(!$success) {
            $errorProc = true;
            $errorMsg = $result['output_str'];
            return;
        }

        $config['runtime'] = array(
            'thread_count' => $result['output_str']
        );

        if($configName) {
            return $config['runtime'];
        }
    }

    return $config;
}

function proc_set_site_to_site_config() {
    global $errorProc;
    global $errorMsg;
    global $scriptsPath;
    global $flex_log;
    
    initConfig();
    
    
    $data = json_decode($_POST['data']);
    
    $data->publicDns = $data->host = $data->site_to_site_fuusion_node;
    $data->username = $data->user = $data->site_to_site_username;
    $data->admin_username = 'admin';
    $data->password = $data->pass = $data->site_to_site_password;
    $data->ui_port = $data->site_to_site_ui_port;
    $data->data_port = $data->site_to_site_data_port;
    
    $updateLog = '/tmp/exchange-certs.log';
    sudo_execute("echo 'Configuring Site-to-Site ($data->host)' >> $updateLog");
    
    $data->restartNifi = false;
    $nifi_config = proc_set_nificonfig($data, true);
    if($errorProc) {
        sudo_execute("echo >> $updateLog");
        return;
    }
    
    $data->restartNifi = true;
    
    $data->publicDns = $data->host;
    
    
    $result = proc_exchange_certificates($data);
    if($errorProc) {
        sudo_execute("echo >> $updateLog");
        return;
    }
    sudo_execute("echo >> $updateLog");
    return $nifi_config;
}

function proc_set_nificonfig($data = null, $asRemote = false) {
    global $errorProc;
    global $errorMsg;
    global $scriptsPath;
    global $flex_log;

    initConfig();

    $data = $data ? $data : json_decode($_POST['data']);
    $restartNifi = isset($data->restartNifi) && $data->restartNifi ? 'true' : 'false';
    $data = (object) array_map('trim',(array) $data);

    $updateLog = '/tmp/exchange-certs.log';
    if (isset($data->id)) {
        $updateLog = "/tmp/exchange-certs-{$data->id}.log";
    }

    if(is_numeric($data->ui_port) == false) {
        $errorProc = true;
        $errorMsg = "Invalid input: Web ui port must be numeric";
        return;   
    }

    if (is_numeric($data->data_port) == false) {
        $errorProc = true;
        $errorMsg = "Invalid input: Data port must be numeric";
        return;     
    }

    if($asRemote) {
        $cmd = "{$scriptsPath}nifi_tls_utils.sh --setupAuthRemote \
              --restartNifi={$restartNifi} \
              --remoteNode={$data->host} \
              --userName={$data->user} \
              --passWord='{$data->pass}' \
              --advertisedIP={$data->host} \
              --webUIPort={$data->ui_port} \
              --dataPort={$data->data_port} \
              --adminUser={$data->admin_username} \
              --updateLog={$updateLog}";
    }
    else {
        $cmd = "{$scriptsPath}nifi_tls_utils.sh --setupAuth \
              --restartNifi={$restartNifi} \
              --advertisedIP={$data->host} \
              --webUIPort={$data->ui_port} \
              --dataPort={$data->data_port} \
              --adminUser={$data->admin_username} \
              --updateLog={$updateLog}";
    }

    $result = executeCmd($cmd);
    $success = $result['return_value'] === 0;

    if(!$success) {
        $errorProc = true;
        $errorMsg = $result['output_str'];

        return;
    }
    
    if (isset($data->fuusion_use_ultrafast)) {
        if ($data->fuusion_use_ultrafast == true || $data->fuusion_use_ultrafast == 'true') {
            $useUltra = 'enable';
        } else {
            $useUltra = 'disable';
        }
        $cmd = "{$scriptsPath}enable_ultrafast_for_nifi.sh $useUltra";
        $flex_log->LogDebug("Executing command: $cmd");
        $result = executeCmd($cmd);
        $success = $result['return_value'] === 0;
        if(!$success && $result['output_str'] != "Nothing to do") {
            $errorProc = true;
            $errorMsg = $result['output_str'];
            return;
        }
    }

    //5660
    $cmd = "{$scriptsPath}config-generator-monit.sh";
    $result = executeCmd($cmd);
    $success = $result['return_value'] === 0;

    if(!$success) {
        $flex_log->LogWarn("Error in config-generator-monit: " .$result['output_str']);
    }
    $retval = proc_get_nificonfig('flexfiles');
    $flex_log->LogDebug("Done updating nifi config: " . json_encode($retval));
    return $retval;
}

function proc_set_repositoryconfig($data = null, $asRemote = false) {
    global $errorProc;
    global $errorMsg;
    global $scriptsPath;
    global $flex_log;
    global $log;

    initConfig();

    $repo_status_path = '/tmp/repo_status';
    sudo_execute("echo > $repo_status_path");
    
    $data = $data ? $data : json_decode($_POST['data']);
    $restartNifi = isset($data->restartNifi) && $data->restartNifi ? 'true' : 'false';
    $newHomeDir = isset($data->location) ? $data->location : "";

    sudo_execute("echo 'status:Migrating Nifi repository' >> $repo_status_path");
    $cmd = "{$scriptsPath}nifi_tls_utils.sh --migrateNifiHome --newHomeDir=$newHomeDir";
    $flex_log->LogDebug("proc_set_repositoryconfig: Executing command: $cmd");

    $result = executeCmd($cmd);
    $success = $result['return_value'] === 0;
    sudo_execute("echo > $repo_status_path");
    if(!$success) {
        $errorProc = true;
        $errorMsg = $result['output_str'];
        return;
    }

    return proc_get_nificonfig('repository');
}

function proc_get_repo_config_status() {
    global $errorProc;
    global $errorMsg;
    global $scriptsPath;
    global $flex_log;
    global $log;
    
    $repo_status_path = '/tmp/repo_status';
    $log_path = FLEXFILES_LOG_PATH;
    $result = sudo_execute("cat $repo_status_path | grep 'status:' | tail -1");
    $status = str_replace("status:", "", $result['output_str']);
    $percents = '';
    if ($status == 'Migrating Nifi repository') {
        //$currentNifiDir = get_nifi_home();
        $log_status_result = sudo_execute("tail -1 $log_path");
        
        $log_arr = explode(" --> ", $log_status_result['output_str']);
        if (count($log_arr) > 1) {
            $status = $log_arr[1];
        }
        
        if (stripos($status, 'Moving files from current home') !== false ||
            stripos($status, 'Cmd: rsync -tpogslr') !== false ) {
            $status = 'Moving files';
        }
        
    }
    
    if (!$status) {
        $status = "Configuring repository";
    }
    
    return array(
        'status' => $status,
        'percents' => $percents
    );
    
}

function proc_get_site_to_site_config_status() {
    global $errorProc;
    global $errorMsg;
    global $scriptsPath;
    global $flex_log;
    global $log;
    
    $status_path = '/tmp/exchange-certs.log';
    $log_path = FLEXFILES_LOG_PATH;
    $result = sudo_execute("cat $status_path | tail -1");
    $status = $result['output_str'];
        
    if (!$status) {
        $status = "Configuring Site-to-Site";
    }
    
    return array(
        'status' => $status
    );
    
}

function proc_set_runtimeconfig($data = null, $asRemote = false) {
    global $errorProc;
    global $errorMsg;
    global $scriptsPath;
    global $flex_log;
    global $nifiCmdPhpFile;

    initConfig();

    $data = $data ? $data : json_decode($_POST['data']);
    $restartNifi = isset($data->restartNifi) && $data->restartNifi ? 'true' : 'false';

    $thread_count = isset($data->thread_count) ? (int)$data->thread_count : false;
    if (!$thread_count) {
         $errorProc = true;
         $errorMsg = "Thread count not entered";
         return;
    }

    $result = executeCmd("nproc");
    if ($result['return_value'] !== 0) {
        $errorProc = true;
        $errorMsg = "Failed to get number of cpu cores";
        return;
    }
    # 14456 - Limit value to set
    $ideal_thread_count = ((int)$result['output_str']) * 10;
    if ($thread_count > $ideal_thread_count) {
        $errorProc = true;
        $errorMsg = "The maximum value that can be set is {$ideal_thread_count} (cpu cores x 10)";
        return;
    }

    $configfile = getConfigFileForLocalSettings();
    $cmd = "$nifiCmdPhpFile --set_maxtimer_threadcount=$thread_count";
    $result = executeCmd($cmd);
    $success = $result['return_value'] === 0;

    if (!$success) {
        $errorProc = true;
        $errorMsg = $result['output_str'];
        return;
    }

    return proc_get_nificonfig('runtime');
}

function proc_exchange_certificates($data = null) {
    global $errorProc;
    global $errorMsg;
    global $scriptsPath;
    global $avoidMonit;
    global $flex_log;

    initConfig();
    
    if (!$data) {
        $data = getFlowById($_POST['flowId']);
    }
    $restartNifi = isset($_POST['restartNifi']) && $_POST['restartNifi'] === 'true' ? 'true' : 'false';
    if (isset($data->restartNifi)) {
        $restartNifi = $data->restartNifi ? 'true' : 'false';
    }
    
    $updateLog = '/tmp/exchange-certs.log';
    if ($data->id) {
        $updateLog = "/tmp/exchange-certs-{$data->id}.log";
    }

    // end previous call if still running (#6041)
    $result = executeCmd("ps aux | grep '[n]ifi_tls_utils.sh --exchangeCerts ' | awk {'print $2'}");
    $flex_log->LogDebug("Previous called process (--exchangeCerts) ".$result['output_str']);
    if ($result['return_value'] === 0 && count($result['output_arr']) > 0) {
        $exchange_certs_pid = (int)($result['output_arr'][0]);
        $flex_log->LogDebug("Found previous: $exchange_certs_pid");
        if ($setup_auth_pid > 0) {
            $result = executeCmd("kill -9 $exchange_certs_pid");
            $flex_log->LogDebug("Previous called process ($exchange_certs_pid): ".$result['output_str']);
            if ($result['return_value'] === 0) {
                $flex_log->LogInfo("Previous called nifi_tls_utils.sh --exchangeCerts process ($exchange_certs_pid) ended.");
            }
        }
    }

    $result = executeCmd("{$scriptsPath}nifi_tls_utils.sh --exchangeCerts \
                          --restartNifi={$restartNifi} \
                          --remoteNode={$data->publicDns} \
                          --userName={$data->username} \
                          --passWord='{$data->password}' \
                          --updateLog={$updateLog}");

    $success = $result['return_value'] === 0;

    if(!$success) {
        $errorProc = true;
        $avoidMonit = true;
        $errorMsg = $result['output_str'];
        if (trim($errorMsg) == "") {
            global $logPath;
            $result_err = sudo_execute("tail -1 {$logPath}/flexfiles.log");
            $errorMsg = $result_err['output_str'];
        }
        return;
    }
}

function proc_get_exchange_certs_status() {
    global $errorProc;
    global $errorMsg;
    
    $flowId = $_POST['flowId'];
    $deletePreviousLog = $_POST['deletePreviousLog'] === 'true';
    $logFile = "/tmp/exchange-certs-{$flowId}.log";
    $log = '';
    
    if($deletePreviousLog && file_exists($logFile)) {
        sudo_execute("rm -f $logFile");
    }
    else if(is_file($logFile)) {
        $log = file_get_contents($logFile);
    }

    return array(
        'log' => $log
    );
}

function proc_is_validlocationforrepository($location = null) {
    $location = $location ? $location : $_POST['location'];

    $result = sudo_execute("[ -d {$location} ]");
    if ($result['rv'] !== 0) {
        return array(
            'valid' => false,
            'exists' => false
        );
    }

    return array(
        'valid' => true,
        'exists' => true
    );
}

function proc_check_nifiready() {
    global $scriptsPath;
    global $errorProc;
    global $errorMsg;
    global $successMsg;

    initConfig();

    $result = executeCmd("{$scriptsPath}nifi_tls_utils.sh --waitNifi");
    $success = $result['return_value'] === 0;
    if(!$success) {
        $errorProc = true;
        $errorMsg = $result['output_str'];

        return;
    }

    $successMsg = $result['output_str'];
}

function proc_check_nifitarget() {
    global $scriptsPath;
    global $errorProc;
    global $errorMsg;
    global $successMsg;

    initConfig();

    $flowId = $_POST['flowId'];
    $flow = getFlowById($flowId);

    if(!$flow->publicDns) {
        $errorProc = true;
        $errorMsg = 'proc_check_nifitarget: Failed - Missing publicDns';
        return;
    }

    $localNifiConfig = proc_get_nificonfig('flexfiles');
    $result = executeCmd("{$scriptsPath}nifi_tls_utils.sh --checkNifi \
                          --restartNifi=false \
                          --remoteNode={$flow->publicDns} \
                          --webUIPort={$localNifiConfig['ui_port']} \
                          --dataPort={$localNifiConfig['data_port']}");
    $success = $result['return_value'] === 0;
    if(!$success) {
        $errorProc = true;
        $errorMsg = $result['output_str'];

        return;
    }

    $successMsg = $result['output_str'];
}
