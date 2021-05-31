<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2 Final//EN">
<html>
<head>
	<title>FrogRSS Feed</title>
</head>
<body link="blue" vlink="blue" alink="blue">
<?php
require_once("functions.php");
require_once("dbconn.php");
require_once("check_session.php");

if (isset($_GET['feedId'])) {
	$feedId = $_GET['feedId'];
} else {
	echo "Required feedId missing";
	exit;
}

$action = "";
if (isset($_GET['action'])) {
	$action = $_GET['action'];
}

$cmd = "SELECT f.name, rf.categoryId, f.fetchTime, c.name as catName ".
	"FROM feeds f ".
	"INNER JOIN reader_feeds rf ON f.id=rf.feedId AND rf.readerId=".$readerId." ".
	"LEFT JOIN categories c ON rf.categoryId=c.id AND rf.readerId=c.readerId ".
	"WHERE f.id = ".$feedId;
$res = $dbh->query($cmd);
$row = $res->fetch_assoc();
$feedName = $row["name"];
$feedCatId = $row["categoryId"];
$feedFetchTime = $row["fetchTime"];
$categoryName = $row["catName"];

if ($action == "all_read") {
	$cmd = "UPDATE reader_items SET status='read' WHERE readerId=".$readerId." and feedId=".$feedId." and status='unread'";
	$ok = $dbh->query($cmd);
	if ($ok) {
		echo "All articles marked as Read sucessfully.<p>";
	    echo "<font size=\"4\"><a href=\"feeds.php#cat_".$feedCatId."\">&lt;&lt; Back to feed list</a></font></body></html>";
	} else {
		echo "Error: Unable to mark all articles.";
	}	
	exit;
}
else if ($action == "all_unread") {
	$cmd = "UPDATE reader_items SET status='unread' WHERE readerId=".$readerId." and feedId=".$feedId." and status='read'";
	$ok = $dbh->query($cmd);
	if ($ok) {
		echo "All articles marked as Unread sucessfully.<p><a href=\"show_items.php?feedId=".$feedId."\">&lt;&lt; Back to the feed...</a></body></html>";
	} else {
		echo "Error: Unable to mark all articles.";
	}	
	exit;
}
// unsubscribe from feed
else if ($action == "unsub") {
	echo "Do you really want to unsubscribe from feed <b>".$feedName."</b> ?<p>";
	echo "<a href=\"show_items.php?feedId=".$feedId."\">[ No, go back to Feed ]</a> ";
	echo "<a href=\"show_items.php?feedId=".$feedId."&action=unsub_ok\">[ Yes, remove Feed ]</a> ";
	exit;
}
// really unsubscibe from feed :-)
else if ($action == "unsub_ok") {
	$cmd = "DELETE FROM reader_items WHERE feedId=".$feedId." and readerId=".$readerId;
	$ok1 = $dbh->query($cmd);
	$cmd = "DELETE FROM reader_feeds WHERE feedId=".$feedId." and readerId=".$readerId;
	$ok2 = $dbh->query($cmd);
	if ($ok1 and $ok2) {
		echo "Sucessfully unsubscribed...<p>";
	} else {
		echo "Error occured...<p>";
	}
	echo "<a href=\"feeds.php#cat_".$feedCatId."\">&lt;&lt; Back to feed list</a>";
	exit;
}


echo "<h2>".$feedName."</h2>";


// move feed to a new category
if (isset($_REQUEST['doChangeCat'])) {
	$newCategoryId = $_REQUEST['newCat'];
	$cmd = "update reader_feeds set categoryId=".$newCategoryId." where feedId=".$feedId." and readerId=".$readerId;
	$ok = $dbh->query($cmd);
	if ($ok) {
		echo "Feed sucessfully moved to new category.<p>";
		echo "<a href=\"feeds.php#cat_".$newCategoryId."\">&lt;&lt; Back to feed list</a>";
		echo "</body></html>";
		exit;
	}
}

