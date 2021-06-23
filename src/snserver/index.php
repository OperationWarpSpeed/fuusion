<?php
//
//  index.php - SoftNAS Session Initiation
//
//  Copyright (c) SoftNAS Inc.  All Rights Reserved.
//
include_once ('session_functions.php');
if (!check_logged_in()) {
	header("Location: ./login.php");
}
echo "Buurst&trade; Fuusion&trade;...";
header("Location: /buurst/fuusion/");
exit;
