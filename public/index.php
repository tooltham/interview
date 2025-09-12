<?php
require_once __DIR__ . '/../src/auth.php';
require_once '/var/www/config/db.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$username = $_SESSION['username'] ?? '';
$roles = $_SESSION['roles'] ?? [];

// ดึงข้อมูลสรุป
$user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$form_count = $pdo->query("SELECT COUNT(*) FROM responses")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;700&display=swap" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>

<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3>สวัสดีคุณ <?= htmlspecialchars($username) ?>!</h3>
            <a href="logout.php" class="btn btn-outline-danger">Logout</a>
        </div>
        <div class="mb-4">
            <p>คุณเข้าสู่ระบบในบทบาท: <strong><?= htmlspecialchars(implode(', ', $roles)) ?></strong></p>
        </div>
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">จำนวนผู้ใช้ทั้งหมด</h5>
                        <p class="display-5"><?= $user_count ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">จำนวนฟอร์มที่ถูกกรอก</h5>
                        <p class="display-5"><?= $form_count ?></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-5">
            <?php if (has_role('admin')): ?>
                <a href="users.php" class="btn btn-primary">จัดการผู้ใช้</a>
                <a href="survey.php" class="btn btn-warning ms-2">จัดการแบบสอบถาม</a>
                <a href="data_manage.php" class="btn btn-info ms-2">จัดการข้อมูล</a>
            <?php endif; ?>
            <?php if (has_role('user')): ?>
                <a href="survey.php" class="btn btn-success">จัดการแบบสอบถาม</a>
            <?php endif; ?>
            <?php if (has_role('manager')): ?>
                <a href="data_manage.php" class="btn btn-info">จัดการข้อมูล</a>
            <?php endif; ?>
        </div>
    </div>
    <?php include 'footer.php'; ?>
</body>

</html>