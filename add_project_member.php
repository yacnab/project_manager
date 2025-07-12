<?php
require 'config/db.php';
session_start();

if (!isset($_SESSION['user'])) {
    die("لطفاً ابتدا وارد شوید.");
}


$user_mobile = $_SESSION['user']['mobile'] ?? null;
if (!$user_mobile) {
    die("اطلاعات کاربر یافت نشد.");
}


$stmt = $conn->prepare("SELECT id FROM users WHERE mobile = ?");
$stmt->execute([$user_mobile]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("کاربر یافت نشد.");
}

$user_id = $user['id'];

$project_id = $_GET['project_id'] ?? null;
if (!$project_id) {
    die("شناسه پروژه مشخص نشده است.");
}


$stmt = $conn->prepare("SELECT * FROM project_members WHERE user_id = ? AND project_id = ?");
$stmt->execute([$user_id, $project_id]);
$exists = $stmt->fetch(PDO::FETCH_ASSOC);

if ($exists) {
    echo "شما قبلاً عضو این پروژه هستید.";
} else {
    
    $role = 'مدیر پروژه';

    $stmt = $conn->prepare("INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, ?)");
    $stmt->execute([$project_id, $user_id, $role]);

    echo "شما با موفقیت به پروژه اضافه شدید.";
}
?>
