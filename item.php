<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2 Final//EN">
<html>
<head>
	<title>FrogRSS Article</title>
</head>
<body link="blue" vlink="blue" alink="blue">
<?php
require_once("dbconn.php");
require_once("functions.php");
require_once("check_session.php");

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (isset($_GET['feedId'])) {
	$feedId = sprintf("%d",$_GET['feedId']);
} else {
	echo "Required feedId missing";
	exit;
}

if (isset($_GET['itemId'])) {
	$itemId = $_GET['itemId'];
} else {
	echo "Required itemId missing";
	exit;
}

// ##############################################
function find_next ( $oper, $date, $feedId, $dbh, $readerId) {
	$sort = ($oper == ">") ? "ASC" : "DESC";
	$cmd = "SELECT id ".
		"FROM items i ".
		"JOIN reader_items ri ON ri.readerId=? and ri.feedId=i.feedId and ri.itemId=i.id ".
		"WHERE ri.feedId=? and ri.status<>'deleted' AND i.publishDate ".$oper." ? ".
		"ORDER BY publishDate ".$sort;
    $stm = $dbh->prepare($cmd);
    $stm->bind_param('iis', $readerId, $feedId, $date);
    $stm->execute();
    $res = $stm->get_result();
	$row = $res->fetch_assoc();
	return $row["id"];
}
// ##############################################

// moznosti:
// u - set unread
// d - delete
$action = "";
if (isset($_GET['action'])) $action = $_GET['action'];
// set item as unread
if ($action == "u") {
	$cmd = "UPDATE reader_items SET status='unread' WHERE readerId=? AND feedId=? and itemId=?";
    $stm = $dbh->prepare($cmd);
    $stm->bind_param('iis', $readerId, $feedId, $itemId);
    $ok = $stm->execute();
	if (!$ok) echo "Warning: Unable to set as unread. readerId:".$readerId." feedId:".$feedId." itemId:".$itemId;
	else {
		echo "Article set as unread sucessfully.<p><a href=\"show_items.php?feedId=".$feedId."\">Back to the feed...</a></body></html>";
		exit;
	}
}
// delete readers item
if ($action == "d") {
	$cmd = "UPDATE reader_items SET status='deleted' WHERE readerId=? AND feedId=? and itemId=?";
    $stm = $dbh->prepare($cmd);
    $stm->bind_param('iis', $readerId, $feedId, $itemId);
    $ok = $stm->execute();
	if (!$ok) echo "Warning: Unable to delete. readerId:".$readerId." feedId:".$feedId." itemId:".$itemId;
	else {
		echo "Article deleted.<p><a href=\"show_items.php?feedId=".$feedId."\">Back to the feed...</a></body></html>";
		exit;
	}
}

// display article
$stm = $dbh->prepare("SELECT name FROM feeds WHERE id = ?");
$stm->bind_param('i', $feedId);
$stm->execute();
$res = $stm->get_result();
$row = $res->fetch_assoc();
echo "<h2>".$row["name"]."</h2>";
echo _fB()."<a href=\"show_items.php?feedId=".$feedId."\">&lt;&lt; Back to the feed</a>"._fE();

// select item data
$cmd = "SELECT i.*, ri.status+0 as status FROM items i JOIN reader_items ri ON ri.readerId=? and ri.feedId=i.feedId and ri.itemId=i.id WHERE i.id=? and i.feedId=?";
$stm = $dbh->prepare($cmd);
$stm->bind_param('isi', $readerId, $itemId, $feedId);
$stm->execute();
$res = $stm->get_result();
$stm->close();
$row = $res->fetch_assoc();

// set item as read
if ($row["status"] == RI_UNREAD) {
	$cmd = "UPDATE reader_items SET status='read' WHERE readerId=? AND feedId=? and itemId=? and status='unread'";
    $stm = $dbh->prepare($cmd);
    $stm->bind_param('iis', $readerId, $feedId, $itemId);
    $ok = $stm->execute();
	if (!$ok) echo "Warning: Unable to set as read. readerId:".$readerId." feedId:".$feedId." itemId:".$itemId;
    $stm->close();
}

// display item
echo "<hr><h3><a href=\"http://frogfind.com/read.php?a=".$row["permalink"]."\">".$row["title"]."</a></h3>";
echo "<table width=\"100%\">".
    "<tr><td>".
        _fB()."Actions: <a href=\"item.php?feedId=".$feedId."&itemId=".$itemId."&action=u\">[ Mark as unread ]</a> ".
        "<a href=\"item.php?feedId=".$feedId."&itemId=".$itemId."&action=d\">[ Delete article ]</a>"._fE()."</td>".
        "<td align=\"right\">"._fB();
// previous article
$prevItemId = find_next('<', $row["publishDate"], $feedId, $dbh, $readerId);
if ($prevItemId) echo "<a href=\"item.php?feedId=".$feedId."&itemId=".$prevItemId."\">[ &lt;&lt; Older article ]</a> ";
// next article
$nextItemId = find_next('>', $row["publishDate"], $feedId, $dbh, $readerId);
if ($nextItemId) echo "<a href=\"item.php?feedId=".$feedId."&itemId=".$nextItemId."\">[ Newer article &gt;&gt; ]</a> ";
echo _fE()."</td></tr></table>";

echo "<small>Original URL: <a href=\"".$row["permalink"]."\" target=\"_blank\" rel=\"noopener noreferrer\">".$row["permalink"]."</a></small>";

// show thumbnail
if ($row["thumbnail"]) { echo "<p><img src=\"img.php?i=".$row["thumbnail"]."\" /></p>"; }
// article
echo "<p>"._fB().$row["description"]._fE()."<p><small>Posted on:".$row["publishDate"]."</small>";
// footer
_footer();
?>
</body></html>
