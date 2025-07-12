<?php
require 'config/db.php';
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user_mobile = $_SESSION['user']['mobile'] ?? null;
if (!$user_mobile) {
    die("اطلاعات کاربر یافت نشد.");
}

$stmt = $conn->prepare("SELECT * FROM users WHERE mobile = ?");
$stmt->execute([$user_mobile]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("کاربر یافت نشد.");
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!$first_name || !$last_name || !$mobile) {
        $errors[] = "نام، نام خانوادگی و شماره موبایل نمی‌توانند خالی باشند.";
    }

    if ($mobile !== $user['mobile']) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE mobile = ? AND id != ?");
        $stmt->execute([$mobile, $user['id']]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "شماره موبایل وارد شده قبلا استفاده شده است.";
        }
    }

    if ($current_password || $new_password || $confirm_password) {
        if (!$current_password || !$new_password || !$confirm_password) {
            $errors[] = "برای تغییر رمز عبور، همه فیلدهای رمز عبور باید پر شوند.";
        } else {
            if (!password_verify($current_password, $user['password'])) {
                $errors[] = "رمز عبور فعلی اشتباه است.";
            }

            if (strlen($new_password) < 8 || !preg_match('/[0-9]/', $new_password) || !preg_match('/[a-zA-Z]/', $new_password)) {
                $errors[] = "رمز عبور جدید باید حداقل ۸ کاراکتر شامل حروف و عدد باشد.";
            }

            if ($new_password !== $confirm_password) {
                $errors[] = "رمز عبور جدید با تاییدیه مطابقت ندارد.";
            }
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, mobile = ? WHERE id = ?");
        $stmt->execute([$first_name, $last_name, $mobile, $user['id']]);

        if ($new_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $user['id']]);
        }

        // آپدیت کامل اطلاعات سشن
        $_SESSION['user']['mobile'] = $mobile;
        $_SESSION['user']['first_name'] = $first_name;
        $_SESSION['user']['last_name'] = $last_name;

        $success = "اطلاعات شما با موفقیت بروزرسانی شد.";

        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title>پروفایل کاربری</title>
</head>
<body>
    <h2>پروفایل کاربری</h2>

    <?php if (!empty($errors)): ?>
        <ul style="color:red;">
            <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars($err) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if ($success): ?>
        <p style="color:green;"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <form method="post" action="">
        <label>نام:</label><br>
        <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required><br><br>

        <label>نام خانوادگی:</label><br>
        <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required><br><br>

        <label>شماره موبایل:</label><br>
        <input type="text" name="mobile" value="<?= htmlspecialchars($user['mobile']) ?>" required><br><br>

        <hr>
        <h3>تغییر رمز عبور</h3>

        <label>رمز عبور فعلی:</label><br>
        <input type="password" name="current_password"><br><br>

        <label>رمز عبور جدید:</label><br>
        <input type="password" name="new_password"><br><br>

        <label>تایید رمز عبور جدید:</label><br>
        <input type="password" name="confirm_password"><br><br>

        <button type="submit">ذخیره تغییرات</button>
    </form>

    <br><a href="dashboard.php">بازگشت به داشبورد</a>
</body>
</html>
