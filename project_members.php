<?php
require 'config/db.php';
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$project_id = $_GET['project_id'] ?? null;
if (!$project_id) {
    die("پروژه مشخص نشده است.");
}

$stmt = $conn->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->execute([$project_id]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    die("پروژه مورد نظر یافت نشد.");
}

$current_user_mobile = $_SESSION['user']['mobile'];
$current_user_global_role = $_SESSION['user']['role'] ?? '';

$stmt = $conn->prepare("SELECT id FROM users WHERE mobile = ?");
$stmt->execute([$current_user_mobile]);
$current_user = $stmt->fetch(PDO::FETCH_ASSOC);
$current_user_id = $current_user['id'] ?? null;

if (!$current_user_id) {
    die("کاربر نامعتبر است.");
}

$stmt = $conn->prepare("SELECT role FROM project_members WHERE project_id = ? AND user_id = ?");
$stmt->execute([$project_id, $current_user_id]);
$role_in_project = $stmt->fetchColumn();

if ($role_in_project !== 'مدیر پروژه' && $current_user_global_role !== 'مدیر پروژه') {
    die("دسترسی غیرمجاز.");
}

$stmt = $conn->prepare("
    SELECT u.id, u.first_name, u.last_name, u.mobile, pm.role
    FROM project_members pm
    JOIN users u ON pm.user_id = u.id
    WHERE pm.project_id = ?
");
$stmt->execute([$project_id]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT id, first_name, last_name FROM users WHERE id NOT IN (SELECT user_id FROM project_members WHERE project_id = ?)");
$stmt->execute([$project_id]);
$all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_member'])) {
    $user_id = $_POST['user_id'] ?? null;
    $role = $_POST['role'] ?? null;

    if (!$user_id || !$role) {
        $error = "لطفاً کاربر و نقش را انتخاب کنید.";
    } else {
        $check = $conn->prepare("SELECT * FROM project_members WHERE project_id = ? AND user_id = ?");
        $check->execute([$project_id, $user_id]);
        if ($check->rowCount() > 0) {
            $error = "این کاربر قبلاً عضو پروژه است.";
        } else {
            $insert = $conn->prepare("INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, ?)");
            $insert->execute([$project_id, $user_id, $role]);
            header("Location: project_members.php?project_id=$project_id");
            exit();
        }
    }
}

if (isset($_GET['remove_user_id'])) {
    $remove_user_id = $_GET['remove_user_id'];

    $stmt = $conn->prepare("SELECT role FROM project_members WHERE project_id = ? AND user_id = ?");
    $stmt->execute([$project_id, $remove_user_id]);
    $role_to_remove = $stmt->fetchColumn();

    if ($role_to_remove === 'مدیر پروژه') {
        $error = "نمی‌توانید مدیر پروژه را حذف کنید.";
    } else {
        $delete = $conn->prepare("DELETE FROM project_members WHERE project_id = ? AND user_id = ?");
        $delete->execute([$project_id, $remove_user_id]);
        header("Location: project_members.php?project_id=$project_id");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user_id'], $_POST['new_role'])) {
    $edit_user_id = $_POST['edit_user_id'];
    $new_role = $_POST['new_role'];

    $valid_roles = ['مدیر پروژه', 'برنامه نویس بک اند', 'برنامه نویس فرانت اند', 'گرافیست', 'امنیت'];

    $stmt = $conn->prepare("SELECT role FROM project_members WHERE project_id = ? AND user_id = ?");
    $stmt->execute([$project_id, $edit_user_id]);
    $current_role = $stmt->fetchColumn();

    if ($current_role === 'مدیر پروژه' && $edit_user_id == $current_user_id) {
        // مدیر خودش نمی‌تونه نقش مدیر خودش رو تغییر بده
        $error = "شما نمی‌توانید نقش مدیر پروژه خودتان را تغییر دهید.";
    } elseif ($current_role === 'مدیر پروژه' && $edit_user_id != $current_user_id) {
        // فقط مدیر اصلی اجازه تغییر نقش مدیران دیگه رو داره
        if ($current_user_global_role !== 'مدیر پروژه') {
            $error = "نقش مدیر پروژه فقط توسط مدیر اصلی قابل تغییر است.";
        } elseif (!in_array($new_role, $valid_roles)) {
            $error = "نقش انتخاب شده معتبر نیست.";
        } else {
            $update = $conn->prepare("UPDATE project_members SET role = ? WHERE project_id = ? AND user_id = ?");
            $update->execute([$new_role, $project_id, $edit_user_id]);
            header("Location: project_members.php?project_id=$project_id");
            exit();
        }
    } else {
        if (!in_array($new_role, $valid_roles)) {
            $error = "نقش انتخاب شده معتبر نیست.";
        } else {
            $update = $conn->prepare("UPDATE project_members SET role = ? WHERE project_id = ? AND user_id = ?");
            $update->execute([$new_role, $project_id, $edit_user_id]);
            header("Location: project_members.php?project_id=$project_id");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8" />
    <title>مدیریت اعضای پروژه</title>
    <style>
        body { font-family: Tahoma, sans-serif; direction: rtl; padding: 20px; }
        table { border-collapse: collapse; width: 100%; max-width: 800px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        th { background-color: #f2f2f2; }
        select { padding: 4px; }
        button { padding: 6px 12px; cursor: pointer; }
        .error { color: red; }
        a { text-decoration: none; color: #007BFF; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h2>مدیریت اعضای پروژه</h2>

    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>نام و نام خانوادگی</th>
                <th>شماره موبایل</th>
                <th>سمت</th>
                <th>عملیات</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($members as $member): ?>
                <tr>
                    <td><?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?></td>
                    <td><?= htmlspecialchars($member['mobile']) ?></td>
                    <td>
                        <?php
                        $can_edit = false;
                        if ($current_user_global_role === 'مدیر پروژه') {
                            $can_edit = true; // مدیر پروژه می‌تونه نقش همه رو تغییر بده
                        }

                        if ($member['role'] === 'مدیر پروژه' && $member['id'] != $current_user_id) {
                            // نمایش فرم برای مدیرهای دیگه، کنترل تغییر در سرور انجام میشه
                            $can_edit = true;
                        }

                        if ($can_edit):
                        ?>
                            <form method="post" style="margin:0;">
                                <input type="hidden" name="edit_user_id" value="<?= $member['id'] ?>">
                                <select name="new_role" onchange="this.form.submit()">
                                    <?php foreach (['مدیر پروژه', 'برنامه نویس بک اند', 'برنامه نویس فرانت اند', 'گرافیست', 'امنیت'] as $r): ?>
                                        <option value="<?= $r ?>" <?= $r === $member['role'] ? 'selected' : '' ?>><?= $r ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        <?php else: ?>
                            <?= htmlspecialchars($member['role']) ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($member['role'] !== 'مدیر پروژه' && $member['id'] != $current_user_id): ?>
                            <a href="project_members.php?project_id=<?= $project_id ?>&remove_user_id=<?= $member['id'] ?>" onclick="return confirm('آیا مطمئن هستید؟')">حذف</a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <hr>

    <h3>افزودن عضو جدید</h3>
    <form method="post">
        <select name="user_id" required>
            <option value="">انتخاب کاربر</option>
            <?php foreach ($all_users as $user): ?>
                <option value="<?= $user['id'] ?>">
                    <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="role" required>
            <option value="">انتخاب نقش</option>
            <option value="برنامه نویس بک اند">برنامه نویس بک اند</option>
            <option value="برنامه نویس فرانت اند">برنامه نویس فرانت اند</option>
            <option value="گرافیست">گرافیست</option>
            <option value="امنیت">امنیت</option>
            <option value="مدیر پروژه">مدیر پروژه</option>
        </select>

        <button type="submit" name="add_member">افزودن</button>
    </form>

    <br>
    <a href="dashboard.php">بازگشت به داشبورد</a>
</body>
</html>
