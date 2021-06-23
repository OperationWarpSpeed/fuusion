<?php
//
// config.php - system-wide configuration parameters
//
// Copyright (c) SoftNAS Inc.  All Rights Reserved.
//
// SoftNAS global configuration array
if (!isset($_config)) {
	$_config = array();
}
// project data
$_config['name'] = 'SoftNAS';
// Common commands and paths
$_config['tempdir'] = '/tmp';
$_config['proddir'] = '/var/www/softnas';
$_config['systemcmd'] = array(
	'azure' => '/usr/lib/node_modules/azure-cli/bin/azure',
	'bash' => '/usr/bin/bash',
	'cat' => '/usr/bin/cat',
	'chgrp' => '/usr/bin/chgrp',
	'chown' => '/usr/bin/chown',
	'chmod' => '/usr/bin/chmod',
	'dd' => '/usr/bin/dd',
	'find' => '/usr/bin/find',
	'hssh' => '/usr/local/bin/hssh',
	'ifconfig' => '/usr/sbin/ifconfig',
	'ls' => '/usr/bin/ls',
	'mpstat' => '/usr/bin/mpstat',
	'nohup' => '/usr/bin/nohup',
	'openssl' => '/usr/bin/openssl',
	'php' => '/usr/bin/php',
	'sh' => '/usr/bin/sh',
	'scp' => '/usr/bin/scp',
	'servce' => '/usr/sbin/service',
	'ssh' => '/usr/bin/ssh',
	'smbpasswd' => '/usr/bin/smbpasswd',
	'sudo' => '/usr/bin/sudo',
	'sum' => '/usr/bin/sum',
	'tar' => '/usr/bin/tar',
	'throttle' => '/usr/bin/throttle',
	'touch' => '/usr/bin/touch',
	'wget' => '/usr/bin/wget',
	'unlink' => '/usr/bin/unlink',
	'zdb' => '/usr/sbin/zdb',
	'zfs' => '/usr/sbin/zfs',
	'zpool' => '/usr/sbin/zpool',
	'hostname' => '/usr/bin/hostname'
);
$_config['path'] = array(
	'scripts' => '../scripts',
	'samba' => '/etc/samba/smb.conf'
);
$_config['rc.d'] = array(
	'samba' => '/usr/local/etc/rc.d/samba',
	'NFS' => '/etc/rc.d/nfsserver',
	'iSCSI' => '/usr/local/etc/rc.d/istgt'
);
$_config['salt'] = '648F9BBCDD48DB14';
$_config['license'] = 'CPUAAA-AASAAF-27NXVJ-AMACTX-B3QW8Z-94RUDB'; // built-in default license key
$_config['sig'] = '9A95755C';
$_config['urlupdate'] = 'https://mirror.softnas.com/fuusion/aptrepo';
$_config['urltestupdate'] = 'https://mirror.softnas.com/fuusion/aptrepo_test';
$_config['urlstableupdate'] = 'https://mirror.softnas.com/fuusion/aptrepo_stableupdate';
$_config['urldevnextupdate'] = 'https://mirror.softnas.com/fuusion/aptrepo_devnextupdate';
$_config['urldevupdate'] = 'https://mirror.softnas.com/fuusion/aptrepo_dev';
$_config['urlcustomupdate'] = 'https://mirror.softnas.com/fuusion/aptrepo_custom';
$_config['url_auth_google'] = "https://softnas.com/auth/test.php";
$_config['url_auth_facebook'] = "https://softnas.com/auth/testfb.php";
$_config['segment_write_key'] = "OkEO3mV4K92mhsZSAH5pJ2K09HyMxSN0";
//$_config['drift_id'] = "7977fmrv3vrw"; // ID for testing
//$_config['drift_token'] = "mr81O9CmigNliJvYonxYAVoP4jMzqcQS"; // Token for testing
$_config['drift_id'] = "r8vm6aag37yr";
$_config['drift_token'] = "fzd5kYateg8bfzrWjCNK1r9vUolh0250";
$_config['aws_wrapper'] = $_config['proddir'].'/scripts/aws_wrapper.sh';
$_config['az_wrapper'] = $_config['proddir'].'/scripts/azwrapper.sh';

require ($_config['proddir'].'/config/database.php');

require_once ('common.php');
$found = false;
foreach (get_included_files() as $value) {
	if(strpos($value, 'common.php') !== false) {
		$found = true;
		break;
	}
}
if (!$found) {
	include_once __DIR__ . '/common.php';
}
sync_server_timezone();
?>
