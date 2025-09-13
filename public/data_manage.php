<?php
require_once __DIR__ . '/../src/auth.php';
require_once '/var/www/config/db.php';
if (!is_logged_in() || (!has_role('admin') && !has_role('manager'))) {
    header('Location: login.php');
    exit;
}
// ไม่ต้อง filter ด้วย user_id อีกต่อไป
//$user_id = $_GET['user_id'] ?? '';
$filter_age_min = $_GET['age_min'] ?? '';
$filter_age_max = $_GET['age_max'] ?? '';
$filter_job = $_GET['job'] ?? '';
$filter_edu = $_GET['edu'] ?? '';
$filter_income_min = $_GET['income_min'] ?? '';
$filter_income_max = $_GET['income_max'] ?? '';
$where = '';
$params = [];
// ไม่ต้อง filter user_id
//$where = 'WHERE r.user_id = ?';
//$params[] = $user_id;
// ดึง responses
$sql = "SELECT r.*, u.username FROM responses r JOIN users u ON r.user_id = u.id ORDER BY r.submitted_at ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$responses = $stmt->fetchAll();
// เตรียมดึง answers (Q2, Q4, Q5, Q6, Q8) ของ responses ที่แสดง
$resp_ids = array_column($responses, 'id');
$resp_answers = [];
if ($resp_ids) {
    $in = implode(',', array_fill(0, count($resp_ids), '?'));
    $ans_stmt = $pdo->prepare("SELECT response_id, question_id, answer FROM answers WHERE response_id IN ($in) AND question_id IN ('Q2','Q4','Q5','Q6','Q7','Q8','Q9')");
    $ans_stmt->execute($resp_ids);
    foreach ($ans_stmt->fetchAll() as $row) {
        $resp_answers[$row['response_id']][$row['question_id']] = $row['answer'];
    }
}
// ดึง user ทั้งหมดสำหรับ filter
$users = $pdo->query('SELECT id, username FROM users ORDER BY username')->fetchAll();
// Filter ฝั่ง PHP ตามอายุ, อาชีพ, การศึกษา, รายได้
$filtered = [];
foreach ($responses as $resp) {
    $ans = $resp_answers[$resp['id']] ?? [];
    // อายุ
    $age = isset($ans['Q5']) ? (int)$ans['Q5'] : null;
    if ($filter_age_min !== '' && ($age === null || $age < (int)$filter_age_min)) continue;
    if ($filter_age_max !== '' && ($age === null || $age > (int)$filter_age_max)) continue;
    // อาชีพ
    if ($filter_job !== '' && ($ans['Q8'] ?? '') !== $filter_job) continue;
    // การศึกษา
    if ($filter_edu !== '' && ($ans['Q7'] ?? '') !== $filter_edu) continue;
    // รายได้
    $income = isset($ans['Q9']) ? (float)$ans['Q9'] : null;
    if ($filter_income_min !== '' && ($income === null || $income < (float)$filter_income_min)) continue;
    if ($filter_income_max !== '' && ($income === null || $income > (float)$filter_income_max)) continue;
    $filtered[] = $resp;
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <?php include 'header.php'; ?>
    <meta charset="UTF-8">
    <title>จัดการข้อมูล</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container py-4">
        <h3 class="mb-4">จัดการข้อมูล</h3>
        <!-- ปุ่มกลับ Dashboard ถูกย้ายไป header -->
        <form class="row g-3 mb-3" method="get">
            <div class="col-auto">
                <label class="col-form-label">อายุ:</label>
            </div>
            <div class="col-auto">
                <input type="number" name="age_min" class="form-control" placeholder="ต่ำสุด" value="<?= htmlspecialchars($filter_age_min) ?>" style="width:80px;">
            </div>
            <div class="col-auto">-</div>
            <div class="col-auto">
                <input type="number" name="age_max" class="form-control" placeholder="สูงสุด" value="<?= htmlspecialchars($filter_age_max) ?>" style="width:80px;">
            </div>
            <div class="col-auto">
                <label for="job" class="col-form-label">อาชีพ:</label>
            </div>
            <div class="col-auto">
                <select name="job" id="job" class="form-select">
                    <option value="">-- ทั้งหมด --</option>
                    <option value="ไม่ได้ประกอบอาชีพ" <?= $filter_job == 'ไม่ได้ประกอบอาชีพ' ? 'selected' : '' ?>>ไม่ได้ประกอบอาชีพ</option>
                    <option value="เกษตรกร" <?= $filter_job == 'เกษตรกร' ? 'selected' : '' ?>>เกษตรกร</option>
                    <option value="รับจ้าง" <?= $filter_job == 'รับจ้าง' ? 'selected' : '' ?>>รับจ้าง</option>
                    <option value="ค้าขาย" <?= $filter_job == 'ค้าขาย' ? 'selected' : '' ?>>ค้าขาย</option>
                    <option value="รับราชการ/รัฐวิสาหกิจ" <?= $filter_job == 'รับราชการ/รัฐวิสาหกิจ' ? 'selected' : '' ?>>รับราชการ/รัฐวิสาหกิจ</option>
                    <option value="อื่นๆ" <?= $filter_job == 'อื่นๆ' ? 'selected' : '' ?>>อื่นๆ</option>
                </select>
            </div>
            <div class="col-auto">
                <label for="edu" class="col-form-label">การศึกษา:</label>
            </div>
            <div class="col-auto">
                <select name="edu" id="edu" class="form-select">
                    <option value="">-- ทั้งหมด --</option>
                    <option value="ไม่ได้เรียน" <?= $filter_edu == 'ไม่ได้เรียน' ? 'selected' : '' ?>>ไม่ได้เรียน</option>
                    <option value="ประถมศึกษา" <?= $filter_edu == 'ประถมศึกษา' ? 'selected' : '' ?>>ประถมศึกษา</option>
                    <option value="มัธยมศึกษา/ปวช." <?= $filter_edu == 'มัธยมศึกษา/ปวช.' ? 'selected' : '' ?>>มัธยมศึกษา/ปวช.</option>
                    <option value="อนุปริญญา/ปวส." <?= $filter_edu == 'อนุปริญญา/ปวส.' ? 'selected' : '' ?>>อนุปริญญา/ปวส.</option>
                    <option value="ปริญญาตรี" <?= $filter_edu == 'ปริญญาตรี' ? 'selected' : '' ?>>ปริญญาตรี</option>
                    <option value="สูงกว่าปริญญาตรี" <?= $filter_edu == 'สูงกว่าปริญญาตรี' ? 'selected' : '' ?>>สูงกว่าปริญญาตรี</option>
                </select>
            </div>
            <div class="col-auto">
                <label class="col-form-label">รายได้:</label>
            </div>
            <div class="col-auto">
                <input type="number" name="income_min" class="form-control" placeholder="ต่ำสุด" value="<?= htmlspecialchars($filter_income_min) ?>" style="width:100px;">
            </div>
            <div class="col-auto">-</div>
            <div class="col-auto">
                <input type="number" name="income_max" class="form-control" placeholder="สูงสุด" value="<?= htmlspecialchars($filter_income_max) ?>" style="width:100px;">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="data_manage.php" class="btn btn-secondary ms-2">Clear</a>
            </div>
        </form>
        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th>ลำดับ</th>
                    <th>ชื่อ-นามสกุล</th>
                    <th>เพศ</th>
                    <th>อายุ</th>
                    <th>สถานภาพ</th>
                    <th>อาชีพ</th>
                    <th>ระดับการศึกษา</th>
                    <th>รายได้ (บาท/เดือน)</th>
                    <th>วันที่ส่ง</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($filtered as $resp): ?>
                    <tr>
                        <td><?= $resp['id'] ?></td>
                        <td><?= htmlspecialchars($resp_answers[$resp['id']]['Q2'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($resp_answers[$resp['id']]['Q4'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($resp_answers[$resp['id']]['Q5'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($resp_answers[$resp['id']]['Q6'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($resp_answers[$resp['id']]['Q8'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($resp_answers[$resp['id']]['Q7'] ?? '-') ?></td>
                        <td>
                            <?php
                            $income = $resp_answers[$resp['id']]['Q9'] ?? null;
                            echo $income !== null && $income !== '' ? number_format((float)$income, 0) : '-';
                            ?>
                        </td>
                        <td><?= htmlspecialchars($resp['submitted_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    </div>
    <?php include 'footer.php'; ?>
</body>

</html>