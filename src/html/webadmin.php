<?php
	include(  dirname(dirname(__FILE__)).'/snserver/session_functions.php');
	if(!check_logged_in()) 
	{
		header("Location: /");
	}
	// Get redirect path (if any)
	$path = isset($_GET["path"]) ? $_GET["path"] :'';
	header("Location: " . webmin_url($path));
	exit;
?>
