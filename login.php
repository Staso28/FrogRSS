<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2 Final//EN">
<html>
<head>
	<title>FrogRSS Login</title>
</head>
<body link="blue" vlink="blue" alink="blue">
<center>
<h2>Welcome to <b>FrogRSS</b>!</h2>
This is a RSS aggregator built for using on vintage computers.
<br>No Javascript, no SSL - just pure HTML and some images :)<p>
It is based on ideas brought by excelent site <a href="http://frogfind.com">FrogFind!</a> and <a href="http://68k.news">68k.news</a>!
<h3>Login to FrogRSS</h3>
<form action="loginprocess.php" method="POST">
<table>
<tr><td>Username:</td><td><input type="text" required="" name="uname"></td></tr>
<tr><td>Password:</td><td><input type="password" required="" name="upassword"></td></tr>
</table>
<input type="submit" value="Login" name="sub">
<br>
<?php 
if(isset($_REQUEST["err"]))
	$msg="Invalid username or Password";
?>
<p style="color:red;">
<?php
if(isset($msg)) {
	echo $msg;
}
?>

</p>
</form>
<a href="register.php">Register</a>
</center>
</body>
</html>

