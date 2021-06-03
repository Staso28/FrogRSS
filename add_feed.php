<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2 Final//EN">
<html>
<head>
	<title>FrogRSS Add feed</title>
</head>
<body link="blue" vlink="blue" alink="blue">

<h2>Add feed</h2>
<font size="4">
<form action="add_feed.php" method="POST">
<?php
require_once("functions.php");
require_once("dbconn.php");
require_once("check_session.php");
require_once('php/autoloader.php');


if(isset($_REQUEST['doSave'])) {
	$fname = $_REQUEST['fname'];
	$ftitle = $_REQUEST['ftitle'];
	$furl = $_REQUEST['furl'];
	$cat = sprintf("%d",$_REQUEST['cat']);
	// check input strings
	if (!_check_db_input($furl) or !_check_db_input($ftitle)) {
		echo "<font color=\"red\">Wrong URL or feed name.</font><p><a href=\"feeds.php\">Back to feed list</a></body></html>";
		exit;
	}
	// mame uz v db takyto feed podla URL?
	$stm = $dbh->prepare("select id from feeds where url=?");
    $stm->bind_param('s', $furl);
    $stm->execute();
	$res = $stm->get_result();
    $stm->close();
	$row = $res->fetch_assoc();
	if ($row) {
		$feedId = $row["id"];
	} else {
		// nemame tak ho vlozime
		$stm = $dbh->prepare("INSERT INTO feeds SET id=null,name=?, url=?");
        $stm->bind_param('ss', $ftitle, $furl);
        $stm->execute();
		$feedId = $dbh->insert_id;
        $stm->close();
	}
	$stm = $dbh->prepare("INSERT INTO reader_feeds SET readerId=?, feedId=?, categoryId=?");
    $stm->bind_param('iii', $readerId, $feedId, $cat);
    $ok = $stm->execute();
	if ($ok) {
		echo "Feed added succesfully. <a href=\"feeds.php\">Back to feed list</a></body></html>";
		exit;
	}
}

$fname = $_REQUEST["fname"];

?>
<a href="feeds.php">&lt;&lt; Back to feed list</a><p>
Site name / RSS URL: <input type="text" name="fname" value="<?php if ($fname) echo $fname; ?>" size="50">
<input type="submit" name="doPreview" value="Find feed and preview ">
<p>
<?php
// try to find RSS a show preview
if(isset($_REQUEST['doPreview'])) {
	$web = $_REQUEST["fname"];
	// TODO: nejake validacie na meno stranky??
	$feed = new SimplePie();
	$feed->set_feed_url($web);
	$ok = $feed->init();
	echo "Requested URL: <b>".$web."</b><br>";
	
	if($ok) {
		echo "<br>Feed title: <input type=\"text\" size=\"40\" name=\"ftitle\" value=\"".$feed->get_title()."\">";
		echo "<br>Feed link:<input type=\"text\" size=\"40\" name=\"furl\" value=\"".$feed->subscribe_url()."\">";
		echo "<p>Newest articles:";
	    echo "<ul>";
		$i = 1;
		foreach ($feed->get_items() as $item):
			if ($i > 5) break;
	        echo "<li>".$item->get_title()." ";
			echo "<small>(posted on ".$item->get_gmdate('Y-m-d H:i:s').")</small></li>";
			$i++;
	    endforeach;
	    echo "</ul>";
		echo "Category: <select name=\"cat\">";
		echo _combo_options_categories($dbh, $readerId);
		echo "</select><p>";
		echo "<input type=\"submit\" name=\"doSave\" value=\"Save feed\">";
	} else {
	    echo "<p>No RSS feed found. Please try entering different site/url...";
	}
}
?>

</form>
</font>
</body></html>
