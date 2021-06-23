<?php
if (count($argv) >= 2) {
	switch (count($argv)) {
		case 2:
			$method = 'encrypt';
			$ciphertext = $argv[1];
		break;
		case 3:
			$method = $argv[1];
			$ciphertext = $argv[2];
		break;
		case 4:
			$method = $argv[1];
			$encryption_key = $argv[2];
			$ciphertext = $argv[3];
		break;
		default:
			// default option: die, because it means the parameters were undefined
			echo "Error\t: Invalid parameter number!\nUsage\t: cmd_encrypt [ method [encryption_key] ]  ciphertext\n";
			exit(1);
		break;
	}
	include_once 'utils.php';
	if (isset($encryption_key) and $encryption_key) {
		if (!defined('ENCRYPTION_KEY')) define('ENCRYPTION_KEY', $encryption_key);
	} else {
		set_encryption_key();
	}
	if ($method == 'decrypt') {
		echo quick_decrypt(ENCRYPTION_KEY, $ciphertext);
	} else {
		echo quick_encrypt(ENCRYPTION_KEY, $ciphertext);
	}
} else {
	echo "Error\t: Invalid parameter number!\nUsage\t: cmd_encrypt [ method [encryption_key] ]  ciphertext\n";
	exit(1);
}
