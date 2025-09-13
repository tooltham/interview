<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../src/auth.php';
?>
<style>
    .main-header {
        background: #fff;
        border-bottom: 1px solid #e5e7eb;
        box-shadow: 0 2px 8px 0 rgba(0, 0, 0, 0.02);
        padding: 0.5rem 0;
    }

    .main-header .navbar-brand {
        font-weight: 600;
        font-size: 1.25rem;
        color: #2d3a4b !important;
        letter-spacing: 0.5px;
    }

    .main-header .navbar-nav .nav-link {
        color: #374151 !important;
        font-weight: 500;
        padding: 0.5rem 1.1rem;
        border-radius: 6px;
        transition: background 0.15s, color 0.15s;
    }

    .main-header .navbar-nav .nav-link:hover,
    .main-header .navbar-nav .nav-link.active {
        background: #f3f4f6;
        color: #2563eb !important;
    }

    .main-header .navbar-nav .nav-link.text-danger {
        color: #ef4444 !important;
    }

    .main-header .navbar-nav .nav-link.text-danger:hover {
        background: #fee2e2;
        color: #b91c1c !important;
    }

    .main-header .navbar-toggler {
        border: none;
    }
</style>
<nav class="navbar navbar-expand-lg main-header mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">Dashboard</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="mainNavbar">
            <ul class="navbar-nav mb-2 mb-lg-0 align-items-lg-center" style="gap: 0.25rem;">
                <?php if (basename($_SERVER['SCRIPT_NAME']) !== 'index.php'): ?>
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-secondary px-3 py-1 me-2" style="font-weight:500;" href="index.php">กลับสู่ Dashboard</a>
                    </li>
                <?php endif; ?>
                <?php if (has_role('admin')): ?>
                    <li class="nav-item"><a class="nav-link" href="users.php">จัดการผู้ใช้</a></li>
                    <li class="nav-item"><a class="nav-link" href="survey.php">จัดการแบบสอบถาม</a></li>
                    <li class="nav-item"><a class="nav-link" href="data_manage.php">จัดการข้อมูล</a></li>
                <?php endif; ?>
                <?php if (has_role('user')): ?>
                    <li class="nav-item"><a class="nav-link" href="survey.php">จัดการแบบสอบถาม</a></li>
                <?php endif; ?>
                <?php if (has_role('manager')): ?>
                    <li class="nav-item"><a class="nav-link" href="data_manage.php">จัดการข้อมูล</a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link text-danger" href="logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>