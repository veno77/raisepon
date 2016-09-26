<?php
include ("common.php");
include ("dbconnect.php");
navigation();

$nameErr = $addrErr = $egnErr = $onuErr = $oltErr = $ponErr = $macErr = $max_cpeErr = "";
$name = $address = $egn = $svr_template = $template_id = $onu = $olt = $pon_port = $mac_address = $max_cpe = $customer_id = $old_pon_port = $old_olt = $old_pon_onu_id = $state = $old_onu_ports = "";



if ($_SERVER["REQUEST_METHOD"] == "POST") {
	if (empty($_POST["name"])) {
		$nameErr = "Name is required";
	} else {
		$name = test_input($_POST["name"]);
		//check if name only contains letters and whitespace
		if (!preg_match("/^[a-zA-Z ]*$/",$name)) {
			$nameErr = "Only letters and white space allowed";
		}
	}

	if ($_POST["address"]) {
		$address = test_input($_POST["address"]);
	}

	if (isset($_POST["svr_template"])) {
		$svr_template = test_input($_POST["svr_template"]);
	}

	if (isset($_POST["customer_id"])) {
		$customer_id = test_input($_POST["customer_id"]);
	}

	if ($_POST["egn"]) {
		$egn = test_input($_POST["egn"]);
		if (!preg_match('/^[0-9]*$/',$egn)) {
			$egnErr = "Only numerals are allowed";
		}
	}
	
	if ($_POST["max_cpe"]) {
		$max_cpe = test_input($_POST["max_cpe"]);
		if (!preg_match('/^[0-9]*$/',$max_cpe)) {
			$max_cpeErr = "Only numerals are allowed";
		}
	}
	
	if (isset($_POST["old_onu_ports"])) {
		$old_onu_ports = test_input($_POST["old_onu_ports"]);
	}

	if (empty($_POST["onu"])) {
		$onuErr = "ONU is required";
	} else {
		$onu = test_input($_POST["onu"]);
	}

	if (empty($_POST["olt"])) {
		$oltErr = "OLT is required";
	} else {
		$olt = test_input($_POST["olt"]);
	}

	if ($_POST["old_olt"]) {
		$old_olt = test_input($_POST["old_olt"]);
	}

	if ($_POST["old_pon_port"]) {
		$old_pon_port = test_input($_POST["old_pon_port"]);
	}
	if ($_POST["old_pon_onu_id"]) {
		$old_pon_onu_id = test_input($_POST["old_pon_onu_id"]);
	}

	if (empty($_POST["pon_port"])) {
		$ponErr = "PON Port is required";
	} else {
		$pon_port = test_input($_POST["pon_port"]);
	}

	if (empty($_POST["mac_address"])) {
		$macErr = "MAC Address is required";
	} else {
		$mac_address = test_input($_POST["mac_address"]);
	if(!preg_match('/([a-fA-F0-9]{2}[:|\-]?){6}/', $mac_address))
		exit("Mac Address Format is like AA:BB:CC:DD:EE:FF");
	}

	if ($_POST["state"]) {
		$state = "1";
	}else{
		$state = "2";
	}

	if ($_POST["SUBMIT"]) {
		$submit = test_input($_POST["SUBMIT"]);
	}

//INSERT NEW CUSTOMER

	if ($name !== '' && $onu !== '' && $olt !== '' && $pon_port !== '' && $mac_address !== '' && $submit == "ADD") {
		try {
			$result = $db->query("SELECT PON_ONU_ID from CUSTOMERS where OLT='$olt' and PON_PORT='$pon_port'");
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
		$arr3 = array_filter($arr3);
		if (empty($arr3))
			exit("Not Free ONU IDs");
		$pon_id = array_values($arr3)[0];
		$separator = array(':', '-', '.');
		$stripped_mac = str_replace($separator, '', $mac_address);
		// CHECK MAC ADDRESS for duplicates
		try {
			$result = $db->query("SELECT MAC_ADDRESS from CUSTOMERS where MAC_ADDRESS = x'$stripped_mac'");
		} catch (PDOException $e) {
			echo "Connection Failed:" . $e->getMessage() . "\n";
			exit;
		}
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
			if ($row["MAC_ADDRESS"])
				exit ("DUPLICATE MAC_ADDRESS");
        }
        try {
			$result = $db->query("INSERT INTO CUSTOMERS (NAME, ADDRESS, EGN, ONU_MODEL, OLT, PON_PORT, PON_ONU_ID, MAC_ADDRESS, STATE, SVR_TEMPLATE, MAX_CPE) VALUES ('$name', '$address', '$egn', '$onu', '$olt', '$pon_port', '$pon_id', x'$stripped_mac', '$state', '$svr_template', '$max_cpe')");
		} catch (PDOException $e) {
			echo "Connection Failed:" . $e->getMessage() . "\n";
			exit;
	    }
		try {
			$result = $db->query("SELECT ID from CUSTOMERS where MAC_ADDRESS = x'$stripped_mac'");
		} catch (PDOException $e) {
			echo "Connection Failed:" . $e->getMessage() . "\n";
			exit;
		}
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
			$customer_id = $row["ID"];
        }
		// ADD DATETIME in HISTORY
		try {
			$result = $db->query("INSERT INTO HISTORY (CUSTOMERS_ID, DATE, ACTION, MAC_ADDRESS) VALUES ('$customer_id', NOW(), 'Add New Customer', x'$stripped_mac')");
		} catch (PDOException $e) {
			echo "Connection Failed:" . $e->getMessage() . "\n";
			exit;
		}
		
		// PREPARE SNMPSET TO ADD ONU
        try {
			$result = $db->query("SELECT CUSTOMERS.ID, LPAD(HEX(CUSTOMERS.MAC_ADDRESS), 12, '0') as MAC_ADDRESS, PON_ONU_ID, CUSTOMERS.ONU_MODEL, CUSTOMERS.PON_PORT, CUSTOMERS.OLT, OLT.ID, INET_NTOA(OLT.IP_ADDRESS) as IP_ADDRESS, OLT.RW as RW, OLT_MODEL.TYPE as TYPE, PON.ID, PON.PORT_ID as PORT_ID, PON.SLOT_ID as SLOT_ID, ONU.ID, ONU.DTYPE as DTYPE, ONU.PORTS as PORTS from CUSTOMERS LEFT JOIN OLT on CUSTOMERS.OLT=OLT.ID LEFT JOIN OLT_MODEL on OLT.MODEL=OLT_MODEL.ID LEFT JOIN PON on CUSTOMERS.PON_PORT=PON.ID LEFT JOIN ONU on CUSTOMERS.ONU_MODEL=ONU.ID where CUSTOMERS.MAC_ADDRESS = x'$stripped_mac'");
		} catch (PDOException $e) {
			echo "Connection Failed:" . $e->getMessage() . "\n";
			exit;
		}
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
			$olt_ip_address = $row["IP_ADDRESS"];
			$olt_rw = $row["RW"];
			$pon_interface = $row["PORT_ID"];
			$onu_dtype = $row["DTYPE"];
			$pon_onu_id = $row["PON_ONU_ID"];
			$snmp_mac_address = $row["MAC_ADDRESS"];
			$slot_id = $row["SLOT_ID"];
			$type = $row["TYPE"];
			$ports = $row["PORTS"];
		}
		if ($type == "1")
			$big_onu_id = $slot_id * 10000000 + $pon_interface * 100000 + $pon_onu_id ;
		if ($type == "2")
			$big_onu_id = type2id($slot_id, $pon_interface, $pon_onu_id);
		$first_oid = "iso.3.6.1.4.1.8886.18.2.1.3.1.1.2." . $big_onu_id;
		$second_oid = "iso.3.6.1.4.1.8886.18.2.1.3.1.1.3." . $big_onu_id;
        $third_oid = "iso.3.6.1.4.1.8886.18.2.1.3.1.1.9." . $big_onu_id;
        $forth_oid = "iso.3.6.1.4.1.8886.18.2.1.3.1.1.10." . $big_onu_id;

		//EXECUTE SNMPSET TO ADD ONU
		$session = new SNMP(SNMP::VERSION_2C, $olt_ip_address, $olt_rw);
		if($state == "1") {
			$session->set(array($first_oid, $second_oid, $third_oid, $forth_oid), array('x', 'i', 'i', 'i'), array($snmp_mac_address, $onu_dtype, '4', '1')); 
		} else {
			$session->set(array($first_oid, $second_oid, $third_oid, $forth_oid), array('x', 'i', 'i', 'i'), array($snmp_mac_address, $onu_dtype, '4', '2'));
		}
		if ($session->getError())
			exit(var_dump($session->getError()));
		if ($svr_template) {
			try {
				$result = $db->query("SELECT TEMPLATE_ID from SVR_TEMPLATE where ID = '$svr_template'");
			} catch (PDOException $e) {
				echo "Connection Failed:" . $e->getMessage() . "\n";
				exit;
			}
			while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
				$template_id = $row["TEMPLATE_ID"];
			}
			if ($type == "1")
				$templates_id = $slot_id * 10000000 + $pon_interface;
	        if ($type == "2")
				$templates_id = type2ponid($slot_id, $pon_interface);
			$template_id = $templates_id . "." . $template_id;
			$fifth_oid = "iso.3.6.1.4.1.8886.18.2.6.34.9.1.3." . $template_id;
			$apply_onu_id = 64 - $pon_onu_id + 1;
			$apply_onu_id = str_pad('1', $apply_onu_id, '0');
			//$apply_onu_id = str_pad($apply_onu_id, 64, '0', STR_PAD_LEFT);
			$apply_onu_id = dechex(bindec($apply_onu_id));
			$apply_onu_id = str_pad($apply_onu_id, 16, '0', STR_PAD_LEFT);
			$session = new SNMP(SNMP::VERSION_2C, $olt_ip_address, $olt_rw);
			$session->set($fifth_oid, 'x', $apply_onu_id);
			if ($session->getError())
				exit(var_dump($session->getError()));
		}
		
		if ($max_cpe) {
			$max_cpe_oid = "1.3.6.1.4.1.8886.18.2.1.4.5.1.1." . $big_onu_id;
			$session = new SNMP(SNMP::VERSION_2C, $olt_ip_address, $olt_rw);
			$session->set($max_cpe_oid, 'i', $max_cpe);
			if ($session->getError())
				exit(var_dump($session->getError()));
			
		}

		//CREATE RRD
		$traffic = array("traffic", "unicast", "broadcast", "multicast");
		foreach ($traffic as $tr) {
			$rrd_name = dirname(__FILE__) . "/rrd/" . $olt_ip_address . "_" . $big_onu_id . "_" . $tr . ".rrd";
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

			if( $ret == 0 )
			{
				$err = rrd_error();
				echo "$err";
			}
		}
		for ($i=1; $i <= $ports; $i++) {
			$rrd_name = dirname(__FILE__) . "/rrd/" . $olt_ip_address . "_" . $big_onu_id . "_ethernet_" . $i . ".rrd";
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

			if( $ret == 0 )
			{
				$err = rrd_error();
				echo "$err";
			}
			
			
			
			
		}
		// POWER RRD
		$rrd_name = dirname(__FILE__) . "/rrd/" . $olt_ip_address . "_" . $big_onu_id . "_power.rrd";
		$opts = array( "--step", "300", "--start", 0,
		   "DS:input:GAUGE:600:U:U",
		   "DS:output:GAUGE:600:U:U",
		   "DS:rxolt:GAUGE:600:U:U",
                   "DS:rfin:GAUGE:600:U:U",
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

		if( $ret == 0 )
		{
			$err = rrd_error();
			echo "$err";
		}
		exit("Customer added Succesfully");

	}



	// EDIT CUSTOMER
	if ($customer_id !== '' && $name !== '' && $onu !== '' && $olt !== '' && $pon_port !== '' && $mac_address !== '' && $submit == "EDIT") {
		if ($olt == $old_olt && $pon_port == $old_pon_port) {
			$pon_id = $old_pon_onu_id ;
		} else {
			// FIND FREE ONU_ID
			try {
				$result = $db->query("SELECT PON_ONU_ID from CUSTOMERS where OLT='$olt' and PON_PORT='$pon_port'");
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
        	$pon_id = array_values($arr3)[0];
		}
		//PREPARE MAC ADDRESS
		$separator = array(':', '-', '.');
        $stripped_mac = str_replace($separator, '', $mac_address);

		// CHECK MAC ADDRESS for duplicates
		try {
			$result = $db->query("SELECT MAC_ADDRESS from CUSTOMERS where MAC_ADDRESS = x'$stripped_mac' and ID != '$customer_id'");
	    } catch (PDOException $e) {
			echo "Connection Failed:" . $e->getMessage() . "\n";
			exit;
		}
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
			if ($row["MAC_ADDRESS"])
				exit ("DUPLICATE MAC_ADDRESS");
		}

		// UPDATE CUSTOMER	
		try {
			$result = $db->query("UPDATE CUSTOMERS SET NAME = '$name', ADDRESS = '$address', EGN = '$egn',ONU_MODEL = '$onu', OLT = '$olt', PON_PORT = '$pon_port', PON_ONU_ID = '$pon_id', MAC_ADDRESS = x'$stripped_mac', STATE = '$state', SVR_TEMPLATE = '$svr_template', MAX_CPE = '$max_cpe' where ID = '$customer_id'");
		} catch (PDOException $e) {
			echo "Connection Failed:" . $e->getMessage() . "\n";
			exit;
		}
		// ADD DATETIME in HISTORY
		try {
			$result = $db->query("INSERT INTO HISTORY (CUSTOMERS_ID, DATE, ACTION, MAC_ADDRESS) VALUES ('$customer_id', NOW(), 'Edit Customer', x'$stripped_mac')");
		} catch (PDOException $e) {
			echo "Connection Failed:" . $e->getMessage() . "\n";
			exit;
		}
		//DELETE OLD ONU in OLT
		try {
			$result = $db->query("SELECT INET_NTOA(IP_ADDRESS) as IP_ADDRESS, RW, OLT_MODEL.TYPE from OLT LEFT JOIN OLT_MODEL on OLT.MODEL=OLT_MODEL.ID where OLT.ID='$old_olt'");
		} catch (PDOException $e) {
			echo "Connection Failed:" . $e->getMessage() . "\n";
			exit;
		}
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
			$old_olt_ip_address = $row["IP_ADDRESS"];
			$olt_rw = $row["RW"];
			$type = $row["TYPE"];
		}

		try {
			$result = $db->query("SELECT * from PON where ID='$old_pon_port'");
		} catch (PDOException $e) {
			echo "Connection Failed:" . $e->getMessage() . "\n";
			exit;
		}
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
			$pon_interface = $row["PORT_ID"];
			$slot_id = $row["SLOT_ID"];
		}
		if ($type == "1")
        	$old_big_onu_id = $slot_id * 10000000 + $pon_interface * 100000 + $old_pon_onu_id ;
		if  ($type == "2")
		$old_big_onu_id = type2id($slot_id, $pon_interface, $old_pon_onu_id);
        $destroy_oid = "iso.3.6.1.4.1.8886.18.2.1.3.1.1.9." . $old_big_onu_id;
        $session = new SNMP(SNMP::VERSION_2C, $old_olt_ip_address, $olt_rw);
		$session->set($destroy_oid,'i', '6');
        if ($session->getError())
		var_dump($session->getError());


		// PREPARE SNMPSET TO ADD ONU
		try {
			$result = $db->query("SELECT CUSTOMERS.ID, LPAD(HEX(CUSTOMERS.MAC_ADDRESS), 12, '0') as MAC_ADDRESS, PON_ONU_ID, CUSTOMERS.ONU_MODEL, CUSTOMERS.PON_PORT, CUSTOMERS.OLT, OLT.ID, INET_NTOA(OLT.IP_ADDRESS) as IP_ADDRESS, OLT.RW as RW, OLT_MODEL.TYPE, PON.ID, PON.PORT_ID as PORT_ID, PON.SLOT_ID as SLOT_ID, ONU.ID, ONU.DTYPE as DTYPE, ONU.PORTS as PORTS from CUSTOMERS LEFT JOIN OLT on CUSTOMERS.OLT=OLT.ID LEFT JOIN OLT_MODEL on OLT.MODEL=OLT_MODEL.ID LEFT JOIN PON on CUSTOMERS.PON_PORT=PON.ID LEFT JOIN ONU on CUSTOMERS.ONU_MODEL=ONU.ID where CUSTOMERS.MAC_ADDRESS = x'$stripped_mac'");
		} catch (PDOException $e) {
			echo "Connection Failed:" . $e->getMessage() . "\n";
			exit;
		}
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
			$olt_ip_address = $row["IP_ADDRESS"];
			$olt_rw = $row["RW"];
			$pon_interface = $row["PORT_ID"];
			$onu_dtype = $row["DTYPE"];
			$pon_onu_id = $row["PON_ONU_ID"];
			$snmp_mac_address = $row["MAC_ADDRESS"];
			$slot_id = $row["SLOT_ID"];
			$type = $row["TYPE"];
			$ports = $row["PORTS"];
		}
		if ($type == "1")
        	$big_onu_id = $slot_id * 10000000 + $pon_interface * 100000 + $pon_onu_id ;
        if ($type == "2")
		$big_onu_id = type2id($slot_id, $pon_interface, $pon_onu_id);
        $first_oid = "iso.3.6.1.4.1.8886.18.2.1.3.1.1.2." . $big_onu_id;
        $second_oid = "iso.3.6.1.4.1.8886.18.2.1.3.1.1.3." . $big_onu_id;
        $third_oid = "iso.3.6.1.4.1.8886.18.2.1.3.1.1.9." . $big_onu_id;
        $forth_oid = "iso.3.6.1.4.1.8886.18.2.1.3.1.1.10." . $big_onu_id;
		//EXECUTE SNMPSET TO ADD ONU

        $session = new SNMP(SNMP::VERSION_2C, $olt_ip_address, $olt_rw);
		if ($state == "1") {
			$session->set(array($first_oid, $second_oid, $third_oid, $forth_oid), array('x', 'i', 'i', 'i'), array($snmp_mac_address, $onu_dtype, '4', '1'));
		} else {
			$session->set(array($first_oid, $second_oid, $third_oid, $forth_oid), array('x', 'i', 'i', 'i'), array($snmp_mac_address, $onu_dtype, '4', '2'));
		}
        if ($session->getError())
        	var_dump($session->getError());
		if ($svr_template) {
				try {
				$result = $db->query("SELECT TEMPLATE_ID from SVR_TEMPLATE where ID = '$svr_template'");
			} catch (PDOException $e) {
				echo "Connection Failed:" . $e->getMessage() . "\n";
				exit;
			}
			while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
				$template_id = $row["TEMPLATE_ID"];
			}
			if ($type == "1")
				$templates_id = $slot_id * 10000000 + $pon_interface;
			if ($type == "2")
				$templates_id = type2ponid( $slot_id, $pon_interface);
			$template_id = $templates_id . "." . $template_id;
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
        if ($max_cpe) {
			$max_cpe_oid = "1.3.6.1.4.1.8886.18.2.1.4.5.1.1." . $big_onu_id;
			$session = new SNMP(SNMP::VERSION_2C, $olt_ip_address, $olt_rw);
			$session->set($max_cpe_oid, 'i', $max_cpe);
			if ($session->getError())
				exit(var_dump($session->getError()));
			
		}
	
		//RENAME OLD RRD FILES
		if ($olt != $old_olt || $pon_port != $old_pon_port) {
			$traffic = array("traffic", "unicast", "broadcast", "multicast", "power");
			foreach ($traffic as $tr) {
				$old_rrd_file = dirname(__FILE__) . "/rrd/" . $old_olt_ip_address . "_" . $old_big_onu_id . "_" . $tr . ".rrd";
				$new_rrd_file = dirname(__FILE__) . "/rrd/" . $olt_ip_address . "_" . $big_onu_id . "_" . $tr . ".rrd";
				rename($old_rrd_file, $new_rrd_file);
			}
		}
		if ($ports != $old_onu_ports) {
			for ($i=1; $i <= $old_onu_ports; $i++) {
				$old_rrd_file = dirname(__FILE__) . "/rrd/" . $old_olt_ip_address . "_" . $old_big_onu_id . "_ethernet_" . $i . ".rrd";
				unlink($old_rrd_file);
			}
			for ($i=1; $i <= $ports; $i++) {
				$rrd_name = dirname(__FILE__) . "/rrd/" . $olt_ip_address . "_" . $big_onu_id . "_ethernet_" . $i . ".rrd";
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

				if( $ret == 0 )
				{
					$err = rrd_error();
					echo "$err";
				}

			}
		}
           // POWER RRD
                $rrd_name = dirname(__FILE__) . "/rrd/" . $olt_ip_address . "_" . $big_onu_id . "_power.rrd";
                $opts = array( "--step", "300", "--start", 0,
                   "DS:input:GAUGE:600:U:U",
                   "DS:output:GAUGE:600:U:U",
                   "DS:rxolt:GAUGE:600:U:U",
                   "DS:rfin:GAUGE:600:U:U",
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

                if( $ret == 0 )
                {
                        $err = rrd_error();
                        echo "$err";
                }

       	exit("Customer Edited Succesfully");	
	}

	// DELETE CUSTOMER
	if ($customer_id !== '' && $submit == "DELETE") {
		
		try {
			$result = $db->query("SELECT ID, NAME, ADDRESS, EGN, MAC_ADDRESS from CUSTOMERS where ID='$customer_id'");
		} catch (PDOException $e) {
			echo "Connection Failed:" . $e->getMessage() . "\n";
			exit;
		}
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
			$id = $row["ID"];
			$name = $row["NAME"];
			$address = $row["ADDRESS"];
			$egn = $row["EGN"];
			$mac_address = $row["MAC_ADDRESS"];
		}
		try {
			$result = $db->query("DELETE FROM CUSTOMERS where ID='$customer_id'");
		} catch (PDOException $e) {
			echo "Connection Failed:" . $e->getMessage() . "\n";
			exit;
		}
	// ADD DATETIME in HISTORY
		try {
			$result = $db->query("INSERT INTO HISTORY (CUSTOMERS_ID, DATE, ACTION, MAC_ADDRESS) VALUES ('$customer_id', NOW(), 'Delete Customer $name, $address, $egn', '$mac_address')");
		} catch (PDOException $e) {
			echo "Connection Failed:" . $e->getMessage() . "\n";
			exit;
		}

		//DESTROY ONU in OLT
		try {
			$result = $db->query("SELECT INET_NTOA(IP_ADDRESS) as IP_ADDRESS, RW, OLT_MODEL.TYPE from OLT LEFT JOIN OLT_MODEL on OLT.MODEL=OLT_MODEL.ID where OLT.ID='$old_olt'");
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
			$result = $db->query("SELECT * from PON where ID='$old_pon_port'");
		} catch (PDOException $e) {
			echo "Connection Failed:" . $e->getMessage() . "\n";
			exit;
		}
		while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
			$pon_interface = $row["PORT_ID"];
			$slot_id = $row["SLOT_ID"];
		}

		if ($type == "1")
			$big_onu_id = $slot_id * 10000000 + $pon_interface * 100000 + $old_pon_onu_id ;
		if ($type == "2")
			$big_onu_id = type2id($slot_id, $pon_interface, $old_pon_onu_id);
		$destroy_oid = "iso.3.6.1.4.1.8886.18.2.1.3.1.1.9." . $big_onu_id;
		$session = new SNMP(SNMP::VERSION_2C, $olt_ip_address, $olt_rw);
		$session->set($destroy_oid,'i', '6');
		if ($session->getError())
			var_dump($session->getError());

		//UNLINK RRD FILEs
		$traffic = array("traffic", "unicast", "broadcast", "multicast", "power");
		foreach ($traffic as $tr) {
			$rrd_file = dirname(__FILE__) . "/rrd/" . $olt_ip_address . "_" . $big_onu_id . "_" . $tr . ".rrd";
			unlink($rrd_file);
		}
		for ($i=1; $i <= $old_onu_ports; $i++) {
				$old_rrd_file = dirname(__FILE__) . "/rrd/" . $olt_ip_address . "_" . $big_onu_id . "_ethernet_" . $i . ".rrd";
				unlink($old_rrd_file);
		}
		exit("Customer Deleted Succesfully");
	}
}


