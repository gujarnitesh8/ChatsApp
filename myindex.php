<?php

//echo phpinfo();

$server = "sftp://ec2-35-180-138-81.eu-west-3.compute.amazonaws.com";
$user = "root";
$password = "password";
$dbname = 'Ludo_DB';

global $con;
$con = mysqli_connect($server, $user, $password,$dbname);

if (mysqli_connect_errno())
{
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
}
else
{
    echo "connected successfully";
    mysqli_select_db($con, $dbname);
    mysqli_set_charset($con,"utf8");
}
?>
