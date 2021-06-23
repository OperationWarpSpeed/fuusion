<?php
/* Finally, A light, permissions-checking logging class.
 *
 * Author	: Kenneth Katzgrau < katzgrau@gmail.com >
 * Date	: July 26, 2008
 * Comments	: Originally written for use with wpSearch
 * Website	: http://codefury.net
 * Version	: 1.0
 *
 * Usage:
 *		$log = new KLogger ( "log.txt" , KLogger::INFO );
 *		$log->LogInfo("Returned a million search results");	//Prints to the log file
 *		$log->LogFATAL("Oh dear.");				//Prints to the log file
 *		$log->LogDebug("x = 5");					//Prints nothing due to priority setting
*/
class KLogger {
	const MAINT = 0; // Secret "Maintenance only" level logging
	const DEBUG = 1; // Most Verbose
	const INFO = 2; // ...
	const WARN = 3; // ...
	const ERROR = 4; // ...
	const FATAL = 5; // Least Verbose
	const OFF = 6; // Nothing at all.
	const LOG_OPEN = 1;
	const OPEN_FAILED = 2;
	const LOG_CLOSED = 3;
	/* Public members: Not so much of an example of encapsulation, but that's okay. */
	public $Log_Status = KLogger::LOG_CLOSED;
	public $MessageQueue;
	private $log_file;
	private $priority = KLogger::INFO;
	private $file_handle;
	public function __construct($filepath, $priority) {
		if ($priority == KLogger::OFF) return;
		$this->log_file = $filepath;
		$this->MessageQueue = array();
		$this->priority = $priority;
		if (file_exists($this->log_file)) {
			if (!is_writable($this->log_file)) {
				// #6464 - make file writeable
				exec('sudo chown apache: '.$this->log_file);
				if (!is_writable($this->log_file)) {
					$this->Log_Status = KLogger::OPEN_FAILED;
					$this->MessageQueue[] = "The file exists, but could not be opened for writing. Check that appropriate permissions have been set.";
					return;
				}
			}
		}
		if ($this->file_handle = fopen($this->log_file, "a")) {
			$this->Log_Status = KLogger::LOG_OPEN;
			$this->MessageQueue[] = "The log file was opened successfully.";
		} else {
			$this->Log_Status = KLogger::OPEN_FAILED;
			$this->MessageQueue[] = "The file could not be opened. Check permissions.";
		}

		return;
	}
	public function __destruct() {
		if ($this->file_handle) fclose($this->file_handle);
	}
	public function SetLogLevel($level) {
		$this->priority = $level;
	}
	public function GetLogLevel($level) {
		return $this->priority;
	}
	public function GetLogFile() {
		return $this->log_file;
	}
	public function LogInfo($line) {
		$this->Log($line, KLogger::INFO);
	}
	public function LogMaint($line) {
		$this->Log($line, KLogger::MAINT);
	}
	public function LogDebug($line) {
		$this->Log($line, KLogger::DEBUG);
	}
	public function LogWarn($line) {
		$this->Log($line, KLogger::WARN);
	}
	public function LogError($line) {
		$this->Log($line, KLogger::ERROR);
	}
	public function LogFatal($line) {
		$this->Log($line, KLogger::FATAL);
	}
	public function Log($line, $priority) {
		if ($this->priority <= $priority) {
			$outStr = $line;
			if (is_array($line)) {
				$outStr = var_export($line, true); // convert arrays into readable output
				
			}
			$status = $this->getTimeLine($priority);
			try {
				$this->WriteFreeFormLine("$status $outStr \n");
			} catch (Exception $ex) {
				return false;
			}
		}
	}
	public function WriteFreeFormLine($line) {
		if ($this->Log_Status == KLogger::LOG_OPEN && $this->priority != KLogger::OFF) {
			if (fwrite($this->file_handle, $line) === false) {
				$this->MessageQueue[] = "The file could not be written to. Check that appropriate permissions have been set.";
			}
		}
	}
	private function getTimezone() {
		$zone = "GMT";
		exec('/bin/date' . ' 2>&1', $output, $rv);
		$result = implode(chr(10) , $output);
		$split = explode(" ", $result);
		$zone = $split[4];
		return $zone;
	}
	private function getLocalTime() {
		$datim = array();
		exec('/bin/date' . ' 2>&1', $output, $rv);
		$result = implode(chr(10) , $output);
		$singlespaced = preg_replace('!\s+!', ' ', $result); // collapse multiple spaces into single spacing
		$split = explode(" ", $singlespaced);
		//    [0] => Mon
		//    [1] => Mar
		//    [2] => 10
		//    [3] => 10:27:01
		//    [4] => CDT
		//    [5] => 2014
		//file_put_contents('/var/www/softnas/logs/time.log', print_r($split, true));
		$datim[0] = $split[0] . " " . $split[1] . " " . $split[2] . " " . $split[5]; // Wed Mar 5 2014
		$datim[1] = $split[5]; // CST
		$datim[2] = $split[3]; // 12:34:21
		return $datim;
	}
	private function getTimestamp() {
		return date("D M d Y H:i:s").substr((string)microtime() , 1, 4);
		// #3995
		// PHP Warning:  exec(): Unable to fork [/bin/date 2>&1] in /var/www/softnas/snserver/KLogger.php on line 110
		// PHP Notice:  Undefined offset: 1 in /var/www/softnas/snserver/KLogger.php on line 121
		/*$microtime = floatval(substr((string)microtime() , 1, 8));
		$rounded = round($microtime, 3);
		$datime = $this->getLocalTime(time() , true);
		$strRounded = str_pad(substr((string)$rounded, 1, strlen($rounded)) , 4);
		return $datime[0] . " $datime[2]" . $strRounded . " " . $datime[1];*/
	}
	private function getTimeLine($level) {
		$time = $this->getTimestamp();
		switch ($level) {
			case KLogger::INFO:
				return "$time - INFO  -->";
			case KLogger::WARN:
				return "$time - WARN  -->";
			case KLogger::MAINT:
				return "$time - MAINT -->";
			case KLogger::DEBUG:
				return "$time - DEBUG -->";
			case KLogger::ERROR:
				return "$time - ERROR -->";
			case KLogger::FATAL:
				return "$time - FATAL -->";
			default:
				return "$time - LOG   -->";
		}
	}
}
?>