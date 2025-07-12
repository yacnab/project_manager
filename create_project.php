<?php
require 'config/db.php';
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}


if ($_SESSION['user']['role'] !== "مدیر پروژه") {
    die("فقط مدیر پروژه می‌تواند پروژه بسازد.");
}

$errors = [];
$success = "";

if (isset($_POST['create'])) {
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $start = $_POST['start_date'] ?? '';
    $end = $_POST['end_date'] ?? '';
    $mobile = $_SESSION['user']['mobile'];

    if (!$title || !$start || !$end) {
        $errors[] = "تمام فیلدهای ضروری باید پر شوند.";
    } else {
       
        $stmt = $conn->prepare("INSERT INTO projects (title, description, start_date, end_date, manager_mobile) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$title, $desc, $start, $end, $mobile]);

        
        $new_project_id = $conn->lastInsertId();

        
        $stmtUser = $conn->prepare("SELECT id FROM users WHERE mobile = ?");
        $stmtUser->execute([$mobile]);
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $user_id = $user['id'];
            
            $stmt2 = $conn->prepare("INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, 'مدیر پروژه')");
            $stmt2->execute([$new_project_id, $user_id]);
        }

        $success = "پروژه با موفقیت ثبت شد.";
    }
}

?>

<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title>ساخت پروژه جدید</title>
</head>
<body>
    <h2>تعریف پروژه</h2>

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
        <label>عنوان پروژه:</label><br>
        <input type="text" name="title" required><br>

        <label>توضیحات:</label><br>
        <textarea name="description"></textarea><br>

        <label>تاریخ شروع:</label><br>
        <input type="date" name="start_date" required><br>

        <label>تاریخ پایان:</label><br>
        <input type="date" name="end_date" required><br>

        <button type="submit" name="create">ثبت پروژه</button>
    </form>

    <br>
    <a href="dashboard.php">بازگشت به داشبورد</a>
</body>
</html>
