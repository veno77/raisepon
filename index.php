<?php

include ("common.php");
include("dbconnect.php");
header('Cache-control: private', true);
navigation();
$OLT_ID = $PON_ID = $name = $pon_port = $egn = $mac_address = $rf_state = '';
print "<h2><center>Search ONUs</center></h2>";


if ($_SERVER["REQUEST_METHOD"] == "POST") {

	if ($_POST["SUBMIT"]) {
        $submit = test_input($_POST["SUBMIT"]);
	}
	if ($submit == "LOAD") {

		$OLT_ID = $_POST["olt_port"];
		$PON_ID = $_POST["pon_port"];
	}
	if ($submit == "SEARCH") {

		$name = test_input($_POST["name"]);
		//check if name only contains letters and whitespace
		if (!preg_match("/^[a-zA-Z ]*$/",$name)) {
			$nameErr = "Only letters and white space allowed";
		}

		if ($_POST["egn"]) {
			$egn = test_input($_POST["egn"]);
			if (!preg_match('/^[0-9]*$/',$egn)) 
				$egnErr = "Only numerals are allowed";
			} 

		if ($_POST["mac_address"]) {
			$mac_address = test_input($_POST["mac_address"]);
			if(!preg_match('/([a-fA-F0-9]{2}[:|\-]?){6}/', $mac_address)) 
				exit("Mac Address Format is like AA:BB:CC:DD:EE:FF");
		}
	}

	if ($OLT_ID) {
		try {
			$rst = $db->query("SELECT NAME from OLT WHERE ID=" . $OLT_ID);
		} catch (PDOException $e) {
			echo "Connection Failed:" . $e->getMessage() . "\n";
			exit;
		}

		while ($row = $rst->fetch(PDO::FETCH_ASSOC)) {
			$OLT_NAME = $row{'NAME'};
		}
	}
	
	if ($PON_ID) {
		try {
		$rst = $db->query("SELECT * from PON WHERE ID=" . $PON_ID);
		} catch (PDOException $e) {
			echo "Connection Failed:" . $e->getMessage() . "\n";
			exit;
		}

		while ($row = $rst->fetch(PDO::FETCH_ASSOC)) {
			$PON_NAME = $row{'NAME'};
		$SLOT_ID = $row{'SLOT_ID'};
		$PORT_ID = $row{'PORT_ID'};
		}
	}
}


print "<form action=\"index.php\" method=\"post\">";
print "<center>OLT:<select id=\"select-olt\" name=\"olt_port\">";
print "<option value=\"\" class=\"rhth\">Select OLT</option>";
try {
	$result = $db->query("SELECT * from OLT");
} catch (PDOException $e) {

	exit;
}

while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
	print "<option value=\"" . $row{'ID'} ."\">" . $row{'NAME'} . "</option>";
}


print "</select>&nbsp;";
print "<select id=\"select-pon\" name=\"pon_port\">";
print "<option value=\"\">PON PORT</option></select>&nbsp;";

print "<input type=\"submit\" name=\"SUBMIT\" value=\"LOAD\"></form>";
print "<br><br>";

print "<form action=\"index.php\" method=\"post\">";
print "Name:<input type=\"text\" name=\"name\"  size=\"30\">";
print "EGN:<input type=\"text\" name=\"egn\"  maxlength=\"10\" size=\"12\">";
print "ONU MAC:<input type=\"text\" name=\"mac_address\"  maxlength=\"17\" size=\"17\">";
print "<input type=\"submit\" name=\"SUBMIT\" value=\"SEARCH\">";
print "</form>";

