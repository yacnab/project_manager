<?php
$host = "localhost";
$dbname = "project_db";
$user = "root";      
$pass = "";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
   
} catch (PDOException $e) {
    die("اتصال به دیتابیس برقرار نشد: " . $e->getMessage());
}
?>
