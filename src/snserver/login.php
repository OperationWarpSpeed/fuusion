<?php
//phpinfo();
//ini_set('display_errors','On');//debug statements
//
//  login.php - SoftNAS Session Login
//
//  Copyright (c) SoftNAS Inc.  All Rights Reserved.
//
include_once ('session_functions.php');
include_once ('getUserAgent.php');
require_once 'utils.php';
check_captcha_response(); // Mihajlo 02.Aug.2015 - to be before $dual_auth_status when appears with dual auth
update_login_time_spent(true);
$dual_auth_status = check_dual_auth_logged_in();
if ($dual_auth_status != "OK") {
	echo "<h3>Login failed: $dual_auth_status</h3>";
	exit;
}
$CLEAN = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
if (isset($CLEAN['changing_password'])) {
	$changing_pwd = html_entity_decode($CLEAN['changing_password']);
	if (isset($CLEAN['change_password']) && isset($CLEAN['change_password_confirm'])) {
		$new_pwd = html_entity_decode($CLEAN['change_password']);
		$new_pwd2 = html_entity_decode($CLEAN['change_password_confirm']);
	}
}
$system_platform = get_system_platform();
$pwd_user = "";
$force_pwd_change = db_session("forcepwd", null, 0, false);
$log = init_logging();
$log->LogDebug('force_pwd_change: '.var_export($force_pwd_change, true));
if (($force_pwd_change == true) || (isset($changing_pwd) && $changing_pwd == "yes")) {
	if ($changing_pwd == "yes") {
		if ($new_pwd != "" && $new_pwd == $new_pwd2) {
			$pwd_user = db_session("fpwduser", null, 0, false);
			$log->LogDebug('pwd_user: '.var_export($pwd_user, true));
			$cmd = "(echo $new_pwd; echo $new_pwd) | sudo passwd $pwd_user";
			$result_pwd = "";
			$rv = "";
			exec("$cmd 2>&1", $result_pwd, $rv);
			logout();
			$force_pwd_change = false;
		} else {
			$error = "Error: Password Mismatch";
			$pwd_user = "";
			$force_pwd_change = true;
		}
		$res = db_session("forcepwd", $force_pwd_change, null, 0, false);
		$log->LogDebug('forcepwd res: '.var_export($res, true));
	}
	if ($force_pwd_change == true) {
?>
			<!DOCTYPE html>
			<!--[if lt IE 7]> <html class="lt-ie9 lt-ie8 lt-ie7" lang="en"> <![endif]-->
			<!--[if IE 7]> <html class="lt-ie9 lt-ie8" lang="en"> <![endif]-->
			<!--[if IE 8]> <html class="lt-ie9" lang="en"> <![endif]-->
			<!--[if gt IE 8]><!--> <html lang="en"> <!--<![endif]-->
			<head>
			  <meta charset="utf-8">
			  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
			  <link rel="shortcut icon" href="https://www.buurst.com/favicon.ico"/>
			  <link rel="shortcut icon" href="https://www.buurst.com/favicon.ico" type="image/x-icon" />
			  <title><?php echo $_SERVER['HTTP_HOST']; ?> - Buurst - Change Password</title>
			  <link rel="stylesheet" href="/buurst/css/login.css">
			  <!--[if lt IE 9]><script src="//html5shim.googlecode.com/svn/trunk/html5.js"></script><![endif]-->
			</head>
			<body onload="document.loginform.change_password.focus();">
			    <div class="header">
			       <img id="logo" src="/buurst/images/Logo_200_NoTag.png" alt="Buurst Fuusion" /> <h1>Buurst Fuusion&trade;</h1>
			    </div>
			  <section class="container">
			    <div class="login">
			      <h1>
			      	<div style="color:#FF9800; padding-top:7px;">
			      		You must set a new password with one upper case, one lower case, and one number
			      	</div>
			      </h1>
			      <form name="loginform" method="post" action="">
			        <p><input type="password" required pattern="^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?!.*\s).*$" name="change_password" value="" placeholder="New Password"></p>
			        <p><input type="password" required pattern="^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?!.*\s).*$" name="change_password_confirm" value="" placeholder="Retype New Password"></p>
			        <input type='hidden' name='changing_password' value='yes' />
			      <?php if (isset($error)): ?><div class="error"><img style="margin: 0px 0px -6px 0px;"
			      src="/buurst/images/alert32x32.png" /> &nbsp;<?php echo "$error" ?>
			      </div><?php
		endif; ?>
			
			        <p class="submit">
			            <input type="submit" name="commit" value="Change Password">
			        </p>
			
			      </form>
			    </div>
			  </section>
			
			</body>
			</html>
			<?php
		db_session("forcepwd", false, null, 0, false);
		exit;
	}
}
function updateUserFails($databaseString, $profile, $fails) {
	preg_match("/$profile..*/", $databaseString, $user);
	$pieces = explode($user[0], $databaseString);
	$user = $profile . " $fails";
	return $pieces[0] . $user . $pieces[1];
}
$ua = getBrowser();
$ip = $_SERVER['SERVER_ADDR'];
$platform = $ua['platform'];
$browser = $ua['name'];
$version = $ua['version'];
$profile = "ip=\"$ip\" AND platform=\"$platform\" AND browser=\"$browser\" AND version=\"$version\"";
$failedAttempts = get_captcha_fail_count();
$captchaThreshold = get_captcha_treshold();
$captcha = true;
if ($failedAttempts < $captchaThreshold - 1) {
	$captcha = false;
}
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$CLEAN = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
	$name = html_entity_decode($CLEAN['username']);
	$pw = html_entity_decode($CLEAN['password']); // decode html entities like &amp; such as "Password&"
	if (!$captcha && login($name, $pw)) {
		set_captcha_fail_count(0);
		
		require_once(__DIR__."/segment_utils.php");

		if (isset($_REQUEST['original_url']) && !empty($_REQUEST['original_url'])) {
			$original_url = $_REQUEST['original_url'];
		} 
		else {
			$original_url = "snserver/index.php";	
		}

		$url = parse_url($original_url);
		if (isset($url['host']) && ($url['host'] != $_SERVER['HTTP_HOST'])) {
			$original_url = "snserver/index.php";
		}

		trackLogin();
		
		header('Location: '.$original_url);
		exit();

	} else {
		// FAILURE :(
		$error = 'Incorrect username/password';
		if ($captcha) {
			$error = "Captcha verification";
		}
		$failedAttempts++;
		set_captcha_fail_count($failedAttempts);
	}
} elseif (check_logged_in()) {
	if (!isset($_REQUEST['original_url'])) {
		header('Location: index.php');
	} else {
		header('Location: '.$_REQUEST['original_url']);
	}
}

