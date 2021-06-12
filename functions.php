<?php
function _err($str) {
	echo "<font color=\"red\">".$str."</font>";
	echo "<p>";
	return 0;
}
function _fB($size = 4) {
    return "<font size=\"".$size."\">";
}
function _fE() {
    return "</font>";
}

function _footer() {
	echo "<center><small>Still in Beta stage. Source on <a href=\"https://github.com/Staso28/FrogRSS\" target=\"_blank\" rel=\"noopener noreferrer\">GitHub</a>.</small></center>";
}

// check dangerous characters before inserting data into database
function _check_db_input($inputString) {
	if (preg_match('/[%;\'"\$\^\*]/', $inputString)) {
		return 0;
	}
	return 1;
}

function _combo_options_categories($dbh, $readerId) {
	$retVal="";
	$stm = $dbh->prepare("select id, name from categories where readerId=? order by ordering");
    $stm->bind_param('i', $readerId);
    $stm->execute();
    $res = $stm->get_result();
	while ($row = $res->fetch_assoc()) {
		$retVal .= "<option value=\"".$row["id"]."\">".$row["name"]."</option>\n";
	}
	return $retVal;
}

function _populate_reader_items($dbh) {
	$cmd = "INSERT INTO reader_items ".
		"SELECT rf.readerId, i.feedId, i.id, 'unread',  now() ".
		"FROM items i ".
		"LEFT JOIN reader_feeds rf ON i.feedId=rf.feedId ".
		"LEFT JOIN reader_items ri ON rf.readerId=ri.readerId AND i.feedId=ri.feedId AND i.id=ri.itemId ".
		"WHERE isnull(ri.feedId) AND not isnull(rf.readerId)";
	$ok = $dbh->query($cmd);
	if ($ok) {
		//echo "OK Inserted readers";
	} else {
		echo "ERROR inserting readers";
	}
}

function _get_category($dbh, $categoryId) {
	$stm = $dbh->prepare("SELECT id, ordering, name FROM categories WHERE id=?");
    $stm->bind_param('i', $categoryId);
    $stm->execute();
    $res = $stm->get_result();
    $stm->close();
    return $res->fetch_assoc();
}
?>
