<?php
require_once __DIR__ . '/../src/auth.php';
require_once '/var/www/config/db.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}


$user_id = $_SESSION['user_id'] ?? null;
$roles = $_SESSION['roles'] ?? [];
$fullname = '';
if ($user_id) {
    $stmt = $pdo->prepare('SELECT name FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $row = $stmt->fetch();
    if ($row && !empty($row['name'])) {
        $fullname = $row['name'];
    }
}


/*
// Greeting by time
$hour = (int)date('G');
if ($hour >= 5 && $hour < 12) {
    $greeting = 'สวัสดีตอนเช้า';
} elseif ($hour >= 12 && $hour < 17) {
    $greeting = 'สวัสดีตอนบ่าย';
} elseif ($hour >= 17 && $hour < 21) {
    $greeting = 'สวัสดีตอนเย็น';
} else {
    $greeting = 'สวัสดีตอนกลางคืน';
}
*/

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
    <?php include 'header.php'; ?>
    <div class="container py-5">
        <!-- <div class="d-flex justify-content-between align-items-center mb-4">
            <h3><?= $greeting ?><?= $fullname ? 'คุณ ' . htmlspecialchars($fullname) : '' ?>!</h3>
        </div> -->
        <!-- <div class="mb-4">
            <p>คุณเข้าสู่ระบบในบทบาท: <strong><?= htmlspecialchars(implode(', ', $roles)) ?></strong></p>
        </div> -->
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
        <!-- เมนูถูกย้ายไป header.php -->
    </div>
    <?php include 'footer.php'; ?>
</body>

</html>