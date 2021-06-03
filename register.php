<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2 Final//EN">
<html>
<head>
	<title>FrogRSS User registration</title>
</head>
<body link="blue" vlink="blue" alink="blue">
<center>

<h2>FrogRSS registration</h2>

<?php
require_once("dbconn.php");
require_once("functions.php");

function _err($str) {
	echo "<font color=\"red\">".$str."</font>";
	echo "<p>";
	return 0;
}

function _set_starting_content($dbh, $readerId) {
	$data = [
		[ "Mac stuff", [ 33, 36, 48 ] ],
		[ "PC stuff", [ 60 ] ],
		[ "World news", [ 45 ] ]
	];

	$catOrder = 1;
	foreach ($data as $c):
		$cmd = "insert into categories set id=NULL, readerId=".$readerId.", name='".$c[0]."', ordering=".$catOrder;
		$catOrder++;
		$dbh->query($cmd);
		$ncId = $dbh->insert_id;
		foreach ($c[1] as $f):
			$cmd = "insert into reader_feeds set readerId=".$readerId.", feedId=".$f.", categoryId=".$ncId;
			$dbh->query($cmd);
		endforeach;
	endforeach;
	_populate_reader_items($dbh);
}

if(isset($_REQUEST["doReg"])) {
	$regLogin = $_REQUEST["regLogin"];
	$regPassw = $_REQUEST["regPassw"];
	$regPassw2 = $_REQUEST["regPassw2"];

	$ok = 1;

	if (strlen($regLogin) < 4) $ok = _err("Login name too short");
	if (strlen($regLogin) > 20) $ok = _err("Login name too long");

	if (strlen($regPassw) < 4) $ok = _err("Password too short");
	if ($regPassw == $regLogin) $ok = _err("Can't use the same password as is your logname.");
	if ($regPassw != $regPassw2) $ok = _err("Password mismatch.");

	if ($ok) {
        $cpass = sha1($regPassw);
		$stm = $dbh->prepare("INSERT INTO readers SET id=NULL, login=?, cpasswd=?");
        $stm->bind_param('ss', $regLogin, $cpass);
        $db_ok = $stm->execute();
		if ($db_ok) {
			$readerId = $dbh->insert_id;
			_set_starting_content($dbh, $readerId);
			echo "Registration sucessfull. <p>You can now <a href=\"login.php\">log in</a> and enjoy FrogRSS !!!</body></html>";
			exit;
		} else {
			echo "Something went wrong. Please try again later....</body></html>";
			exit;
		}
	}
}
?>

<form action="register.php" method="POST">
<p><small>Warning: There is no real security on this simple page.
<br>Please choose a simple password which you are not using for anything else critical. Thanks.
<p>
<table>
<tr>
<td>Login name: </td>
<td><input type="text" name="regLogin" size="30" value="<?php if ($regLogin) echo $regLogin; ?>" > <small>(min. 4 characters)</small></td>
</tr>
<tr>
<td>Password: </td>
<td><input type="password" name="regPassw" size="20" value="" > <small>(min. 5 characters)</small></td>
</tr>
<tr>
<td>Retype password:</td>
<td><input type="password" name="regPassw2" size="20" value="" > <small>(min. 5 characters)</small></td>
</tr>
</table>
<p>
<input type="submit" name="doReg" value="Create new account">
</form>
</center>
</body>
</html>
