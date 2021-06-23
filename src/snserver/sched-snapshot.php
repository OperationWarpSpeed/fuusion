<?php
//
//  sched-snapshot.php - SoftNAS Volume Snapshot Scheduler
//
//  Copyright (c) SoftNAS Inc.  All Rights Reserved.
//
//
require_once 'KLogger.php';
require_once ('utils.php');
require_once 'logging.php';
require_once ('snasutils.php');
require_once ('config.php');
function log_devices() {
	global $log;
	$cmd = "ls -lR /dev/zvol";
	$result = sudo_execute($cmd);
	if ($result['rv'] == 0) {
		$log->LogDebug($result['output_str']);
	}
}
$log = init_logging();
//$log->SetLogLevel(KLogger::DEBUG);
$log->LogDebug("SoftNAS snapshot scheduler started.");
$licenseInfo = snas_license_info(); // get the license validity
$valid = $licenseInfo['valid'];
if ($valid == false) // we have an invalid licensing outcome (probably exceeded licensed pool capacity limits or expired license)
{
	$errorMsg = "Snapshot schedulder. License failure - unable to continue. Details: " . $licenseInfo['errMsg'];
	$errorProc = true; // pass error back to client
	$log->LogError($errorMsg);
	exit(1);
}

if (file_exists($_config['proddir'] . '/config/snapshots.ini')) {
	$cmd = $_config['systemcmd']['bash'] . " -c 'cat " . $_config['proddir'] . "/config/snapshots.ini | wc -l'";
	$result = sudo_execute($cmd);
	if ($result['rv'] == 0 && $result['output_str'] != 0) {
		$snapshots = read_ini("snapshots.ini");
		if (!$snapshots) {
			$err = "Snapshot scheduler error - unable to read snapshots.ini - likely permissions problem";
			print $err;
			$log->LogError($err);
			exit(1);
		}
	} else exit(0);
} else exit(0);
//
// Get the Schedule name and snapshot type from the PHP command line
//
if ($argc < 3) {
	$err = "Usage: sched-snapshot.php <schedule-name> <snaptype=hourly|daily|weekly>";
	print $err;
	$log->LogDebug($err);
	exit(1);
}
$scheduleName = $argv[1];
$snaptype = $argv[2];
$log->LogDebug("snapshots.ini contents:");
$log->LogDebug($snapshots);
$nPruned = 0;
//
// Look through all snapshots and see if there are tasks due to run (exit if none due)
//
foreach ($snapshots as $snapname => $snapshot) {
	$schedule = $snapshot['schedule'];
	if ($schedule == $scheduleName) // found snapshot with matching schedule name - process the snapshot
	{
		// Check if this is a shared pool and skip it if this is target node
        $snaprepstatus_file = "{$_config['proddir']}/config/snaprepstatus.ini";
		if (file_exists($snaprepstatus_file)) {
			$snaprepstatus = read_ini("snaprepstatus.ini");
			if ($snaprepstatus['Relationship1']['Role'] == 'target') {
				$log->LogDebug('Skipping snapshot scheduler because we are target node.');
				exit;
			}
		}

	$nSnapMax = 0;
	switch ($snaptype) {
		case 'hourly':
			$nSnapMax = $snapshot['hourlysnaps'];
		break;
		case 'daily':
			$nSnapMax = $snapshot['dailysnaps'];
		break;
		case 'weekly':
			$nSnapMax = $snapshot['weeklysnaps'];
		break;
		default:
		break;
	}
	$log->LogDebug("Processing $nSnapMax of type: $snaptype");
	if ($nSnapMax > 0) // there are snapshots on this schedule to be processed now
	{
		// #6044 - decrease nSnapMax by one to account for newly created snapshot
		$nSnapMax = intval($nSnapMax - 1);
		///////////////
		//
		// 1. Get list of all snapshots matching the snapshot type and prune any that are now beyond the maximum snapshot limit $nSnaps
		//
		// snapshot list command: zfs list -r -t snapshot -o name naspool1/websites
		//
		$poolname = $snapshot['pool'];
		$volname = $snapname;
		$command = $_config['systemcmd']['zfs'] . ' list -r -t snapshot -o name ' . $poolname . '/' . $volname;
		$log->LogDebug("Snapshot list command: $command:");
		// log_devices();
		$result = sudo_execute($command, false, true);
		// if pool has been suspended, clear errors and	then retry zfs list. if	it errors again, the error is probably persistent.
		if ($result['rv'] != 0 && strpos($result['output_str'], 'pool I/O is currently suspended') !== false) {
			$command = $_config['systemcmd']['zpool'] . ' clear ' . $poolname . ' ; ' . $_config['systemcmd']['zfs'] . ' list -r -t snapshot -o name ' . $poolname . '/' . $volname;
			$log->LogDebug("Error clear command: $command:");
			$result = sudo_execute($command, false, true);
			// no need for error log here, the one just below will do
		}
		if ($result['rv'] != 0) {
			$errorMsg = "$command command failed failed. Details: " . $result['output_str'];
			$log->LogError($errorMsg);
		}
		// don't create scheduled snapshots if we are not meeting threshold
		exec('sudo '.$_config['systemcmd']['zfs'].' get -H -o value softnas:minimum_threshold '.$poolname.'/'.$volname, $minimum_threshold, $minimum_threshold_return);
		exec('sudo '.$_config['systemcmd']['zfs'].' get -H -o value -p written '.$poolname.'/'.$volname, $written, $written_return);
		exec('sudo '.$_config['systemcmd']['zfs'].' get -H -o value softnas:scheduled_written '.$poolname.'/'.$volname, $scheduled_written, $schedwritten_return);
		exec('sudo '.$_config['systemcmd']['zfs'].' set softnas:scheduled_written='.($written[0]+$scheduled_written[0]).' '.$poolname.'/'.$volname, $updatesched_written, $updatesched_return);
		$snap_arr = $result['output_arr'];
		$snap_count = count($snap_arr);
		$snaplist_arr = array();
		if ($snap_count > 1) {
			$snaplist = $snap_arr;
			for ($i = 1;$i < $snap_count;$i++) {
				$regex = "/^.*\@" . $snaptype . "-([0-9]{8}\-[0-9]{6})$/"; // regular expression to match the snapshot type; e.g., poolname/volname@weekly1
				if (preg_match($regex, $snaplist[$i], $split)) {
					$snaplist_arr[] = $snaplist[$i];
				}
				/*if( @count($split) == 2 )
				             {
				                 $nSnap = $split[1];             // extracted snapshot number
				                 if( $nSnap >= $nSnapMax )
				                 {
				                    // $log->LogDebug( "Snapshot to delete: " . $nSnap );
				                     $command = $_config['systemcmd']['zfs'] . ' destroy ' . $snaplist[$i];
				                     $result  = sudo_execute( $command, false, true );
				                     if ( $result['rv'] != 0 ) {
				                         $errorMsg = "$command command failed failed. Details: " . $result['output_str'];
				                         $log->LogError( $errorMsg );
				                     }
				                     $nPruned++;
				                 }
				             }*/
			}
			sort($snaplist_arr);
			//print_r($snaplist_arr);
			if (count($snaplist_arr) > $nSnapMax) {
				$snaplist_toremove = array_slice($snaplist_arr, 0, (count($snaplist_arr) - $nSnapMax));
				//print_r($snaplist_toremove);
				foreach ($snaplist_toremove as $key => $value) {
					// check if snapshot is in use
					exec("sudo {$_config['systemcmd']['zfs']} get -H -o value -p userrefs $value", $snapshot_is_used);
					if ($snapshot_is_used != "0" && $snapshot_is_used != "-" && strlen(trim($snapshot_is_used)) != 0) {
						$log->LogInfo("Scheduler: snapshot $value is busy, skipping it's deletion");
						continue;
					}
					$command = $_config['systemcmd']['zfs'] . ' destroy ' . $value;
					$result = sudo_execute($command, false, true);
					if ($result['rv'] != 0) {
						$errorMsg = "$command command failed failed. Details: " . $result['output_str'];
						$log->LogError($errorMsg);
					}
					$nPruned++;
				}
			}
		}
		// $log->LogDebug( "After potential snapshot deletes..." );
		// log_devices();
		//
		// 2. Re-run list of all matching snapshots (after pruning), and rename any remaining snapshots to make room for the new one
		//
		/*$command    = $_config['systemcmd']['zfs'] . ' list -r -t snapshot -o name ' . $poolname . '/' . $volname;
		       $result     = sudo_execute( $command, false, true );
		       $snap_arr  = $result['output_arr'];
		       $snap_count = count( $snap_arr );
		       if 				zz( $snap_count > 1 )
		       {
		         $snaplist = $snap_arr;
		         $index = 1;                             // index for renumbering
		         for ( $i = 1; $i < $snap_count; $i++ )
		         {
		             $regex = "/^.*\@" . $snaptype . "([0-9]+)$/";
		             preg_match( $regex, $snaplist[$i], $split );
		             if( @count($split) == 2 )
		             {
		                 $nSnap = $split[1];             // extracted snapshot number
		                 $log->LogDebug( "Snapshot to rename: " . $nSnap );
		                 $newsuffix = $nSnap + 1;
		                 $newsnap = $snaptype . $newsuffix;
		                 $command = $_config['systemcmd']['zfs'] . ' rename ' . $snaplist[$i] . " " . $newsnap;
		                 $result  = sudo_execute( $command, false, true );
		                 if ( $result['rv'] != 0 ) {
		                     $errorMsg = "$command command failed failed. Details: " . $result['output_str'];
		                     $log->LogError( $errorMsg );
		                 }
		                 $index++;
		           }
		         }
		       }*/
			// $log->LogDebug( "After potential snapshot renames..." );
			// log_devices();
			//
			// 3. Run a fresh snapshot; e.g., "@hourly1"  --> zfs snapshot rickpool/thick1@hourly1
			//
			date_default_timezone_set('UTC'); // #14687
			$command = $_config['systemcmd']['zfs'] . ' snapshot ' . $poolname . '/' . $volname . "@" . $snaptype . "-" . date('Ymd-His');
			$log->LogDebug("Issuing snapshot command: $command");
			$result = sudo_execute($command, false, true);
			if ($result['rv'] != 0) {
				$errorMsg = "$command command failed failed. Details: " . $result['output_str'];
				$log->LogError($errorMsg);
			}
		}
	}
}
// $log->LogDebug( "After all snapshot processing ..." );
// log_devices();
$msg = "SoftNAS snapshot scheduled, pruned $nPruned prior snapshots.";
$log->LogDebug($msg);
echo $msg
?>
