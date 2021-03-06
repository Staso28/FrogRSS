<?php
// Heavily based on: https://github.com/ActionRetro/FrogFind/blob/main/image_compressed.php

$url = "";
$filetype = "";
$raw_image = NULL;

//get the image url
if (isset( $_GET['i'] ) ) {
    $url = $_GET['i'];
	// if there are any other params add it to the URL - needed for reddit thumbnails
	foreach ($_GET as $name => $value) {
		if ($name != "i") $url .= "&".$name.'='.$value;
	}
} else {
    exit();
}

//an image will start with http, anything else is sus
if (substr( $url, 0, 4 ) != "http") {
    exit();
}


//we can only do jpg and png here
if (strpos($url, ".jpg") > 0 || strpos($url, ".jpeg") > 0) {
	$filetype = "jpg";
	$raw_image = imagecreatefromjpeg($url);
} elseif (strpos($url, ".png") > 0) {
    $filetype = "png";
    $raw_image = imagecreatefrompng($url);
} else {
	// unindentifiable extension - trying to figure out the file type
	$size = getimagesize($url);
	if ($size === false) exit();
	if ($size['mime'] == 'image/jpg' || $size['mime'] == 'image/jpeg') {
		$filetype = "jpg";
		$raw_image = imagecreatefromjpeg($url);
	} elseif ($size['mime'] == 'image/png') {
		$filetype = "png";
		$raw_image = imagecreatefrompng($url);
	} elseif ($size['mime'] == 'image/webp') {
		$filetype = "jpg";	// convert to jpg on output
		$raw_image = imagecreatefromwebp($url);
	} else {
		exit();
	}
}

if ($raw_image === false) {
	echo "unable to create image";
	exit();
}

$raw_imagex = imagesx($raw_image);
$raw_imagey = imagesy($raw_image);

if ($raw_imagex >= $raw_imagey) {
	$dest_imagex = 300;
	$dest_imagey = ($raw_imagey / $raw_imagex) * $dest_imagex;
} else {
	$dest_imagey = 200;
	$dest_imagex = ($raw_imagex / $raw_imagey) * $dest_imagey;
}

$dest_image = imagecreatetruecolor($dest_imagex, $dest_imagey);

imagecopyresampled($dest_image, $raw_image, 0, 0, 0, 0, $dest_imagex, $dest_imagey, $raw_imagex, $raw_imagey);

header('Content-type: image/' . $filetype); 
if ($filetype = "jpg") {
    imagejpeg($dest_image,NULL,80); //80% quality
} elseif ($filetype = "png") {
    imagepng($dest_image,NULL,8); //80% compression
}

?>

