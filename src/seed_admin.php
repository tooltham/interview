<?php
// seed_admin.php - สร้าง admin user และ role อัตโนมัติ
require_once '/var/www/config/db.php'; // ใช้ absolute path ใน container

// เพิ่ม roles ถ้ายังไม่มี
$roles = [
    ['admin', 'ผู้ดูแลระบบ'],
    ['user', 'ผู้ใช้งานทั่วไป'],
    ['manager', 'ผู้บริหาร']
];
foreach ($roles as $role) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO roles (name, description) VALUES (?, ?)");
    $stmt->execute($role);
}

// ข้อมูล admin
$username = 'admin';
$email = 'apirak@npu.ac.th';
$password = password_hash('?@xfree86', PASSWORD_DEFAULT); // เปลี่ยนรหัสผ่านตามต้องการ
$name = 'Dr.Apirak Tooltham';
$position = 'Administrator';
$department = 'Nakhon Phanom University';
$phone = '0624419599';

// เพิ่ม user ถ้ายังไม่มี
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
$stmt->execute([$username, $email]);
if (!$stmt->fetch()) {
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, name, position, department, phone, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
    $stmt->execute([$username, $email, $password, $name, $position, $department, $phone]);
    $user_id = $pdo->lastInsertId();
} else {
    $user_id = $pdo->query("SELECT id FROM users WHERE username = 'admin'")->fetchColumn();
}

// กำหนด role ให้ admin
$role_id = $pdo->query("SELECT id FROM roles WHERE name='admin'")->fetchColumn();
$stmt = $pdo->prepare("INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, ?)");
$stmt->execute([$user_id, $role_id]);

echo "Seed admin user and roles completed!\n";
