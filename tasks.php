<?php
require 'config/db.php';
session_start();


if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}


$user_mobile = $_SESSION['user']['mobile'] ?? null;
if (!$user_mobile) {
    die("کاربر یافت نشد.");
}


$stmt = $conn->prepare("SELECT id FROM users WHERE mobile = ?");
$stmt->execute([$user_mobile]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) die("کاربر یافت نشد.");

$user_id = $user['id'];

$board_id = $_GET['board_id'] ?? null;
if (!$board_id) {
    die("برد مشخص نشده است.");
}


$stmt = $conn->prepare("SELECT project_id FROM boards WHERE id = ?");
$stmt->execute([$board_id]);
$board = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$board) die("برد یافت نشد.");

$project_id = $board['project_id'];


$stmt = $conn->prepare("
    SELECT u.mobile, u.first_name, u.last_name
    FROM users u
    JOIN project_members pm ON u.id = pm.user_id
    WHERE pm.project_id = ?
");
$stmt->execute([$project_id]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

$status_filter = $_GET['status'] ?? 'all';

if ($status_filter == 'all') {
    $stmt = $conn->prepare("
        SELECT t.*, u.first_name, u.last_name
        FROM tasks t
        LEFT JOIN users u ON t.assigned_to = u.mobile
        WHERE t.board_id = ?
        ORDER BY t.created_at DESC
    ");
    $stmt->execute([$board_id]);
} else {
    $stmt = $conn->prepare("
        SELECT t.*, u.first_name, u.last_name
        FROM tasks t
        LEFT JOIN users u ON t.assigned_to = u.mobile
        WHERE t.board_id = ? AND t.status = ?
        ORDER BY t.created_at DESC
    ");
    $stmt->execute([$board_id, $status_filter]);
}
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title>تسک‌ها</title>
    <style>
        table {border-collapse: collapse; width: 100%;}
        th, td {border: 1px solid #ddd; padding: 8px; text-align: center;}
        th {background-color: #f2f2f2;}
        tr:hover {background-color: #f9f9f9;}
    </style>
</head>
<body>
    <h2>تسک‌های برد شماره <?= htmlspecialchars($board_id) ?></h2>

    
    <form method="get" action="">
        <input type="hidden" name="board_id" value="<?= htmlspecialchars($board_id) ?>">
        <label>فیلتر وضعیت:</label>
        <select name="status" onchange="this.form.submit()">
            <option value="all" <?= $status_filter == 'all' ? 'selected' : '' ?>>همه</option>
            <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>منتظر</option>
            <option value="in_progress" <?= $status_filter == 'in_progress' ? 'selected' : '' ?>>در حال انجام</option>
            <option value="done" <?= $status_filter == 'done' ? 'selected' : '' ?>>انجام شده</option>
        </select>
    </form>

    <hr>

    
    <table>
        <thead>
            <tr>
                <th>عنوان</th>
                <th>توضیحات</th>
                <th>وضعیت</th>
                <th>مسئول</th>
                <th>ایجاد شده در</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($tasks)): ?>
                <tr>
                    <td colspan="5">تسکی یافت نشد.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($tasks as $task): ?>
                    <tr>
                        <td><?= htmlspecialchars($task['title']) ?></td>
                        <td><?= nl2br(htmlspecialchars($task['description'])) ?></td>
                        <td>
                            <?php
                            switch ($task['status']) {
                                case 'pending': echo "منتظر"; break;
                                case 'in_progress': echo "در حال انجام"; break;
                                case 'done': echo "انجام شده"; break;
                                default: echo "نامشخص"; break;
                            }
                            ?>
                        </td>
                        <td>
                            <?= $task['first_name'] ? 
                                htmlspecialchars($task['first_name'] . ' ' . $task['last_name']) : 
                                'تعیین نشده' ?>
                        </td>
                        <td><?= htmlspecialchars($task['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <hr>

    <a href="create_task.php?board_id=<?= htmlspecialchars($board_id) ?>">ثبت تسک جدید</a><br>
    <a href="boards.php?project_id=<?= htmlspecialchars($project_id) ?>">بازگشت به بردها</a>
</body>
</html>
