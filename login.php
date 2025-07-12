<?php
require 'config/db.php';
session_start();

$errors = [];

if (isset($_POST['login'])) {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$identifier || !$password) {
        $errors[] = "همه فیلدها باید پر شوند.";
    } else {
        
        $stmt = $conn->prepare("SELECT * FROM users WHERE mobile = ? OR national_id = ?");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            
            $_SESSION['user'] = [
                'name' => $user['first_name'] . ' ' . $user['last_name'],
                'mobile' => $user['mobile'],
                'role' => $user['role']
            ];

            header("Location: dashboard.php");
            exit();
        } else {
            $errors[] = "نام کاربری یا رمز عبور اشتباه است.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8" />
    <title>ورود</title>
    <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
    <h2>ورود به سیستم</h2>

    <?php if (!empty($errors)): ?>
        <ul style="color:red;">
            <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars($err) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" action="">
        <input type="text" name="identifier" placeholder="شماره موبایل یا کد ملی" value="<?= htmlspecialchars($_POST['identifier'] ?? '') ?>" required /><br />
        <input type="password" name="password" placeholder="رمز عبور" required /><br />
        <button type="submit" name="login">ورود</button>
    </form>

    <p>ثبت نام نکرده‌اید؟ <a href="register.php">ثبت نام</a></p>
</body>
</html>
