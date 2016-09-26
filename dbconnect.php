<?php
$mysql_user = "username";
$mysql_pass = "password";
try {
	$db = new PDO('mysql:host=localhost;dbname=raisecom;charset=utf8', $mysql_user, $mysql_pass);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
	echo 'Connection Failed: ' . $e->getMessage();
	exit;
}
?>
