<?php
include ("common.php");
navigation();

$page = $_SERVER['PHP_SELF'];
$sec = "5";
header("Refresh: $sec; url=$page");


print "<h1><center>Logs from OLTs<center></h1>";

$my_file = '/var/log/raisecom.log';
$lines = file($my_file);
$lines=str_replace("^M","",$lines);
$ii = "0";
for ($i = count($lines) - 1; $i >= 0; $i--) {
	echo $lines[$i] . '<br/>';
	$ii++;
	if ($ii > 100)
	exit();
}

?>
