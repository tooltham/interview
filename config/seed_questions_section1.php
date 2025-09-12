<?php
// seed_questions_section1.php - นำเข้าคำถามจาก questions_section1.php ลงตาราง questions
require_once '/var/www/config/db.php';

$file = __DIR__ . '/questions_section1.php';
if (!file_exists($file)) {
    exit("ไม่พบไฟล์ questions_section1.php\n");
}
$questions = include $file;
if (!is_array($questions)) {
    exit("questions_section1.php ต้อง return array\n");
}
$total = 0;
foreach ($questions as $q) {
    $id = $q['id'] ?? null;
    $label = $q['label'] ?? '';
    $type = $q['type'] ?? 'text';
    $options = isset($q['options']) ? json_encode($q['options'], JSON_UNESCAPED_UNICODE) : null;
    $is_active = isset($q['is_active']) ? (int)$q['is_active'] : 1;
    if (!$id || !$label) continue;
    $stmt = $pdo->prepare('REPLACE INTO questions (id, section, label, type, options, is_active) VALUES (?, 1, ?, ?, ?, ?)');
    $stmt->execute([$id, $label, $type, $options, $is_active]);
    $total++;
}
echo "Imported $total questions from section 1.\n";
