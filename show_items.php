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

//mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (isset($_GET['feedId'])) {
	$feedId = sprintf("%d",$_GET['feedId']);
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
	"INNER JOIN reader_feeds rf ON f.id=rf.feedId AND rf.readerId=? ".
	"LEFT JOIN categories c ON rf.categoryId=c.id AND rf.readerId=c.readerId ".
	"WHERE f.id=?";
$stm = $dbh->prepare($cmd);
$stm->bind_param('ii', $readerId, $feedId);
$stm->execute();
$res = $stm->get_result();
$stm->close();

$row = $res->fetch_assoc();
$feedName = $row["name"];
$feedCatId = $row["categoryId"];
$feedFetchTime = $row["fetchTime"];
$categoryName = $row["catName"];

if ($action == "all_read") {
	$stm = $dbh->prepare("UPDATE reader_items SET status='read' WHERE readerId=? and feedId=? and status='unread'");
    $stm->bind_param('ii', $readerId, $feedId);
    $ok = $stm->execute();
	if ($ok) {
        $stm->close();
		echo "All articles marked as Read sucessfully.<p>";
	    echo _fB()."<a href=\"feeds.php#cat_".$feedCatId."\">&lt;&lt; Back to feed list</a>"._fE()."</body></html>";
	} else {
		echo "Error: Unable to mark all articles.";
	}	
	exit;
}
else if ($action == "all_unread") {
	$stm = $dbh->prepare("UPDATE reader_items SET status='unread' WHERE readerId=? and feedId=? and status='read'");
    $stm->bind_param('ii', $readerId, $feedId);
    $ok = $stm->execute();
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
	$stm1 = $dbh->prepare("DELETE FROM reader_items WHERE readerId=? and feedId=?");
    $stm1->bind_param('ii', $readerId, $feedId);
    $ok1 = $stm1->execute();
	$stm2 = $dbh->prepare("DELETE FROM reader_feeds WHERE readerId=? and feedId=?");
    $stm2->bind_param('ii', $readerId, $feedId);
    $ok2 = $stm2->execute();
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
    if (!ctype_digit($newCategoryId)) {
        echo "Wrong categoryId</body></html>";
        exit;
    }
	$stm = $dbh->prepare("UPDATE reader_feeds SET categoryId=? where feedId=? and readerId=?");
    $stm->bind_param('iii', $newCategoryId, $feedId, $readerId);
    $ok = $stm->execute();
	if ($ok) {
		echo "Feed sucessfully moved to new category.<p>";
		echo "<a href=\"feeds.php#cat_".$newCategoryId."\">&lt;&lt; Back to feed list</a>";
		echo "</body></html>";
		exit;
	}
}

echo _fB()."<a href=\"feeds.php#cat_".$feedCatId."\">&lt;&lt; Back to feed list</a>"._fE();

// show dialog for choosing new category
if ($action == "catChangeReq") {
	echo "<p>".
		_fB()."<form method=\"POST\" action=\"show_items.php?feedId=".$feedId."\">".
		"Current category: <b>".$categoryName."</b><p>".
		"New category: <select name=\"newCat\">".
		_combo_options_categories($dbh, $readerId).
		"</select> ".
		"<input type=\"submit\" name=\"doChangeCat\" value=\"Save\">";
		echo "</body></html>";
		exit;
}

echo "<p>";
echo "<table width=\"100%\"><tr><td>"._fB();
echo "Actions: ";
echo "<a href=\"show_items.php?feedId=".$feedId."&action=all_read\">[ Mark all as Read ]</a> ";
echo "<a href=\"show_items.php?feedId=".$feedId."&action=all_unread\">[ Mark all as Unread ]</a>";
echo _fE()."</td><td align=\"right\">"._fB();
echo "<a href=\"show_items.php?feedId=".$feedId."&action=unsub\">[ Unsubscribe from Feed ]</a> ";
echo "<a href=\"fetch_items.php?feedId=".$feedId."\">[ Fetch new articles ]</a>";
echo _fE()."</td></tr></table>\n";
echo "<p>";

echo "<table width=\"100%\"><tr>".
	"<td><small>Category: <b>".$categoryName."</b> (<a href=\"show_items.php?feedId=".$feedId."&action=catChangeReq\">Change</a>)</small></td>".
	"<td align=\"right\"><small>Last refresh: ".$feedFetchTime."</small></td>".
	"</tr></table><p>"._fB();


$cmd = "SELECT i.id,i.permalink,i.title,i.publishDate,ri.status+0 as status ".
	"FROM items i ".
	"LEFT JOIN reader_items ri ON ri.readerId=? and i.feedId=ri.feedId and i.id=ri.itemId ".
	"WHERE i.feedId=? AND ri.status<>'deleted' ORDER BY ri.status, i.publishDate DESC";
$stm = $dbh->prepare($cmd);
$stm->bind_param('ii', $readerId, $feedId);
$stm->execute();
$res = $stm->get_result();
$stm->close();

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
