<?php
include ("common.php");
include ("dbconnect.php");
navigation();
if ($_GET) {
        $id = $_GET['id'];
        if (!preg_match('/^[0-9]*$/', $id)) {
        print "that sux";
        exit;
        } else {
try {
$result = $db->query("SELECT CUSTOMERS.ID, CUSTOMERS.SVR_TEMPLATE, CUSTOMERS.STATE, CUSTOMERS.NAME, CUSTOMERS.ADDRESS, LPAD(HEX(CUSTOMERS.MAC_ADDRESS), 12, '0') as MAC_ADDRESS, ONU.NAME as ONU_NAME, OLT.NAME as OLT_NAME, INET_NTOA(OLT.IP_ADDRESS) as IP_ADDRESS, OLT.RO as RO, OLT_MODEL.TYPE, PON.NAME as PON_NAME, PON.PORT_ID as PORT_ID, PON.SLOT_ID as SLOT_ID, PON_ONU_ID, SVR_TEMPLATE.NAME as SVR_NAME from CUSTOMERS LEFT JOIN ONU on CUSTOMERS.ONU_MODEL=ONU.ID LEFT JOIN OLT on CUSTOMERS.OLT=OLT.ID LEFT JOIN OLT_MODEL on OLT.MODEL=OLT_MODEL.ID LEFT JOIN PON on CUSTOMERS.PON_PORT=PON.ID LEFT JOIN SVR_TEMPLATE on CUSTOMERS.SVR_TEMPLATE=SVR_TEMPLATE.ID where CUSTOMERS.ID=$id");
} catch (PDOException $e) {
        echo "Connection Failed:" . $e->getMessage() . "\n";
        exit;
}

while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
	if($row{'TYPE'} == "1")
       		$big_onu_id = $row{'SLOT_ID'} * 10000000 + $row{'PORT_ID'} * 100000 + $row{'PON_ONU_ID'};
	if($row{'TYPE'} == '2')
		$big_onu_id = type2id($row{'SLOT_ID'}, $row{'PORT_ID'}, $row{'PON_ONU_ID'});
        $olt_ip_address = $row["IP_ADDRESS"];
        $rrd_name = dirname(__FILE__) . "/rrd/" . $olt_ip_address . "_" . $big_onu_id . "_traffic.rrd";

  $opts = array( "--start", "-1d", "--lower-limit=0", "--vertical-label=B/s", "--title=Daily Traffic",
                 "DEF:inoctets=$rrd_name:input:AVERAGE",
                 "DEF:outoctets=$rrd_name:output:AVERAGE",
                 "AREA:inoctets#00FF00:In traffic",
                 "LINE1:outoctets#0000FF:Out traffic\\r",
                 "CDEF:inbits=inoctets",
                 "CDEF:outbits=outoctets",
                 "GPRINT:inbits:LAST:Last In\: %6.2lf %sBps",
                 "GPRINT:inbits:AVERAGE:Avg In\: %6.2lf %sBps",
                 "COMMENT:  ",
                 "GPRINT:inbits:MAX:Max In\: %6.2lf %sBps\\r",
                 "COMMENT:\\n",
                 "GPRINT:outbits:LAST:Last Out\: %6.2lf %sBps",
                 "GPRINT:outbits:AVERAGE:Avg Out\: %6.2lf %sBps",
                 "COMMENT: ",
                 "GPRINT:outbits:MAX:Max Out\: %6.2lf %sBps\\r"
               );
  $opts2 = array( "--start", "-1w", "--lower-limit=0", "--vertical-label=B/s", "--title=Weekly Traffic",
                 "DEF:inoctets=$rrd_name:input:AVERAGE",
                 "DEF:outoctets=$rrd_name:output:AVERAGE",
                 "AREA:inoctets#00FF00:In traffic",
                 "LINE1:outoctets#0000FF:Out traffic\\r",
                 "CDEF:inbits=inoctets",
                 "CDEF:outbits=outoctets",
                 "GPRINT:inbits:LAST:Last In\: %6.2lf %sBps",
                 "GPRINT:inbits:AVERAGE:Avg In\: %6.2lf %sBps",
                 "COMMENT:  ",
                 "GPRINT:inbits:MAX:Max In\: %6.2lf %sBps\\r",
                 "COMMENT:\\n",
                 "GPRINT:outbits:LAST:Last Out\: %6.2lf %sBps",
                 "GPRINT:outbits:AVERAGE:Avg Out\: %6.2lf %sBps",
                 "COMMENT: ",
                 "GPRINT:outbits:MAX:Max Out\: %6.2lf %sBps\\r"
               );
  $opts3 = array( "--start", "-1m", "--lower-limit=0", "--vertical-label=B/s", "--title=Monthly Traffic",
                 "DEF:inoctets=$rrd_name:input:AVERAGE",
                 "DEF:outoctets=$rrd_name:output:AVERAGE",
                 "AREA:inoctets#00FF00:In traffic",
                 "LINE1:outoctets#0000FF:Out traffic\\r",
                 "CDEF:inbits=inoctets",
                 "CDEF:outbits=outoctets",
                 "GPRINT:inbits:LAST:Last In\: %6.2lf %sBps",
                 "GPRINT:inbits:AVERAGE:Avg In\: %6.2lf %sBps",
                 "COMMENT:  ",
                 "GPRINT:inbits:MAX:Max In\: %6.2lf %sBps\\r",
                 "COMMENT:\\n",
                 "GPRINT:outbits:LAST:Last Out\: %6.2lf %sBps",
                 "GPRINT:outbits:AVERAGE:Avg Out\: %6.2lf %sBps",
                 "COMMENT: ",
                 "GPRINT:outbits:MAX:Max Out\: %6.2lf %sBps\\r"
               );
  $rrd_traffic_url = $olt_ip_address . "_" . $big_onu_id . "_traffic.gif";
  $rrd_traffic_url_week = $olt_ip_address . "_" . $big_onu_id . "_traffic_week.gif";
  $rrd_traffic_url_month = $olt_ip_address . "_" . $big_onu_id . "_traffic_month.gif";
  $rrd_traffic = dirname(__FILE__) . "/rrd/" . $rrd_traffic_url;
  $rrd_traffic_week = dirname(__FILE__) . "/rrd/" . $rrd_traffic_url_week;
  $rrd_traffic_month = dirname(__FILE__) . "/rrd/" . $rrd_traffic_url_month;

  $ret = rrd_graph($rrd_traffic, $opts);
  $ret = rrd_graph($rrd_traffic_week, $opts2);
  $ret = rrd_graph($rrd_traffic_month, $opts3);

  if( !is_array($ret) )
  {
    $err = rrd_error();
    echo "rrd_graph() ERROR: $err\n";
  }
}
print "<center><h2>RRD Traffic Graphs for ONU: $olt_ip_address :: $big_onu_id</h2> ";
print "<p><img src=\"rrd/" . $rrd_traffic_url . "\"></img></p>";
print "<p><img src=\"rrd/" . $rrd_traffic_url_week . "\"></img></p>";
print "<p><img src=\"rrd/" . $rrd_traffic_url_month . "\"></img></p>";
}
}
?>


