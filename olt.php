<?php
include ("common.php");
include ("dbconnect.php");
navigation();
if ($user_class < "6")
	exit();
$nameErr = $ip_addrErr = $roErr = $rwErr = $olt_modelErr = "";
$edit = $old_ip = $name = $ip_address = $ro = $rw = $olt_id = $olt_model =  "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
	if ($_POST["olt_id"]) {
    	$olt_id = test_input($_POST["olt_id"]);
	}
	if ($_POST["old_ip"]) {
    	$old_ip = test_input($_POST["old_ip"]);
	}
	
	if (empty($_POST["name"])) {
    	$nameErr = "Name is required";
  	} else {
    	$name = test_input($_POST["name"]);
  	}
	
	if (empty($_POST["ip_address"])) {
    	$ip_addrErr = "IP Address is required";
  	} else {
    	$ip_address = test_input($_POST["ip_address"]);
  	}
  	if (empty($_POST["ro"])) {
    	$roErr = "R/O Community is required";
  	} else {
    	$ro = test_input($_POST["ro"]);
  	}
  	if (empty($_POST["rw"])) {
    	$rwErr = "R/W Community is required";
 	} else {
    	$rw = test_input($_POST["rw"]);
  	}
        if ($_POST["olt_model"] == "") {
        $olt_modelErr = "OLT Type is required";
        } else {
        $olt_model = test_input($_POST["olt_model"]);
        }
	if ($_POST["SUBMIT"]) {
        $submit = test_input($_POST["SUBMIT"]);
	}

