<?php
 
// Make sure SimplePie is included. You may need to change this to match the location of autoloader.php
// For 1.0-1.2:
 
#require_once('../simplepie.inc');
// For 1.3+:
require_once('../php/autoloader.php');

#use andreskrey\Readability\Readability;
#use andreskrey\Readability\Configuration;
#use andreskrey\Readability\ParseException;
#
#$readability = new Readability(new Configuration());
 
// Let's begin our XHTML webpage code.  The DOCTYPE is supposed to be the very first thing, so we'll keep it on the same line as the closing-PHP tag.
if (isset($_GET['feed'])) {
	$xml = $_GET['feed'];
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN">
<html>
<head>
	<title>FROG RSS</title>
</head>
<body>
	<p><form method="get">
		XML feed:<input type="text" size="40" name="feed" value="<?php echo $xml; ?>" />
		<input type="submit" value="Go!">
	</form>

<?php
if (!isset($xml)) {
	echo "No feed URL .... no data ";
	echo "</body></html>";
	exit();
}

// We'll process this feed with all of the default options.
$feed = new SimplePie();
 
// Set which feed to process.
$feed->set_feed_url($xml);
 
// Run SimplePie.
$feed->init();
 
// This makes sure that the content is sent to the browser as text/html and the UTF-8 character set (since we didn't change it).
$feed->handle_content_type();

// tu eventualne mozem pridat img aby to vyhodilo aj obrazky
$strip_tags = array('base', 'blink', 'body', 'doctype', 'embed', 'font', 'form', 'frame', 'frameset', 'html', 'iframe', 'input', 'marquee', 'meta', 'noscript', 'object', 'param', 'script', 'style');
$feed->strip_htmltags($strip_tags);
?>
 
	<div class="header">
		<h1><a href="<?php echo $feed->get_permalink(); ?>"><?php echo $feed->get_title(); ?></a></h1>
		<p><?php echo $feed->get_description(); ?></p>
	</div>
 
	<?php
	/*
	Here, we'll loop through all of the items in the feed, and $item represents the current item in the loop.
	*/
	foreach ($feed->get_items() as $item):
		$enc = $item->get_enclosure();
		$idesc = $item->get_description();
		if ($idesc) {
			$idesc = str_replace( 'href="http', 'href="http://frogfind.com/read.php?a=http', $idesc );
			$idesc = preg_replace( '/(<img.*)height="[0-9]+"(.*>)/', '$1$2', $idesc );
			$idesc = preg_replace( '/(<img.*)width="[0-9]+"(.*>)/', '$1$2', $idesc );
			$idesc = preg_replace( '/(<img.*src=")/', '$1http://frogfind.com/image_compressed.php?i=', $idesc );
		}
	?>
 
	<hr />
	<div class="item">
		<h3><a href="http://frogfind.com/read.php?a=<?php echo $item->get_permalink(); ?>"><?php echo $item->get_title(); ?></a></h3>
		<p>Original URL: <a href="<?php echo $item->get_permalink(); ?>" target="_blank" rel="noopener noreferrer"><?php echo $item->get_permalink(); ?></a>
		<p><?php echo $idesc; ?></p>
<?php
	$desc = $enc->get_description();
	$thumb= $enc->get_thumbnail();
	if ($thumb) { echo "<p><img src=\"http://frogfind.com/image_compressed.php?i=".$thumb."\" /></p>"; }
	if ($desc) { echo "<p>".nl2br($desc)."</p>"; }
?>
			<p><small>Posted on <?php echo $item->get_date('j F Y | g:i a'); ?></small></p>
		</div>
 
	<?php endforeach; ?>
 
</body>
</html>
