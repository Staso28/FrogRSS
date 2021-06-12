<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2 Final//EN">
<html>
<head>
	<title>FrogRSS User profile</title>
</head>
<body link="blue" vlink="blue" alink="blue">
<?php
require_once("functions.php");
require_once("dbconn.php");
require_once("check_session.php");
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
?>
<h2>My profile</h2>
<p>
<font size="4">
<a href="feeds.php">&lt;&lt; Back to feeds</a>
</font>
<p>
<h3>Change password</h3>
<?php
if (isset($_REQUEST['doChange'])) {
	$oPass = $_REQUEST['oPass'];
	$nPass = $_REQUEST['nPass'];
	$nPass2 = $_REQUEST['nPass2'];

	$ok = 1;

	if (strlen($oPass) == 0) $ok = _err("Please enter old password");
	if (strlen($nPass) < 4) $ok = _err("New password too short");
	if ($nPass != $nPass2) $ok = _err("New passwords do not match");

	// check old pass
	if ($ok) {
		$pass = sha1($oPass);
		$stm = $dbh->prepare("select login from readers where id=? and cpasswd=?");
		$stm->bind_param('is', $readerId, $pass);
		$stm->execute();
		$res = $stm->get_result();
		$row = $res->fetch_assoc();
		$stm->close();
		if (!$row) $ok = _err("Old password not correct");
		elseif ($row["login"] == $nPass) $ok = _err("Can't use the same password as is your logname.");
	}

	// all OK, change password
	if ($ok) {
		$passNew = sha1($nPass);
		$stm = $dbh->prepare("update readers set cpasswd=? where id=?");
		$stm->bind_param('si', $passNew, $readerId);
		$db_ok = $stm->execute();
		if ($db_ok) {
			echo "<p><font color=\"green\">Password successfully changed</font>";
		}
	} 
}
?>
<form method="POST">
<table>
<tr><td>Current password:</td><td><input type="password" size="20" name="oPass"></td></tr>
<tr><td>New password:</td><td><input type="password" size="20" name="nPass"></td></tr>
<tr><td>Retype new password:</td><td><input type="password" size="20" name="nPass2"></td></tr>
<tr><td colspan="2" align="center"><input type="submit" name="doChange" value="Change password"></td></tr>
</table>
</form>
<?php _footer(); ?>
</body>
</html>
