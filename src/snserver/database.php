<?php
/*
* database support for storagecenter by kashpande 2015-08
*/

require_once __DIR__ . '/../config/database.php';

/* MySQL PDO initialization */
initializeDB();
global $db;

/* DB functions */
class db extends PDO {
	public function last_row_count() {
		return $this->query("SELECT FOUND_ROWS()")->fetchColumn();
	}
}
function initializeDB() {
        global $db, $log, $_config;
        try {
                $db = new PDO('mysql:host='.$_config['db']['host'].';dbname='.$_config['db']['name'].';charset=utf8', $_config['db']['user'], $_config['db']['pass'], array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
        } catch(PDOException $ex) {
                if (is_object($log)) {
                        $log->logError(var_export($ex, true));
                } else {
                        file_put_contents('/var/www/softnas/logs/dberror.log', var_export($ex, true));
                }
				$error_msg = "StorageCenter is undergoing maintenance. Please try again momentarily.";
				$error_msg.= "<script type='text/javascript'>setTimeout(function(){window.top.location.reload();}, 2000);</script>";
				echo($error_msg);
				exit;
        }
		$db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
}
function doSql($string) {
	global $db;
	// use PDO, prepared statements
	$stmt = $db->query($string);
	$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
	foreach ($results as $key => $value) {
		$results[$key] = end($value);
	}
	return $results;
}
?>
