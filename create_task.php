<?php
require 'config/db.php';
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'مدیر پروژه') {
    header("Location: login.php");
    exit();
}

$board_id = $_GET['board_id'] ?? $_POST['board_id'] ?? null;
if (!$board_id) {
    die("شناسه برد مشخص نیست.");
}

$errors = [];
$success = "";

if (isset($_POST['create'])) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $assigned_to = $_POST['assigned_to'] ?? null;

    $allowed_status = ['pending', 'in_progress', 'done'];
    $status = $_POST['status'] ?? '';
    if (!in_array($status, $allowed_status)) {
        $status = 'pending';
    }

    if (!$title) {
        $errors[] = "عنوان تسک الزامی است.";
    }

    if ($assigned_to) {
        // بررسی اینکه این شماره موبایل، عضو پروژه‌ای هست که این برد متعلق بهشه
        $stmt = $conn->prepare("
            SELECT u.id
            FROM users u
            JOIN project_members pm ON u.id = pm.user_id
            JOIN boards b ON pm.project_id = b.project_id
            WHERE u.mobile = ? AND b.id = ?
        ");
        $stmt->execute([$assigned_to, $board_id]);
        if (!$stmt->fetch()) {
            $errors[] = "این شماره موبایل عضو پروژه نیست و نمی‌تواند مسئول تسک باشد.";
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO tasks (board_id, title, description, assigned_to, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$board_id, $title, $description, $assigned_to, $status]);
        $success = "تسک با موفقیت اضافه شد.";
    }
}

// گرفتن اعضای پروژه مربوط به این برد
$stmt = $conn->prepare("
    SELECT u.mobile, u.first_name, u.last_name
    FROM users u
    JOIN project_members pm ON u.id = pm.user_id
    JOIN boards b ON b.project_id = pm.project_id
    WHERE b.id = ?
");
$stmt->execute([$board_id]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title>ساخت تسک</title>
</head>
<body>
    <h2>افزودن تسک به برد شماره <?= htmlspecialchars($board_id) ?></h2>

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
        <label>عنوان تسک:</label><br>
        <input type="text" name="title" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required><br><br>

        <label>توضیحات:</label><br>
        <textarea name="description"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea><br><br>

        <label>اختصاص به (شماره موبایل):</label><br>
        <select name="assigned_to" required>
            <option value="">انتخاب مسئول</option>
            <?php foreach ($members as $member): ?>
                <option value="<?= htmlspecialchars($member['mobile']) ?>" <?= (($_POST['assigned_to'] ?? '') == $member['mobile']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name'] . ' - ' . $member['mobile']) ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <label>وضعیت:</label><br>
        <select name="status" required>
            <option value="pending" <?= (($_POST['status'] ?? '') == 'pending') ? 'selected' : '' ?>>منتظر</option>
            <option value="in_progress" <?= (($_POST['status'] ?? '') == 'in_progress') ? 'selected' : '' ?>>در حال انجام</option>
            <option value="done" <?= (($_POST['status'] ?? '') == 'done') ? 'selected' : '' ?>>انجام شده</option>
        </select><br><br>

        <input type="hidden" name="board_id" value="<?= htmlspecialchars($board_id) ?>">
        <button type="submit" name="create">ثبت تسک</button>
    </form>

    <br><a href="dashboard.php">بازگشت</a>
</body>
</html>
