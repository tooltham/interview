<?php
require_once __DIR__ . '/../src/auth.php';
require_once '/var/www/config/db.php';

if (!is_logged_in() || !has_role('admin')) {
    header('Location: login.php');
    exit;
}


$error = '';
$success = '';

// แก้ไขผู้ใช้
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = intval($_POST['id']);
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    $roles_selected = $_POST['roles'] ?? [];
    if ($username && $email && $name && $roles_selected) {
        // ตรวจสอบซ้ำ username/email (ยกเว้นตัวเอง)
        $check = $pdo->prepare('SELECT id FROM users WHERE (username=? OR email=?) AND id<>?');
        $check->execute([$username, $email, $id]);
        if ($check->fetch()) {
            $error = 'Username หรือ Email นี้ถูกใช้แล้ว';
        } else if ($password && $password !== $password2) {
            $error = 'รหัสผ่านใหม่ทั้งสองช่องไม่ตรงกัน';
        } else {
            $pdo->prepare('UPDATE users SET username=?, email=?, name=?, position=?, department=?, phone=?, status=? WHERE id=?')
                ->execute([$username, $email, $name, $position, $department, $phone, $status, $id]);
            // เปลี่ยนรหัสผ่านถ้ากรอก
            if ($password && $password === $password2) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $pdo->prepare('UPDATE users SET password=? WHERE id=?')->execute([$hash, $id]);
            }
            // อัปเดต roles
            $pdo->prepare('DELETE FROM user_roles WHERE user_id=?')->execute([$id]);
            foreach ($roles_selected as $rid) {
                $pdo->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)')->execute([$id, $rid]);
            }
            $success = 'แก้ไขข้อมูลผู้ใช้เรียบร้อย';
        }
    } else {
        $error = 'กรุณากรอกข้อมูลให้ครบ';
    }
}

// ดึง roles ทั้งหมด
$roles_stmt = $pdo->query('SELECT * FROM roles ORDER BY id');
$all_roles = $roles_stmt->fetchAll();

// เพิ่มผู้ใช้ใหม่
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    $roles_selected = $_POST['roles'] ?? [];
    if ($username && $email && $name && $password && $password2 && $roles_selected) {
        if ($password !== $password2) {
            $error = 'รหัสผ่านทั้งสองช่องไม่ตรงกัน';
        } else {
            // ตรวจสอบซ้ำ
            $check = $pdo->prepare('SELECT id FROM users WHERE username=? OR email=?');
            $check->execute([$username, $email]);
            if ($check->fetch()) {
                $error = 'Username หรือ Email นี้ถูกใช้แล้ว';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $pdo->prepare('INSERT INTO users (username, email, password, name, position, department, phone, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
                    ->execute([$username, $email, $hash, $name, $position, $department, $phone, $status]);
                $uid = $pdo->lastInsertId();
                foreach ($roles_selected as $rid) {
                    $pdo->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)')->execute([$uid, $rid]);
                }
                $success = 'เพิ่มผู้ใช้ใหม่เรียบร้อย';
            }
        }
    } else {
        $error = 'กรุณากรอกข้อมูลให้ครบ';
    }
}

// ลบผู้ใช้
if (isset($_GET['delete'])) {
    $uid = intval($_GET['delete']);
    if ($uid > 0) {
        $pdo->prepare('DELETE FROM user_roles WHERE user_id=?')->execute([$uid]);
        $pdo->prepare('DELETE FROM users WHERE id=?')->execute([$uid]);
        $success = 'ลบผู้ใช้เรียบร้อย';
    }
}