if ($_GET) {
	$customer_id = $_GET['id'];
    if (!preg_match('/^[0-9]*$/', $customer_id)) {
		print "that sux";
		exit;
	} else {
		try {
			$result = $db->query("SELECT ID, NAME, ADDRESS, EGN, ONU_MODEL, PON_ONU_ID, OLT, PON_PORT, LPAD(HEX(CUSTOMERS.MAC_ADDRESS), 12, '0') as MAC_ADDRESS, STATE, SVR_TEMPLATE, MAX_CPE from CUSTOMERS where ID='$customer_id'");
        } catch (PDOException $e) {
			echo "Connection Failed:" . $e->getMessage() . "\n";
			exit;
        }
		while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
			$customer_id =  $row["ID"];
			$name = $row["NAME"];
			$address = $row["ADDRESS"];
			$egn = $row["EGN"];
			$onu = $row["ONU_MODEL"];
			$old_pon_onu_id = $row["PON_ONU_ID"];
			$pon_port = $row["PON_PORT"];
			$olt = $row["OLT"];
			$old_pon_port = $row["PON_PORT"];
			$mac_address = $row["MAC_ADDRESS"];
			$state = $row["STATE"];
			$svr_template = $row["SVR_TEMPLATE"];
			$max_cpe = $row["MAX_CPE"];
		}
		
		
	}
}
?>
<center>
<form action="customers.php" method="post">
<?php

