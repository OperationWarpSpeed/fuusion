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
    $errorMsg = "Usage data processor: License invalid failure - unable to continue. Details: " . $licenseInfo['errMsg'];
    $log->LogError($errorMsg);
    exit(1);
}
// If it's a trial, do not attempt auto-renewal
/*$isTrial = $licenseInfo['istrial'];
if ($isTrial) {
    $errorMsg = "Usage data processor: Nothing to do - skipping trial license.";
    $log->LogDebug($errorMsg);
    exit(1);
}*/
if ($licenseInfo['product-id'] == '4') {

    $regname = $licenseInfo['regname'];
    $license_key = $licenseInfo['currentkey'];
    $hwlock = $licenseInfo['hwlock'];
    $product_id = $licenseInfo['product-id'];
    $max_capacity_allowed = $licenseInfo['storage-capacity-GB'];
    $hwid = $licenseInfo['hardware_id'];

    if( $license_key == "" || $regname == "" || $hwid == "" || $hwlock == '')
    {
        $errorProc = true;                  // pass error back to client
        $error = true;
        $errorMsg = "Invalid usage report credentials!";
        $log->LogDebug( "Key:  $license_key,  Name: $regname, HWID: $hwid");
        $log->LogError($errorMsg);
        exit(1);
    }

    $file = generate_usage_data($product_id, $license_key, $hwid, $regname, $max_capacity_allowed);

    $response = get_remote_hash_key($license_key, $hwid, $regname, $hwlock);
    //print_r($response);
    if (!$response) {
        $errorMsg = "Invalid response received from softnas server!";
        echo $errorMsg;
        $log->LogError($errorMsg);
        exit(2);
    }
    if (!$response['success']) {
        
        $errorMsg = "Unable to get token from server!" . $response['msg'];
        echo $errorMsg;
        $log->LogError($errorMsg);
        exit(3);
        # code...
    }
    $key = $response['records']['key'];
    $reply = send_usage_data($license_key, $hwid, $regname, $hwlock, $file, $key);

    $log->LogDebug($reply);
    if (!$reply['success']) {
        $errorMsg = "Usage data processor: send usage data attempt failed. Details: " . $reply['msg'];
        $log->LogError($errorMsg);
        exit(4);
    }

}

function generate_usage_data($product_id, $license_key, $hwid, $regname, $max_capacity_allowed)
{
global $_config;
    if (!is_dir($_config['proddir'].'/logs/usage_reports/')) {
        exec("sudo mkdir -p {$_config['proddir']}/logs/usage_reports/");
    }
    $timestamp = date('YmdHis');
    $file = $_config['proddir'].'/logs/usage_reports/usage_report-'.$timestamp.'.json';
    $pools_list = snas_pool_list();
    $data = array(
        'product_id'            => $product_id,
        'hwid'                  => $hwid, 
        'license_key'           => $license_key,
        'regname'               => $regname,
        'max_capacity_allowed'  => $max_capacity_allowed,
        'usage_status'          => array(),
        'report_timestamp'      => date('Y-m-d H:i:s'),
        'timezone'              => date('T')

    );
    //print_r($pools_list);
    foreach ($pools_list as $key => $value) {
        $size = size_unformatted($value['size']);
        //print_r($size);
        $alloc = size_unformatted($value['alloc']);
        //print_r($alloc);
        $total_space = ceil(size_normalize($size[0]));
        //echo size_normalize($alloc[0]).PHP_EOL;
        $used_space = ceil(size_normalize($alloc[0]));
        $data['usage_status'][] = array(
            'pool_name' => $key,
            'total_space' => $total_space,
            'used_space' => $used_space, 
        );
    }

    $json = format_json(json_encode($data));
    
    $return = file_put_contents($file, $json);

    if ($return  === FALSE) {
        $tmpfile = tempnam('/tmp', 'softnas_');
        file_put_contents($tmpfile, $json);
        exec("sudo mv $tmpfile $file");
    }

    return $file;

}

function get_remote_hash_key($license_key, $hwid, $regname, $hwlock)
{
global $log;
    $postData = array(
        'license_key' => $license_key,
        'hwid' => $hwid,
        'regname' => $regname,
        'hwlock' => $hwlock
    );
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_URL, "https://www.softnas.com/apps/activation/softnas/usage_report.php");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    $response = curl_exec($ch);
    //close connection
    curl_close($ch);
    $log->LogDebug("JSON Response from activation processor");
    $log->LogDebug($response);
    if (strlen($response) == 0) {
        return false;
    }

    $reply = json_decode($response, true);
    $log->LogDebug("Decoded activation reply:");
    $log->LogDebug($reply);
    /*if (!$reply['success']) {
        return false;
    }*/

    return $reply;

}

function send_usage_data($license_key, $hwid, $regname, $hwlock, $file, $key) 
{
global $log;
    //$cfile = curl_file_create($file, 'application/json','usage_data.json');
    $postData = array(
        'license_key'   => $license_key,
        'hwid'          => $hwid,
        'regname'       => $regname,
        'hwlock'        => $hwlock,
        'key'           => $key,
        'file'          => "@$file"
    );
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_URL, "https://www.softnas.com/apps/activation/softnas/usage_report.php");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_HTTPHEADER,array('Content-Type: multipart/form-data'));
    curl_setopt($ch, CURLOPT_HEADER, 1);
    $response = curl_exec($ch);
    //echo $response.PHP_EOL;
    //close connection
    curl_close($ch);

    $log->LogDebug("JSON Response from activation processor");
    $log->LogDebug($response);
    if (strlen($response) == 0) {
        return false;
    }

    $reply = json_decode($response, true);
    $log->LogDebug("Decoded activation reply:");
    $log->LogDebug($reply);
    return $reply;

}

/**
 * Formats a JSON string for pretty printing
 *
 * @param string $json The JSON to make pretty
 * @param bool $html Insert nonbreaking spaces and <br />s for tabs and linebreaks
 * @return string The prettified output
 * @author Jay Roberts
 */
     function format_json($json, $html = false) {
    $tabcount = 0; 
    $result = ''; 
    $inquote = false; 
    $ignorenext = false; 
    if ($html) { 
        $tab = "&nbsp;&nbsp;&nbsp;"; 
        $newline = "<br/>"; 
    } else { 
        $tab = "\t"; 
        $newline = "\n"; 
    } 
    for($i = 0; $i < strlen($json); $i++) { 
        $char = $json[$i]; 
        if ($ignorenext) { 
            $result .= $char; 
            $ignorenext = false; 
        } else { 
            switch($char) { 
                case '{': 
                    $tabcount++; 
                    $result .= $char . $newline . str_repeat($tab, $tabcount); 
                    break; 
                case '}': 
                    $tabcount--; 
                    $result = trim($result) . $newline . str_repeat($tab, $tabcount) . $char; 
                    break; 
                case ',': 
                    $result .= $char . $newline . str_repeat($tab, $tabcount); 
                    break; 
                case '"': 
                    $inquote = !$inquote; 
                    $result .= $char; 
                    break; 
                case '\\': 
                    if ($inquote) $ignorenext = true; 
                    $result .= $char; 
                    break; 
                default: 
                    $result .= $char; 
            } 
        } 
    } 
    return $result; 
}
?>
