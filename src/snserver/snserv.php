<?php
//
//  snserv.php - SoftNAS Server
//
//  Copyright (c) SoftNAS Inc.  All Rights Reserved.
//
//
require_once ('KLogger.php');
require_once ('utils.php');
require_once ('logging.php');
require_once ('cmdprocessor.php');
require_once ('cmdproc2.php');
require_once ('cmdproc_ultra.php');
require_once ('cmdproc_flexfiles.php');
require_once ('config.php');
require_once ('snasutils.php');
require_once (__DIR__.'/../php-json/JSON.php');
require_once ('session_functions.php');
require_once ('segment_utils.php');

// #6435 - re-enable CORS
// header("Access-Control-Allow-Origin: *");  // send response to browser to allow cross-domain connections (for SnapReplicate to overcome CORS limits)


/*if(is_update_process_active()){
	header("Location: /buurst/storagecenter/");
}*/

$log = init_logging();
$flex_log = init_logging(FLEXFILES_LOG_PATH);
$log->LogDebug( "Buurst Fuusion(tm) request from " . $_SERVER['REMOTE_ADDR'] );

$pageTotal = 0;	  // global - total number of pages available (global variable, for paged responses only)
$successMsg = "";	// global - can be set to a message to be displayed upon success
$errorProc = false;  // global - set to true if error processing is to be invoked on response
$errorMsg = "";	  // global - set to error message text (if errorProc is true)
$isForm = false;	 // global - set to true iff processing a form response (returns "data" instead of "records")
$avoidMonit = false;  // global - set to true to make warning instead error in log, so monit will not send mail if MONITOR_SNSERV is enabled

$json = new Services_JSON(); // create a new instance of Services_JSON class

$response  = array(); // create response array
$extraProperties = array(); // extra properties to add in result json
$error	 = false;
$errorText = "Successful";

$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

$restCmd = "no-cmd";
if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
	$_CLEAN['OP'] = clean( $_POST );

	if(!isset($_CLEAN['OP']['opcode']) && isset($_REQUEST['opcode'])) {		
		$_CLEAN['OP']['opcode'] = $_REQUEST['opcode'];		
	}

	$str_json = file_get_contents( 'php://input' ); // read send by POST method as text
	$restCmd  = "create";
	$log->LogDebug( "POST request:" );

} //'POST' == $_SERVER['REQUEST_METHOD']
else if ( 'PUT' == $_SERVER['REQUEST_METHOD'] ) {
	$_CLEAN['OP'] = clean( $_PUT );
	$str_json = file_get_contents( 'php://input' ); // read send by POST method as text
	$restCmd  = "update";
	$log->LogDebug( "PUT request:" );
} //'PUT' == $_SERVER['REQUEST_METHOD']
else if ( 'GET' == $_SERVER['REQUEST_METHOD'] ) {
	$_CLEAN['OP'] = clean( $_GET );
	$str_json	  = $_CLEAN['OP']['opcode'];
	$restCmd	   = "get";
	if ( empty( $_CLEAN['OP'] ) )
	  $log->LogDebug( "No GET variables" );

	$log->LogDebug( "GET request:" . $str_json );
} //'GET' == $_SERVER['REQUEST_METHOD']
else if ( 'DELETE' == $_SERVER['REQUEST_METHOD'] ) {
	$str_json = file_get_contents( 'php://input' ); // read send by DELETE method as text
	$decode1 = json_decode ($str_json, 1);		  // decode into an array
	$_CLEAN['OP'] = clean( $decode1 );
	$restCmd = "delete";
	$log->LogDebug( "DELETE request:" );
} //'DELETE' == $_SERVER['REQUEST_METHOD']
else {
	$error	 = true;
	$errorText = "Invalid request method";
}

$decoded = $json->decode( $str_json ); // decode JSON string to PHP object

$log->LogDebug( "Received request:" );
$log->LogDebug("REQUEST_METHOD: " . $method );
$log->LogDebug( "REQUEST_URI: " . $uri );
$log->LogDebug( $str_json );
$log->LogDebug( "End request.");


if (!handle_license_feature_request($_CLEAN['OP']['opcode'])) {
	$error	 = true;
	$avoidMonit = true;
	$errorText = "Option not supported by this license!";
	$response['blocked_by_license'] = true;
	$response['blocked_opcpde'] = $_CLEAN['OP']['opcode'];
}

