<?php
include ("common.php");
include ("dbconnect.php");

$onu_rx_power = $big_onu_id = $customer_id = $type = $rf_status = "";


if ($_SERVER["REQUEST_METHOD"] == "POST") {
	if (isset($_POST["customer_id"])) {
                $customer_id = test_input($_POST["customer_id"]);
        }
	if ($_POST["type"]) {
                $type = test_input($_POST["type"]);
        }
	if ($_POST["type"] == "Reboot") {
		try {
                        $result = $db->query("SELECT CUSTOMERS.ID, CUSTOMERS.NAME as NAME, LPAD(HEX(CUSTOMERS.MAC_ADDRESS), 12, '0') as MAC_ADDRESS, PON_ONU_ID, CUSTOMERS.ONU_MODEL, CUSTOMERS.PON_PORT, CUSTOMERS.OLT, CUSTOMERS.STATE as STATE, CUSTOMERS.SVR_TEMPLATE as SVR_TEMPLATE, OLT.ID, INET_NTOA(OLT.IP_ADDRESS) as IP_ADDRESS, OLT.NAME as OLT_NAME, OLT.RO as RO, OLT.RW as RW, OLT_MODEL.TYPE, PON.ID as PON_ID, PON.PORT_ID as PORT_ID, PON.SLOT_ID as SLOT_ID, ONU.ID, ONU.PORTS as ONU_PORTS, ONU.RF as RF, ONU.PSE as PSE from CUSTOMERS LEFT JOIN OLT on CUSTOMERS.OLT=OLT.ID LEFT JOIN OLT_MODEL on OLT.MODEL=OLT_MODEL.ID LEFT JOIN PON on CUSTOMERS.PON_PORT=PON.ID LEFT JOIN ONU on CUSTOMERS.ONU_MODEL=ONU.ID
where CUSTOMERS.ID = '$customer_id'");
                } catch (PDOException $e) {
                        echo "Connection Failed:" . $e->getMessage() . "\n";
                        exit;
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

                $index = $slot_id * 10000000 + $port_id * 100000 + $pon_onu_id;
                $reboot_oid = "1.3.6.1.4.1.8886.18.2.6.1.3.1.1." . $index;
                snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
                $session = new SNMP(SNMP::VERSION_2C, $ip_address, $rw);
                $reboot = $session->set($reboot_oid, 'i', '1');
                if ($session->getError())
                        exit(var_dump($session->getError()));
        print "<center>Onu Rebooted Succesfully</center>";

        }


	if ($type == "info"){
		try {
			$result = $db->query("SELECT CUSTOMERS.ID, CUSTOMERS.NAME as NAME, CUSTOMERS.ADDRESS, LPAD(HEX(CUSTOMERS.MAC_ADDRESS), 12, '0') as MAC_ADDRESS, PON_ONU_ID, CUSTOMERS.ONU_MODEL, CUSTOMERS.PON_PORT, CUSTOMERS.OLT, CUSTOMERS.STATE as STATE, CUSTOMERS.SVR_TEMPLATE as SVR_TEMPLATE, OLT.ID, INET_NTOA(OLT.IP_ADDRESS) as IP_ADDRESS, OLT.NAME as OLT_NAME, OLT.RO as RO, OLT.RW as RW, OLT_MODEL.TYPE, PON.ID as PON_ID, PON.PORT_ID as PORT_ID, PON.SLOT_ID as SLOT_ID, ONU.ID, ONU.PORTS as ONU_PORTS, ONU.RF as RF, ONU.PSE as PSE, SVR_TEMPLATE.NAME as SVR_NAME from CUSTOMERS LEFT JOIN OLT on CUSTOMERS.OLT=OLT.ID LEFT JOIN OLT_MODEL on OLT.MODEL=OLT_MODEL.ID LEFT JOIN PON on CUSTOMERS.PON_PORT=PON.ID LEFT JOIN ONU on CUSTOMERS.ONU_MODEL=ONU.ID LEFT JOIN SVR_TEMPLATE on CUSTOMERS.SVR_TEMPLATE=SVR_TEMPLATE.ID where CUSTOMERS.ID = '$customer_id'");
                } catch (PDOException $e) {
                        echo "Connection Failed:" . $e->getMessage() . "\n";
                        exit;
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
			$svr_template = $row['SVR_NAME'];
			$customer_address = $row['ADDRESS'];
		}
		$index = $slot_id * 10000000 + $port_id * 100000 + $pon_onu_id;

	        if($olt_type == '1') {
			$big_onu_id = $slot_id * 10000000 + $port_id * 100000 + $pon_onu_id;
                	$onu_rx_power_oid = "iso.3.6.1.4.1.8886.18.2.8.1.2.1.2.5." . $big_onu_id;
			$onu_tx_power_oid = "iso.3.6.1.4.1.8886.18.2.8.1.2.1.2.4." . $big_onu_id;

        	}
        	if($olt_type == '2') {
        		$big_onu_id = type2id($slot_id, $port_id, $pon_onu_id);
			$big_onu_id_2 = $slot_id * 10000000 + $port_id * 100000 + $pon_onu_id;
                	$onu_rx_power_oid = "iso.3.6.1.4.1.8886.18.2.8.1.2.1.2.5." . $big_onu_id_2;
                        $onu_tx_power_oid = "iso.3.6.1.4.1.8886.18.2.8.1.2.1.2.4." . $big_onu_id_2;
        	}

		$version_oid = "1.3.6.1.4.1.8886.18.2.6.1.1.1.6." . $index;
		$firmware_oid = "1.3.6.1.4.1.8886.18.2.6.1.1.1.7." . $index;
		$device_type_oid = "1.3.6.1.4.1.8886.18.2.6.1.1.1.12." . $index;
		$last_online_oid = "iso.3.6.1.4.1.8886.18.2.1.3.1.1.7." . $big_onu_id;
		$offline_reason_oid = "iso.3.6.1.4.1.8886.18.2.1.3.1.1.17." . $big_onu_id;
		snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
		$session = new SNMP(SNMP::VERSION_2C, $ip_address, $ro);
		$device_type = $session->get($device_type_oid);
		$version = $session->get($version_oid);
		$firmware = $session->get($firmware_oid);
//LAST ONLINE
		$last_online = "Never";
		snmp_set_valueretrieval(SNMP_VALUE_LIBRARY);
		$session = new SNMP(SNMP::VERSION_2C, $ip_address, $ro);
		$last_online = $session->get($last_online_oid);
		$last_online = str_replace('Hex-STRING: ', '', $last_online);
		$loa = explode(' ', $last_online);
		$year = $loa[0] . $loa[1];
		$year = hexdec($year);
		$month = hexdec($loa[2]);
		$day = hexdec($loa[3]);
		$hour = hexdec($loa[4]);
		$hour = str_pad($hour, 2, '0', STR_PAD_LEFT);
		$minute = hexdec($loa[5]);
		$minute = str_pad($minute, 2, '0', STR_PAD_LEFT);
		$last_online = $year . "-". $month . "-". $day . "  " . $hour . ":" . $minute ;
//OFFLINE REASON
                snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
                $session = new SNMP(SNMP::VERSION_2C, $ip_address, $ro);
	        $offline_reason = $session->get($offline_reason_oid);
		if ($offline_reason == '1') {
			$offline_reason = "unknown(1)" ;
		} else if($offline_reason == '2') {
			$offline_reason = "dyingGasp(2)" ;
		} else if($offline_reason == '3') {
			$offline_reason = "backboneFiberCut(3)" ;
		} else if($offline_reason == '4') {
			$offline_reason = "branchFiberCut(4)" ;
		} else if($offline_reason == '5') {
			$offline_reason = "oamDisconnect(5)" ;
		} else if($offline_reason == '6') {
			$offline_reason = "duplicateReg(6)" ;
		} else if ($offline_reason == '7') {
			$offline_reason = "oltDeregOperation(7)" ;
		}
//ONU RX POWER
		$onu_rx_power = $session->get($onu_rx_power_oid);
                $onu_rx_power = round(10*log10($onu_rx_power/10000),2) . " dBm";
//ONU TX POWER
                $onu_tx_power = $session->get($onu_tx_power_oid);
                $onu_tx_power = "+" . round(10*log10($onu_tx_power/10000),2) . " dBm";

//CONSTRUCT TABLE
		print "<table border=1>";
                print "<tr><td>Customer Address:</td><td>" . $customer_address . "</td></tr>";

		print "<tr><td>Device Type:</td><td>" . $device_type . "</td></tr>";
		print "<tr><td>Software version:</td><td>" . $version . "</td></tr>";
		print "<tr><td>Firmware version:</td><td>" . $firmware . "</td></tr>";
		if ($svr_template !== '0')
                	print "<tr><td>Service Template:</td><td>" . $svr_template . "</td></tr>";
                print "<tr><td>Last Online:</td><td>" . $last_online . "</td></tr>";
                print "<tr><td>Offline Reason:</td><td>" . $offline_reason . "</td></tr>";
                print "<tr><td>ONU RxPower:</td><td>" . $onu_rx_power . "</td></tr>";
                print "<tr><td>ONU TxPower:</td><td>" . $onu_tx_power . "</td></tr>";
		print "</table>";

		print "<BR><BR><form action=\"onu_info.php\" method=\"post\">";
                print "<input type=\"hidden\" name=\"customer_id\" value=\"". $customer_id ."\">";
		print "<p><input type='submit' name='type' value='Reboot'></p>";
		print "</form>";

	}


        if ($type == "ports"){
		 try {
                        $result = $db->query("SELECT CUSTOMERS.ID, CUSTOMERS.NAME as NAME, LPAD(HEX(CUSTOMERS.MAC_ADDRESS), 12, '0') as MAC_ADDRESS, PON_ONU_ID, CUSTOMERS.ONU_MODEL, CUSTOMERS.PON_PORT, CUSTOMERS.OLT, CUSTOMERS.STATE as STATE, CUSTOMERS.SVR_TEMPLATE as SVR_TEMPLATE, OLT.ID, INET_NTOA(OLT.IP_ADDRESS) as IP_ADDRESS, OLT.NAME as OLT_NAME, OLT.RO as RO, OLT.RW as RW, OLT_MODEL.TYPE, PON.ID as PON_ID, PON.PORT_ID as PORT_ID, PON.SLOT_ID as SLOT_ID, ONU.ID, ONU.PORTS as ONU_PORTS, ONU.RF as RF, ONU.PSE as PSE from CUSTOMERS LEFT JOIN OLT on CUSTOMERS.OLT=OLT.ID LEFT JOIN OLT_MODEL on OLT.MODEL=OLT_MODEL.ID LEFT JOIN PON on CUSTOMERS.PON_PORT=PON.ID LEFT JOIN ONU on CUSTOMERS.ONU_MODEL=ONU.ID
where CUSTOMERS.ID = '$customer_id'");
                } catch (PDOException $e) {
                        echo "Connection Failed:" . $e->getMessage() . "\n";
                        exit;
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
		print "<center><table border=1 cellpadding=1 cellspacing=1><tr align=center style=font-weight:bold><td>UNI</td><td>Admin</td><td>Link</td><td>Flow Control</td><td>Speed/Duplex</td><td>Auto-Neg</td><td>Isolation</td></tr>";
		$index = $slot_id * 10000000 + $port_id * 100000 + $pon_onu_id * 1000;
		for ($i = 1; $i <= $onu_ports ; $i++) {
			$gindex = $index + $i;
			$port_link_oid = "1.3.6.1.4.1.8886.18.2.6.3.1.1.2." . $gindex;
			$port_admin_oid = "1.3.6.1.4.1.8886.18.2.6.3.1.1.3." . $gindex;
			$port_autong_oid = "1.3.6.1.4.1.8886.18.2.6.3.1.1.5." . $gindex;
			$port_flowctrl_oid = "1.3.6.1.4.1.8886.18.2.6.3.1.1.10." . $gindex;
			$port_speed_duplex_oid = "1.3.6.1.4.1.8886.18.2.6.3.2.1.2." . $gindex;
			$port_isolation_oid = "1.3.6.1.4.1.8886.18.2.6.3.2.1.4." . $gindex;

			snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
			$session = new SNMP(SNMP::VERSION_2C, $ip_address, $ro);
			$port_admin = $session->get($port_admin_oid);
			$port_link = $session->get($port_link_oid);
			$port_autong = $session->get($port_autong_oid);
			$port_flowctrl = $session->get($port_flowctrl_oid);
			$port_speed_duplex = $session->get($port_speed_duplex_oid);
			$port_isolation = $session->get($port_isolation_oid);
			if ($port_admin == '1') {
				$port_admin = "<font color=red>Disabled</font>";
			} else if ($port_admin == '2') {
				$port_admin = "<font color=green>Enabled</font>";
			} else if ($port_admin == '0') {
				$port_admin = "Unknown";
			}

			if ($port_link == '1') {
				$port_link = "<font color=red>Down</font>";
			} else if ($port_link == '2') {
				$port_link = "<font color=green>Up</font>";
			} else if ($port_link == '0') {
				$port_link = "Unknown";
			}
			if ($port_autong == '1') {
				$port_autong = "<font color=red>Disabled</font>";
			} else if ($port_autong == '2') {
				$port_autong = "<font color=green>Enabled</font>";
			} else if ($port_autong == '0') {
				$port_autong = "Unknown";
			}
			if ($port_flowctrl == '1') {
				$port_flowctrl = "<font color=red>Disabled</font>";
			} else if ($port_flowctrl == '2') {
				$port_flowctrl = "<font color=green>Enabled</font>";
			} else if ($port_flowctrl == '0') {
				$port_flowctrl = "Unknown";
			}
			if ($port_speed_duplex == '1') {
				$port_speed_duplex = "Unknown";
			} else if ($port_speed_duplex == '2') {
				$port_speed_duplex = "half_10";
			} else if ($port_speed_duplex == '3') {
				$port_speed_duplex = "full_10";
			} else if ($port_speed_duplex == '4') {
			$port_speed_duplex = "half_100";
			} else if ($port_speed_duplex == '5') {
				$port_speed_duplex = "full_100";
			} else if ($port_speed_duplex == '6') {
				$port_speed_duplex = "half_1000";
			} else if ($port_speed_duplex == '7') {
				$port_speed_duplex = "full_1000";
			} else if ($port_speed_duplex == '99') {
				$port_speed_duplex = "illegal";
			}
			if ($port_isolation == '1') {
				$port_isolation = "<font color=green>enabled</font>";
			} else if ($port_isolation = '2') {
                                $port_isolation = "<font color=red>disabled</font>";
			}	
			print "<tr  align=center><td>" . $i . "</td><td>" . $port_admin . "</td><td>" . $port_link .  "</td><td>" . $port_flowctrl . "</td><td>" . $port_speed_duplex . "</td><td>" . $port_autong . "</td><td>" . $port_isolation . "</td></tr>";
		}
		print "</table>";
		print "<BR><BR><form action=\"onu_details.php\" method=\"post\">";
		print "<input type=\"hidden\" name=\"customer_id\" value=\"". $customer_id ."\">";
		print "<center></center>";

	}

	if ($type == "graphs"){
		try {
			$result = $db->query("SELECT CUSTOMERS.ID, CUSTOMERS.SVR_TEMPLATE, CUSTOMERS.STATE, CUSTOMERS.NAME, CUSTOMERS.ADDRESS, LPAD(HEX(CUSTOMERS.MAC_ADDRESS), 12, '0') as MAC_ADDRESS, ONU.NAME as ONU_NAME, ONU.PORTS as PORTS, ONU.RF as RF, OLT.NAME as OLT_NAME, INET_NTOA(OLT.IP_ADDRESS) as IP_ADDRESS, OLT.RO as RO, OLT_MODEL.TYPE, PON.NAME as PON_NAME, PON.PORT_ID as PORT_ID, PON.SLOT_ID as SLOT_ID, PON_ONU_ID, SVR_TEMPLATE.NAME as SVR_NAME from CUSTOMERS LEFT JOIN ONU on CUSTOMERS.ONU_MODEL=ONU.ID LEFT JOIN OLT on CUSTOMERS.OLT=OLT.ID LEFT JOIN OLT_MODEL on OLT.MODEL=OLT_MODEL.ID LEFT JOIN PON on CUSTOMERS.PON_PORT=PON.ID LEFT JOIN SVR_TEMPLATE on CUSTOMERS.SVR_TEMPLATE=SVR_TEMPLATE.ID where CUSTOMERS.ID=$customer_id");
			} catch (PDOException $e) {
				echo "Connection Failed:" . $e->getMessage() . "\n";
				exit;
		}
		while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
			$ports = $row{'PORTS'};
			$rf = $row{'RF'};
			$customer_name = $row{'NAME'};
			$olt_name = $row{'OLT_NAME'};
			$mac_address = $row["MAC_ADDRESS"];

			if ($row{'TYPE'} == "1")
			$big_onu_id = $row{'SLOT_ID'} * 10000000 + $row{'PORT_ID'} * 100000 + $row{'PON_ONU_ID'};
			if ($row{'TYPE'} == "2")
			$big_onu_id = type2id($row{'SLOT_ID'}, $row{'PORT_ID'}, $row{'PON_ONU_ID'});
			$olt_ip_address = $row["IP_ADDRESS"];
			$rrd_name = dirname(__FILE__) . "/rrd/" . $olt_ip_address . "_" . $big_onu_id . "_traffic.rrd";
			$rrd_power = dirname(__FILE__) . "/rrd/" . $olt_ip_address . "_" . $big_onu_id . "_power.rrd";

			$opts = array( "--start", "-1d", "--lower-limit=0", "--vertical-label=B/s", "--title=Daily Traffic",
			"DEF:inoctets=$rrd_name:input:AVERAGE",
			"DEF:outoctets=$rrd_name:output:AVERAGE",
			"AREA:inoctets#00FF00:In traffic",
			"LINE1:outoctets#0000FF:Out traffic\\r",
			"CDEF:inbits=inoctets",
			"CDEF:outbits=outoctets",
			"GPRINT:inbits:LAST:Last In\: %6.2lf %SBps",
                        "GPRINT:inbits:AVERAGE:Avg In\: %6.2lf %SBps",
                        "COMMENT:  ",
                        "GPRINT:inbits:MAX:Max In\: %6.2lf %SBps\\r",
                        "COMMENT:\\n",
                        "GPRINT:outbits:LAST:Last Out\: %6.2lf %SBps",
                        "GPRINT:outbits:AVERAGE:Avg Out\: %6.2lf %SBps",
                        "COMMENT: ",
                        "GPRINT:outbits:MAX:Max Out\: %6.2lf %SBps\\r"
			);
			$pkts = array("unicast", "broadcast", "multicast");
                        foreach ($pkts as $tr) {
                        	$$tr = dirname(__FILE__) . "/rrd/" . $olt_ip_address . "_" . $big_onu_id . "_" . $tr . ".rrd";
                                ${$tr."_opts"} = array( "--start", "-1d", "--lower-limit=0", "--vertical-label=Pkts/s", "--title=Daily $tr",
                                "DEF:inoctets=${$tr}:input:AVERAGE",
                                "DEF:outoctets=${$tr}:output:AVERAGE",
                                "AREA:inoctets#00FF00:In",
                                "LINE1:outoctets#0000FF:Out\\r",
                                "CDEF:inbits=inoctets",
                                "CDEF:outbits=outoctets",
                                "GPRINT:inbits:LAST:Last In\: %6.0lf pkts/s",
                                "COMMENT:  ",
                                "GPRINT:inbits:MAX:Max In\: %6.0lf pkts/s\\r",
                                "COMMENT:\\n",
                                "GPRINT:outbits:LAST:Last Out\: %6.0lf pkts/s",
                                "COMMENT: ",
                                "GPRINT:outbits:MAX:Max Out\: %6.0lf pkts/s\\r"
                              	);
                        }

                        for ($i=1; $i <= $row{'PORTS'}; $i++) {
                                $octets_ethernet = dirname(__FILE__) . "/rrd/" . $olt_ip_address . "_" . $big_onu_id . "_ethernet_" . $i . ".rrd";
                                ${$i."_opts"} = array( "--start", "-1d", "--lower-limit=0", "--vertical-label=B/s", "--title=Daily Traffic Ethernet Port $i",
                                "DEF:inoctets=$octets_ethernet:input:AVERAGE",
                                "DEF:outoctets=$octets_ethernet:output:AVERAGE",
                                "AREA:inoctets#00FF00:In traffic",
                                "LINE1:outoctets#0000FF:Out traffic\\r",
                                "CDEF:inbits=inoctets",
                                "CDEF:outbits=outoctets",
                                "GPRINT:inbits:LAST:Last In\: %6.2lf %SBps",
                                "GPRINT:inbits:AVERAGE:Avg In\: %6.2lf %SBps",
                                "COMMENT:  ",
                                "GPRINT:inbits:MAX:Max In\: %6.2lf %SBps\\r",
                                "COMMENT:\\n",
                                "GPRINT:outbits:LAST:Last Out\: %6.2lf %SBps",
                                "GPRINT:outbits:AVERAGE:Avg Out\: %6.2lf %SBps",
                                "COMMENT: ",
                                "GPRINT:outbits:MAX:Max Out\: %6.2lf %SBps\\r"
                                );
                                ${$i."_url"} = $olt_ip_address . "_" . $big_onu_id . "_ethernet_" . $i . ".gif";
                                ${$i."_gif"} = dirname(__FILE__) . "/rrd/" . $olt_ip_address . "_" . $big_onu_id . "_ethernet_" . $i . ".gif";
                                $ret = rrd_graph(${$i."_gif"}, ${$i."_opts"});
                        }
			if ($rf == "1") {
                                $opts4 = array( "--start", "-1d", "--vertical-label=dBm", "--title=Daily Power",
                                "DEF:inoctets=$rrd_power:input:AVERAGE",
                                "DEF:outoctets=$rrd_power:output:AVERAGE",
                                "DEF:rx_olt=$rrd_power:rxolt:AVERAGE",
                                "DEF:rf_in=$rrd_power:rfin:AVERAGE",
                                "LINE2:rx_olt#D6213B:RX@OLT",
                                "GPRINT:rx_olt:LAST:Last\: %6.2lf dBm\\r",
                                "LINE2:outoctets#C6913B:TX@ONU",
                                "GPRINT:outoctets:LAST:Last\: %6.2lf dBm\\r",
                                "LINE2:inoctets#7FB37C:RX@ONU",
                                "GPRINT:inoctets:LAST:Last\: %6.2lf dBm\\r",
                                "LINE2:rf_in#FFD87C:RF@ONU",
                                "GPRINT:rf_in:LAST:Last\: %6.2lf dBm\\r",
                                );
                        } else {
                                $opts4 = array( "--start", "-1d", "--vertical-label=dBm", "--title=Daily Power",
                                "DEF:inoctets=$rrd_power:input:AVERAGE",
                                "DEF:outoctets=$rrd_power:output:AVERAGE",
                                "DEF:rx_olt=$rrd_power:rxolt:AVERAGE",
                                "LINE2:rx_olt#D6213B:RX@OLT",
                                "GPRINT:rx_olt:LAST:Last\: %6.2lf dBm\\r",
                                "LINE2:outoctets#C6913B:TX@ONU",
                                "GPRINT:outoctets:LAST:Last\: %6.2lf dBm\\r",
                                "LINE2:inoctets#7FB37C:RX@ONU",
                                "GPRINT:inoctets:LAST:Last\: %6.2lf dBm\\r",
                                );
                        }

                        $rrd_traffic_url = $olt_ip_address . "_" . $big_onu_id . "_traffic.gif";
                        $unicast_url =  $olt_ip_address . "_" . $big_onu_id . "_unicast.gif";
			$broadcast_url =  $olt_ip_address . "_" . $big_onu_id . "_broadcast.gif";
			$multicast_url =  $olt_ip_address . "_" . $big_onu_id . "_multicast.gif";
                        $rrd_power_url = $olt_ip_address . "_" . $big_onu_id . "_power.gif";
                        $rrd_traffic = dirname(__FILE__) . "/rrd/" . $olt_ip_address . "_" . $big_onu_id . "_traffic.gif";
                        $rrd_power = dirname(__FILE__) . "/rrd/" . $olt_ip_address . "_" . $big_onu_id . "_power.gif";
                        $unicast = dirname(__FILE__) . "/rrd/" . $olt_ip_address . "_" . $big_onu_id . "_unicast.gif";
                        $broadcast = dirname(__FILE__) . "/rrd/" . $olt_ip_address . "_" . $big_onu_id . "_broadcast.gif";
                        $multicast = dirname(__FILE__) . "/rrd/" . $olt_ip_address . "_" . $big_onu_id . "_multicast.gif";
                        $ret = rrd_graph($rrd_traffic, $opts);
                        $ret = rrd_graph($rrd_power, $opts4);
                        $ret = rrd_graph($unicast, $unicast_opts);
			$ret = rrd_graph($broadcast, $broadcast_opts);
			$ret = rrd_graph($multicast, $multicast_opts);


			if( !is_array($ret) )
			{
				$err = rrd_error();
				echo "rrd_graph() ERROR: $err\n";
			}

		}
		print "<center><h2>RRD Graphs for <font color=blue> $customer_name </font> @ OLT::$olt_name ONU::$big_onu_id MAC::$mac_address</h2> ";
		print "<table>";
		print "<tr><td><p><a href=\"graph_traffic.php?id=" . $customer_id . "\"><img src=\"rrd/" . $rrd_traffic_url . "\"></img></a></p></td>";
		$end = "1";
		for ($i=1; $i <= $ports; $i++) {
			$name = ${$i."_url"};
			print "<td><p><a href=\"graph_onu_ethernet_ports.php?id=" . $customer_id . "&port=" . $i . "\"><img src=\"rrd/" . $name . "\"></img></a></p></td>";
			$end++;
			if ($end == "2") {
				$end = "0";
				print "</tr><tr>";
			}
        	}
		print "</tr>";
		print "<tr><td><p><a href=\"graph_packets.php?id=" . $customer_id . "&type=unicast\"><img src=\"rrd/" . $unicast_url . "\"></img></a></p></td>";
		print "<td><p><a href=\"graph_packets.php?id=" . $customer_id . "&type=broadcast\"><img src=\"rrd/" . $broadcast_url . "\"></img></a></p></td></tr>";
		print "<tr><td><p><a href=\"graph_packets.php?id=" . $customer_id . "&type=multicast\"><img src=\"rrd/" . $multicast_url . "\"></img></a></p></td>";
		print "<td><p><a href=\"graph_power.php?id=" . $customer_id . "\"><img src=\"rrd/" . $rrd_power_url . "\"></img></a></p></td></tr>";
		print "<table>";
        }

}
?>

