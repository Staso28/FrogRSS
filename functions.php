<?php
function _fB($size = 4) {
    return "<font size=\"".$size."\">";
}
function _fE() {
    return "</font>";
}

function _footer() {
	echo "<center><small>TBD: footer</small></center>";
}

// check dangerous characters before inserting data into database
function _check_db_input($inputString) {
	if (preg_match('/[\'"\$\^\*]/', $inputString)) {
		return 0;
	}
	return 1;
}

function _combo_options_categories($dbh, $readerId) {
	$retVal="";
	$res = $dbh->query("select id, name from categories where readerId=".$readerId." order by ordering");
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
?>