// ดึงรายชื่อผู้ใช้และ role
$stmt = $pdo->query('SELECT u.id, u.username, u.email, GROUP_CONCAT(r.name) as roles FROM users u
    LEFT JOIN user_roles ur ON u.id = ur.user_id
    LEFT JOIN roles r ON ur.role_id = r.id
    GROUP BY u.id, u.username, u.email
    ORDER BY u.id ASC');
$users = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>จัดการผู้ใช้</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;700&display=swap" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<?php include 'header.php'; ?>
<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3>จัดการผู้ใช้</h3>
            <a href="index.php" class="btn btn-secondary">กลับสู่ Dashboard</a>
        </div>
        <?php if ($error): ?>
            <div class="alert alert-danger"> <?= htmlspecialchars($error) ?> </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"> <?= htmlspecialchars($success) ?> </div>
        <?php endif; ?>
        <table class="table table-bordered table-hover">
            <thead class="table-light">
                <tr>
                    <th>ลำดับ</th>
                    <th>ชื่อผู้ใช้</th>
                    <th>อีเมล</th>
                    <th>บทบาท</th>
                    <th>การจัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['id']) ?></td>
                        <td><?= htmlspecialchars($user['username']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= htmlspecialchars($user['roles']) ?></td>
                        <td>
                            <a href="?edit=<?= $user['id'] ?>" class="btn btn-sm btn-warning">แก้ไข</a>
                            <a href="?delete=<?= $user['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('ยืนยันการลบ?')">ลบ</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="mt-4">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addUserModal">เพิ่มผู้ใช้ใหม่</button>
        </div>

        <!-- Modal เพิ่มผู้ใช้ -->
        <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form method="post" class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addUserModalLabel">เพิ่มผู้ใช้ใหม่</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ชื่อ-นามสกุล</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ตำแหน่ง</label>
                            <input type="text" name="position" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">หน่วยงาน/แผนก</label>
                            <input type="text" name="department" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">เบอร์โทร</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">สถานะ</label>
                            <select name="status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" id="add-password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ยืนยัน Password อีกครั้ง</label>
                            <input type="password" name="password2" class="form-control" id="add-password2" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Roles</label>
                            <?php foreach ($all_roles as $role): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="roles[]" value="<?= $role['id'] ?>" id="role<?= $role['id'] ?>">
                                    <label class="form-check-label" for="role<?= $role['id'] ?>">
                                        <?= htmlspecialchars($role['name']) ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-primary">บันทึก</button>
                    </div>
                </form>
            </div>
        </div>
        <script>
            // ตรวจสอบ password ตรงกัน ก่อน submit (เพิ่มผู้ใช้)
            document.querySelector('#addUserModal form').addEventListener('submit', function(e) {
                var pw = document.getElementById('add-password').value;
                var pw2 = document.getElementById('add-password2').value;
                if (pw !== pw2) {
                    alert('รหัสผ่านทั้งสองช่องไม่ตรงกัน');
                    e.preventDefault();
                }
            });
        </script>

        <!-- Modal แก้ไขผู้ใช้ (แสดงเมื่อมี ?edit=ID) -->
        <?php if (isset($_GET['edit'])):
            $edit_id = intval($_GET['edit']);
            $edit_stmt = $pdo->prepare('SELECT * FROM users WHERE id=?');
            $edit_stmt->execute([$edit_id]);
            $edit_user = $edit_stmt->fetch();
            $edit_roles_stmt = $pdo->prepare('SELECT role_id FROM user_roles WHERE user_id=?');
            $edit_roles_stmt->execute([$edit_id]);
            $edit_roles = array_column($edit_roles_stmt->fetchAll(), 'role_id');
        ?>
            <div class="modal fade show" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" style="display:block; background:rgba(0,0,0,0.5);">
                <div class="modal-dialog">
                    <form method="post" class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editUserModalLabel">แก้ไขผู้ใช้</h5>
                            <a href="users.php" class="btn-close"></a>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="id" value="<?= $edit_user['id'] ?>">
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($edit_user['username']) ?>" required readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($edit_user['email']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">ชื่อ-นามสกุล</label>
                                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($edit_user['name']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">ตำแหน่ง</label>
                                <input type="text" name="position" class="form-control" value="<?= htmlspecialchars($edit_user['position']) ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">หน่วยงาน/แผนก</label>
                                <input type="text" name="department" class="form-control" value="<?= htmlspecialchars($edit_user['department']) ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">เบอร์โทร</label>
                                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($edit_user['phone']) ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">สถานะ</label>
                                <select name="status" class="form-select">
                                    <option value="active" <?= $edit_user['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= $edit_user['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password (ถ้าไม่เปลี่ยนให้เว้นว่าง)</label>
                                <input type="password" name="password" class="form-control" id="edit-password">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">ยืนยัน Password อีกครั้ง</label>
                                <input type="password" name="password2" class="form-control" id="edit-password2">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Roles</label>
                                <?php foreach ($all_roles as $role): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="roles[]" value="<?= $role['id'] ?>" id="editrole<?= $role['id'] ?>" <?= in_array($role['id'], $edit_roles) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="editrole<?= $role['id'] ?>">
                                            <?= htmlspecialchars($role['name']) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <a href="users.php" class="btn btn-secondary">ยกเลิก</a>
                            <button type="submit" class="btn btn-primary">บันทึก</button>
                        </div>
                    </form>
                </div>
            </div>
            <script>
                document.body.classList.add('modal-open');
                // ตรวจสอบ password ตรงกัน ก่อน submit
                document.querySelector('#editUserModal form').addEventListener('submit', function(e) {
                    var pw = document.getElementById('edit-password').value;
                    var pw2 = document.getElementById('edit-password2').value;
                    if (pw || pw2) {
                        if (pw !== pw2) {
                            alert('รหัสผ่านใหม่ทั้งสองช่องไม่ตรงกัน');
                            e.preventDefault();
                        }
                    }
                });
            </script>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php include 'footer.php'; ?>
</body>

</html>