if ($customer_id) 
	print "<input type=\"hidden\" name=\"customer_id\" value=\"". $customer_id ."\">";
if ($olt)
        print "<input type=\"hidden\" name=\"old_olt\" value=\"". $olt ."\">";
if ($old_pon_port)
        print "<input type=\"hidden\" name=\"old_pon_port\" value=\"". $old_pon_port ."\">";
if ($old_pon_onu_id)
        print "<input type=\"hidden\" name=\"old_pon_onu_id\" value=\"". $old_pon_onu_id ."\">";

?>
<p><table>
<tr><td>Name*:</td><td><input type="text" name="name" <?php if($name) print "value=\"".$name ."\""; ?>></td>
<?php if($nameErr != "") print "<td style=\"color:red\">" . $nameErr . "</td>"; ?>
</tr>
<tr><td>Address:</td><td><input type="text" name="address" <?php if($address) print "value=\"".$address ."\""; ?>></td>
</tr>
<tr><td>EGN:</td><td><input type="text" name="egn" maxlength="10" size="10" <?php if($egn) print "value=\"".$egn ."\""; ?>></td>
<?php if($egnErr != "") print "<td style=\"color:red\">" . $egnErr . "</td>"; ?>
</tr>
<?php
if (isset($_GET["edit"])) {
	$edit = $_GET["edit"];
} else {
	$edit = NULL;
}
if ($edit == "1" || $submit == "EDIT") {
	try {
		$result = $db->query("SELECT * from OLT where ID='$olt'");
	} catch (PDOException $e) {
		echo "Connection Failed:" . $e->getMessage() . "\n";
		exit;
	}
	
	while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
		$olt_name = $row['NAME'];
	}
	print "<input  id=\"select-olt\" type=\"hidden\" name=\"olt\" value=\"". $olt ."\">";
	print "<tr><td>OLT*:</td><td>" . $olt_name . "</td>";
}else{
	print "<tr><td>OLT*:</td><td><select id=\"select-olt\" name=\"olt\">";
	print "<option value=\"\">---</option>";
	try {
		$result = $db->query("SELECT * from OLT");
	} catch (PDOException $e) {
		echo "Connection Failed:" . $e->getMessage() . "\n";
		exit;
	}

	while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
		if($olt == $row{'ID'}) {
			print "<option value=\"" . $row{'ID'} ."\" selected>" . $row{'NAME'} . "</option>";
		} else {
			print "<option value=\"" . $row{'ID'} ."\">" . $row{'NAME'} . "</option>";
		}
	}


	print "</select></div></td>";
	if($oltErr != "") 
		print "<td style=\"color:red\">" . $oltErr . "</td>";
}

