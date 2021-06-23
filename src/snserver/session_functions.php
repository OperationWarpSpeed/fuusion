<?php
//
//  session_functions.php - SoftNAS Session Management Library
//
//  Copyright (c) SoftNAS Inc.  All Rights Reserved.
//
require_once 'utils.php';
require_once 'common.php';
//require_once 'config.php' ;
include_once "KLogger.php";
include_once "logging.php";

//register_shutdown_function('shutdown');
//set_error_handler( "log_error" );
global $session_log;
try {
$session_log = init_logging(__DIR__."/../logs/session.log");
}
catch (Exception $e) {
	//error_log($e->getMessage() . ' 0.1');
	$session_log->LogError($e->getMessage() . ' 0.1');
}
function _open()
{
	return true;
}

function _close()
{
	return true;
}
function _read($id) {

	global $db;
	try {
		$p = $db->prepare("SELECT * FROM sessions WHERE guid = :id");
		if ($p !== FALSE) {
			$result = $p->execute(array(
				':id' => $id
			));
			if ($result) {
				if (!empty($p) && $p->rowCount() > 0) {
					$record = $p->fetchAll(PDO::FETCH_ASSOC);
					return $record[0]['session'];
				}
                else {
                    return '';
                }
			}
			else {
				file_put_contents(__DIR__."/../logs/session.log", date('Y-m-d H:i:s'). ' ' . $p->errorInfo(). PHP_EOL, FILE_APPEND);
			}
		}
		else {
			file_put_contents(__DIR__."/../logs/session.log", date('Y-m-d H:i:s'). ' ' . $db->errorInfo(). PHP_EOL, FILE_APPEND);
		}	
	} 
	catch (Exception $e) {
		//error_log($e->getMessage() . ' 1.1');
		file_put_contents(__DIR__."/../logs/session.log", date('Y-m-d H:i:s'). ' ' . $e->getMessage(). PHP_EOL, FILE_APPEND);
	}
}

function _write($id, $data) {   

	global $db;
	try {
		if (!is_object($db)) {
			// #2720 among others - PHP bug requires the DB be initialized directly within the _write function
			initializeDB();
		}
		$access = time();
	
		$p = $db->prepare("INSERT INTO sessions (`guid`, `date`, `session`) VALUES (:guid, :date, :session) ON DUPLICATE KEY UPDATE `guid`=VALUES(`guid`), `date`=VALUES(`date`), `session`=VALUES(`session`);");
		if ($p !== FALSE) {
			$result = $p->execute(array(':guid'=>$id, ':date'=>time(), ':session'=>$data));

			if ($result) {
				if (!empty($p) && $p->rowCount() > 0) {
					return true;
				} else {
					return false;
				}
			}
			else {
				file_put_contents(__DIR__."/../logs/session.log", date('Y-m-d H:i:s'). ' ' . $p->errorInfo() . PHP_EOL, FILE_APPEND);
			}
		}
		else {
			file_put_contents(__DIR__."/../logs/session.log", date('Y-m-d H:i:s'). ' ' . $db->errorInfo() . PHP_EOL, FILE_APPEND);
		}
	} catch (Exception $e) {
		file_put_contents(__DIR__."/../logs/session.log", date('Y-m-d H:i:s'). ' ' . $e->getMessage(), FILE_APPEND);
	}
}
function _destroy($id) {
	global $db;
	try {
		$p = $db->prepare("DELETE FROM sessions WHERE guid=:guid;");
		if ($p !== FALSE) {
			$result = $p->execute(array(
				':guid' => $id
			));
			if ($result) {
				if (!empty($p) && $p->rowCount() > 0) {
					return true;
				} else {
					return false;
				}
			}
			else {
				file_put_contents(__DIR__."/../logs/session.log", date('Y-m-d H:i:s'). ' ' . $p->errorInfo() . PHP_EOL, FILE_APPEND);
			}
		}
		else {
			file_put_contents(__DIR__."/../logs/session.log", date('Y-m-d H:i:s'). ' ' . $db->errorInfo() . PHP_EOL, FILE_APPEND);
		}
	} catch (Exception $e) {
		//error_log($e->getMessage() . ' 1.3');
		file_put_contents(__DIR__."/../logs/session.log", date('Y-m-d H:i:s'). ' ' . $e->getMessage(), FILE_APPEND);
	}
}
function _clean($max) {
	global $db;
	$old = time() - $max;
	try {
		$p = $db->prepare("DELETE FROM sessions WHERE date<:test;");
		if ($p !== FALSE) {
			$result = $p->execute(array(
				':test' => $old
			));
			if ($result) {
				if (!empty($p) && $p->rowCount() > 0) {
					return true;
				} else {
					return false;
				}
			}
			else {
				file_put_contents(__DIR__."/../logs/session.log", date('Y-m-d H:i:s'). ' ' . $p->errorInfo() . PHP_EOL, FILE_APPEND);
			}
		}
		else {
			file_put_contents(__DIR__."/../logs/session.log", date('Y-m-d H:i:s'). ' ' . $db->errorInfo() . PHP_EOL, FILE_APPEND);
		}
	} catch (Exception $e) {
		file_put_contents(__DIR__."/../logs/session.log", date('Y-m-d H:i:s'). ' ' . $e->getMessage(), FILE_APPEND);
	}
}
try {
	$is_update_request = is_update_request();
	require_once 'database.php'; // mysql support
	session_set_save_handler('_open', '_close', '_read', '_write', '_destroy', '_clean');
	if (php_sapi_name() != 'cli') {
		session_name('PHPSESSID_port'.$_SERVER['SERVER_PORT']);
		session_start();
	}
}
catch (Exception $e) {
	//error_log($e->getMessage() . ' 0.2');
	$session_log->LogError($e->getMessage() . ' 0.2');
}




