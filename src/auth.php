<?php
// src/auth.php - ฟังก์ชันตรวจสอบสิทธิ์และ session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

function has_role($role)
{
    return isset($_SESSION['roles']) && in_array($role, $_SESSION['roles']);
}

// ตัวอย่างการใช้งานในแต่ละหน้า:
// require_once __DIR__ . '/auth.php';
// if (!is_logged_in() || !has_role('admin')) {
//     header('Location: login.php');
//     exit;
// }
