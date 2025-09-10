<?php
$db_host = "localhost";
$db_user = "DATABASE_USER";
$db_pass = "DATABASE_PASSWORD";
$db_name = "DATABASE_NAME";

$mysqli = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if(mysqli_connect_errno()){
	echo "Failed to connect to MySQL: " . mysqli_connect_error();
} 
// Change it for Security Reasons
$admin_username = 'admin';
$admin_password = 'admin';

$link[1] = "http://btc.ms/api/?api=86b6c147ce28028e5c7762afce1656f898279889&url=http://coinbox.club/tung/link.php?k={key}&format=text";

$link[2] = "http://btc.ms/api/?api=86b6c147ce28028e5c7762afce1656f898279889&url=http://coinbox.club/tung/link.php?k={key}&format=text";

$link_default ='http://btc.ms/api/?api=86b6c147ce28028e5c7762afce1656f898279889&url=http://coinbox.club/tung/link.php?k={key}&format=text';
?>   
