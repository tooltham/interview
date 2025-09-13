<?php

require_once __DIR__ . '/../src/auth.php';
require_once '/var/www/config/db.php';


$user_id = $_SESSION['user_id'];
$is_admin = has_role('admin');
if (!is_logged_in() || (!has_role('user') && !$is_admin)) {
    header('Location: login.php');
    exit;
}

// --- ลบ response (soft delete) ---
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    if ($is_admin) {
        $pdo->prepare('UPDATE responses SET status="deleted" WHERE id=?')->execute([$del_id]);
    } else {
        $pdo->prepare('UPDATE responses SET status="deleted" WHERE id=? AND user_id=?')->execute([$del_id, $user_id]);
    }
    header('Location: survey.php');
    exit;
}

// --- ดู/แก้ไข response ---
$view_response = null;
if (isset($_GET['view']) || isset($_GET['edit'])) {
    $edit_mode = isset($_GET['edit']);
    $rid = intval($_GET['view'] ?? $_GET['edit']);
    if ($is_admin) {
        $stmt = $pdo->prepare('SELECT * FROM responses WHERE id=? AND status="active"');
        $stmt->execute([$rid]);
    } else {
        $stmt = $pdo->prepare('SELECT * FROM responses WHERE id=? AND user_id=? AND status="active"');
        $stmt->execute([$rid, $user_id]);
    }
    $view_response = $stmt->fetch();
    if ($view_response) {
        $ans_stmt = $pdo->prepare('SELECT question_id, answer FROM answers WHERE response_id=?');
        $ans_stmt->execute([$rid]);
        $view_answers = [];
        foreach ($ans_stmt->fetchAll() as $row) {
            $view_answers[$row['question_id']] = $row['answer'];
        }
    }
}

// responses ของ user หรือทั้งหมด (admin)
if ($is_admin) {
    $responses = $pdo->query('SELECT * FROM responses WHERE status = "active" ORDER BY submitted_at ASC')->fetchAll();
} else {
    $responses = $pdo->prepare('SELECT * FROM responses WHERE user_id = ? AND status = "active" ORDER BY submitted_at ASC');
    $responses->execute([$user_id]);
    $responses = $responses->fetchAll();
}

// ตรวจสอบว่ามี response ล่าสุดหรือไม่ (สำหรับฟอร์มใหม่/แก้ไข)
if ($is_admin) {
    $has_response = false; // admin สามารถเพิ่ม responses ได้ตลอด
    $msg = '';
} else {
    $has_response = count($responses) > 0 ? $responses[0] : false;
    $msg = $has_response ? 'คุณได้ส่งแบบสอบถามแล้ว' : '';
}

// ดึงคำถามทั้งหมด (ตัวอย่าง: section 1)
$questions = $pdo->query('SELECT * FROM questions WHERE is_active = 1 ORDER BY section, LENGTH(id), id')->fetchAll();

