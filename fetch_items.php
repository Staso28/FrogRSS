<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2 Final//EN">
<html>
<head>
	<title>FrogRSS Feed</title>
</head>
<body link="blue" vlink="blue" alink="blue">
<?php
require_once("dbconn.php");
require_once("functions.php");

$feedId = 0;
if (isset($_GET['feedId'])) { 
    //$feedId = $_GET['feedId'];
    $feedId = sprintf("%d",$_GET['feedId']);
}


// simplepie
require_once('php/autoloader.php');

// refresh data only once in 300 seconds 
$minRefreshPeriod = 300;

// check if not running too often
// naschval beriem min(fetchTime) - ak refreshujeme vsetky feedy tak staci ze najdem jeden feed,
// ktory este nebol refreshnuty za poslednych $minRefreshPeriod sekund
$cmd = "SELECT min(unix_timestamp(f.fetchTime)) as lastFetch, unix_timestamp(now()) as currTime FROM feeds f";
if ($feedId) $cmd .= " WHERE id=".$feedId;
else $cmd .= " WHERE EXISTS (SELECT 1 FROM reader_feeds rf WHERE rf.feedId=f.id)";
$res = $dbh->query($cmd);
$row = $res->fetch_assoc();
if ($row["lastFetch"] && ($row["currTime"] - $row["lastFetch"]) < $minRefreshPeriod) {
	echo "Feeds are still quite fresh, no need to refresh data.";
	exit;
}

if ($feedId) {
	echo "<a href=\"show_items.php?feedId=".$feedId."\">&lt;&lt; Back to feed</a><p>";
}

// fetch feed list - only feeds which have some readers
$cmd = "SELECT f.id, f.url, f.name FROM feeds f";
if ($feedId) $cmd .= " WHERE f.id=".$feedId;
else $cmd .= " WHERE EXISTS (SELECT 1 FROM reader_feeds rf WHERE rf.feedId=f.id)";
$cmd .= " ORDER BY f.id";
$res = $dbh->query($cmd);

while ($row = $res->fetch_assoc()) {
	$fId = $row["id"];
    $feed = new SimplePie();
	// Set which feed to process.
	$feed->set_feed_url($row["url"]);
	// Run SimplePie.
	$feed->init();
	$feed->handle_content_type();
	// tu eventualne mozem pridat img aby to vyhodilo aj obrazky
	$strip_tags = array('base', 'blink', 'body', 'doctype', 'embed', 'font', 'form', 'frame', 'frameset', 'html', 'iframe', 'input', 'marquee', 'meta', 'noscript', 'object', 'param', 'script', 'style');
	$feed->strip_htmltags($strip_tags);
	echo "FEED: ".$row["name"]."<br>";

	foreach ($feed->get_items() as $item):
		// hlavna desc
		$idesc = $item->get_description();
		if ($idesc) {
			$idesc = str_replace( 'href="http', 'href="http://frogfind.com/read.php?a=http', $idesc );
			$idesc = preg_replace( '/(<img.*)height="[0-9]+"(.*>)/', '$1$2', $idesc );
			$idesc = preg_replace( '/(<img.*)width="[0-9]+"(.*>)/', '$1$2', $idesc );
			$idesc = preg_replace( '/(<img.*src=")/', '$1img.php?i=', $idesc );
		}
        // thumbnail a desc pre YT videa
		$enc = $item->get_enclosure();
	    $desc = $enc->get_description();
	    $thumb= $enc->get_thumbnail();
		if ($desc) { $idesc .= "<p>".nl2br($desc); }
		$idesc = str_replace('\'','\'\'', $idesc);
		// title
		$title = 
		$title = str_replace('\'','\'\'', $item->get_title());
		// item id unique
		$itemId = $item->get_id(true);
		// nebudeme zistovat ci uz je, rovno urobime insert ignore
        // TODO: prepared statement
		$cmd = "INSERT IGNORE INTO items SET id='".$itemId."', feedId=".$row["id"].", ".
			"title='".$title."', permalink='".$item->get_permalink()."', ".
			"description='".$idesc."', publishDate='".$item->get_gmdate('Y-m-d H:i:s')."', ".
			"updateDate='".$item->get_updated_gmdate('Y-m-d H:i:s')."'";
		if ($thumb) { $cmd .= ", thumbnail='".$thumb."'"; }
		$ok = $dbh->query($cmd);
		if ($ok) {
		    //echo "OK Inserted id:".$itemId." Title:".$item->get_title()." ".$item->get_gmdate('Y-m-d H:i:s')."<br />";
		} else {
			//echo "ERROR inserting: itemId:".$itemId." title:".$item->get_title()." cmd:".$cmd."<br />";
			echo "ERROR inserting: itemId:".$itemId."<br />";
		}
	endforeach;
	echo "<p>";
	// set last fetch time for feed
	$dbh->query("UPDATE feeds SET fetchTime=now() WHERE id=".$fId);
}

// pre vsetkych klientov urobime vazbu medzi novymi clankami a usermi
_populate_reader_items($dbh);

echo "All done!";
?>
</body></html>
