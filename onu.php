<?php
include ("common.php");
include ("dbconnect.php");
navigation();
if ($user_class < "9")
	exit();

$nameErr = $portsErr = $dtypeErr = "";
$rf = $pse = $name = $ports = $onu_id = $dtype = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
	if ($_POST["onu_id"]) {
		$onu_id = test_input($_POST["onu_id"]);
	}
	if (empty($_POST["name"])) {
		$nameErr = "Name is required";
	} else {
		$name = test_input($_POST["name"]);
	}

	if (empty($_POST["ports"])) {
		$portsErr = "Number of Ports Required!";
	} else {
		$ports = test_input($_POST["ports"]);
	}

	if (empty($_POST["dtype"])) {
		$dtypeErr = "Device type is required";
	} else {
		$dtype =  test_input($_POST["dtype"]);
	}
	if (isset($_POST["rf"])) {
		$rf = "1";
	}else{
		$rf = "0";
	}
	if (isset($_POST["pse"])) {
		$pse = "1";
	}else{
		$pse = "0";
	}
	if ($_POST["SUBMIT"]) {
		$submit = test_input($_POST["SUBMIT"]);
	}
	
	// ADD ONU
	if ($name !== '' && $ports !== ''  && $dtype !== '' && $submit == "ADD") {
		try {
			$result = $db->query("INSERT INTO ONU (NAME, PORTS, DTYPE, RF, PSE) VALUES ('$name', '$ports', '$dtype', '$rf', '$pse')");
		} catch (PDOException $e) {
			echo "Connection Failed:" . $e->getMessage() . "\n";
	        exit;
		}
		exit("ONU added Succesfully");
	}

	// EDIT ONU
	if ($onu_id !== '' && $name !== '' && $ports !== '' && $dtype !== '' && $submit == "EDIT") {
		// UPDATE ONU
        try {
			$result = $db->query("UPDATE ONU SET NAME = '$name', PORTS = '$ports', DTYPE = '$dtype', RF = '$rf', PSE = '$pse' where ID = '$onu_id'");
		} catch (PDOException $e) {
			echo "Connection Failed:" . $e->getMessage() . "\n";
			exit;
		}
		exit("ONU Edited Succesfully");
	}
	
	// DELETE ONU
	if ($onu_id !== '' && $submit == "DELETE") {
		// CHECK IF ONU IS ASSIGNED TO ANY CUSTOMER
		try {
			$result = $db->query("SELECT ONU_MODEL from CUSTOMERS where ONU_MODEL =  '$onu_id'");
		} catch (PDOException $e) {
			echo "Connection Failed:" . $e->getMessage() . "\n";
			exit;
		}
		while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
			if ($row["ONU_MODEL"])
				exit ("ONU IS ASSIGNED TO CUSTOMERS, Please remove ONU from customers to Delete it!");
		}
		
		try {
			$result = $db->query("DELETE FROM ONU where ID='$onu_id'");
		} catch (PDOException $e) {
			echo "Connection Failed:" . $e->getMessage() . "\n";
			exit;
		}
		exit("ONU Deleted Succesfully");
	}
}

// BUILD EXISTING TABLE
try {
	$result = $db->query("SELECT ID, NAME, PORTS, DTYPE, RF, PSE from ONU");
} catch (PDOException $e) {
	echo "Connection Failed:" . $e->getMessage() . "\n";
	exit;
}

print "<p><center>ONU Configuration</p>";
print "<table border=1 cellpadding=1 cellspacing=1><tr align=center style=font-weight:bold><td>ID</td><td>NAME</td><td>PORTS</td><td>DEVICE TYPE</td><td>RF</td><td>PSE</td></tr>";

while($row = $result->fetch(PDO::FETCH_ASSOC)) {
	print "<tr align=right><td><a href='onu.php?edit=1&id=". $row{'ID'} . "'>" . $row{'ID'} . "</a></td><td>" . $row{'NAME'} . "</td><td>" . $row{'PORTS'} . "</td><td>" . $row{'DTYPE'} . "</td><td>" . $row{'RF'} . "</td><td>" . $row{'PSE'} ."</td></tr>";
}
print "</table>";
if ($_GET) {
	$onu_id = $_GET['id'];
	if (!preg_match('/^[0-9]*$/', $onu_id)) {
		print "that sux";
        exit;
	} else {
		try {
			$result = $db->query("SELECT ID, NAME, PORTS, DTYPE, RF, PSE from ONU where ID='$onu_id'");
		} catch (PDOException $e) {
			echo "Connection Failed:" . $e->getMessage() . "\n";
			exit;
		}
		while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
			$onu_id =  $row["ID"];
			$name = $row["NAME"];
			$ports = $row["PORTS"];
			$dtype = $row["DTYPE"];
			$rf = $row["RF"];
			$pse = $row["PSE"];
		}
	}
}

print "<form action=\"onu.php\" method=\"post\">";
if ($onu_id)
	print "<input type=\"hidden\" name=\"onu_id\" value=\"". $onu_id ."\">";
?>
<p><table>
<tr><td>Name*:</td><td><input type="text" name="name" <?php if($name) print "value=\"".$name ."\""; ?>></td>
<?php if($nameErr != "") print "<td style=\"color:red\">" . $nameErr . "</td>"; ?>
</tr>
<tr><td>PORTS*:</td><td><input type="text" name="ports" <?php if($name) print "value=\"".$ports ."\""; ?>></td>
<?php if($portsErr != "") print "<td style=\"color:red\">" . $portsErr . "</td>"; ?>
</tr>
<tr><td>DTYPE*:</td><td><input type="text" name="dtype" <?php if($dtype) print "value=\"".$dtype ."\""; ?>></td>
<?php if($dtypeErr != "") print "<td style=\"color:red\">" . $dtypeErr . "</td>"; ?>
</tr>
<tr><td>RF:</td><td><input type="checkbox" name="rf" value="1" <?php if($rf == "1") print " checked"; ?>></td>
<tr><td>PSE:</td><td><input type="checkbox" name="pse" value="1" <?php if($pse == "1") print " checked"; ?>></td>


</table></p>
<?php
if (isset($_GET["edit"])) {
        $edit = $_GET["edit"];
} else {
        $edit = NULL;
}

if ($edit == "1" || $onu_id) {
	print "<input type='submit' name='SUBMIT' value='EDIT'>";
	print "&nbsp;&nbsp;&nbsp;<input type='submit' name='SUBMIT' value='DELETE'>";
}else{
	print "<input type='submit' name='SUBMIT' value='ADD'>";
}
print "</center>";
?>
