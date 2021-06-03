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
//mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

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
		$cmd = "SELECT max(ordering) as m, sum(if(name=?,1,0)) as dupl FROM categories WHERE readerId=?";
        $stm = $dbh->prepare($cmd);
        $stm->bind_param('si', $catName, $readerId);
        $stm->execute();
        $res = $stm->get_result();
        $stm->close();
		$row = $res->fetch_assoc();
		$maxOrder = $row["m"];
		if ($row["dupl"] > 0) {
			$newErr = "Category name already used";
			$newOK = 0;
		}
	}

	if ($newOK) {
        $maxOrder++;
		$cmd = "INSERT INTO categories SET id=NULL, readerId=?, name=?, ordering=?";
        $stm = $dbh->prepare($cmd);
        $stm->bind_param('isi', $readerId, $catName, $maxOrder);
        $ok = $stm->execute();
		if (!$ok) $newErr = "Error while creating category";
        $stm->close();
	}
}
else if ($action == "up" and $categoryId) {
    $c = _get_category($dbh, $categoryId);
	$myOrder = $c["ordering"];
	$stm = $dbh->prepare("SELECT id, ordering FROM categories WHERE readerId=? AND ordering < ? ORDER BY ordering desc limit 1");
    $stm->bind_param('ii', $readerId, $myOrder);
    $stm->execute();
    $res = $stm->get_result();
    $stm->close();
	$other = $res->fetch_assoc();
	if ($other) {
        // change ordering values
		$stm = $dbh->prepare("update categories set ordering = ? where id=?");
        $newOrder = $other["ordering"];
        $cId = $categoryId;
        $stm->bind_param('ii', $newOrder, $cId);
        $stm->execute();
        $newOrder = $myOrder;
        $cId = $other["id"];
        $stm->execute();
        $stm->close();
	}
}
else if ($action == "down" and $categoryId) {
    $c = _get_category($dbh, $categoryId);
	$myOrder = $c["ordering"];
	$stm = $dbh->prepare("SELECT id, ordering FROM categories WHERE readerId=? AND ordering > ? ORDER BY ordering limit 1");
    $stm->bind_param('ii', $readerId, $myOrder);
    $stm->execute();
    $res = $stm->get_result();
    $stm->close();
	$other = $res->fetch_assoc();
	if ($other) {
        // change ordering values
		$stm = $dbh->prepare("update categories set ordering = ? where id=?");
        $newOrder = $other["ordering"];
        $cId = $categoryId;
        $stm->bind_param('ii', $newOrder, $cId);
        $stm->execute();
        $newOrder = $myOrder;
        $cId = $other["id"];
        $stm->execute();
        $stm->close();
	}
}
else if ($action == "del" and $categoryId) {
	$stm = $dbh->prepare("SELECT count(*) as poc FROM reader_feeds WHERE readerId=? and categoryId=?");
    $stm->bind_param('ii', $readerId, $categoryId);
    $stm->execute();
    $res = $stm->get_result();
    $stm->close();
	$row = $res->fetch_assoc();
	if ($row["poc"] > 0) {
		echo "<font color=\"red\">Unable to delete category with assigned feeds. Please move feeds to different category and try again.</font><p>";
	}
	else {
		$stm = $dbh->prepare("DELETE FROM categories WHERE id=?");
        $stm->bind_param('i', $categoryId);
        $ok = $stm->execute();
        $stm->close();
		if (!$ok) echo "<font color=\"red\">Error while deleting category....</font><p>";
	}
}


$stm = $dbh->prepare("SELECT id, ordering, name from categories where readerId=? order by ordering");
$stm->bind_param('i', $readerId);
$stm->execute();
$res = $stm->get_result();

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
