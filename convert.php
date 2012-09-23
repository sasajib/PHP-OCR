<?php
if(isset($_GET['im'])){
	require('config.php');
	require('Image2String.php');

	$image = new Image2String($_GET['im']);
	
	echo nl2br($image->getImageString());
}
?>