// Generates a "form key" pair, which is a random MD5 hash used to encrypt the remote address of the user (to prevent brute force attacks not using our form on this server)
function generateFormKey($remote_ip = "") {
	global $session_log;
	try {
		//Get the IP-address of the user
		// user IP-based hashes #2018 (2015-08 kashpande)
		$login_ini = read_ini('login.ini');
		$login = $login_ini['login'];
		if (!isset($login['ipHash']) || (isset($login['ipHash']) && $login['ipHash'] == 1)) {
			$ip = $_SERVER['REMOTE_ADDR'];
		} elseif (isset($login['ipHash']) && $login['ipHash'] < 1) {
			$ip = '';
		}
		if ($remote_ip != "") // override with different IP target
		$ip = $remote_ip;
		//We use mt_rand() instead of rand() because it is better for generating random numbers.
		//We use 'true' to get a longer string.
		//See http://www.php.net/mt_rand for a precise description of the function and more examples.
		$uniqid = uniqid(mt_rand() , true);
		$hash = md5($ip . $uniqid);
		include_once 'encrypt.php';
		$encrypt = new Encrypt();
		$encrypt->set_key($hash);
		$cryptIP = $encrypt->encode($ip); // encrypt the IP address using the hash code
		//Return the hash + encrypted IP address pair
		return "$hash:$cryptIP";
	}
	catch (Exception $e) {
		//error_log($e->getMessage() . ' 1.5');
		$session_log->LogError($e->getMessage() . ' 1.5');
	}
}
function load_config($file_name) {
	global $session_log;
	try {
		$ini = read_ini($file_name, dirname(dirname(__FILE__)) . '/config/'); // reads the config file into PHP associative array
		if (isset($_SESSION['update_in_progress'])) {
			$ini['login']['timeout'] = 1000000;
		}
		if (!defined('SESSION_TIMEOUT')) define('SESSION_TIMEOUT', intVal($ini['login']['timeout']) * 60);
		if (!defined('SESSION_FOLDER')) define('SESSION_FOLDER', $ini['login']['session_folder']);
		set_encryption_key(); //if ( ! defined('ENCRYPTION_KEY')) define('ENCRYPTION_KEY',$ini['login']['encryption_key'] . 'S0ftNa5ky' );
		if (!is_dir(SESSION_FOLDER)) {
			mkdir(SESSION_FOLDER);
		}
	}
	catch (Exception $e) {
		//error_log($e->getMessage() . ' 1.6');
		$session_log->LogError($e->getMessage() . ' 1.6');
	}
}
function site_url($path = '', $username = '', $password = '') {
	global $session_log;
	try {
		$auth = '';
		if ($username) {
			$auth = $username;
			if ($password) {
				$auth.= ':' . $password;
			}
			$auth.= '@';
		}
		$base_url = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' ? 'https' : 'http';
		$base_url.= '://' . $auth . $_SERVER['HTTP_HOST'];
		$base_url.= str_replace(basename($_SERVER['SCRIPT_NAME']) , '', $_SERVER['SCRIPT_NAME']);
		$base_url.= $path;
	}
	catch (Exception $e) {
		//error_log($e->getMessage() . ' 1.7');
		$session_log->LogError($e->getMessage() . ' 1.7');
	}
}
function login($username, $password) {
	global $is_update_request, $session_log;
	try {
		$session_log->LogDebug("Begin process login request");
		$login_ini = read_ini('login.ini');
		$login = $login_ini['login'];
		// First, ensure the request came from our form on this server, not somewhere else...
		include_once 'encrypt.php';
		$CLEAN = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
		$form_key = isset($CLEAN['form_key']) ? $CLEAN['form_key'] : '';
		$chunks = explode(":", $form_key);

		$key = isset($chunks[0]) ? $chunks[0] : '';
		$ciphertext = isset($chunks[1]) ? $chunks[1] : '';
		$decrypt = new Encrypt();
		$decrypt->set_key($key);
		$checkIP = $decrypt->decode($ciphertext); // decrypt the user's remote IP address
		if (!isset($login['ipHash']) || $login['ipHash'] == 1) {
			$session_log->LogDebug("Using IP based hash algorithm");
			$ip = $_SERVER['REMOTE_ADDR']; // current user's IP
		} elseif (isset($login['ipHash'])) {
			$ip = '';
		}
		//
		// ***** The following was disabled on 12-5-2013 in order to ship 2.0.0. SnapReplicate was not compatible
		//       with this form check, and we ran out of time to resolve this issue for the release.
		//       THIS NEEDS TO BE RE-ENABLED TO PREVENT HACKER ATTACKS AT NEXT AVAILABLE OPPORTUNITY.  rgb
		// *****
		// Ensure the post request came from our form, not a foreign site or attack bot
		//    if(!isset($_COOKIE['KEY_SS_port'.$_SERVER['SERVER_PORT']]) ||          // cookie must be set
		//       $form_key != $_COOKIE['KEY_SS_port'.$_SERVER['SERVER_PORT']] ||     // cookie and form values must match
		//       $checkIP != $ip)                       // original form encrypted IP must match this user's IP
		//    {
		//        sleep(10);       // delay hackers attempting brute force attacks
		//        return false;
		//    }
		$safe_password = escapeshellarg($password); // addcslashes ( $password, "&");
		$safe_username = escapeshellarg($username);
		$result_change = "";
		$rv_change = "";
		$session_log->LogDebug("login: Checking if password is expired.");
		exec("sudo chage -l $safe_username  2>&1", $result_change, $rv_change);
		$session_log->LogDebug("login: password expiration check output: $rv_change: " . var_export($result_change, true));
		if (stripos($result_change[0], 'password must be changed') !== false) {
			$session_log->LogDebug("User must change password");
			$rv = "";
			// check if can login:
			$result = "";
			exec("sudo groups '$safe_username' | grep softnas 2>&1", $result, $rv);
			if (!$result[0] || $result[0] == "") {
				header("Location: login.php");
				exit;
			}
			// get user data from shadow:
			$result = "";
			exec("sudo cat /etc/shadow | grep '$safe_username:' 2>&1", $result, $rv);
			// get encr. type and salt: user:$type$salt$...
			$pwd_data = explode('$', $result[0]);
			// make hash and compare it to $safe_password :
			$cmd = "python -c 'import crypt; " . "print crypt.crypt(\"$safe_password\", \"$" . $pwd_data[1] . "$" . $pwd_data[2] . "\")'";
			$result_pwd = "";
			exec("sudo $cmd 2>&1", $result_pwd, $rv);
			if (strpos($result[0], $result_pwd[0]) !== false) {
				echo "GOOD PASSWORD \n\n";
				$user = isset($CLEAN['username']) ? $CLEAN['username'] : $username;
				$_SESSION['USERNAME'] = $user;
				db_session("forcepwd", true, null, 0, false); // login.php -> change password
				db_session("fpwduser", $safe_username, null, 0, false);
				$session_log->LogDebug("DB result 1: ".var_export(db_session("forcepwd", null, 0, false), true));
				$session_log->LogDebug("DB result 1: ".var_export(db_session("fpwduser", null, 0, false), true));
			}
			// or just login.php normal
			$session_log->LogDebug("Redirect user to login (1)");
			header("Location: login.php");
			exit;
		}
		// this is a legitimate request from our local server's form
		$res = array('result', 'rv');
		$session_log->LogDebug("login: Check the password.");
		exec(dirname(dirname(__FILE__)) . "/scripts/login.sh $safe_username $safe_password 2>&1", $res['result'], $res['rv']);
		$session_log->LogDebug("login: password check result: " . var_export($res, true));
		if ($res['rv'] == 0) {
			$session_log->LogDebug("User logged in");
			$_SESSION['LOGGED_IN'] = 1;
			$user = isset($CLEAN['username']) ? $CLEAN['username'] : $username;
			$_SESSION['USERNAME'] = $user;
			$encrypt = new Encrypt();
			$encrypt->set_key(ENCRYPTION_KEY);
			$_SESSION['HASH_VAR'] = $encrypt->encode($CLEAN['password']);
			// Prevent hijack
			if (!isset($login['ipHash']) || (isset($login['ipHash']) && $login['ipHash'] == 1)) {
				$_SESSION['HASH'] = md5($_SERVER['HTTP_USER_AGENT'] . $username . $_SERVER['REMOTE_ADDR'] . ENCRYPTION_KEY);
			} elseif (isset($login['ipHash']) && $login['ipHash'] == 0) {
				$_SESSION['HASH'] = md5($_SERVER['HTTP_USER_AGENT'] . $username . ENCRYPTION_KEY);
			}
			// session time
			$_SESSION['LAST_ACTIVITY'] = time();
			$file = md5(rand() . $_SESSION['USERNAME']);
			create_temp_file($file);

			$cookieSecure = canSetCookieSecureFlag();

			setcookie('USER_SS_port'.$_SERVER['SERVER_PORT'],$file, time() + SESSION_TIMEOUT,"/", '', $cookieSecure, false); // 31.07.2015 - revert #1719
			//setcookie('USER_SS_port'.$_SERVER['SERVER_PORT'], $file, 0, "/"); // #1719 - remove the code that controls timeout
			// The USER_SESSION cookie is an ephemeral cookie only used for detecting when browser exits and comes back (because USER_SS expires based on time)
			// When the browser exits, the ephemeral USER_SESSION cookie gets deleted by the browser and will not be present on next access attempt, forcing a login
			// NOTE:  Chrome browser has feature "Continue where I left off", which overrides normal session cookie behavior (session cookies persist across browser closing/opening)
			$dummyKey = generateFormKey(); // this key is unused at this point, as added complexity for hackers to marvel over :-)
			setcookie('USER_SESSION_port'.$_SERVER['SERVER_PORT'], $dummyKey, 0, "/", '', $cookieSecure, false); // this cookie is truly a session-cookie - expires when the browser exits
	
			$session_log->LogInfo("SESSION: ".print_r($_SESSION, true));
			session_commit();
			update_login_time_spent(true);
			return true;
		} else {
			$session_log->LogDebug("login: Password check failed, result: " . var_export($res, true));
			return false;
		}
	}
	catch (Exception $e) {
		//error_log($e->getMessage() . ' 1.8');
		$session_log->LogError($e->getMessage() . ' 1.8');
	}
}
function webmin_url($path) {
	global $session_log;
	try {
		include_once 'encrypt.php';
		$encrypt = new Encrypt();
		$encrypt->set_key(ENCRYPTION_KEY);
		$password = $encrypt->decode($_SESSION['HASH_VAR']);
		$prefix = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' ? 'https' : 'http';
		if ($path == "") $path = "/"; // for root webmin access to /webmin/
		//		return "https://{$_SESSION['USERNAME']}:{$password}@{$_SERVER['HTTP_HOST']}/webmin" . $path;  // don't pass Basic auth in clear text!
		return $prefix."://{$_SERVER['HTTP_HOST']}/webmin" . $path;
	}
	catch (Exception $e) {
		//error_log($e->getMessage() . ' 1.9');
		$session_log->LogError($e->getMessage() . ' 1.9');
	}
}
function check_logged_in() {
	global $is_update_request, $session_log;
	try {
		$session_log->LogDebug("check_logged_in: ".print_r($_SESSION, true));
		// #1719 - keep cookie until end of session, or longer if now is update
		// it sets back to 0 when update is finished.
		if($is_update_request){
			$session_log->LogInfo("Extend session because update is in progress!");
			$session_log->LogDebug('$_SESSION variable: '.var_export($_SESSION, true));
			$session_timeout = 18600;
		}
		$login_ini = read_ini('login.ini');
		$login = $login_ini['login'];
		if (isset($_SESSION['LOGGED_IN']) && // session shows logged in
		(time() - $_SESSION['LAST_ACTIVITY'] < SESSION_TIMEOUT) and     // last activity within the timeout period  // #1719 - remove the code that controls timeout // 31.07.2015 - revert #1719
		isset($_COOKIE['USER_SS_port'.$_SERVER['SERVER_PORT']]) and file_exists(SESSION_FOLDER . '/' . $_COOKIE['USER_SS_port'.$_SERVER['SERVER_PORT']]) && // user session cookie and session file exist
		isset($_COOKIE['USER_SESSION_port'.$_SERVER['SERVER_PORT']])) // ephemeral session cookie is present (browser has not been closed)
		{
			// Prevent hijack
			if (
				// option one: use ip-based hash by default
				($_SESSION['HASH'] == md5($_SERVER['HTTP_USER_AGENT'] . $_SESSION['USERNAME'] . $_SERVER['REMOTE_ADDR'] . ENCRYPTION_KEY) || (!isset($login['ipHash']) || (isset($login['ipHash']) && $login['ipHash'] == 1)))
				|| // OR
				// option two: explicitly disable ip-based hash
				($_SESSION['HASH'] == md5($_SERVER['HTTP_USER_AGENT'] . $_SESSION['USERNAME'] . ENCRYPTION_KEY) && (!isset($login['ipHash']) || $login['ipHash'] == 0))
			) {
				// Prevent hijack
				//session_regenerate_id(true);
				// session time
				$_SESSION['LAST_ACTIVITY'] = time();
				// recreate file
				create_temp_file($_COOKIE['USER_SS_port'.$_SERVER['SERVER_PORT']]);

                setcookie('USER_SS_port'.$_SERVER['SERVER_PORT'], $_COOKIE['USER_SS_port'.$_SERVER['SERVER_PORT']], time() + SESSION_TIMEOUT,"/", '', canSetCookieSecureFlag(), false); // 31.07.2015 - revert #1719


                session_commit();
                update_login_time_spent();
				return true;
			}
		}
		if (isset($_SESSION['LOGGED_IN'])) {
			$session_log->LogDebug("SESSION LOGGED_IN WAS SET");
		} else {
			$session_log->LogDebug("SESSION LOGGED_IN WAS NOT SET");
		}

		if (isset($_SESSION['LAST_ACTIVITY']) && time() - $_SESSION['LAST_ACTIVITY'] < SESSION_TIMEOUT) {
			$session_log->LogDebug("SESSION IS FRESH - NO REASON TO SUSPECT ANYTHING");
		} else {
			$session_log->LogDebug("SESSION IS STALE");
		}

		if (isset($_COOKIE['USER_SS_port'.$_SERVER['SERVER_PORT']])) {
			$session_log->LogDebug("USER_SS_port".$_SERVER['SERVER_PORT']." COOKIE IS SET");
		} else {
			$session_log->LogDebug("USER_SS_port".$_SERVER['SERVER_PORT']." COOKIE IS NOT SET");
		}

		if (isset($_COOKIE['USER_SS_port'.$_SERVER['SERVER_PORT']]) && file_exists(SESSION_FOLDER.'/'.$_COOKIE['USER_SS_port'.$_SERVER['SERVER_PORT']])) {
			$session_log->LogDebug("SESSION FOLDER FILE EXISTS");
		} else {
			$session_log->LogDebug("USER_SS_port SESSION COOKIE OR FOLDER FILE DOES NOT EXIST");
		}

		if (isset($_COOKIE['USER_SESSION_port'.$_SERVER['SERVER_PORT']])) {
			$session_log->LogDebug("COOKIE USER_SESSION_port".$_SERVER['SERVER_PORT']." EXISTS");
		} else {
			$session_log->LogDebug("COOKIE USER_SESSION_port".$_SERVER['SERVER_PORT']." DOESNT EXIST:");
			$session_log->LogDebug(var_export($_COOKIE, true));
		}
		logout();
		return false;
	}
	catch (Exception $e) {
		//error_log($e->getMessage() . ' 1.10');
		$session_log->LogError($e->getMessage() . ' 1.10');
	}
}
function check_dual_auth_logged_in() {
	/*if(isset($_SESSION['dual_auth_status'])){
			return;
		}*/
	global $session_log;
	try {
		$settings = read_ini("general_settings.ini");
		if (!$settings || !isset($settings['authentication']) || !isset($settings['authentication']['auth_type'])) {
			return "OK";
		}
		// This is workaround added to skip dual auth login for snapreplicate verification operation
		if (isset($_REQUEST['hash']) && isset($_REQUEST['username']) && isset($_REQUEST['password'])) {
			$chuncks = explode(':',$_REQUEST['hash']);
			if (count($chuncks) == 2) {
				$salt = 'P@ss4W0rd$';
				$pass = $chuncks[0];
				$hash_received = $chuncks[1];
				$operation = quick_decrypt($pass.$salt,$hash_received);
				if ($operation == 'snapverify') return "OK";
			}
		}
		$auth = $settings['authentication'];
		$auth_type = $auth['auth_type'];
		/*global $_config;
			if(!$_config['url_auth_google']){
				// fixit log error...
				return "OK";
			}
			//$url_auth_google = $_config['url_auth_google'];
		*/
		// fixit config, reguire -> reguire_once, common.php, ...
		//$url_auth_google = "https://softnas.com/auth/test.php";
		//$url_auth_facebook = "https://softnas.com/auth/testfb.php";
		$url_auth = array(
			//"google" => $_config['url_auth_google'],
			"google" => "https://softnas.com/auth/test.php",
			"facebook" => "https://softnas.com/auth/testfb.php"
		);
		if ($auth_type == "not_using") {
			return "OK";
		}
		$titles = array(
			"google" => "Google",
			"facebook" => "Facebook"
		);
		if ($auth_type == "google" || $auth_type == "facebook") {
			$url_auth_address = $url_auth[$auth_type];
			$type_title = $titles[$auth_type];
			if (isset($_REQUEST["response_$auth_type"])) {
				/*if($_SERVER["HTTP_REFERER"] != $url_auth_address){
						echo "<pre>";
						
						return $_SERVER["HTTP_REFERER"]." : Access denied";
					}*/
				if ($_REQUEST['scope'] == "") { // email
					return "Not logged in to $type_title. <br/>
							<a href='" . $_REQUEST['auth_url'] . "' target='_blank'>Manage $type_title Account</a>";
					//header("Location: login.php");
					
				}
				$user_id_hash = "11" . sha1($auth['auth_user'] . $_SERVER['HTTP_HOST'] . $auth['auth_user']);
				if ($_REQUEST['scope'] == $user_id_hash) {
					//if($_REQUEST['email'] == $auth['auth_user']){
					/*if($_REQUEST['newrefr']){
							$auth['google_refresh_token'] = $_REQUEST['newrefr'];
							$settings['authentication'] = $auth;
							write_ini($settings);
						}*/
					return "OK";
					//header("Location: login.php");
					
				} else {
					return "Wrong $type_title account is active in web browser or connected to this SoftNAS instance. <br/>
							<a href='" . $_REQUEST['auth_url'] . "' target='_blank'>Manage $type_title Account</a>
							<br/>
							<a href='https://" . $_SERVER['HTTP_HOST'] . "'>Try again</a> after setting your $type_title account";
					//header("Location: login.php");
					
				}
			} else {
				//header("LOCATION: $google_auth_url?try_to_login&refr=".$auth['google_refresh_token']);
				header("LOCATION: $url_auth_address" . "?try_to_login=" . $_SERVER['HTTP_HOST']);
			}
		}
	}
	catch (Exception $e) {
		//error_log($e->getMessage() . ' 1.11');
		$session_log->LogError($e->getMessage() . ' 1.11');
	}
}
function check_captcha_response() {
	global $session_log;
	try {
		if (isset($_REQUEST["response_captcha"])) {
			if (isset($_REQUEST["snaskey"])) {
				if (sha1($_SERVER['HTTP_HOST'] . "3_") == $_REQUEST["snaskey"]) {
					set_captcha_fail_count(0);
					exit("Verified!");
				}
			}
		}
	}
	catch (Exception $e) {
		//error_log($e->getMessage() . ' 1.12');
		$session_log->LogError($e->getMessage() . ' 1.12');
	}
}
function set_captcha_fail_count($fails = 0) {
	global $session_log;
	try {
		if ($fails !== null) {
			file_put_contents("/tmp/fail_count", $fails);
		}
	}
	catch (Exception $e) {
		//error_log($e->getMessage() . ' 1.13');
		$session_log->LogError($e->getMessage() . ' 1.13');
	}
}
function get_captcha_fail_count() {
	global $session_log;
	try {
		$fail_count = file_get_contents("/tmp/fail_count");
		return (int)$fail_count;
	}
	catch (Exception $e) {
		//error_log($e->getMessage() . ' 1.14');
		$session_log->LogError($e->getMessage() . ' 1.14');
	}
}
function get_captcha_treshold() {
	global $session_log;
	try {
		//$ini=file_get_contents('/var/www/softnas/config/softnas.ini');
		//preg_match('/failsBeforeCaptcha..*\n/',$ini,$ini);
		//preg_match('/[0-9][0-9]*/',$ini[0],$ini);
		//$captchaThreshold=$ini[0];
		//if($captchaThreshold<1){
		//	$captchaThreshold=5;
		//}
		//$captchaThreshold--;
		$captchaThreshold = 5;
		$login_ini = read_ini("login.ini");
		$login_data = isset($login_ini['login']) ? $login_ini['login'] : array();
		
		if (isset($login_data['captcha']) && $login_data['captcha'] === 'false') {
			$captchaThreshold = 1000000;
		} else {
			if (isset($login_data['captcha_treshold'])) {
				$captchaThreshold = (int)($login_data['captcha_treshold']);
			}
		}
		return $captchaThreshold;
	}
	catch (Exception $e) {
		//error_log($e->getMessage() . ' 1.15');
		$session_log->LogError($e->getMessage() . ' 1.15');
	}
}
function create_temp_file($file) {
	global $session_log;
	try {
		if (!is_dir(SESSION_FOLDER)) {
			mkdir(SESSION_FOLDER);
		}
		$f = @fopen(SESSION_FOLDER . '/' . $file, 'w');
		if (!$f) {
			echo "Unable to log in. Fatal error - permissions problem!  Contact the administrator for assistance";
			error_log("Cannot create session file: $file! Please chmod folder /tmp/softnas to 777");
		} else {
			$cmd = "chgrp root " . SESSION_FOLDER . '/' . $file;
			sudo_execute($cmd);
			fclose($f);
		}
	}
	catch (Exception $e) {
		//error_log($e->getMessage() . ' 1.16');
		$session_log->LogError($e->getMessage() . ' 1.16');
	}
}
function logout() {
	global $session_log;
	try {
		update_login_time_spent();
		
		if (!isset($_SESSION)) {
			session_name('PHPSESSID_port'.$_SERVER['SERVER_PORT']);
			session_start(); //PHP Warning:  session_destroy(): Trying to destroy uninitialized session
		}
		// remove cookie and file related
		if (isset($_COOKIE['USER_SS_port'.$_SERVER['SERVER_PORT']])) {
			if (file_exists(SESSION_FOLDER . '/' . $_COOKIE['USER_SS_port'.$_SERVER['SERVER_PORT']])) {
				@unlink(SESSION_FOLDER . '/' . $_COOKIE['USER_SS_port'.$_SERVER['SERVER_PORT']]);
			}
		}

		$cookieSecure = canSetCookieSecureFlag();

		setcookie('USER_SS_port'.$_SERVER['SERVER_PORT'], '', 1, "/", '', $cookieSecure, false); // force user session to expire
		setcookie('USER_SESSION_port'.$_SERVER['SERVER_PORT'], '', 0, "/", '', $cookieSecure, false); // reset user session
		setcookie('KEY_SS_port'.$_SERVER['SERVER_PORT'], ' ', 0, "/", '', $cookieSecure, false); // reset form key identifier
		// destroy session vars
		$_SESSION = array();
		unset($_SESSION);
		session_destroy();
		$cmd = "sudo TERM=dumb /var/www/softnas/scripts/session_cleanup.sh 2>&1 /dev/null";
		system($cmd); // force cleanup of all old PHP session files
	}
	catch (Exception $e) {
		//error_log($e->getMessage() . ' 1.17');
		$session_log->LogError($e->getMessage() . ' 1.17');
	}
	
}
function cleanup_session_files() {
	global $session_log;
	// (called from the cron every minute)
	////return; // #1719 - remove the code that controls timeout // 31.07.2015 - revert #1719
	try {
		$d = opendir($dirname = SESSION_FOLDER);
		if (!$d) return;
		while ($f = @readdir($d)) {
			if (is_dir("$dirname/$f")) continue;
			if ((time() - @filemtime("$dirname/$f")) > SESSION_TIMEOUT) {
				@unlink("$dirname/$f");
				@exec("sudo rm $dirname/$f");
			}
		}
		closedir($d);
	}
	catch (Exception $e) {
		//error_log($e->getMessage() . ' 1.18');
		$session_log->LogError($e->getMessage() . ' 1.18');
	}
}


try {
	load_config('login.ini');
}
catch (Exception $e) {
	//error_log($e->getMessage() . ' 0.3');
	$session_log->LogError($e->getMessage() . ' 0.3');
}

function canSetCookieSecureFlag() {
    return !file_exists('/tmp/azure_test_drive');
}

function update_login_time_spent($logged_in_now = false) {
	global $_config;
	$login_path = $_config['proddir']."/config/login_time";
	sudo_execute("touch $login_path");
	sudo_execute("chmod 660 $login_path");
	sudo_execute("chown root:apache $login_path");
	$result = sudo_execute("grep time_spent $login_path | awk '{print $2}'");
	$time_spent = intVal($result['output_str']);
	$result = sudo_execute("grep last_updated $login_path | awk '{print $2}'");
	$last_updated = intVal($result['output_str']);
	
	if ($logged_in_now || !$last_updated) {
		$last_updated = time();
	}
	$time_spent += (time() - $last_updated);
	$last_updated = time();
	
	sudo_execute("echo 'time_spent $time_spent \nlast_updated $last_updated' > $login_path");
	return $time_spent;
}

?>
