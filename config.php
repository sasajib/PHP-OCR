<?php
$dbhost = "localhost";
$dbuser = "root";
$dbpassword = "";
$dbdatabase = "test";
$db = mysql_connect($dbhost, $dbuser, $dbpassword);
mysql_set_charset('utf8',$db);
mysql_select_db($dbdatabase, $db);
?>