// Validation & บันทึกคำตอบ
$errors = [];
// --- เพิ่ม/แก้ไข response ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $is_edit = isset($_GET['edit']);
    $edit_id = $is_edit ? intval($_GET['edit']) : null;
    // ตรวจสอบสิทธิ์การแก้ไข (user ต้องเป็นเจ้าของ response)
    if ($is_edit) {
        if ($is_admin) {
            $stmt = $pdo->prepare('SELECT * FROM responses WHERE id=? AND status="active"');
            $stmt->execute([$edit_id]);
        } else {
            $stmt = $pdo->prepare('SELECT * FROM responses WHERE id=? AND user_id=? AND status="active"');
            $stmt->execute([$edit_id, $user_id]);
        }
        $edit_response = $stmt->fetch();
        if (!$edit_response) {
            header('Location: survey.php');
            exit;
        }
    }
    // Validate
    foreach ($questions as $q) {
        $required = isset($q['required']) ? $q['required'] : false;
        $val = $_POST['q_' . $q['id']] ?? null;
        if ($required) {
            if ($q['type'] === 'checkbox') {
                if (empty($val) || !is_array($val)) {
                    $errors[$q['id']] = 'กรุณาเลือกอย่างน้อย 1 ตัวเลือก';
                }
            } else if ($q['type'] === 'radio') {
                if (empty($val)) {
                    $errors[$q['id']] = 'กรุณาเลือกตัวเลือก';
                }
            } else {
                if (empty($val)) {
                    $errors[$q['id']] = 'กรุณากรอกข้อมูล';
                }
            }
        }
    }
    if (!$errors) {
        $pdo->beginTransaction();
        if ($is_edit) {
            // update responses.updated_at
            $pdo->prepare('UPDATE responses SET updated_at=NOW() WHERE id=?')->execute([$edit_id]);
            // ลบ answers เดิม
            $pdo->prepare('DELETE FROM answers WHERE response_id=?')->execute([$edit_id]);
            $response_id = $edit_id;
        } else {
            $pdo->prepare('INSERT INTO responses (user_id) VALUES (?)')->execute([$user_id]);
            $response_id = $pdo->lastInsertId();
        }
        foreach ($questions as $q) {
            $answer = $_POST['q_' . $q['id']] ?? null;
            if (is_array($answer)) $answer = implode(", ", $answer);
            $pdo->prepare('INSERT INTO answers (response_id, question_id, answer) VALUES (?, ?, ?)')
                ->execute([$response_id, $q['id'], $answer]);
        }
        $pdo->commit();
        header('Location: survey.php?success=1');
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="th">

<head>
    <?php include 'header.php'; ?>
    <meta charset="UTF-8">
    <title>จัดการแบบสอบถาม</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;700&display=swap" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>

<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3>จัดการแบบสอบถาม</h3>
        </div>

        <h5 class="mb-3">ประวัติการกรอกแบบสอบถาม</h5>
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div></div>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addSurveyModal">เพิ่มแบบสอบถาม</button>
        </div>
        <?php
        // เตรียมดึงชื่อ-นามสกุล (Q2) และที่อยู่ (Q3) ของแต่ละ response
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
        ?>
        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th>ลำดับ</th>
                    <th>ชื่อ-นามสกุล</th>
                    <th>เพศ</th>
                    <th>อายุ</th>
                    <th>สถานภาพ</th>
                    <th>อาชีพ</th>
                    <th>วันที่ส่ง</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($responses as $i => $resp): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($resp_answers[$resp['id']]['Q2'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($resp_answers[$resp['id']]['Q4'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($resp_answers[$resp['id']]['Q5'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($resp_answers[$resp['id']]['Q6'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($resp_answers[$resp['id']]['Q8'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($resp['submitted_at']) ?></td>
                        <td>
                            <a href="survey.php?view=<?= $resp['id'] ?>" class="btn btn-sm btn-info">ดู</a>
                            <a href="survey.php?edit=<?= $resp['id'] ?>" class="btn btn-sm btn-warning">แก้ไข</a>
                            <a href="survey.php?delete=<?= $resp['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('ยืนยันการลบ?')">ลบ</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <hr class="my-4">

        <!-- Modal ดู/แก้ไขแบบสอบถาม -->
        <?php if ($view_response): ?>
            <div class="modal fade show" id="viewSurveyModal" tabindex="-1" aria-labelledby="viewSurveyModalLabel" style="display:block; background:rgba(0,0,0,0.5);">
                <div class="modal-dialog modal-lg">
                    <form method="post" class="modal-content" novalidate>
                        <div class="modal-header">
                            <h5 class="modal-title" id="viewSurveyModalLabel">
                                <?= isset($_GET['edit']) ? 'แก้ไขแบบสอบถาม' : 'ดูแบบสอบถาม' ?>
                            </h5>
                            <a href="survey.php" class="btn-close"></a>
                        </div>
                        <div class="modal-body">
                            <?php foreach ($questions as $idx => $q): ?>
                                <div class="mb-3">
                                    <label class="form-label">Q<?= ($idx + 1) ?>: <?= htmlspecialchars($q['label']) ?><?= isset($q['required']) && $q['required'] ? ' <span class=\'text-danger\'>*</span>' : '' ?></label>
                                    <?php $val = $view_answers[$q['id']] ?? ''; ?>
                                    <?php if ($q['type'] === 'text'): ?>
                                        <input type="text" name="q_<?= $q['id'] ?>" class="form-control" value="<?= htmlspecialchars($val) ?>" <?= isset($_GET['edit']) ? '' : 'readonly' ?>>
                                    <?php elseif ($q['type'] === 'number'): ?>
                                        <input type="number" name="q_<?= $q['id'] ?>" class="form-control" value="<?= htmlspecialchars($val) ?>" <?= isset($_GET['edit']) ? '' : 'readonly' ?>>
                                    <?php elseif ($q['type'] === 'textarea'): ?>
                                        <textarea name="q_<?= $q['id'] ?>" class="form-control" <?= isset($_GET['edit']) ? '' : 'readonly' ?>><?= htmlspecialchars($val) ?></textarea>
                                        <?php elseif ($q['type'] === 'radio' && $q['options']):
                                        $opts = json_decode($q['options'], true);
                                        foreach ($opts as $opt): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="q_<?= $q['id'] ?>" value="<?= htmlspecialchars($opt) ?>" id="viewq<?= $q['id'] ?>_<?= htmlspecialchars($opt) ?>" <?= ($val == $opt) ? 'checked' : '' ?> <?= isset($_GET['edit']) ? '' : 'disabled' ?>>
                                                <label class="form-check-label" for="viewq<?= $q['id'] ?>_<?= htmlspecialchars($opt) ?>">
                                                    <?= htmlspecialchars($opt) ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                        <?php elseif ($q['type'] === 'checkbox' && $q['options']):
                                        $opts = json_decode($q['options'], true);
                                        $vals = array_map('trim', explode(',', $val));
                                        foreach ($opts as $opt): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="q_<?= $q['id'] ?>[]" value="<?= htmlspecialchars($opt) ?>" id="viewq<?= $q['id'] ?>_<?= htmlspecialchars($opt) ?>" <?= in_array($opt, $vals) ? 'checked' : '' ?> <?= isset($_GET['edit']) ? '' : 'disabled' ?>>
                                                <label class="form-check-label" for="viewq<?= $q['id'] ?>_<?= htmlspecialchars($opt) ?>">
                                                    <?= htmlspecialchars($opt) ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="modal-footer">
                            <a href="survey.php" class="btn btn-secondary">ปิด</a>
                            <?php if (isset($_GET['edit'])): ?>
                                <button type="submit" class="btn btn-primary">บันทึก</button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
            <script>
                document.body.classList.add('modal-open');
            </script>
        <?php endif; ?>


        <!-- Modal เพิ่มแบบสอบถาม -->
        <div class="modal fade" id="addSurveyModal" tabindex="-1" aria-labelledby="addSurveyModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <form method="post" class="modal-content" novalidate>
                    <div class="modal-header">
                        <h5 class="modal-title" id="addSurveyModalLabel">เพิ่มแบบสอบถาม</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?php foreach ($questions as $idx => $q): ?>
                            <div class="mb-3">
                                <label class="form-label">Q<?= ($idx + 1) ?>: <?= htmlspecialchars($q['label']) ?><?= isset($q['required']) && $q['required'] ? ' <span class=\'text-danger\'>*</span>' : '' ?></label>
                                <?php if ($q['type'] === 'text'): ?>
                                    <input type="text" name="q_<?= $q['id'] ?>" class="form-control<?= isset($errors[$q['id']]) ? ' is-invalid' : '' ?>" value="<?= htmlspecialchars($_POST['q_' . $q['id']] ?? '') ?>" <?= isset($q['required']) && $q['required'] ? 'required' : '' ?>>
                                <?php elseif ($q['type'] === 'number'): ?>
                                    <input type="number" name="q_<?= $q['id'] ?>" class="form-control<?= isset($errors[$q['id']]) ? ' is-invalid' : '' ?>" value="<?= htmlspecialchars($_POST['q_' . $q['id']] ?? '') ?>" <?= isset($q['required']) && $q['required'] ? 'required' : '' ?>>
                                <?php elseif ($q['type'] === 'textarea'): ?>
                                    <textarea name="q_<?= $q['id'] ?>" class="form-control<?= isset($errors[$q['id']]) ? ' is-invalid' : '' ?>" <?= isset($q['required']) && $q['required'] ? 'required' : '' ?>><?= htmlspecialchars($_POST['q_' . $q['id']] ?? '') ?></textarea>
                                    <?php elseif ($q['type'] === 'radio' && $q['options']):
                                    $opts = json_decode($q['options'], true);
                                    foreach ($opts as $opt): ?>
                                        <div class="form-check">
                                            <input class="form-check-input<?= isset($errors[$q['id']]) ? ' is-invalid' : '' ?>" type="radio" name="q_<?= $q['id'] ?>" value="<?= htmlspecialchars($opt) ?>" id="q<?= $q['id'] ?>_<?= htmlspecialchars($opt) ?>" <?= (isset($_POST['q_' . $q['id']]) && $_POST['q_' . $q['id']] == $opt) ? 'checked' : '' ?> <?= isset($q['required']) && $q['required'] ? 'required' : '' ?>>
                                            <label class="form-check-label" for="q<?= $q['id'] ?>_<?= htmlspecialchars($opt) ?>">
                                                <?= htmlspecialchars($opt) ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php elseif ($q['type'] === 'checkbox' && $q['options']):
                                    $opts = json_decode($q['options'], true);
                                    foreach ($opts as $opt): ?>
                                        <div class="form-check">
                                            <input class="form-check-input<?= isset($errors[$q['id']]) ? ' is-invalid' : '' ?>" type="checkbox" name="q_<?= $q['id'] ?>[]" value="<?= htmlspecialchars($opt) ?>" id="q<?= $q['id'] ?>_<?= htmlspecialchars($opt) ?>" <?= (isset($_POST['q_' . $q['id']]) && is_array($_POST['q_' . $q['id']]) && in_array($opt, $_POST['q_' . $q['id']])) ? 'checked' : '' ?> <?= isset($q['required']) && $q['required'] ? 'required' : '' ?>>
                                            <label class="form-check-label" for="q<?= $q['id'] ?>_<?= htmlspecialchars($opt) ?>">
                                                <?= htmlspecialchars($opt) ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <?php if (isset($errors[$q['id']])): ?>
                                    <div class="invalid-feedback d-block"> <?= htmlspecialchars($errors[$q['id']]) ?> </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-primary">ส่งแบบสอบถาม</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php include 'footer.php'; ?>
</body>

</html>