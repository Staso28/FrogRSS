<?php
session_start();

require_once("dbconn.php");

if(isset($_REQUEST['sub']))
{
	$a = $_REQUEST['uname'];
	$b = $_REQUEST['upassword'];
	
	$cmd = "select id from readers where login='".$a."'and cpasswd='".sha1($b)."'";
//echo "cmd:".$cmd;
	$res = $dbh->query($cmd);
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
