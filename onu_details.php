<?php

include ("common.php");
include ("dbconnect.php");
navigation();


if ($_GET) {
	$customer_id = $_GET['id'];
	if (!preg_match('/^[0-9]*$/', $customer_id)) {
		print "that sux";
		exit;
	} else {
		try {
			$result = $db->query("SELECT CUSTOMERS.ID, CUSTOMERS.NAME as NAME, LPAD(HEX(CUSTOMERS.MAC_ADDRESS), 12, '0') as MAC_ADDRESS, PON_ONU_ID, CUSTOMERS.ONU_MODEL, CUSTOMERS.PON_PORT, CUSTOMERS.OLT, CUSTOMERS.STATE as STATE, CUSTOMERS.SVR_TEMPLATE as SVR_TEMPLATE, OLT.ID, INET_NTOA(OLT.IP_ADDRESS) as IP_ADDRESS, OLT.NAME as OLT_NAME, OLT.RO as RO, OLT.RW as RW, OLT_MODEL.TYPE, PON.ID as PON_ID, PON.PORT_ID as PORT_ID, PON.SLOT_ID as SLOT_ID, ONU.ID, ONU.PORTS as ONU_PORTS, ONU.RF as RF, ONU.PSE as PSE from CUSTOMERS LEFT JOIN OLT on CUSTOMERS.OLT=OLT.ID LEFT JOIN OLT_MODEL on OLT.MODEL=OLT_MODEL.ID LEFT JOIN PON on CUSTOMERS.PON_PORT=PON.ID LEFT JOIN ONU on CUSTOMERS.ONU_MODEL=ONU.ID where CUSTOMERS.ID = '$customer_id'");
		} catch (PDOException $e) {
			echo "Connection Failed:" . $e->getMessage() . "\n";
			exit;
		}
	}
	while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
		$ip_address = $row['IP_ADDRESS'];
		$port_id = $row['PORT_ID'];
		$slot_id = $row['SLOT_ID'];
		$pon_onu_id = $row['PON_ONU_ID'];
		$olt_name = $row['OLT_NAME'];
		$onu_ports = $row['ONU_PORTS'];
		$ro = $row['RO'];
		$rw = $row['RW'];
		$rf = $row['RF'];
		$pse = $row['PSE'];
		$olt_type = $row['TYPE'];
		$name = $row['NAME'];
	}
}
print "<center><h2>" . $olt_name . " >> " . $name . " " . $slot_id . "/" . $port_id . "/" . $pon_onu_id . " Statistics</center></h2>";
print "<center>";
print "<div id=\"menu\">";
print "<input type=\"button\" value=\"info\" onClick=\"getPage('". $customer_id . "', 'info');\">";	
print "<input type=\"button\" value=\"ports\" onClick=\"getPage('". $customer_id . "', 'ports');\">";	
print "<input type=\"button\" value=\"graphs\" onClick=\"getPage('". $customer_id . "', 'graphs');\">";	

print "</div>";

print "<div id=\"output\">";


print "<script>getPage('". $customer_id . "', 'info');</script>";


print "</div>";


print "</center>";


?>