print "</tr>";
print "<tr><td>PON PORT*:</td><td><select id=\"select-pon\" name=\"pon_port\">";
print "<option value=\"\">Select OLT</option>";

if ($edit == "1" || $_POST) {
	try {
		$result2 = $db->query("SELECT * from PON where OLT='$olt' order by SLOT_ID, PORT_ID");
   	} catch (PDOException $e) {
		 echo "Connection Failed:" . $e->getMessage() . "\n";
		 exit;
	}
	while ($row2 = $result2->fetch(PDO::FETCH_ASSOC)) {
		if($pon_port == $row2{'ID'}) {
			echo "<option value=\"" . $row2{'ID'} ."\" selected>" . $row2{'NAME'} ." === ". $row2{'SLOT_ID'} ."/" . $row2{'PORT_ID'} ."</option>";
        } else {
			echo "<option value=\"" . $row2{'ID'} ."\">" . $row2{'NAME'} ." === ". $row2{'SLOT_ID'} ."/" . $row2{'PORT_ID'} ."</option>";
		}
	}
}
?>
</select></td>
<?php if($ponErr != "") print "<td style=\"color:red\">" . $ponErr . "</td>"; ?>
</tr>

<tr><td>ONU*:</td><td><select id="select-onu" name="onu">
<option value="" class="rhth">---</option>
<?php
try {
	$result = $db->query("SELECT * from ONU");
} catch (PDOException $e) {
	echo "Connection Failed:" . $e->getMessage() . "\n";
	exit; 
}
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
	if($onu == $row{'ID'}) {
		print "<option value=\"" . $row{'ID'} ."\" selected>" . $row{'NAME'} . "</option>";
		$old_onu_ports = $row{'PORTS'};
	} else {
		print "<option value=\"" . $row{'ID'} ."\">" . $row{'NAME'} . "</option>";
	}
	
}

