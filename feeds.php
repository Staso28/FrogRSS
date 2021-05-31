<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2 Final//EN">
<html>
<head>
	<title>FrogRSS Feeds</title>
</head>
<body link="blue" vlink="blue" alink="blue">

<?php
require_once("functions.php");
require_once("dbconn.php");
require_once("check_session.php");

$showLive = 0;
if (isset($_GET['live'])) { $showLive = 1; }

$action = "";
if (isset($_GET['action'])) {
	$action = $_GET['action'];
}

if ($action == "allRead") {
    echo "<font size=\"4\">Do you really want to mark all articles?<p>";
    echo "<a href=\"feeds.php\">[ No, take me back ]</a> ";
    echo "<a href=\"feeds.php?action=doAllRead\">[ Yes, mark them all ]</a> ";
    echo "</font></body></html>";
    exit;
} else if ($action == "doAllRead") {
	$cmd = "UPDATE reader_items SET status='read' WHERE readerId=".$readerId." and status='unread'";
	$ok = $dbh->query($cmd);
	if ($ok) {
		echo "<font size=\"4\">All articles marked as Read sucessfully.<p><a href=\"feeds.php\">Back to the feed...</a></font></body></html>";
	} else {
		echo "Error: Unable to mark all articles.";
	}	
	exit;
}
?>

<table width="100%">
<tr><td>
<h2>My feeds</h2>
</td><td align="right" valign="top">
<a href="add_feed.php">[ Add RSS feed ]</a> 
<a href="categories.php">[ Manage categories ]</a> 
[ My profile ] 
<a href="logout.php">Logout</a>
</td></tr>
</table>
Actions: 
<!--<a href="fetch_items.php">[ Fetch articles for all feeds ]</a> -->
<a href="feeds.php?action=allRead">[ Mark all as Read ]</a>
<?php
$cmd = "SELECT c.name as cat_name, if(isnull(rf.feedName),f.name,rf.feedName) as feed_name, url, rf.categoryId, f.id, ".
	"sum(if(isnull(ri.itemId),0,1)) as itemCount, ".
	"sum(if(!isnull(ri.itemId) and ri.status='unread',1,0)) as unreadCount ".
	"FROM feeds f ".
	"JOIN reader_feeds rf ON rf.readerId=".$readerId." and rf.feedId=f.id and rf.delflag='N' ".
	"LEFT JOIN categories c ON rf.categoryId=c.id ".
	"LEFT JOIN reader_items ri ON ri.readerId=".$readerId." AND ri.feedId=f.id ".
	"WHERE isnull(ri.status) or ri.status <> 'deleted' ".
	"GROUP by f.id ORDER BY c.ordering, f.id";
$res = $dbh->query($cmd);
if (!$res) echo $cmd;

$cat = "";
while ($row = $res->fetch_assoc()) {
	if ($cat != $row["categoryId"]) {
		if ($cat != "")  print "</font></ul>";
		print "<h3><a name=\"cat_".$row["categoryId"]."\">".$row["cat_name"]."</a></h3>";
        echo "<font size=\"4\"><ul>";
		$cat = $row["categoryId"];
	}
	//print("<li></li>\n");
	$col = ($row["unreadCount"] > 0) ? ("darkgreen") : ("blue");
	echo "<li>";
	if ($row["unreadCount"] > 0) echo "<b>";
	echo "<a href=\"show_items.php?feedId=".$row["id"]."\"><font color=\"".$col."\">".$row["feed_name"]." [".$row["unreadCount"]."/".$row["itemCount"]."]</font></a>";
	if ($row["unreadCount"] > 0) echo "</b>";
	if ($showLive) echo " <small><a href=\"rss.php?feed=".urlencode($row["url"])."\">(live data)</a></small>";
	echo "</li>\n";
}
if ($cat) echo "</font></ul>";

_footer();
?>
</body>
</html>

