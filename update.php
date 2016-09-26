<?php
include ("common.php");
include ("dbconnect.php");
navigation();

$check_list = $new_pon_id = $new_olt = $state =  "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
	if ($_POST["olt_port"]) {
		$new_olt = test_input($_POST["olt_port"]);
	}
	if ($_POST["pon_port"]) {
		$new_pon_id = test_input($_POST["pon_port"]);
	}
	if ($_POST["check_list"]) {
		$check_list = $_POST["check_list"];
	}
	if ($new_olt !== '' && $new_pon_id !== '' && $check_list !== '') {
		foreach($check_list as $customer_id) {	
			try {
				$result = $db->query("SELECT CUSTOMERS.ID, LPAD(HEX(CUSTOMERS.MAC_ADDRESS), 12, '0') as MAC_ADDRESS, PON_ONU_ID, CUSTOMERS.ONU_MODEL, CUSTOMERS.PON_PORT, CUSTOMERS.OLT, CUSTOMERS.STATE as STATE, CUSTOMERS.SVR_TEMPLATE as SVR_TEMPLATE, OLT.ID, INET_NTOA(OLT.IP_ADDRESS) as IP_ADDRESS, OLT.RW as RW, OLT_MODEL.TYPE, PON.ID as PON_ID, PON.PORT_ID as PORT_ID, PON.SLOT_ID as SLOT_ID, ONU.ID, ONU.DTYPE as DTYPE from CUSTOMERS LEFT JOIN OLT on CUSTOMERS.OLT=OLT.ID LEFT JOIN OLT_MODEL on OLT.MODEL=OLT_MODEL.ID LEFT JOIN PON on CUSTOMERS.PON_PORT=PON.ID LEFT JOIN ONU on CUSTOMERS.ONU_MODEL=ONU.ID where CUSTOMERS.ID = '$customer_id'");
			} catch (PDOException $e) {
				echo "Connection Failed:" . $e->getMessage() . "\n";
				exit;
			}
			while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
				$olt = $row["OLT"];
				$old_olt_ip_address = $row["IP_ADDRESS"];
				$olt_rw = $row["RW"];
				$port_id = $row["PORT_ID"];
				$onu_dtype = $row["DTYPE"];
				$old_pon_onu_id = $row["PON_ONU_ID"];
				$snmp_mac_address = $row["MAC_ADDRESS"];
				$slot_id = $row["SLOT_ID"];
				$pon_id = $row["PON_ID"];
				$state = $row["STATE"];
				$svr_template = $row["SVR_TEMPLATE"];
				$type = $row["TYPE"];
			}
			if ($olt == $new_olt && $pon_id == $new_pon_id) {
				exit("Same OLT and PON PORT");
			}else{
				// FIND FREE ONU_ID
				try {
					$result = $db->query("SELECT PON_ONU_ID from CUSTOMERS where OLT='$new_olt' and PON_PORT='$new_pon_id'");
				} catch (PDOException $e) {
					echo "Connection Failed:" . $e->getMessage() . "\n";
					exit;
				}
				$arr2 = array();
				while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
					array_push($arr2, $row{'PON_ONU_ID'});
				}
				$arr1 = range(1,64);
				$arr3 = array_diff($arr1,$arr2);
				$pon_onu_id = array_values($arr3)[0];
				//UPDATE ONU
				try {
					$result = $db->query("UPDATE CUSTOMERS SET OLT = '$new_olt', PON_PORT = '$new_pon_id', PON_ONU_ID = '$pon_onu_id' where ID = '$customer_id'");
				} catch (PDOException $e) {
					echo "Connection Failed:" . $e->getMessage() . "\n";
        			exit;
				}
				//DELETE ONU from OLD OLT/INTERFACE
				if($type == "1")
					$old_big_onu_id = $slot_id * 10000000 + $port_id * 100000 + $old_pon_onu_id ;
				if($type == "2")
					$old_big_onu_id = type2id($slot_id, $port_id, $old_pon_onu_id);
				$destroy_oid = "iso.3.6.1.4.1.8886.18.2.1.3.1.1.9." . $old_big_onu_id;
				$session = new SNMP(SNMP::VERSION_2C, $old_olt_ip_address, $olt_rw);
				$session->set($destroy_oid,'i', '6');
				if ($session->getError())
					var_dump($session->getError());
				//CREATE NEW ONU
				try {
					$result = $db->query("SELECT INET_NTOA(IP_ADDRESS) as IP_ADDRESS, RW, OLT_MODEL.TYPE from OLT LEFT JOIN OLT_MODEL on OLT.MODEL=OLT_MODEL.ID where OLT.ID='$new_olt'");
				} catch (PDOException $e) {
					echo "Connection Failed:" . $e->getMessage() . "\n";
					exit;
				}
        			while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
					$olt_ip_address = $row["IP_ADDRESS"];
					$olt_rw = $row["RW"];
					$type = $row["TYPE"];
				}
				try {
					$result = $db->query("SELECT * from PON where ID='$new_pon_id'");
				} catch (PDOException $e) {
					echo "Connection Failed:" . $e->getMessage() . "\n";
					exit;
				}
				while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
					$port_id = $row["PORT_ID"];
					$slot_id = $row["SLOT_ID"];
				}
				if($type == "1")
			  		$big_onu_id = $slot_id * 10000000 + $port_id * 100000 + $pon_onu_id ;
                                if($type == "2")
                                        $big_onu_id = type2id($slot_id, $port_id, $pon_onu_id);
				$first_oid = "iso.3.6.1.4.1.8886.18.2.1.3.1.1.2." . $big_onu_id;
				$second_oid = "iso.3.6.1.4.1.8886.18.2.1.3.1.1.3." . $big_onu_id;
				$third_oid = "iso.3.6.1.4.1.8886.18.2.1.3.1.1.9." . $big_onu_id;
				$forth_oid = "iso.3.6.1.4.1.8886.18.2.1.3.1.1.10." . $big_onu_id;
				$session = new SNMP(SNMP::VERSION_2C, $olt_ip_address, $olt_rw);
        			if ($state == "1") {
					$session->set(array($first_oid, $second_oid, $third_oid, $forth_oid), array('x', 'i', 'i', 'i'), array($snmp_mac_address, $onu_dtype, '4', '1'));
				} else {
					$session->set(array($first_oid, $second_oid, $third_oid, $forth_oid), array('x', 'i', 'i', 'i'), array($snmp_mac_address, $onu_dtype, '4', '2'));
				}
				if ($session->getError())
					var_dump($session->getError());
				if ($svr_template) {
					if ($type == "1")
						$template_id = $slot_id * 10000000 + $port_id;
					if ($type == "2")
						$template_id = type2ponid($slot_id, $port_id);
					$template_id = $template_id . "." . $svr_template;
					$fifth_oid = "iso.3.6.1.4.1.8886.18.2.6.34.9.1.3." . $template_id;
					$apply_onu_id = 64 - $pon_onu_id + 1;
					$apply_onu_id = str_pad('1', $apply_onu_id, '0');
					//$apply_onu_id = str_pad($apply_onu_id, 64, '0', STR_PAD_LEFT);
					$apply_onu_id = dechex(bindec($apply_onu_id));
					$apply_onu_id = str_pad($apply_onu_id, 16, '0', STR_PAD_LEFT);
					$session = new SNMP(SNMP::VERSION_2C, $olt_ip_address, $olt_rw);
					$session->set($fifth_oid, 'x', $apply_onu_id);
					if ($session->getError())
						var_dump($session->getError());
				}
				//RENAME RRD FILES
				$rrd_name = array("traffic", "unicast", "broadcast", "multicast", "power");
				foreach ($rrd_name as $rrd) {
				$old_rrd_file = dirname(__FILE__) . "/rrd/" . $old_olt_ip_address . "_" . $old_big_onu_id . "_" . $rrd . ".rrd";
				$new_rrd_file = dirname(__FILE__) . "/rrd/" . $olt_ip_address . "_" . $big_onu_id . "_" . $rrd . ".rrd";
				rename($old_rrd_file, $new_rrd_file);
				}
				print "Customer " . $customer_id . " Updated!!!<BR>";
				sleep(1);
			}
		}
	}
}

?>