// ADD OLT

	if ($name !== '' && $ip_address !== '' && $ro !== '' && $rw !== '' && $olt_model !== '' && $submit == "ADD") {
		// CHECK IP ADDRESS for DUPLICATES
		try {
			$result = $db->query("SELECT IP_ADDRESS from OLT where IP_ADDRESS = INET_ATON('$ip_address')");
		} catch (PDOException $e) {
			echo "Connection Failed:" . $e->getMessage() . "\n";
	        exit;
		}
		while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
			if ($row["IP_ADDRESS"])
				exit ("ERROR!  DUPLICATE IP ADDRESS!");
		}
   

		try {
			$result = $db->query("INSERT INTO OLT (NAME, MODEL, IP_ADDRESS, RO, RW) VALUES ('$name', '$olt_model', INET_ATON('$ip_address'), '$ro', '$rw')");
		} catch (PDOException $e) {
	        echo "Connection Failed:" . $e->getMessage() . "\n";
	        exit;
		}

		//CREATE RRD
		foreach (range(1, 18) as $port_number) {
	        $rrd_name = dirname(__FILE__) . "/rrd/" . $ip_address . "_" . $port_number . "_traffic.rrd";
	        $opts = array( "--step", "300", "--start", 0,
	           "DS:input:DERIVE:600:0:U",
	           "DS:output:DERIVE:600:0:U",
	           "RRA:AVERAGE:0.5:1:600",
	           "RRA:AVERAGE:0.5:6:700",
	           "RRA:AVERAGE:0.5:24:775",
	           "RRA:AVERAGE:0.5:288:797",
	           "RRA:MAX:0.5:1:600",
	           "RRA:MAX:0.5:6:700",
	           "RRA:MAX:0.5:24:775",
	           "RRA:MAX:0.5:288:797"
	        );
			$ret = rrd_create($rrd_name, $opts);
			if( $ret == 0 ){
				$err = rrd_error();
				echo "$err";
			}
			$rrd_name = dirname(__FILE__) . "/rrd/" . $ip_address . "_" . $port_number . "_broadcast.rrd";
	        $opts = array( "--step", "300", "--start", 0,
	           "DS:input:DERIVE:600:0:U",
	           "DS:output:DERIVE:600:0:U",
	           "RRA:AVERAGE:0.5:1:600",
	           "RRA:AVERAGE:0.5:6:700",
	           "RRA:AVERAGE:0.5:24:775",
	           "RRA:AVERAGE:0.5:288:797",
	           "RRA:MAX:0.5:1:600",
	           "RRA:MAX:0.5:6:700",
	           "RRA:MAX:0.5:24:775",
	           "RRA:MAX:0.5:288:797"
	        );
	        $ret = rrd_create($rrd_name, $opts);
		
			if( $ret == 0 ){
				$err = rrd_error();
				echo "$err";
			}
		
		}
		exit("OLT added Succesfully");
	}

	// EDIT OLT
	if ($olt_id !== '' && $name !== '' && $ip_address !== '' && $ro !== '' && $rw !== '' &&  $olt_model !== '' && $submit == "EDIT") {
		// CHECK IP ADDRESS for DUPLICATES
		try {
			$result = $db->query("SELECT IP_ADDRESS from OLT where IP_ADDRESS = INET_ATON('$ip_address') AND ID <> $olt_id");
		} catch (PDOException $e) {
			echo "Connection Failed:" . $e->getMessage() . "\n";
			exit;
		}
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
			if ($row["IP_ADDRESS"])
				exit ("ERROR!  DUPLICATE IP ADDRESS!");
		}
		
		// UPDATE CUSTOMER
        try {
			$result = $db->query("UPDATE OLT SET NAME = '$name', MODEL = '$olt_model', IP_ADDRESS = INET_ATON('$ip_address'), RO = '$ro', RW = '$rw' where ID = '$olt_id'");
		} catch (PDOException $e) {
			echo "Connection Failed:" . $e->getMessage() . "\n";
			exit;
		}

		//CREATE RRD
		if ($old_ip != $ip_address) {
			foreach (range(1, 18) as $port_number) {
				$old_rrd_file = dirname(__FILE__) . "/rrd/" . $old_ip . "_" . $port_number . "_traffic.rrd";
				unlink($old_rrd_file);
				$rrd_name = dirname(__FILE__) . "/rrd/" . $ip_address . "_" . $port_number . "_traffic.rrd";
				$opts = array( "--step", "300", "--start", 0,
					"DS:input:DERIVE:600:0:U",
					"DS:output:DERIVE:600:0:U",
					"RRA:AVERAGE:0.5:1:600",
					"RRA:AVERAGE:0.5:6:700",
					"RRA:AVERAGE:0.5:24:775",
					"RRA:AVERAGE:0.5:288:797",
					"RRA:MAX:0.5:1:600",
					"RRA:MAX:0.5:6:700",
					"RRA:MAX:0.5:24:775",
					"RRA:MAX:0.5:288:797"
				);
				$ret = rrd_create($rrd_name, $opts);
				if( $ret == 0 ) {
					$err = rrd_error();
					echo "$err";
				}    
				$old_rrd_file = dirname(__FILE__) . "/rrd/" . $old_ip . "_" . $port_number . "_broadcast.rrd";
				unlink($old_rrd_file);
				$rrd_name = dirname(__FILE__) . "/rrd/" . $ip_address . "_" . $port_number . "_broadcast.rrd";
				$opts = array( "--step", "300", "--start", 0,
					"DS:input:DERIVE:600:0:U",
					"DS:output:DERIVE:600:0:U",
					"RRA:AVERAGE:0.5:1:600",
					"RRA:AVERAGE:0.5:6:700",
					"RRA:AVERAGE:0.5:24:775",
					"RRA:AVERAGE:0.5:288:797",
					"RRA:MAX:0.5:1:600",
					"RRA:MAX:0.5:6:700",
					"RRA:MAX:0.5:24:775",
					"RRA:MAX:0.5:288:797"
				);
				$ret = rrd_create($rrd_name, $opts);

				if( $ret == 0 ) {
					$err = rrd_error();
					echo "$err";
				}    
			}
		}
		exit("OLT Edited Succesfully");
	}
	
	// DELETE OLT
	if ($olt_id !== '' && $submit == "DELETE") {
		// CHECK IF OLT IS ASSIGNED TO ANY CUSTOMER
		try {
			$result = $db->query("SELECT OLT from CUSTOMERS where OLT =  '$olt_id'");
		} catch (PDOException $e) {
			echo "Connection Failed:" . $e->getMessage() . "\n";
			exit;
		}
		while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
			if ($row["OLT"])
				exit ("OLT IS ASSIGNED TO CUSTOMERS, Please remove OLT from customers to Delete it!");
		}
		try {
			$result = $db->query("DELETE FROM OLT where ID='$olt_id'");
		} catch (PDOException $e) {
			echo "Connection Failed:" . $e->getMessage() . "\n";
			exit;
		}
  		try {
                        $result = $db->query("DELETE FROM PON where OLT='$olt_id'");
                } catch (PDOException $e) {
                        echo "Connection Failed:" . $e->getMessage() . "\n";
                        exit;
                }
		foreach (range(1, 18) as $port_number) {
			$old_rrd_file = dirname(__FILE__) . "/rrd/" . $old_ip . "_" . $port_number . "_traffic.rrd";
			unlink($old_rrd_file);
			$old_rrd_file = dirname(__FILE__) . "/rrd/" . $old_ip . "_" . $port_number . "_broadcast.rrd";
			unlink($old_rrd_file);
		}
		exit("OLT Deleted Succesfully");
	}

}


