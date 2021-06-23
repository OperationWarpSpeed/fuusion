<?php
//
//  userdata.php - SoftNAS User Data backing-store
//
//  Copyright (c) SoftNAS Inc.  All Rights Reserved.
//
//
include_once 'database.php'; // initialize DB support if it hasn't been done already

/*
* these functions are to be used for temporary user data storage, like sessions - but not as "official" or guaranteed.
*	they can have an expiry set, otherwise the session cleanup will do these too.
*/

class user_data {
	/* Use $db connection from arguments:
		global $db;
		$udata = New user_data($db, $username);
	*/
	protected $db;
	public function __construct(PDO $db, $user) {
		$this->db = $db;
		$this->user = $user;
	}

	/* Create OR update as needed */
	public function insert($segment, $object, $expire = 'NULL') {
		// row `id` is set to a combination of the segment and user name so the primary key is still user-specific
		$p = $this->db->prepare("INSERT INTO `user_data` (`id`, `segment`, `userid`, `object`, `created`, `updated`, `expire`) VALUES (:id, :segment, :user, :object, :created, :updated, :expire) ON DUPLICATE KEY UPDATE `object`=:newobject, `updated`=:newupdated, `expire`=:newexpire;");
		$p->execute(
			array(
				':id'=>$segment.'-'.$this->user,
				':segment' => $segment,
				':user' => $this->user,
				':object' => serialize($object),
				':created' => time(),
				':updated' => time(),
				':expire' => $expire,
				':newobject' => serialize($object),
				':newupdated' => time(),
				':newexpire' => $expire
			)
		);
		if ($p === FALSE) {
			// statement failed
			return FALSE;
		}
		if ($p->rowCount() > 0) {
			return true;
		}
	}

	/* Read */
	public function select($segment) {
		$p = $this->db->prepare("SELECT * FROM `user_data` WHERE `segment`=:segment AND `userid`=:user;");
		$p->execute(
			array(
				':segment' => $segment,
				':user' => $this->user
			)
		);
		if ($p === FALSE) {
			return FALSE;
		}
		$output_arr = array();
		foreach($p->fetchAll(PDO::FETCH_ASSOC) as $key => $value) {
			$output_arr[] = unserialize($value['object']);
		}
		return count($output_arr) > 0 ? $output_arr[0] : null;
	}

	/* Update */
	public function update($segment, $object, $expire = 'NULL') {
		$p = $this->db->prepare("UPDATE `user_data` SET `object`=:object, `expire`=:expire WHERE `userid`=:user AND `segment`=:segment;");
		$p->execute(
			array(
				':object'=> serialize($object),
				':segment'=> $segment,
				':user'=> $this->user,
				':expire'=> $expire
			)
		);
		if ($p->rowCount() > 0) {
			return true;
		}
		return false;
	}

	/* Delete */
	public function delete($segment) {
		$p = $this->db->prepare("DELETE FROM `user_data` WHERE `segment`=:segment AND `userid`=:user;");
		$p->execute(
			array(
				':segment'=> $segment,
				':user'=> $this->user
			)
		);
	}

	/* Clean-up all expired objects */
	public function clean($timeout = 1400) {
		$p = $this->db->prepare("DELETE FROM `user_data` WHERE `expire`<:expiretime OR `created`<:expiretime;");
		$expiretime = time() - $timeout;
		$p->execute(
			array(
				':expiretime'=> $expiretime
			)
		);
	}
}
?>