//
// Do some server-side work and return the response
//
$reply = array();
if ( !$error ) {
	$opcode = $_CLEAN['OP']['opcode'];
	
	if (stripos($opcode, "flex_") === 0) {
		check_platinum_features();
	}
	if (stripos($opcode, "ultrafast_") === 0) {
		check_platinum_features();
	}
	
	trackSnserverActivity();
	$log->LogDebug( "opcode: " . $opcode . "\n" );
	switch ( $opcode ) {
		case "overview": {
			$reply = proc_overview();
			break;
		}
		case "licenseactivate": {
			$reply = proc_licenseactivate();
			break;
		}
		case "licenseinfo": {
			$reply = proc_licenseinfo();
			break;
		}
		case "islicensedfeature": {
			$reply = proc_islicensedfeature();
			break;
		}
		case "islicensedfeatures":{
			$reply = proc_islicensedfeatures();
			break;
		}
		case "newlicense": {
			$reply = proc_newlicense();
			break;
		}
		case "internallicense": {
			$reply = proc_internallicense();
			break;
		}
		case "checkupdate": {
			$reply = proc_checkupdate();
			break;
		}
		case "checkupdate_homepage": {
			$reply = proc_checkupdate_homepage();
			break;
		}
		case "executeupdate": {
			$reply = proc_executeupdate();
			break;
		}
		case "statusupdate": {
			$reply = proc_statusupdate();
			break;
		}
		case "readini": {
			$reply = proc_getini();
			break;
		}
		case "ackagreement": {
			$reply = proc_ackagreement();
			break;
		}
		case "enableflexfiles": {
			$reply = proc_enableflexfiles();
			break;
		}
		case "resetsessiontimer": {
			$reply = proc_resetsessiontimer();
			break;
		}
		case "gettingstarted": {
			$reply = proc_gettingstarted();
			break;
		}
		case "product_registration": {
			$reply = proc_product_registration();
			break;
		}
		case "feature_request": {
			$reply = proc_feature_request();
			break;
		}
		case "registration_setnotshowagain": {
			$reply = proc_registration_setnotshowagain();
			break;
		}
		case "registration_exists": {
			$reply = proc_registration_exists();
			break;
		}
		case "applet_data": {
			$reply = proc_applet_data();
			break;
		}

		case 'ultrafast_scheduling_timezone':
			$reply = proc_ultrafast_scheduling_timezone();
			break;
		case 'ultrafast_notifications':
			$reply = proc_ultrafast_notifications();
			break;
		case 'ultrafast_restart':
			$reply = proc_ultrafast_restart();
			break;
		case 'ultrafast_rpc':
			$reply = proc_ultrafast_rpc();
			break;
		case 'ultrafast_authorize_onramp':
			$reply = proc_ultrafast_authorize_onramp();
			break;
		case 'ultrafast_get_channels':
			$reply = proc_ultrafast_get_channels($_POST['connection_uuid']);
			break;
		case 'ultrafast_get_authorized_onramps':
			$reply = proc_ultrafast_get_authorized_onramps();
			break;
		case 'ultrafast_add_offramp':
			$reply = proc_ultrafast_add_offramp();
			break;
		case 'ultrafast_remove_offramp':
			$reply = proc_ultrafast_remove_offramp();
			break;
		case 'ultrafast_remove_onramp':
			$reply = proc_ultrafast_remove_onramp();
			break;
		case 'ultrafast_get_configured_offramps':
			$reply = proc_ultrafast_get_configured_offramps();
			break;
		case 'ultrafast_get_uuid':
			$reply = proc_ultrafast_get_uuid();
			break;
		case 'ultrafast_target_config':
			$reply = proc_ultrafast_target_config();
			break;
		case 'ultrafast_save_target_config':
			$reply = proc_ultrafast_save_target_config();
			break;
		case 'ultrafast_connections':
		case 'connection-ls':
			$reply = proc_ultrafast_connections();
			break;
		case 'ultrafast_new_connection':
			$reply = proc_ultrafast_save_connection();
			break;
		case 'ultrafast_edit_connection':
			$reply = proc_ultrafast_save_connection();
			break;
		case 'connection-rm':
			$reply = proc_ultrafast_delete_connection();
			break;
		case 'service-ls':
			$reply = proc_ultrafast_services();
			break;
		case 'ultrafast_new_service':
			$reply = proc_ultrafast_save_service();
			break;
		case 'service-rm':
			$reply = proc_ultrafast_delete_service();
			break;
		case 'ultrafast_scheduling':
		case 'schedule-ls':
			$reply = proc_ultrafast_scheduling();
			break;
		case 'schedule-mk':
			$reply = proc_ultrafast_save_scheduling();
			break;
		case 'schedule-ch':
			$reply = proc_ultrafast_save_scheduling();
			break;
		case 'schedule-rm':
			$reply = proc_ultrafast_delete_scheduling();
			break;
		case 'ultrafast_performance':
			$reply = proc_ultrafast_performance();
			break;
		case 'ultrafast_peak_speed':
			$reply = proc_ultrafast_peak_speed();
			break;
		case 'ultrafast_test_connection':
			$reply = proc_ultrafast_test_connection();
			break;
		case 'ultrafast_stop_test_connection':
			$reply = proc_ultrafast_stop_test_connection();
			break;
		case 'ultrafast_manage_connection':
			$reply = proc_ultrafast_manage_connection();
			break;
		case 'iperf_port':
			$reply = proc_iperf_port();
			break;
		case "serverTest": {
			$reply = proc_serverTest();
			break;
		}

		case "general_settings": {
			$reply = proc_general_settings();
			break;
		}
		case "monit_settings": {
			$reply = proc_monit_settings();
			break;
		}
		case "kms_settings": {
			$reply = proc_kms_settings();
			break;
		}
		case "log_settings": {
			$reply = proc_log_settings();
			break;
		}
		case "get_update_log": {
			$reply = proc_get_update_log();
			break;
		}
		case "get_update_progress": {
			$reply = proc_get_update_progress();
			break;
		}
		case "support_settings": {
			$reply = proc_support_settings();
			break;
		}
		case "email_setup": {
			$reply = proc_email_setup();
			break;
		}
		case "remote_product_registration": {
			$reply = proc_remote_product_registration();
			break;
		}
		case "prodreg_inputs": {
			$reply = proc_prodreg_inputs();
			break;
		}
		case "save_license_settings": {
			$reply = proc_save_license_settings();
			break;
		}
		case "get_auth_default_frame": {
			$reply = proc_get_auth_default_frame();
			break;
		}
		case "log_js_error" : {
			$reply = proc_log_js_error();
			break;
		}
		case "restart" : {
			$reply = proc_restart();
			break;
		}
		case "loading_location" : {
			exit; // already done by trackSnserverActivity
		}

		case "log_js_errors" : {
			$reply = proc_log_js_errors();
			break;
		}
		
		// flexfilex commands
		case 'flex_check_nificonfig':
			$reply = proc_check_nificonfig();
			break;
		case 'flex_is_validlocationforrepository':
			$reply = proc_is_validlocationforrepository();
			break;
		case 'flex_get_nificonfig':
			$reply = proc_get_nificonfig();
			break;
		case 'flex_set_nificonfig':
			$reply = proc_set_nificonfig();
			break;
		case 'flex_set_repositoryconfig':
			$reply = proc_set_repositoryconfig();
			break;
		case 'flex_get_repo_config_status':
			$reply = proc_get_repo_config_status();
			break;
		case 'flex_set_site_to_site_config':
			$reply = proc_set_site_to_site_config();
			break;
		case 'flex_get_site_to_site_config_status':
			$reply = proc_get_site_to_site_config_status();
			break;
		case 'flex_set_runtimeconfig':
			$reply = proc_set_runtimeconfig();
			break;
		case 'flex_exchange_certificates':
			$reply = proc_exchange_certificates();
			break;
		case 'flex_get_exchange_certs_status':
			$reply = proc_get_exchange_certs_status();
			break;
		case 'flex_check_nifiready':
			$reply = proc_check_nifiready();
			break;
		case 'flex_check_nifitarget':
			$reply = proc_check_nifitarget();
			break;
		case "meterstatus" : {
			$reply = proc_meterstatus();
			break;
		}
		case "change_pwd_warning" : {
			$reply = proc_change_pwd_warning();
			break;
		}
		case 'test_remote_address':
			$reply = proc_test_remote_address();
			break;
		case "userpassword" : {
			$reply = proc_userpassword();
			break;
		}
		case "ssh_auth" : {
			$reply = proc_ssh_auth();
			break;
		}
		case "set_status_live_support":
			$reply = proc_set_status_live_support();
			break;
		case "get_live_support_info":
			$reply = proc_get_live_support_info();
			break;
		case "gold_support_welcome":
			$reply = proc_gold_support_welcome($method);
			break;
		case "submit_platinum_license":
			$reply = proc_submit_platinum_license();
			break;
		case "get_platinum_license":
			$reply = proc_get_platinum_license();
			break;
		
		default: {
			$func = "proc_$opcode";
			if (function_exists($func)) {
				$reply = $func();
			}
			else {
				$error	 = true;
				$errorText = "Invalid opcode: '" . $opcode . "' - ignored";
				break;
			}
		}
	} //$opcode
} //!$error

