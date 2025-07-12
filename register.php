<?php
require 'config/db.php';
session_start();

$errors = [];
$valid_roles = ["مدیر پروژه", "برنامه نویس بک اند", "برنامه نویس فرانت اند", "گرافیست", "امنیت"];

if (isset($_POST['register'])) {
    
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $national_id = trim($_POST['national_id'] ?? '');
    $role = $_POST['role'] ?? '';
    $password = $_POST['password'] ?? '';

   
    if (!$first_name || !$last_name || !$mobile || !$national_id || !$role || !$password) {
        $errors[] = "تمام فیلدها باید پر شوند.";
    }

   
    if (strlen($password) < 8 || !preg_match('/[0-9]/', $password) || !preg_match('/[a-zA-Z]/', $password)) {
        $errors[] = "رمز عبور باید حداقل 8 کاراکتر شامل حروف و عدد باشد.";
    }

    
    if (!in_array($role, $valid_roles)) {
        $errors[] = "سمت انتخاب شده معتبر نیست.";
    }

    if (empty($errors)) {
       
        $stmt = $conn->prepare("SELECT id FROM users WHERE mobile = ? OR national_id = ?");
        $stmt->execute([$mobile, $national_id]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "موبایل یا کد ملی قبلاً ثبت شده است.";
        } else {
            
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

           
            $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, mobile, national_id, password, role) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$first_name, $last_name, $mobile, $national_id, $hashed_password, $role]);

            
            $_SESSION['user'] = [
                'name' => $first_name . ' ' . $last_name,
                'mobile' => $mobile,
                'role' => $role
            ];

            header("Location: dashboard.php");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title>ثبت نام</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <h2>ثبت نام</h2>

    <?php if (!empty($errors)): ?>
        <ul style="color:red;">
            <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars($err) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" action="">
        <input type="text" name="first_name" placeholder="نام" value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required><br>
        <input type="text" name="last_name" placeholder="نام خانوادگی" value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" required><br>
        <input type="text" name="mobile" placeholder="شماره موبایل" value="<?= htmlspecialchars($_POST['mobile'] ?? '') ?>" required><br>
        <input type="text" name="national_id" placeholder="کد ملی" value="<?= htmlspecialchars($_POST['national_id'] ?? '') ?>" required><br>
        <select name="role" required>
            <option value="">سمت خود را انتخاب کنید</option>
            <?php
            foreach ($valid_roles as $r) {
                $selected = (($_POST['role'] ?? '') === $r) ? 'selected' : '';
                echo "<option value=\"$r\" $selected>$r</option>";
            }
            ?>
        </select><br>
        <input type="password" name="password" placeholder="رمز عبور" required><br>
        <button type="submit" name="register">ثبت نام</button>
    </form>

    <p>قبلاً ثبت نام کرده‌اید؟ <a href="login.php">ورود</a></p>
</body>
</html>
