<?php
//
//  licenseinfo.php - Prints SoftNAS License Information
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
$licenseInfo = snas_license_info();
print_r($licenseInfo);
?>