if( $errorProc ) {					  // there was an error during processing
	$error = true;
	$errorText = $errorMsg;			   // copy processor's error message text
}
if ( $error ) {
	$response['success'] = false;
	$response['msg']  = $errorText;
	if(substr($opcode, 0, 5) === "flex_") {
		$response['records'] = $reply;
		
		// change log file to flexfiles.log
		if ($avoidMonit === false) {
			$flex_log->LogError( "Error: " . $errorText );
		} else {
			$avoidMonit = false; // apply only one time
			$flex_log->LogWarn( "Error: " . $errorText );
		}	

	} else {
		if ($avoidMonit === false) {
			$log->LogError( "Error: " . $errorText );
		} else {
			$avoidMonit = false; // apply only one time
			$log->LogWarn( "Error: " . $errorText );
		}		
	}
} //$error
else {
	$response['success'] = true; // successful completion status
	$response['msg']  = $successMsg;
	if( $isForm )
		$response['data'] = $reply; // reply with a single record as "data"
	else
		$response['records'] = $reply; // reply with multiple records as "records"

	// if the reply contains no 'total' entry, create one
	if ( $pageTotal == 0 ) {					   // pageTotal override for total number of records "available" for paging in
		$response['total'] = @count( array_keys($reply) );	  // total records returned
	}
	else {
	  $response['total'] = $pageTotal;			// use the page total
	}
}

$response = array_merge($response, $extraProperties);

$encoded = $json->encode( $response ); // encode array $json to JSON string
echo( $encoded ); // send response back to client and end script execution

exit;
?>
