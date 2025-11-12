<?php
require_once 'includes/admin_layout.php';
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

$error_message = '';
$success_message = '';

// Xử lý các action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_status':
            $maKH = (int)$_POST['ma_kh'];
            $trangThai = $_POST['trang_thai'];
            
            try {
                $stmt = $conn->prepare("UPDATE KHACHHANG SET TrangThai = ? WHERE MaKH = ?");
                $stmt->execute([$trangThai, $maKH]);
                $success_message = 'Cập nhật trạng thái thành công!';
            } catch (Exception $e) {
                $error_message = 'Lỗi: ' . $e->getMessage();
            }
            break;
    }
}

// Lấy danh sách khách hàng
$customers = [];
try {
    $stmt = $conn->query("
        SELECT kh.*, tk.TenTK, tk.TrangThai as TKTrangThai, tk.Quyen
        FROM KHACHHANG kh
        LEFT JOIN TAIKHOANKH tk ON kh.MaKH = tk.MaKH
        ORDER BY kh.NgayDangKy DESC
    ");
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        <h2 class="admin-card-title">Quản lý Tài khoản Khách hàng</h2>
    </div>

    <div class="admin-card-body">
        <?php if (empty($customers)): ?>
            <p style="text-align: center; color: var(--admin-text-light); padding: 40px;">
                Chưa có khách hàng nào
            </p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Mã KH</th>
                            <th>Họ tên</th>
                            <th>Email</th>
                            <th>SĐT</th>
                            <th>Tên đăng nhập</th>
                            <th>Quyền</th>
                            <th>Trạng thái</th>
                            <th>Ngày đăng ký</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td><?php echo $customer['MaKH']; ?></td>
                                <td><?php echo htmlspecialchars($customer['TenKH']); ?></td>
                                <td><?php echo htmlspecialchars($customer['Email']); ?></td>
                                <td><?php echo htmlspecialchars($customer['SDT']); ?></td>
                                <td><?php echo htmlspecialchars($customer['TenTK'] ?? '-'); ?></td>
                                <td>
                                    <span class="badge <?php echo $customer['Quyen'] === 'Admin' ? 'badge-warning' : 'badge-info'; ?>">
                                        <?php echo htmlspecialchars($customer['Quyen'] ?? 'User'); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="ma_kh" value="<?php echo $customer['MaKH']; ?>">
                                        <select name="trang_thai" onchange="this.form.submit()" 
                                                style="padding: 4px 8px; border-radius: 4px; border: 1px solid var(--admin-border);">
                                            <option value="HoatDong" <?php echo $customer['TrangThai'] === 'HoatDong' ? 'selected' : ''; ?>>Hoạt động</option>
                                            <option value="Khoa" <?php echo $customer['TrangThai'] === 'Khoa' ? 'selected' : ''; ?>>Khóa</option>
                                        </select>
                                    </form>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($customer['NgayDangKy'])); ?></td>
                                <td>
                                    <a href="admin_customer_detail.php?id=<?php echo $customer['MaKH']; ?>" 
                                       class="btn btn-outline" style="padding: 6px 12px; font-size: 0.85rem;">
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

