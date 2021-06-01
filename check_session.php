<?php
session_start ();
if(!isset($_SESSION["login"])) header("location:login.php"); 
$readerId = $_SESSION["readerId"];

// check if readerId is integer - if not destroy session
if (!ctype_digit($readerId)) {
    session_destroy();
    header("location:login.php"); 
}
?>