try {
	$result = $db->query("SELECT OLT.ID, OLT.NAME, OLT.MODEL, INET_NTOA(IP_ADDRESS) as IP_ADDRESS, RO, RW, OLT_MODEL.NAME as OLT_NAME,OLT_MODEL.TYPE as TYPE from OLT LEFT JOIN OLT_MODEL on OLT.MODEL = OLT_MODEL.ID");
} catch (PDOException $e) {
	echo "Connection Failed:" . $e->getMessage() . "\n";
	exit;
}

print "<p><center>OLT Configuration</p>";
print "<p><table border=1 cellpadding=1 cellspacing=1><tr align=center style=font-weight:bold><td>ID</td><td>NAME</td><td>MODEL</td><td>IP_ADDRESS</td><td>R/O</td><td>R/W</td><td>Status</td><td>Temp</td><td>CPU</td><td>Config</td></tr>";

while($row = $result->fetch(PDO::FETCH_ASSOC)) {
	snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
	$first_oid = "1.3.6.1.2.1.1.3.0";
	$session = new SNMP(SNMP::VERSION_2C, $row{'IP_ADDRESS'}, $row{'RO'}, 100000, 2);
	$status = $session->get($first_oid);
	$temp = '';
	$save = '';
	$cpu = '';
	if ($status) {
		$status = "<font color=green>Online</font>";
		if ($row{'TYPE'} == '1') {
			$temp_oid = '1.3.6.1.4.1.8886.1.27.2.1.1.9.0';
			$cpu_oid = '1.3.6.1.4.1.8886.1.1.1.2.0';
		}else if($row{'TYPE'} == '2') {
			$temp_oid = '1.3.6.1.4.1.8886.1.27.2.1.1.10.0';
			$cpu_oid = '1.3.6.1.4.1.8886.18.1.7.1.1.1.4.1.0';
		}
		$session = new SNMP(SNMP::VERSION_2C, $row{'IP_ADDRESS'}, $row{'RO'});
        $temp = $session->get($temp_oid);
		if ($temp > '65') {
			$temp = "<font color=red>" . $temp . "\xc2\xb0C</font>";
		}else {
			$temp = $temp . "\xc2\xb0C";
		}
		$cpu = $session->get($cpu_oid);
		if ($cpu > '50') {
			$cpu = "<font color=red>" . $cpu . "%</font>";
		}else{
			$cpu = $cpu . "%";
		}
		$save = '<form action="save.php" method="post"><input type="hidden" name="ip_address" value="' . $row{'IP_ADDRESS'} .'"><input type="hidden" name="rw" value="' . $row{'RW'} .'"><input type="submit" name="SUBMIT" value="SAVE"></form>';
	}else{
		$status = "<font color=red>Offline</font>";
	}
	print "<tr align=right><td><a href='olt.php?edit=1&id=". $row{'ID'} . "'>" . $row{'ID'} . "</a></td><td>" . $row{'NAME'} . "</td><td>" .$row{'OLT_NAME'} . "</td><td>" . $row{'IP_ADDRESS'} . "</td><td>" . $row{'RO'} . "</td><td>" . $row{'RW'} . "</td><td>" . $status . "</td><td>" . $temp .  "</td><td>" . $cpu . "</td><td>" . $save . "</td></tr>";
}

