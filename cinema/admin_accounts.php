<?php
require_once 'includes/admin_layout.php';
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

$error_message = '';
$success_message = '';

// Xử lý các actin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_role':
            $maTK = (int)$_POST['ma_tk'];
            $quyen = $_POST['quyen'];
            
            try {
                $stmt = $conn->prepare("UPDATE TAIKHOANKH SET Quyen = ? WHERE MaTK = ?");
                $stmt->execute([$quyen, $maTK]);
                $success_message = 'Cập nhật quyền thành công!';
            } catch (Exception $e) {
                $error_message = 'Lỗi: ' . $e->getMessage();
            }
            break;
            
        case 'update_status':
            $maTK = (int)$_POST['ma_tk'];
            $trangThai = $_POST['trang_thai'];
            
            try {
                $stmt = $conn->prepare("UPDATE TAIKHOANKH SET TrangThai = ? WHERE MaTK = ?");
                $stmt->execute([$trangThai, $maTK]);
                $success_message = 'Cập nhật trạng thái thành công!';
            } catch (Exception $e) {
                $error_message = 'Lỗi: ' . $e->getMessage();
            }
            break;
    }
}

// Lấy danh sách tài khoản
$accounts = [];
try {
    $stmt = $conn->query("
        SELECT tk.*, kh.TenKH, kh.Email, kh.SDT
        FROM TAIKHOANKH tk
        JOIN KHACHHANG kh ON tk.MaKH = kh.MaKH
        ORDER BY tk.NgayTao DESC
    ");
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error_message = 'Lỗi khi tải danh sách: ' . $e->getMessage();
}
?>

<?php if ($error_message): ?>
    <div class="alert alert-error">
        <ion-icon name="alert-circle"></ion-icon>
        <span><?php echo htmlspecialchars($error_message); ?></span>
    </div>
<?php endif; ?>

<?php if ($success_message): ?>
    <div class="alert alert-success">
        <ion-icon name="checkmark-circle"></ion-icon>
        <span><?php echo htmlspecialchars($success_message); ?></span>
    </div>
<?php endif; ?>

<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title">Quản lý Tài khoản</h2>
    </div>

    <div class="admin-card-body">
        <?php if (empty($accounts)): ?>
            <p style="text-align: center; color: var(--admin-text-light); padding: 40px;">
                Chưa có tài khoản nào
            </p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Mã TK</th>
                            <th>Tên đăng nhập</th>
                            <th>Họ tên</th>
                            <th>Email</th>
                            <th>SĐT</th>
                            <th>Quyền</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($accounts as $account): ?>
                            <tr>
                                <td><?php echo $account['MaTK']; ?></td>
                                <td><strong><?php echo htmlspecialchars($account['TenTK']); ?></strong></td>
                                <td><?php echo htmlspecialchars($account['TenKH']); ?></td>
                                <td><?php echo htmlspecialchars($account['Email']); ?></td>
                                <td><?php echo htmlspecialchars($account['SDT']); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="update_role">
                                        <input type="hidden" name="ma_tk" value="<?php echo $account['MaTK']; ?>">
                                        <select name="quyen" onchange="this.form.submit()" 
                                                style="padding: 4px 8px; border-radius: 4px; border: 1px solid var(--admin-border);">
                                            <option value="User" <?php echo $account['Quyen'] === 'User' ? 'selected' : ''; ?>>User</option>
                                            <option value="Admin" <?php echo $account['Quyen'] === 'Admin' ? 'selected' : ''; ?>>Admin</option>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="ma_tk" value="<?php echo $account['MaTK']; ?>">
                                        <select name="trang_thai" onchange="this.form.submit()" 
                                                style="padding: 4px 8px; border-radius: 4px; border: 1px solid var(--admin-border);">
                                            <option value="HoatDong" <?php echo $account['TrangThai'] === 'HoatDong' ? 'selected' : ''; ?>>Hoạt động</option>
                                            <option value="Khoa" <?php echo $account['TrangThai'] === 'Khoa' ? 'selected' : ''; ?>>Khóa</option>
                                        </select>
                                    </form>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($account['NgayTao'])); ?></td>
                                <td>
                                    <a href="admin_customers.php" class="btn btn-outline" style="padding: 6px 12px; font-size: 0.85rem;">
                                        <ion-icon name="eye-outline"></ion-icon>
                                        Chi tiết
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/admin_footer.php'; ?>