$original_url = isset($_REQUEST['original_url']) ? $_REQUEST['original_url'] : false;
if ($original_url && stripos($original_url, ".js") == strlen($original_url) - 3) {
	if (stripos($_SERVER['HTTP_REFERER'], 'https://'.$_SERVER['SERVER_NAME'].'/buurst/applets/update') === 0) {
		if (is_update_process_active()) {
			exit(file_get_contents("/var/www/softnas/".$_REQUEST['original_url']));
		}
	}
}

$user_val = $pwd_user ? trim($pwd_user, "'") : "";
$login_message = "Log in to Fuusion&trade;";
$js_focus = "document.loginform.username.focus();";
if ($user_val != "") {
	$login_message.= " <br/>with your new password";
	$error = null;
	$js_focus = "document.loginform.password.focus();";
} else {
	$user_val = 'buurst';
	$js_focus = "document.loginform.password.focus();";
}
 
// #4365
$display_placeholder = false;
$tmp_path = "/tmp/default_password_warning";
if (!isset($_COOKIE['initialpwd'])) { // if never went to login page
	
	$display_placeholder = true;
	setcookie('initialpwd', "seen", time() + (86400 * 365 * 10) , "/");
	
	$default_pwd = "";
	if ($system_platform == "amazon") {
		$default_pwd = file_get_contents("http://169.254.169.254/latest/meta-data/instance-id");
	}
	if ($system_platform == "VM") {
		$default_pwd = "Pass4W0rd";
	}
	if ($default_pwd && $default_pwd !== "" && $system_platform !== "azure") {
		$res_change = array('result', 'rv');
		exec("sudo chage -l buurst 2>&1", $res_change['result'], $res_change['rv']);
		if (stripos($res_change[0]['result'], 'password must be changed') !== false) {
			$display_placeholder = false;
		} else {
			$res = array('result', 'rv');
			exec(dirname(dirname(__FILE__))."/scripts/login.sh buurst $default_pwd", $res['result'], $res['rv']);
			if ($res['rv'] != 0) {
				$display_placeholder = false; // if never been to login page but password already changed from SSH
			} else {
				file_put_contents($tmp_path, "yes");
				//if ($system_platform == "amazon") {
				//	file_put_contents($tmp_path, "yes");
				//}
				//if ($system_platform == "VM") {
				//	sudo_execute("chage -d 0 softnas");
				//}
			}
		}
	}
	if ($system_platform === "azure" ) {
		if (!file_exists($tmp_path)) {
			file_put_contents($tmp_path, "yes");
		}
	}
	
}

