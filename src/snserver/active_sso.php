<?php
//
//  active_sso.php - SoftNAS OKTA SSO details verification
//
//  Copyright (c) SoftNAS Inc.  All Rights Reserved.
//
require_once 'utils.php';
require_once 'logging.php';

$base_protocol = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' ? 'https' : 'http';
$redirect_login_uri = $base_protocol.'://'.$_SERVER['HTTP_HOST'].'/buurst/login.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

   if (isset($_REQUEST["verify"])) {

      if (empty($_REQUEST["client_id"])) {
          $clientMsg = "Client ID is required!";
      }else {
          $client_id = $_REQUEST["client_id"];
      }

      if (empty($_REQUEST["secret_id"])) {
          $secretMsg  = "Secret ID is required!";
      }else {
          $secret_id = $_REQUEST["secret_id"];
      }

      if (empty($_REQUEST["domain_url"]))  {
          $domainMsg  = "Domain URL is required!";
      }else {
          $domain_url = $_REQUEST["domain_url"];
      }
      /*------Verified entered OKTA SSO Application details-----*/  
      if (empty($clientMsg) && empty($secretMsg) && empty($domainMsg)) {
          $data = verifyAuth(trim($client_id), trim($secret_id), trim($domain_url));
      }
    }
}

function verifyAuth($client_id, $secret_id, $domain_url){

  $log = init_logging();
  try {      

       $ini = [
             'CONFIG' => [
                  'CLIENTID' => $client_id,
                  'CLIENTSECRET' => $secret_id,
                  'DOMAIN' => $domain_url
              ]
          ]; 
        $file_path = '/var/www/buurst/config/oktaSSOCredential.ini';
        write_ini_file($ini, $file_path, true);
        return true;
          
     } catch (Exception $e) {
        $log->LogInfo("OKTA Credential not saved!");
     }
}
?>
<!DOCTYPE html>
<!--[if lt IE 7]> <html class="lt-ie9 lt-ie8 lt-ie7" lang="en"> <![endif]-->
<!--[if IE 7]> <html class="lt-ie9 lt-ie8" lang="en"> <![endif]-->
<!--[if IE 8]> <html class="lt-ie9" lang="en"> <![endif]-->
<!--[if gt IE 8]><!--> <html lang="en"> <!--<![endif]-->
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
  <title><?php echo $_SERVER['HTTP_HOST']; ?> - OKTA SSO Verification</title>
  <link rel="shortcut icon" href="https://www.softnas.com/favicon.ico"/>
  <link rel="shortcut icon" href="https://www.softnas.com/favicon.ico" type="image/x-icon" />
  <link rel="stylesheet" href="/buurst/css/login.css">
  <!--[if lt IE 9]><script src="//html5shim.googlecode.com/svn/trunk/html5.js"></script><![endif]-->
  <script>
    function confirmActivation() {

        var r = confirm("Are you sure to Activate SSO?");
        if (r == true) {
            document.cookie = 'okta_sso_verified_flag=1';
            var confirmBtn = document.getElementById("confirm_section");
            var concelBtn = document.getElementById("cancel_section");
            var div = document.getElementById('note');
            if (confirmBtn.style.display === "none") {
                  confirmBtn.style.display = "block";
            concelBtn.style.display = "none";
            div.innerHTML = '<span style="color:green;">Your application successfully activated!</span>';
            } 
        } else {
            document.cookie = 'okta_sso_verified_flag=0';
            location.replace("<?php echo $redirect_login_uri;?>");
        }
    
   }
function copyRedirectUrls(ids) {
    var range = document.createRange();
    range.selectNode(document.getElementById(ids));
    window.getSelection().removeAllRanges(); // clear current selection
    window.getSelection().addRange(range); // to select text
    document.execCommand("copy");
    window.getSelection().removeAllRanges();// to deselect
}
</script>
</head>
<body onload="">
<div class="main-section">
   <div class="header">
     <img id="logo" src="/buurst/images/Logo_200_NoTag.png" alt="SoftNAS StorageCenter" />
     <h1>STORAGECENTER&trade;</h1>
   </div>
  <section class="container">
  <div class="login">

      <?php if(empty($data)){?>

      <form name="verifyform" method="post" action="">
        <p>
            <input type="text" name="client_id" value="<?php echo !empty($client_id) ? $client_id : '';?>" placeholder="Your Client ID*"><br>
            <h2 style="color:#ad5454"><?php echo !empty($clientMsg) ? $clientMsg : '';?></h2>
        </p>
        <p>
            <input type="text" name="secret_id" value="<?php echo !empty($secret_id) ? $secret_id : '';?>" placeholder="Your Secret ID*"><br>
            <h2 style="color:#ad5454"><?php echo !empty($secretMsg) ? $secretMsg : '';?></h2>
        </p>
        <p>
            <input type="text" name="domain_url" value="<?php echo !empty($domain_url) ? $domain_url : '';?>" placeholder="Your Okta domain*"><br>
            <h2 style="color:#ad5454"><?php echo !empty($domainMsg) ? $domainMsg : '';?></h2>
        </p>

        <p class="submit">
            <input type="submit" id="btnLogin" name="verify" value="Verify">
        </p>
      </form>
    <?php }else{ ?>
      </p>
            <h1 style="font-size: 12px;">Please add these URLs in your OKTA SSO Application.</h1>
        </p>
        <p>
    <label style="font-weight: 600;">Login redirect URIs</label>
              <div class="copy_parent">
              <h2 id="login_url">
                <?php echo $redirect_login_uri;?>
        </h2>
              <span onclick="copyRedirectUrls('login_url')" class="copy_span"><img src="/buurst/images/copy.png" class="copy_image"></span>
        </div>
             </p>
    <label style="font-weight: 600;"> Logout redirect URIs </label>
      <div class="copy_parent">
      <h2 id="initiate_url">
        <?php echo $redirect_login_uri;?>                
      </h2>
          <span onclick="copyRedirectUrls('initiate_url')" class="copy_span"><img src="/buurst/images/copy.png" class="copy_image"></span>
    </div>
        <p>
          <label style="font-weight: 600;"> Initiate login URI
      </p>
              <div class="copy_parent">
              <h2 id="callback_url">
                <?php echo $redirect_login_uri;?>
        </h2>
        <span onclick="copyRedirectUrls('callback_url')" class="copy_span"><img src="/buurst/images/copy.png" class="copy_image"></span>
      </div>
      </p>
            </p>
                <h2 id="note" style="color: red;">Note: After adding redirect url in your application, Please click on confirm.</h2>
            </p>
            <p id="cancel_section" class="btn_pointer" style="display: block;">
                <span class="login_SSO_btn" id="confirm_btn" onclick="confirmActivation()">Confirm</span>
            </p>
            <p id="confirm_section" class="btn_pointer" style="display: none;">
               <a href="<?php echo !empty($redirect_login_uri) ? $redirect_login_uri : '#';?>" class="login_SSO_btn">Login</a>
            </p>
         <?php }?>
        </div>
      </section>     
      </div>
</div>
</body>
</html>
