<?php
require 'config/db.php';
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'مدیر پروژه') {
    header("Location: login.php");
    exit();
}

$project_id = $_GET['project_id'] ?? null;
if (!$project_id) {
    die("شناسه پروژه مشخص نیست.");
}

$errors = [];
$success = "";

if (isset($_POST['create'])) {
    $title = trim($_POST['title'] ?? '');

    if (!$title) {
        $errors[] = "عنوان برد الزامی است.";
    } else {
        $stmt = $conn->prepare("INSERT INTO boards (project_id, title) VALUES (?, ?)");
        $stmt->execute([$project_id, $title]);
        $success = "برد با موفقیت ساخته شد.";
    }
}
?>

<!DOCTYPE html>
<html lang="fa">
<head><meta charset="UTF-8"><title>ساخت برد</title></head>
<body>
    <h2>ساخت برد برای پروژه <?= htmlspecialchars($project_id) ?></h2>

    <?php if ($success): ?>
        <p style="color:green;"><?= $success ?></p>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <ul style="color:red;">
            <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars($err) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post">
        <label>عنوان برد:</label><br>
        <input type="text" name="title" required><br><br>
        <button type="submit" name="create">ثبت برد</button>
    </form>

    <br><a href="dashboard.php">بازگشت</a>
</body>
</html>
