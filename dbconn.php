<?php
$db_host = "46.229.230.164";
$db_user = "do1509000";
$db_passw = "tawgislac";
$db_name = "do1509000db";

$dbh = new mysqli($db_host, $db_user, $db_passw, $db_name);

// reader_items.status values
define("RI_UNREAD", 1);
define("RI_READ", 2);
define("RI_DELETED", 3);

?>
