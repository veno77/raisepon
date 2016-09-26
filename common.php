<?php
header('Content-Type: text/html; charset=utf-8');
print "<link rel=\"stylesheet\" type=\"text/css\" href=\"style.css\">";


?>
<script src="./jquery-1.9.0.js"></script>
<script>
$(function() {
        $("#select-olt").change(function() {
                $("#select-pon").load("get.php?choice=" + $("#select-olt").val());
        });
});

$(function() {
        $("#select-olt-2").change(function() {
                $("#select-pon-2").load("get.php?choice=" + $("#select-olt-2").val());
        });
});


$(function() {
        $("#select-onu").change(function() {
                $("#svr-template").load("get_template.php?choice=" + $("#select-onu").val() + "&olt=" + $("#select-olt").val());
        });
});

$(function() {
$("#selectall").click(function () {
var checkAll = $("#selectall").prop('checked');
    if (checkAll) {
        $(".case").prop("checked", true);
    } else {
        $(".case").prop("checked", false);
    }
});
});

$(function() {
$("tr#hover").hover(
  function () {
    $(this).css("background","#E6E6FA");
  }, 
  function () {
    $(this).css("background","");
  }
);
});

//  End -->
</script>

<?php

session_start();
if (!isset($_SESSION["id"])) {
header("Location: login.php");
}
$user_class = $_SESSION["type"];
$pon_dropdown = array();

function navigation() {
	global $user_class;
	print "<body>";
	print "<center><img src=\"pic/logo.png\">";
	print "<p><a href=\"index.php\">HOME</a> :: ";
	print "<a href=\"customers.php\">CUSTOMERS</a> :: ";
	if ($user_class >= "6") {
		print "<a href=\"olt.php\">OLT</a> :: ";
		print "<a href=\"pon.php\">PON PORTS</a> :: ";
		print "<a href=\"onu.php\">ONU</a> :: ";
		print "<a href=\"templates.php\">TEMPLATES</a> :: ";
	}
	print "<a href=\"graphs.php\">GRAPHS</a> :: ";
	print "<a href=\"logs.php\">LOGS</a> <BR> ";
	print "<a href=\"mac_trace.php\">MAC_TRACE</a> :: ";
	if ($user_class == "9")
		print "<a href=\"accounts.php\">ACCOUNTS</a> :: ";
	print "<a href=\"logout.php\">LOGOUT</a> ";
	print "</p></center>";
	print "<hr style=\"width:800;height:1px\">";
}

function test_input($data) {
	$data = trim($data);
	$data = stripslashes($data);
	$data = htmlspecialchars($data);
	return $data;
}

function type2id($slot, $pon_port, $onu_id) {
        $vif = "0001";
        $slot = str_pad(decbin($slot),5, "0", STR_PAD_LEFT);
        $pon_port = str_pad(decbin($pon_port), 6, "0", STR_PAD_LEFT);
        $onu_id = str_pad(decbin($onu_id), 16, "0", STR_PAD_LEFT);
        $big_onu_id = bindec($vif . $slot . "0" . $pon_port . $onu_id);
        return $big_onu_id;
}

function type2ponid ($slot, $pon_port) {
        $slot = decbin($slot);
        $pon_port = str_pad(decbin($pon_port), 6, "0", STR_PAD_LEFT);
        $pon_id = bindec($slot . $pon_port);
        return $pon_id;
}

?>
