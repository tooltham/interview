<?php
require_once __DIR__ . '/../src/auth.php';
require_once '/var/www/config/db.php';
if (!is_logged_in()) return;
$user_id = $_SESSION['user_id'];
// ดึงชื่อ-นามสกุล
$stmt = $pdo->prepare("SELECT a.answer FROM answers a JOIN responses r ON a.response_id = r.id WHERE r.user_id = ? AND a.question_id = 'Q2' ORDER BY r.submitted_at DESC LIMIT 1");
$stmt->execute([$user_id]);
$fullname = $stmt->fetchColumn() ?: 'ผู้ใช้';
?>
<style>
    .header-bar {
        width: 100%;
        background: #f8f9fa;
        border-bottom: 1px solid #e0e0e0;
        padding: 12px 0 12px 0;
        position: sticky;
        top: 0;
        z-index: 1100;
    }

    .header-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 24px;
    }

    .greeting {
        font-weight: 500;
        color: #2d3a4b;
    }

    .logout-btn {
        min-width: 90px;
    }
</style>
<div class="header-bar">
    <div class="header-content">
        <div class="greeting">สวัสดี, <?= htmlspecialchars($fullname) ?></div>
        <a href="logout.php" class="btn btn-outline-danger logout-btn">Logout</a>
    </div>
</div>