print "</table></p>";

if ($_GET) {
	$olt_id = $_GET['id'];
	$edit = $_GET["edit"];
	if (!preg_match('/^[0-9]*$/', $olt_id)) {
		print "that sux";
		exit;
	} else {
		try {
			$result = $db->query("SELECT ID, NAME, MODEL, INET_NTOA(IP_ADDRESS) as IP_ADDRESS, RO, RW from OLT where ID='$olt_id'");
		} catch (PDOException $e) {
			echo "Connection Failed:" . $e->getMessage() . "\n";
			exit;
		}
		while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
			$olt_id =  $row["ID"];
			$name = $row["NAME"];
			$ip_address = $row["IP_ADDRESS"];
			$ro = $row["RO"];
			$rw = $row["RW"];
			$olt_model = $row["MODEL"];
		}
	}
}


print "<form action=\"olt.php\" method=\"post\">";
if ($olt_id)
	print "<input type=\"hidden\" name=\"olt_id\" value=\"". $olt_id ."\">";
print "<input type=\"hidden\" name=\"old_ip\" value=\"". $ip_address ."\">";
print "<table>";
print "<tr><td>Name*:</td><td><input type=\"text\" name=\"name\"";
if ($name) 
	print "value=\"" . $name . "\"";
print "></td>";
if ($nameErr != "") 
	print "<td style=\"color:red\">" . $nameErr . "</td>";
print "</tr>";
print "<tr><td>OLT MODEL*:</td><td><select id=\"select-olt-model\" name=\"olt_model\">";
print "<option  value=\"\" class=\"rhth\">---</option>";
try {
        $result = $db->query("SELECT * from OLT_MODEL");
} catch (PDOException $e) {
        echo "Connection Failed:" . $e->getMessage() . "\n";
        exit;
}
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        if($olt_model == $row{'ID'}) {
                print "<option value=\"" . $row{'ID'} ."\" selected>" . $row{'NAME'} . "</option>";
        } else {
                print "<option value=\"" . $row{'ID'} ."\">" . $row{'NAME'} . "</option>";
        }
}
if ($olt_modelErr != "")
        print "<td style=\"color:red\">" . $olt_modelErr . "</td>";

print "<tr><td>IP Address*:</td><td><input type=\"text\" name=\"ip_address\"";
if ($name) 
	print "value=\"".$ip_address ."\"";
print "></td>";
if ($ip_addrErr != "") 
	print "<td style=\"color:red\">" . $ip_addrErr . "</td>";
print "</tr><tr><td>R/O Community*:</td><td><input type=\"text\" name=\"ro\"";
if ($name) 
	print "value=\"".$ro ."\"";
print "></td>";
if($roErr != "") 
	print "<td style=\"color:red\">" . $roErr . "</td>";
print "</tr><tr><td>R/W Community *:</td><td><input type=\"text\" name=\"rw\"";
if($name) 
	print "value=\"".$rw ."\"";
print "></td>";
if($rwErr != "") 
	print "<td style=\"color:red\">" . $rwErr . "</td>";
print "</tr><tr><td>";
if ($edit == "1" || $olt_id) {
	print "<input type='submit' name='SUBMIT' value='EDIT'>";
	print "&nbsp;&nbsp;&nbsp;<input type='submit' name='SUBMIT' value='DELETE'>";
}else{
	print "<input type='submit' name='SUBMIT' value='ADD'>";
}
print "</td></table></center>";
?>
