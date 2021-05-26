<?php
session_start ();
if(!isset($_SESSION["login"])) header("location:login.php"); 
$readerId = $_SESSION["readerId"];
?>