if ($PON_ID || $name || $egn || $mac_address) {
	$where = "PON.ID='" . $PON_ID ."' and OLT.ID='" . $OLT_ID . "'";

if ($submit == "SEARCH") {
	if($name)
		$where = "CUSTOMERS.NAME LIKE '%$name%'";
	if($egn)
		$where = "CUSTOMERS.EGN = '$egn'";
	if($mac_address) {
		$separator = array(':', '-', '.');
		$stripped_mac = str_replace($separator, '', $mac_address);
		$where = "CUSTOMERS.MAC_ADDRESS = x'$stripped_mac'";
	}
}

try {
	$result = $db->query("SELECT CUSTOMERS.ID, CUSTOMERS.SVR_TEMPLATE, CUSTOMERS.STATE, CUSTOMERS.NAME, CUSTOMERS.ADDRESS, LPAD(HEX(CUSTOMERS.MAC_ADDRESS), 12, '0') as MAC_ADDRESS, ONU.NAME as ONU_NAME, ONU.DTYPE as DTYPE, ONU.RF as RF, OLT.NAME as OLT_NAME, INET_NTOA(OLT.IP_ADDRESS) as IP_ADDRESS, OLT.RO as RO, OLT_MODEL.TYPE as TYPE, PON.NAME as PON_NAME, PON.PORT_ID as PORT_ID, PON.SLOT_ID as SLOT_ID, PON_ONU_ID, SVR_TEMPLATE.NAME as SVR_NAME from CUSTOMERS LEFT JOIN ONU on CUSTOMERS.ONU_MODEL=ONU.ID LEFT JOIN OLT on CUSTOMERS.OLT=OLT.ID LEFT JOIN OLT_MODEL on OLT.MODEL=OLT_MODEL.ID LEFT JOIN PON on CUSTOMERS.PON_PORT=PON.ID LEFT JOIN SVR_TEMPLATE on CUSTOMERS.SVR_TEMPLATE=SVR_TEMPLATE.ID WHERE " . $where ." order by PON_ONU_ID");
} catch (PDOException $e) {
    echo "Connection Failed:" . $e->getMessage() . "\n";
	exit;
} 
print "<p><center>";
if ($PON_ID)
	print '<form name="myform3" action="update.php" method="post">';
print "<h1>OLT: " . $OLT_NAME . "</h1><h2>PON: " . $PON_NAME . "   (" . $SLOT_ID . "/" . $PORT_ID . ")</h2><br><br>"  ;
print "<table border=1 cellpadding=1 cellspacing=1><tr align=center style=font-weight:bold><td><input type=\"checkbox\" id=\"	\"></td><td>ONU</td><td>Name</td><td>Address</td><td>MODEL</td><td>RF</td><td>MAC_ADDRESS</td><td>Admin STATE</td><td>RxPower</td><td>STATUS</td><td>SYNC</td></tr>";
while ($row = $result->fetch(PDO::FETCH_ASSOC)) { 
	if($row{'TYPE'} == '1') {
		$big_onu_id = $row{'SLOT_ID'} * 10000000 + $row{'PORT_ID'} * 100000 + $row{'PON_ONU_ID'};
	        $second_oid = "iso.3.6.1.4.1.8886.18.2.8.1.2.1.2.5." . $big_onu_id;
	}
        if($row{'TYPE'} == '2') {
		$big_onu_id = type2id($row{'SLOT_ID'}, $row{'PORT_ID'}, $row{'PON_ONU_ID'});
                $big_onu_id_2 = $row{'SLOT_ID'} * 10000000 + $row{'PORT_ID'} * 100000 + $row{'PON_ONU_ID'};
	        $second_oid = "iso.3.6.1.4.1.8886.18.2.8.1.2.1.2.5." . $big_onu_id_2;
	}
	$first_oid = "iso.3.6.1.4.1.8886.18.2.1.3.1.1.8." . $big_onu_id;
	$mac_oid = "iso.3.6.1.4.1.8886.18.2.1.3.1.1.2." . $big_onu_id;
	$onutype_oid = "iso.3.6.1.4.1.8886.18.2.1.3.1.1.3." . $big_onu_id;
	//GET STATUS via SNMP
 	snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
	$session = new SNMP(SNMP::VERSION_2C, $row{'IP_ADDRESS'}, $row{'RO'});
	$status = $session->get($first_oid);
	$power = '';
	$rf_state = "";
	if ($status == '1') {
		$status = "<font color=green>Online</font>";
		//GET POWER via SNMP
		$power = $session->get($second_oid);
		$power = round(10*log10($power/10000),2);
		if ($power > "25") {
			$power = "<font color=red>" . $power . "</font>" ;
		} else {
			$power = "<font color=green>" . $power . "</font>" ;
		}
		if ($row{'RF'} == "1") {
			$index = $row{'SLOT_ID'} * 10000000 + $row{'PORT_ID'} * 100000 + $row{'PON_ONU_ID'} * 1000 + 162;
			$rf_status_oid = "1.3.6.1.4.1.8886.18.2.6.21.3.1.2." . $index;
			snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
			$session = new SNMP(SNMP::VERSION_2C, $row{'IP_ADDRESS'}, $row{'RO'});
			$rf_state = $session->get($rf_status_oid);
			if ($rf_state == "0" || $rf_state == "2") {
				$rf_state = "<img src=\"pic/off_small.png\">" ;
			}else if($rf_state == "1") {
				$rf_state = "<img src=\"pic/green_small.png\">" ;
			}
		}
	}else{
        $status = "<font color=red>Offline</font>";
	}
//ADMIN STATE
	if ($row{'STATE'} == 1) {
        $state = "<font color=green>Enabled</font>";
	}else{
        $state = "<font color=red>Disabled</font>";
	}
//SYNC CHCECK
        snmp_set_valueretrieval(SNMP_VALUE_LIBRARY);
	$session = new SNMP(SNMP::VERSION_2C, $row{'IP_ADDRESS'}, $row{'RO'});
	$check_mac = $session->get($mac_oid);
	$check_mac = trim(str_replace('Hex-STRING: ', '', $check_mac));
	$check_mac = str_replace(' ', ':', $check_mac);
	$db_mac = implode(':', str_split($row{'MAC_ADDRESS'},2));

	snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
	$session = new SNMP(SNMP::VERSION_2C, $row{'IP_ADDRESS'}, $row{'RO'});
	$onutype = $session->get($onutype_oid);
	$onudtype = $row{'DTYPE'};
	if ( strcmp( $check_mac, $db_mac) == 0  &&  strcmp( $onutype, $onudtype) == 0 ){
		$sync = "<font color=green>OK</font>" ;
	}
	else{
                $sync = "<font color=red>NOT OK</font>";
	}
// PRINT TABLE
	print "<tr id=hover align=right><td><input type=\"checkbox\" class=\"case\" name=\"check_list[]\" value=\"" . $row{'ID'} . "\"></td><td><a href=\"customers.php?edit=1&id=".$row{'ID'}."\">".$row{'PON_ONU_ID'}."</a></td><td>".$row{'NAME'}."</td><td>".$row{'ADDRESS'}."</td><td>".$row{'ONU_NAME'}."</td><td><a href=\"onu_details.php?id=" . $row{'ID'} . "\">".$rf_state."</a></td><td>" . $db_mac ."</td><td align=\"center\" style=\"vertical-align:middle\"><a href=\"onu_details.php?id=" . $row{'ID'} . "\">". $state ."</a></td><td>" . $power ."</td><td align=\"center\" style=\"vertical-align:middle\">" . $status ."</td><td>" . $sync ."</td></tr>";
}

print "</table></p>";
?>

<center>
OLT:
<select id="select-olt-2" name="olt_port">
<option value="" class="rhth">Select OLT</option>
<?php
try {
	$result = $db->query("SELECT * from OLT");
} catch (PDOException $e) {
	echo "Connection Failed:" . $e->getMessage() . "\n";
	exit;
}

while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
	print "<option value=\"" . $row{'ID'} ."\">" . $row{'NAME'} . "</option>";
}

?>
</select>
<select id="select-pon-2" name="pon_port">
<option value="">PON PORT</option>
</select>

<input type="submit" name="SUBMIT" value="MOVE SELECTED">
<?php
}
?>