echo "<font size=\"4\">";
echo "<a href=\"feeds.php#cat_".$feedCatId."\">&lt;&lt; Back to feed list</a>";
echo "</font>\n";

// show dialog for choosing new category
if ($action == "catChangeReq") {
	echo "<p><font size=\"4\">".
		"<form method=\"POST\" action=\"show_items.php?feedId=".$feedId."\">".
		"Current category: <b>".$categoryName."</b><p>".
		"New category: <select name=\"newCat\">".
		_combo_options_categories($dbh, $readerId).
		"</select> ".
		"<input type=\"submit\" name=\"doChangeCat\" value=\"Save\">";
		echo "</body></html>";
		exit;
}

echo "<p>";
echo "<table width=\"100%\"><tr><td><font size=\"4\">";
echo "Actions: ";
echo "<a href=\"show_items.php?feedId=".$feedId."&action=all_read\">[ Mark all as Read ]</a> ";
echo "<a href=\"show_items.php?feedId=".$feedId."&action=all_unread\">[ Mark all as Unread ]</a>";
echo "</font></td><td align=\"right\"><font size=\"4\">";
echo "<a href=\"show_items.php?feedId=".$feedId."&action=unsub\">[ Unsubscribe from Feed ]</a> ";
echo "<a href=\"fetch_items.php?feedId=".$feedId."\">[ Fetch new articles ]</a>";
echo "</font></td></tr></table>\n";
echo "<p>";

echo "<table width=\"100%\"><tr>".
	"<td><small>Category: <b>".$categoryName."</b> (<a href=\"show_items.php?feedId=".$feedId."&action=catChangeReq\">Change</a>)</small></td>".
	"<td align=\"right\"><small>Last refresh: ".$feedFetchTime."</small></td>".
	"</tr></table><p>";
echo "<font size=\"4\">";

//TODO: zobrazit posledny cas kedy boli stiahnute clanky pre tento feed - last fetch time

$cmd = "SELECT i.id,i.permalink,i.title,i.publishDate,ri.status+0 as status ".
	"FROM items i ".
	"LEFT JOIN reader_items ri ON ri.readerId=".$readerId." and i.feedId=ri.feedId and i.id=ri.itemId ".
	"WHERE i.feedId=".$feedId." AND ri.status<>'deleted' ORDER BY ri.status, i.publishDate DESC";
$res = $dbh->query($cmd);

$rist="";
while ($row = $res->fetch_assoc()) {
	if ($rist == "") {
		if ($row["status"] == RI_UNREAD) echo "Unread articles:\n<ol>";
		if ($row["status"] == RI_READ) echo "Read articles:\n<ol>";
		$rist = $row["status"];
    }
	if ($rist != "" and $rist != $row["status"]) {
		echo "</ol><p>Read articles:<ol>";
		$rist = $row["status"];
	}

	echo "<li>";
	$col = ($row["status"] == RI_UNREAD) ? ("darkgreen") : ("blue");
	if ($row["status"] == RI_UNREAD)  echo "<b>";
	echo "<a href=\"item.php?feedId=".$feedId."&itemId=".$row["id"]."\"><font color=\"$col\">".$row["title"]."</font></a> | ";
	if ($row["status"] == RI_UNREAD) echo "</b>";
	echo "<a href=\"http://frogfind.com/read.php?a=".$row["permalink"]."\" target=\"_blank\" rel=\"noopener noreferrer\" title=\"Open using FrogFind!\">[F]</a> ";
	echo "<a href=\"".$row["permalink"]."\" target=\"_blank\" rel=\"noopener noreferrer\" title=\"Open original URL\">[O]</a> ";
	echo "<small>(posted on ".$row["publishDate"].")</small> ";
	echo "</li>\n";
}
if($rist) echo "</ol>";
else echo "<p>Oh...I'm sorry. No articles found. Try to fetch new articles with the link top right...";
?>
</font>
<?php _footer(); ?>
</body></html>
