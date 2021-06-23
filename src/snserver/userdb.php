<?php
// userdb.php - user database
//
//
// (c) 2015 SoftNAS
// @author kashpande 2015-09

class UserDB {
	protected $db;
	protected $username;
	protected $password;

	public function __construct(PDO $db, $username, $password) {
		$this->db = $db;
		$this->username = strtolower($username); // usernames should all be lower-case!
		$this->password = password_hash($password, PASSWORD_BCRYPT);
	}
	public function register() {
		// simple function to add a user, can be removed for production
		$p = $this->db->prepare("INSERT INTO users (`username`, `password`) VALUES (:username, :password);");
		$p->execute(array(':username'=>$username, ':password'=>$password));
		if ($p->rowCount() > 0) {
			echo "USER REGISTERED!\n";
			return true;
		}
		echo "USER NOT REGISTERED.\n";
		return false;
	}
	public function login() {
		$p = $this->db->prepare("SELECT password FROM users WHERE username=:username AND active=1;");
		$p->execute(array(':username'=>$this->username));
		foreach($p->fetchAll(PDO::FETCH_ASSOC) as $id => $row) {
			echo "USER FOUND!\n";
			if (password_verify($this->$password, $row['password'])) {
				echo "USER VERIFIED!\n";
				return true;
			}
		}
		echo "USER NOT VERIFIED!\n";
		return false;
	}
}

?>