print "</select></div></td>";
print "<input type=\"hidden\" name=\"old_onu_ports\" value=\"". $old_onu_ports ."\">";

if($onuErr != "") 
	print "<td style=\"color:red\">" . $onuErr . "</td>"; 

?>
<tr><td>SVR Template:</td><td><select id="svr-template" name="svr_template">
<option value="">---------</option>
<?php

if ($edit == "1" || $_POST) {
	try {
		$result2 = $db->query("SELECT ONU.ID, SVR_TEMPLATE.NAME, SVR_TEMPLATE.ID as SVR_ID from ONU LEFT JOIN SVR_TEMPLATE on ONU.PORTS=SVR_TEMPLATE.PORTS where ONU.ID='$onu' AND SVR_TEMPLATE.OLT='$olt'");
	} catch (PDOException $e) {
		echo "Connection Failed:" . $e->getMessage() . "\n";
		exit;
	}

	while ($row2 = $result2->fetch(PDO::FETCH_ASSOC)) {
		if($svr_template == $row2{'SVR_ID'}) {
			echo "<option value=\"" . $row2{'SVR_ID'} ."\" selected>" . $row2{'NAME'} ."</option>";
        } else {
			echo "<option value=\"" . $row2{'SVR_ID'} ."\">" . $row2{'NAME'} ."</option>";
		}
	}
}
?>
</select></td>
</tr>
<tr><td>ONU MAC*:</td><td><input type="text" name="mac_address"  maxlength="17" size="17" <?php if($mac_address&&$_GET["edit"]) { print "value=\"".implode(':', str_split($mac_address,2))."\""; }else{ print "value=\"" . $mac_address ."\""; }?>></td>
<?php if($macErr != "") print "<td style=\"color:red\">" . $macErr . "</td>"; ?>
</tr>
<tr><td>Active:</td><td><input type="checkbox" name="state" value="1" <?php if($state == "1"||!($edit)) print " checked"; ?>></td></tr>
<tr><td>MAX CPE:</td><td><input type="text" name="max_cpe" maxlength="4" size="4" <?php if($max_cpe) print "value=\"".$max_cpe ."\""; ?>> (1-8190)</td>
<?php if($max_cpeErr != "") print "<td style=\"color:red\">" . $max_cpeErr . "</td>"; ?>

</tr></table></p>
<?php

if ($edit == "1" || $customer_id) {
	print "<input type='submit' name='SUBMIT' value='EDIT'>";
	print "&nbsp;&nbsp;&nbsp;<input type='submit' name='SUBMIT' value='DELETE'>";
}else{
	print "<input type='submit' name='SUBMIT' value='ADD'>";
}
print "</form><br><br><br>";
if ($customer_id) {
	print "History:<br>";
	try {
		$result = $db->query("SELECT DATE, ACTION, LPAD(HEX(MAC_ADDRESS), 12, '0') as MAC_ADDRESS from HISTORY where CUSTOMERS_ID='$customer_id'");
	} catch (PDOException $e) {
		echo "Connection Failed:" . $e->getMessage() . "\n";
		exit;
	}
	print "<table border=1 cellspacing=0>";
	print "<tr><td>Date</td><td>Action</td><td>ONU Mac</td></tr>";
	while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
		print "<tr><td>" . $row['DATE'] . "</td><td>" . $row['ACTION'] . "</td><td>" . $row['MAC_ADDRESS'] . "</td></tr>";
	}
	print "</table></center>";

}






?>


