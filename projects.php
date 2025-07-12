<?php
require 'config/db.php';
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$mobile = $_SESSION['user']['mobile'];

$stmt = $conn->prepare("SELECT * FROM projects WHERE manager_mobile = ?");
$stmt->execute([$mobile]);
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title>پروژه‌های من</title>
</head>
<body>
    <h2>پروژه‌های تعریف‌شده توسط من</h2>

    <?php if (empty($projects)): ?>
        <p>هنوز هیچ پروژه‌ای ثبت نکرده‌اید.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($projects as $project): ?>
                <li>
                    <strong><?= htmlspecialchars($project['title']) ?></strong><br>
                    <?= nl2br(htmlspecialchars($project['description'])) ?><br>
                    <small><?= $project['start_date'] ?> تا <?= $project['end_date'] ?></small>
                </li>
                <hr>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <a href="dashboard.php">برگشت به داشبورد</a>
</body>
</html>
