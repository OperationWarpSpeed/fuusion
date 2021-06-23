<?php
//
//  verifyOktaSSO.php - buurst OKTA SSO Management Library
//
//  Copyright (c) buurst Inc.  All Rights Reserved.
//
require_once 'utils.php';
require_once 'logging.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') 
{  
    $base_protocol = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' ? 'https' : 'http';
    $redirect_login_uri = $base_protocol.'://'.$_SERVER['HTTP_HOST'].'/buurst/login.php';

    $ini = [
             'CONFIG' => [
                  'CLIENTID' => $_REQUEST["client_id"],
                  'CLIENTSECRET' => $_REQUEST['secret_id'],
                  'DOMAIN' => $_REQUEST['domain_url']
              ]
          ];
    $file_path = '/var/www/buurst/config/oktaSSOCredential.ini';
    write_ini_file($ini, $file_path, true);
    $response['success']= true;
    $response['msg']= 'Please use these redirect URLs into your application';
    $response['Login_redirect_URIs']= $redirect_login_uri; 
    $response['Logout_redirect_URIs']= $redirect_login_uri;
    $response['Initiate_login_URI']= $redirect_login_uri;
    echo json_encode($response, JSON_UNESCAPED_SLASHES);  exit;	  
}
?>
