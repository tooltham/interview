<?php
// seed_questions.php - นำเข้าคำถามจากไฟล์ questions_section1-7.php ลงตาราง questions
require_once '/var/www/config/db.php';

$sections = range(1, 7);
$total = 0;
foreach ($sections as $sec) {
    $file = __DIR__ . "/questions_section{$sec}.php";
    if (!file_exists($file)) continue;
    $questions = include $file;
    if (!is_array($questions)) continue;
    foreach ($questions as $q) {
        // เตรียมข้อมูล
        $id = $q['id'] ?? null;
        $label = $q['label'] ?? '';
        $type = $q['type'] ?? 'text';
        $options = isset($q['options']) ? json_encode($q['options'], JSON_UNESCAPED_UNICODE) : null;
        $is_active = isset($q['is_active']) ? (int)$q['is_active'] : 1;
        if (!$id || !$label) continue;
        // upsert
        $stmt = $pdo->prepare('REPLACE INTO questions (id, section, label, type, options, is_active) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$id, $sec, $label, $type, $options, $is_active]);
        $total++;
    }
}
echo "Imported $total questions.\n";
