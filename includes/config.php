<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();}
// إنشاء CSRF Token في بداية الجلسة
if (empty($_SESSION['csrf_token']))
{
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
// تعريف الثوابت بشرط عدم وجودها مسبقًا
if (!defined('BASE_PATH')) 
{
    define('BASE_PATH', realpath(dirname(__FILE__) . '/../') . '/');
}
if (!defined('BASE_URL')) 
{
    define('BASE_URL', 'http://localhost/lms/');  
}



$host = "localhost";
$user = "phpmyadmin";
$password = "P@ssw0rd_123!";
$dbname = "test_db";


$conn = new mysqli($host, $user, $password, $dbname);
$conn->set_charset("utf8mb4");


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>