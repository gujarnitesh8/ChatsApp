<?php
/**
 * Created by PhpStorm.
 * User: c119
 * Date: 04/03/15
 * Time: 12:10 PM
 */

include_once 'Logger.php';
//ini_set('display_errors', 1);

$logger = new Logger();
date_default_timezone_set('UTC');

$server = "localhost";
$user = "root";
$password = "";
$dbname = 'ChatsApp';


global $con;
$con = mysqli_connect($server, $user, $password,$dbname);
$con->set_charset('utf8mb4');


if (mysqli_connect_errno())
{
//    echo "Failed to connect to MySQL: " . mysqli_connect_error();
}
else
{ 
//echo "connected successfully";
	//mysqli_set_charset($con,"utf8mb4");
    mysqli_select_db($con, $dbname);
}
?>