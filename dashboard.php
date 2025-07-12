<?php
require 'config/db.php';
require 'auth.php'; // پیشنهاد: فایل auth.php بساز که فقط سشن رو چک کنه، ساده‌تر میشه

$user_mobile = $_SESSION['user']['mobile'] ?? '';
$user_role = $_SESSION['user']['role'] ?? '';

$stmt = $conn->prepare("SELECT id, first_name, last_name FROM users WHERE mobile = ?");
$stmt->execute([$user_mobile]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) die("کاربر یافت نشد.");
$user_id = $user['id'];

// فقط پروژه‌هایی که کاربر توش عضوه یا خودش مدیره
$stmt = $conn->prepare("
    SELECT DISTINCT p.*
    FROM projects p
    LEFT JOIN project_members pm ON pm.project_id = p.id
    LEFT JOIN users u ON pm.user_id = u.id
    WHERE pm.user_id = :user_id OR p.manager_mobile = :user_mobile
    ORDER BY p.created_at DESC
");
$stmt->execute([
    'user_id' => $user_id,
    'user_mobile' => $user_mobile
]);
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// نقش کاربر در هر پروژه
$stmt2 = $conn->prepare("SELECT project_id, role FROM project_members WHERE user_id = ?");
$stmt2->execute([$user_id]);
$roles = $stmt2->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title>داشبورد کاربر</title>
    <style>
        body { font-family: Tahoma, sans-serif; direction: rtl; padding: 20px; }
        ul { list-style-type: none; padding: 0; }
        li { margin-bottom: 10px; }
        a { text-decoration: none; color: #007BFF; }
        a:hover { text-decoration: underline; }
        .btn-create { background: #28a745; color: white; padding: 8px 12px; border-radius: 4px; text-decoration: none; }
        .btn-create:hover { background: #218838; }
    </style>
</head>
<body>
    <h2>سلام، <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h2>
    <p>سمت شما: <?= htmlspecialchars($user_role) ?></p>

    <h3>پروژه‌های شما</h3>
    <a href="create_project.php" class="btn-create">+ ساخت پروژه جدید</a>

    <?php if (empty($projects)): ?>
        <p>هیچ پروژه‌ای برای شما یافت نشد.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($projects as $project): ?>
                <li>
                    <a href="boards.php?project_id=<?= $project['id'] ?>">
                        <?= htmlspecialchars($project['title']) ?>
                    </a>
                    <?php
                    $role_in_project = $roles[$project['id']] ?? null;
                    if ($role_in_project === 'مدیر پروژه' || $project['manager_mobile'] === $user_mobile): ?>
                        | <a href="project_members.php?project_id=<?= $project['id'] ?>">مدیریت اعضا</a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <hr>

    <h3>اطلاعات حساب کاربری</h3>
    <p>نام: <?= htmlspecialchars($user['first_name']) ?></p>
    <p>نام خانوادگی: <?= htmlspecialchars($user['last_name']) ?></p>
    <p>شماره موبایل: <?= htmlspecialchars($user_mobile) ?></p>
    <p>سمت: <?= htmlspecialchars($user_role) ?></p>

    <p>
        سلام، <?= htmlspecialchars($user['first_name']) ?> | 
        <a href="profile.php">پروفایل کاربری</a> | 
        <a href="logout.php">خروج از حساب</a>
    </p>
</body>
</html>
