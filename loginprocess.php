<?php
session_start();

require_once("dbconn.php");
require_once("functions.php");

if(isset($_REQUEST['sub']))
{
	$a = $_REQUEST['uname'];
	$b = $_REQUEST['upassword'];
    // check login name
    if (!_check_db_input($a)) {
		header("location:login.php?err=1");
        exit;
    }
    // hash password
    $b = sha1($b);

	$stm = $dbh->prepare("select id from readers where login=? and cpasswd=?");
    $stm->bind_param('ss', $a, $b);
    $stm->execute();
    $res = $stm->get_result();
	
	$result=$res->fetch_assoc();
	if($result) {
		$_SESSION["login"]="1";
		$_SESSION["readerId"]=$result["id"];
		header("location:feeds.php");
	} else {
		header("location:login.php?err=1");
	}
}
?>
