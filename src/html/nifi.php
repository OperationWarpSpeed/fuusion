
<?php
	include(  dirname(dirname(__FILE__)).'/snserver/session_functions.php');
	if(!check_logged_in()) 
	{
		header("Location: /");
	}
	
	header("Location: https://{$_SERVER[HTTP_HOST]}/softnas/snserver/snserv.php?opcode=licenseinfo&handle_feature=flexfiles_architect");
	exit;
	
	// Get redirect path (if any)
	//header("Location: https://{$_SERVER[HTTP_HOST]}:8443/nifi");
	//exit;
?>