$password_placeholder = 'Password';
if ($display_placeholder === true) {
	// Set password placeholder depending on system platform
	switch ($system_platform) {
		case 'azure':
			$password_placeholder = 'Password was set during instance creation';
			break;
		case 'amazon':
			$password_placeholder = 'Initial Password: Use AWS Instance ID';
			break;
		case 'VM':
			$password_placeholder = 'Initial Password: Pass4W0rd';
			break;
		default:
			$password_placeholder = 'Password';
			break;
	}
}
?><!DOCTYPE html>
<!--[if lt IE 7]> <html class="lt-ie9 lt-ie8 lt-ie7" lang="en"> <![endif]-->
<!--[if IE 7]> <html class="lt-ie9 lt-ie8" lang="en"> <![endif]-->
<!--[if IE 8]> <html class="lt-ie9" lang="en"> <![endif]-->
<!--[if gt IE 8]><!--> <html lang="en"> <!--<![endif]-->
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
  <title><?php echo $_SERVER['HTTP_HOST']; ?> - Buurst Login</title>
  <link rel="shortcut icon" href="https://www.buurst.com/favicon.ico"/>
  <link rel="shortcut icon" href="https://www.buurst.com/favicon.ico" type="image/x-icon" />
  <link rel="stylesheet" href="/buurst/css/login.css">
  <!--[if lt IE 9]><script src="//html5shim.googlecode.com/svn/trunk/html5.js"></script><![endif]-->
</head>
<body onload="<?php echo $js_focus; ?>">
    <div class="header">
       <img id="logo" src="/buurst/images/Logo_200_NoTag.png" alt="Buurst Cloud" /></div>
    </div>
  <section class="container">
    <div class="login">
      <h1><?php echo $login_message; ?></h1>
      <form name="loginform" method="post" action="">
        <p><input type="text" name="username" value="<?php echo $user_val; ?>" placeholder="Username"></p>
        <p><input type="password" name="password" value="" placeholder="<?php echo $password_placeholder ?>"></p>
        <input type='hidden' name='form_key' id='form_key' value='<?php $formKey = generateFormKey();
setcookie('KEY_SS_port'.$_SERVER['SERVER_PORT'], $formKey, 0, "/");
echo $formKey; ?>' />
        
      <?php if (isset($error)): ?><div class="error"><img
      src="/buurst/images/alert32x32.png" /> &nbsp;<?php echo "$error" ?>
      </div><?php
endif; ?>
      	
      	<?php
if ($captcha) {
	$tmpkey = sha1($_SERVER['HTTP_HOST'] . "3_");
	$captcha_url = "https://www.softnas.com/instance_captcha/captcha_buurst.php?snaskey=$tmpkey";
?>
      	<p style="text-align:center; max-height: 100px;">
      		<iframe id="captcha_iframe" urlooo="<?php echo $_SERVER['HTTP_HOST']; ?>" 
      			src="<?php echo $captcha_url; ?>" width="550" height="800"></iframe>
      	</p>
      	<?php
} ?>
        <p class="submit">
            <input type="submit" id="btnLogin" name="commit" value="Login">
        </p>
      </form>
    </div>
  </section>
<?php if ($captcha) { ?>
  	<script type="text/javascript">
  		var btn_login = document.getElementById("btnLogin");
  		var login_style_display = btn_login.style.display;
  		btn_login.style.display = "none";
  		var captcha_interval = setInterval(function(){
  			var fr = document.getElementById('captcha_iframe');
  			var fr_html = fr.contentWindow.document.documentElement.innerHTML;
  			if(fr_html.indexOf("Verified") != -1){
  				btn_login.style.display = login_style_display;
  				fr.style.display="none";
  				clearInterval(captcha_interval);
  			}
  			//console.log("CONTENT: ", fr_html);
  		}, 1000);
  	</script>
<?php
} ?>
</body>
</html>
