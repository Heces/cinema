<?php
require_once 'includes/admin_layout.php';
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

$error_message = '';
$success_message = '';
$searchQuery = trim($_GET['search'] ?? '');

// Xử lý các action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_account':
            $maTK = (int)$_POST['account_id'];
            
            try {
                // Lấy thông tin tài khoản hiện tại
                $stmt = $conn->prepare("
                    SELECT tk.MaKH, tk.TenTK 
                    FROM TAIKHOANKH tk 
                    WHERE tk.MaTK = ?
                ");
                $stmt->execute([$maTK]);
                $currentAccount = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$currentAccount) {
                    throw new Exception('Không tìm thấy tài khoản');
                }
                
                $conn->beginTransaction();
                
                // Cập nhật thông tin khách hàng
                $stmt = $conn->prepare("
                    UPDATE KHACHHANG 
                    SET TenKH = ?, SDT = ?, Email = ?, DiaChi = ?, NgaySinh = ?, GioiTinh = ?
                    WHERE MaKH = ?
                ");
                $stmt->execute([
                    $_POST['ten_kh'],
                    $_POST['sdt'] ?? '',
                    $_POST['email'],
                    $_POST['dia_chi'] ?? '',
                    !empty($_POST['ngay_sinh']) ? $_POST['ngay_sinh'] : null,
                    $_POST['gioi_tinh'] ?? null,
                    $currentAccount['MaKH']
                ]);
                
                // Cập nhật thông tin tài khoản
                $updateFields = [];
                $updateValues = [];
                
                if (isset($_POST['ten_tk']) && $_POST['ten_tk'] !== $currentAccount['TenTK']) {
                    // Kiểm tra tên đăng nhập đã tồn tại
                    $checkStmt = $conn->prepare("SELECT MaTK FROM TAIKHOANKH WHERE TenTK = ? AND MaTK != ?");
                    $checkStmt->execute([$_POST['ten_tk'], $maTK]);
                    if ($checkStmt->fetch()) {
                        throw new Exception('Tên đăng nhập đã được sử dụng');
                    }
                    $updateFields[] = "TenTK = ?";
                    $updateValues[] = $_POST['ten_tk'];
                }
                
                if (!empty($_POST['matkhau'])) {
                    $hashedPassword = password_hash($_POST['matkhau'], PASSWORD_DEFAULT);
                    $updateFields[] = "Matkhau = ?";
                    $updateValues[] = $hashedPassword;
                }
                
                if (isset($_POST['quyen'])) {
                    $updateFields[] = "Quyen = ?";
                    $updateValues[] = $_POST['quyen'];
                }
                
                if (isset($_POST['trang_thai'])) {
                    $updateFields[] = "TrangThai = ?";
                    $updateValues[] = $_POST['trang_thai'];
                }
                
                if (!empty($updateFields)) {
                    $updateValues[] = $maTK;
                    $sql = "UPDATE TAIKHOANKH SET " . implode(", ", $updateFields) . " WHERE MaTK = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute($updateValues);
                }
                
                $conn->commit();
                $success_message = 'Cập nhật tài khoản thành công!';
            } catch (Exception $e) {
                $conn->rollBack();
                $error_message = 'Lỗi: ' . $e->getMessage();
            }
            break;
            
        case 'delete_account':
            $maTK = (int)$_POST['account_id'];
            
            try {
                // Kiểm tra xem có vé nào đã đặt cho tài khoản này không
                $stmt = $conn->prepare("
                    SELECT COUNT(*) as count 
                    FROM VE 
                    WHERE MaKH = (SELECT MaKH FROM TAIKHOANKH WHERE MaTK = ?) AND TrangThai != 'Huy'
                ");
                $stmt->execute([$maTK]);
                $ticketCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                
                if ($ticketCount > 0) {
                    throw new Exception('Không thể xóa tài khoản đã có vé được đặt!');
                }
                
                // Lấy MaKH trước khi xóa
                $stmt = $conn->prepare("SELECT MaKH FROM TAIKHOANKH WHERE MaTK = ?");
                $stmt->execute([$maTK]);
                $account = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$account) {
                    throw new Exception('Không tìm thấy tài khoản để xóa!');
                }
                
                $conn->beginTransaction();
                
                // Xóa tài khoản (sẽ tự động xóa khách hàng do CASCADE)
                $stmt = $conn->prepare("DELETE FROM TAIKHOANKH WHERE MaTK = ?");
                $stmt->execute([$maTK]);
                
                if ($stmt->rowCount() === 0) {
                    throw new Exception('Không tìm thấy tài khoản để xóa!');
                }
                
                $conn->commit();
                $success_message = 'Xóa tài khoản thành công!';
            } catch (Exception $e) {
                $conn->rollBack();
                $error_message = 'Lỗi: ' . $e->getMessage();
            }
            break;
            
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
    $sql = "
        SELECT tk.*, kh.TenKH, kh.Email, kh.SDT, kh.DiaChi, kh.NgaySinh, kh.GioiTinh
        FROM TAIKHOANKH tk
        JOIN KHACHHANG kh ON tk.MaKH = kh.MaKH
    ";
    $params = [];
    
    if ($searchQuery !== '') {
        $sql .= "
            WHERE (
                tk.TenTK LIKE :search_tentk
                OR kh.TenKH LIKE :search_tenkh
                OR kh.Email LIKE :search_email
                OR kh.SDT LIKE :search_sdt
            )
        ";
        $searchParam = '%' . $searchQuery . '%';
        $params = [
            ':search_tentk' => $searchParam,
            ':search_tenkh' => $searchParam,
            ':search_email' => $searchParam,
            ':search_sdt' => $searchParam,
        ];
    }
    
    $sql .= " ORDER BY tk.NgayTao DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error_message = 'Lỗi khi tải danh sách: ' . $e->getMessage();
}

