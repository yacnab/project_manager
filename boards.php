<?php
require 'config/db.php';
session_start();


if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$project_id = $_GET['project_id'] ?? null;
if (!$project_id) {
    die("شناسه پروژه مشخص نشده است.");
}


$current_user_mobile = $_SESSION['user']['mobile'];
$current_user_role_global = $_SESSION['user']['role'] ?? '';


$stmt = $conn->prepare("SELECT id FROM users WHERE mobile = ?");
$stmt->execute([$current_user_mobile]);
$current_user = $stmt->fetch(PDO::FETCH_ASSOC);
$current_user_id = $current_user['id'] ?? null;

if (!$current_user_id) {
    die("خطا در شناسایی کاربر.");
}


$stmt = $conn->prepare("SELECT role FROM project_members WHERE project_id = ? AND user_id = ?");
$stmt->execute([$project_id, $current_user_id]);
$role_in_project = $stmt->fetchColumn();


if (!$role_in_project && $current_user_role_global !== 'مدیر پروژه') {
    die("شما عضو این پروژه نیستید.");
}


$stmt = $conn->prepare("SELECT * FROM boards WHERE project_id = ?");
$stmt->execute([$project_id]);
$boards = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title>بردهای پروژه <?= htmlspecialchars($project_id) ?></title>
    <style>
        body { font-family: Tahoma, sans-serif; direction: rtl; padding: 20px; }
        a { text-decoration: none; color: #007BFF; }
        a:hover { text-decoration: underline; }
        .btn-create { background: #28a745; color: white; padding: 8px 12px; border-radius: 4px; text-decoration: none; }
        .btn-create:hover { background: #218838; }
        ul { list-style-type: none; padding: 0; }
        li { margin-bottom: 10px; }
    </style>
</head>
<body>
    <h2>بردهای پروژه شماره <?= htmlspecialchars($project_id) ?></h2>

    <?php if ($role_in_project === 'مدیر پروژه' || $current_user_role_global === 'مدیر پروژه'): ?>
        <a href="create_board.php?project_id=<?= $project_id ?>" class="btn-create">➕ ساخت برد جدید</a><br><br>
    <?php endif; ?>

    <?php if (empty($boards)): ?>
        <p>هیچ بردی برای این پروژه ثبت نشده است.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($boards as $board): ?>
                <li>
                    <a href="tasks.php?board_id=<?= $board['id'] ?>">
                        <?= htmlspecialchars($board['title']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <br><a href="dashboard.php">بازگشت به داشبورد</a>
</body>
</html>
