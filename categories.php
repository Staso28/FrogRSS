<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2 Final//EN">
<html>
<head>
	<title>FrogRSS Feeds</title>
</head>
<body link="blue" vlink="blue" alink="blue">
<h2>Categories</h2>
<p>
<font size="4">
<a href="feeds.php">&lt;&lt; Back to feeds</a>
</font>
<p>
<?php
require_once("functions.php");
require_once("dbconn.php");
require_once("check_session.php");

if (isset($_GET['id'])) {
	$categoryId = sprintf("%d",$_GET['id']);
}
if (isset($_GET['action'])) {
	$action = $_GET['action'];
}

$newErr = "";
$newOK = 1;
$catName= "";

// create new category
if(isset($_REQUEST['doNew'])) {
	$catName = $_REQUEST['newCatName'];
	// TODO: check for bad characters
	if (!_check_db_input($catName)) {
		$newErr = "Wrong category name";
		$newOK = 0;
	}
	// get max ordering and check for duplicate name
	if ($newOK) {
		$cmd = "SELECT max(ordering) as m, sum(if(name='".$catName."',1,0)) as dupl ".
			"FROM categories WHERE readerId=".$readerId;
		$res = $dbh->query($cmd);
		$row = $res->fetch_assoc();
		$maxOrder = $row["m"];
		if ($row["dupl"] > 0) {
			$newErr = "Category name already used";
			$newOK = 0;
		}
	}

	if ($newOK) {
		$cmd = "INSERT INTO categories SET id=NULL, readerId=".$readerId.", name='".$catName."', ordering=".($maxOrder+1);
		$ok = $dbh->query($cmd);
		if (!$ok) $newErr = "Error while creating category";
	}
}
else if ($action == "up" and $categoryId) {
	$cmd = "select ordering FROM categories WHERE id=".$categoryId;
	$res = $dbh->query($cmd);
	$row = $res->fetch_assoc();
	$myOrder = $row["ordering"];
	$cmd = "select id, ordering FROM categories WHERE readerId=".$readerId." AND ordering < ".$myOrder." ORDER BY ordering desc limit 1";
	$res = $dbh->query($cmd);
	$other = $res->fetch_assoc();
	if ($other) {
		$dbh->query("update categories set ordering = ".$other["ordering"]." where id=".$categoryId);
		$dbh->query("update categories set ordering = ".$myOrder." where id=".$other["id"]);
	}
}
else if ($action == "down" and $categoryId) {
	$cmd = "select ordering FROM categories WHERE id=".$categoryId;
	$res = $dbh->query($cmd);
	$row = $res->fetch_assoc();
	$myOrder = $row["ordering"];
	$cmd = "select id, ordering FROM categories WHERE readerId=".$readerId." AND ordering > ".$myOrder." ORDER BY ordering asc limit 1";
	$res = $dbh->query($cmd);
	$other = $res->fetch_assoc();
	if ($other) {
		$dbh->query("update categories set ordering = ".$other["ordering"]." where id=".$categoryId);
		$dbh->query("update categories set ordering = ".$myOrder." where id=".$other["id"]);
	}
}
else if ($action == "del" and $categoryId) {
	$cmd = "SELECT count(*) as poc FROM reader_feeds WHERE readerId=".$readerId." and categoryId=".$categoryId;
	$res = $dbh->query($cmd);
	$row = $res->fetch_assoc();
	if ($row["poc"] > 0) {
		echo "<font color=\"red\">Unable to delete category with assigned feeds. Please move feeds to different category and try again.</font><p>";
	}
	else {
		$cmd = "DELETE FROM categories WHERE id=".$categoryId;
		$ok = $dbh->query($cmd);
		if (!$ok) echo "<font color=\"red\">Error while deleting category....</font><p>";
	}
}


$cmd = "select id, ordering, name from categories where readerId=".$readerId." order by ordering";
$res = $dbh->query($cmd);

echo "<table width=\"400\">".
	"<tr><td><b>Order</b></td>".
		"<td width=\"40%\"><b>Name</b></td>".
		"<td><b>Actions</b></td></tr>\n";

while($row = $res->fetch_assoc()) {
	echo "<tr>".
		"<td align=\"center\">".$row["ordering"]."</td>".
		"<td><font size=\"4\">".$row["name"]."</font></td>";
	echo "<td><font size=\"4\">| ".
		"<a href=\"categories.php?id=".$row["id"]."&action=up\" title=\"Move this category Up one position\">Up</a> ".
		"<a href=\"categories.php?id=".$row["id"]."&action=down\" title=\"Move this category Down one position\">Down</a> | ".
		"<a href=\"categories.php?id=".$row["id"]."&action=del\" title=\"Delete category, if empty\">Delete</a> ".
		"</font></td></tr>\n";
}
echo "</table>";
?>
<p>
<font size="4">
<?php
if ($newErr) echo "<font color=\"red\">".$newErr."</font><p>";
?>
<form method="POST">
New category: <input type="text" size="15" name="newCatName" value="<?php if ($catName) echo $catName; ?>"> 
<input type="submit" name="doNew" value="Create">
</form>
</font>
<?php
_footer();
?>
</body>
</html>