// Lấy tài khoản để edit (nếu có)
$editAccount = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    try {
        $stmt = $conn->prepare("
            SELECT tk.*, kh.TenKH, kh.Email, kh.SDT, kh.DiaChi, kh.NgaySinh, kh.GioiTinh
            FROM TAIKHOANKH tk
            JOIN KHACHHANG kh ON tk.MaKH = kh.MaKH
            WHERE tk.MaTK = ?
        ");
        $stmt->execute([$_GET['edit']]);
        $editAccount = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error_message = 'Lỗi khi lấy thông tin tài khoản: ' . $e->getMessage();
    }
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
    <div class="admin-card-header" style="gap: 16px; flex-wrap: wrap;">
        <h2 class="admin-card-title" style="margin-right: auto;">Quản lý Tài khoản</h2>
        <form method="GET" class="admin-search-form" style="display: flex; gap: 8px; align-items: center;">
            <input type="text" name="search" placeholder="Tìm tên đăng nhập, email..." 
                   value="<?php echo htmlspecialchars($searchQuery); ?>"
                   style="padding: 8px 12px; border: 1px solid var(--admin-border); border-radius: 6px; min-width: 240px;">
            <?php if ($searchQuery !== ''): ?>
                <a href="admin_accounts.php" class="btn btn-outline" style="padding: 8px 12px;">Xóa lọc</a>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary" style="padding: 8px 16px;">
                <ion-icon name="search-outline"></ion-icon>
                Tìm kiếm
            </button>
        </form>
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
                                    <?php
                                        $accountEditParams = ['edit' => $account['MaTK']];
                                        if ($searchQuery !== '') {
                                            $accountEditParams['search'] = $searchQuery;
                                        }
                                        $accountEditUrl = '?' . http_build_query($accountEditParams);
                                    ?>
                                    <a href="<?php echo $accountEditUrl; ?>" 
                                       class="btn btn-outline" style="padding: 6px 12px; font-size: 0.85rem;">
                                        <ion-icon name="create-outline"></ion-icon>
                                        Sửa
                                    </a>
                                    <form method="POST" style="display: inline;" 
                                          onsubmit="return confirm('Bạn có chắc chắn muốn xóa tài khoản này?');">
                                        <input type="hidden" name="action" value="delete_account">
                                        <input type="hidden" name="account_id" value="<?php echo $account['MaTK']; ?>">
                                        <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 0.85rem;">
                                            <ion-icon name="trash-outline"></ion-icon>
                                            Xóa
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Edit Account -->
<div id="accountModal" class="modal <?php echo $editAccount ? 'show' : ''; ?>">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Sửa tài khoản</h3>
            <button class="modal-close" onclick="closeModal('accountModal')">
                <ion-icon name="close-outline"></ion-icon>
            </button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="action" value="update_account">
                <input type="hidden" name="account_id" value="<?php echo $editAccount['MaTK']; ?>">
                
                <div class="form-group">
                    <label for="ten_tk" class="form-label">Tên đăng nhập *</label>
                    <input type="text" id="ten_tk" name="ten_tk" class="form-input" 
                           value="<?php echo htmlspecialchars($editAccount['TenTK']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="matkhau" class="form-label">Mật khẩu mới</label>
                    <input type="password" id="matkhau" name="matkhau" class="form-input" 
                           placeholder="Để trống nếu không đổi mật khẩu">
                    <small>Chỉ điền nếu muốn đổi mật khẩu</small>
                </div>
                
                <div class="form-group">
                    <label for="ten_kh" class="form-label">Họ và tên *</label>
                    <input type="text" id="ten_kh" name="ten_kh" class="form-input" 
                           value="<?php echo htmlspecialchars($editAccount['TenKH']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Email *</label>
                    <input type="email" id="email" name="email" class="form-input" 
                           value="<?php echo htmlspecialchars($editAccount['Email']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="sdt" class="form-label">Số điện thoại</label>
                    <input type="tel" id="sdt" name="sdt" class="form-input" 
                           value="<?php echo htmlspecialchars($editAccount['SDT'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="dia_chi" class="form-label">Địa chỉ</label>
                    <textarea id="dia_chi" name="dia_chi" class="form-textarea"><?php echo htmlspecialchars($editAccount['DiaChi'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="ngay_sinh" class="form-label">Ngày sinh</label>
                    <input type="date" id="ngay_sinh" name="ngay_sinh" class="form-input" 
                           value="<?php echo $editAccount['NgaySinh'] ? date('Y-m-d', strtotime($editAccount['NgaySinh'])) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="gioi_tinh" class="form-label">Giới tính</label>
                    <select id="gioi_tinh" name="gioi_tinh" class="form-select">
                        <option value="">-- Chọn giới tính --</option>
                        <option value="Nam" <?php echo ($editAccount['GioiTinh'] === 'Nam') ? 'selected' : ''; ?>>Nam</option>
                        <option value="Nu" <?php echo ($editAccount['GioiTinh'] === 'Nu') ? 'selected' : ''; ?>>Nữ</option>
                        <option value="Khac" <?php echo ($editAccount['GioiTinh'] === 'Khac') ? 'selected' : ''; ?>>Khác</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="quyen" class="form-label">Quyền</label>
                    <select id="quyen" name="quyen" class="form-select">
                        <option value="User" <?php echo ($editAccount['Quyen'] === 'User') ? 'selected' : ''; ?>>User</option>
                        <option value="Admin" <?php echo ($editAccount['Quyen'] === 'Admin') ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="trang_thai" class="form-label">Trạng thái</label>
                    <select id="trang_thai" name="trang_thai" class="form-select">
                        <option value="HoatDong" <?php echo ($editAccount['TrangThai'] === 'HoatDong') ? 'selected' : ''; ?>>Hoạt động</option>
                        <option value="Khoa" <?php echo ($editAccount['TrangThai'] === 'Khoa') ? 'selected' : ''; ?>>Khóa</option>
                    </select>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('accountModal')">Hủy</button>
                    <button type="submit" class="btn btn-primary">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    <?php if ($editAccount): ?>
    // Auto open modal if editing
    document.addEventListener('DOMContentLoaded', function() {
        openModal('accountModal');
    });
    <?php endif; ?>
</script>

<?php require_once 'includes/admin_footer.php'; ?>

