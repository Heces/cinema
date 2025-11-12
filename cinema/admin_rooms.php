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
        case 'add_room':
            $tenPhong = trim($_POST['ten_phong'] ?? '');
            $soGhe = (int)$_POST['so_ghe'];
            $loaiPhong = $_POST['loai_phong'] ?? '2D';
            $trangThai = $_POST['trang_thai'] ?? 'HoatDong';
            
            if (empty($tenPhong) || $soGhe <= 0) {
                $error_message = 'Vui lòng nhập đầy đủ thông tin!';
            } else {
                try {
                    $stmt = $conn->prepare("
                        INSERT INTO PHONG (TenPhong, SoGhe, LoaiPhong, TrangThai) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$tenPhong, $soGhe, $loaiPhong, $trangThai]);
                    $success_message = 'Thêm phòng chiếu thành công!';
                } catch (Exception $e) {
                    $error_message = 'Lỗi: ' . $e->getMessage();
                }
            }
            break;
            
        case 'update_room':
            $maPhong = (int)$_POST['ma_phong'];
            $tenPhong = trim($_POST['ten_phong'] ?? '');
            $soGhe = (int)$_POST['so_ghe'];
            $loaiPhong = $_POST['loai_phong'] ?? '2D';
            $trangThai = $_POST['trang_thai'] ?? 'HoatDong';
            
            if (empty($tenPhong) || $soGhe <= 0) {
                $error_message = 'Vui lòng nhập đầy đủ thông tin!';
            } else {
                try {
                    $stmt = $conn->prepare("
                        UPDATE PHONG 
                        SET TenPhong = ?, SoGhe = ?, LoaiPhong = ?, TrangThai = ?
                        WHERE MaPhong = ?
                    ");
                    $stmt->execute([$tenPhong, $soGhe, $loaiPhong, $trangThai, $maPhong]);
                    $success_message = 'Cập nhật phòng chiếu thành công!';
                } catch (Exception $e) {
                    $error_message = 'Lỗi: ' . $e->getMessage();
                }
            }
            break;
            
        case 'delete_room':
            $maPhong = (int)$_POST['ma_phong'];
            
            try {
                // Kiểm tra xem phòng có đang được sử dụng không
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM SUATCHIEU WHERE MaPhong = ?");
                $stmt->execute([$maPhong]);
                $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                
                if ($count > 0) {
                    $error_message = 'Không thể xóa phòng đang có suất chiếu!';
                } else {
                    $stmt = $conn->prepare("DELETE FROM PHONG WHERE MaPhong = ?");
                    $stmt->execute([$maPhong]);
                    $success_message = 'Xóa phòng chiếu thành công!';
                }
            } catch (Exception $e) {
                $error_message = 'Lỗi: ' . $e->getMessage();
            }
            break;
    }
}

// Lấy danh sách phòng
$rooms = [];
try {
    $stmt = $conn->query("
        SELECT p.*, COUNT(sc.MaSuat) as SoSuatChieu
        FROM PHONG p
        LEFT JOIN SUATCHIEU sc ON p.MaPhong = sc.MaPhong
        GROUP BY p.MaPhong
        ORDER BY p.TenPhong
    ");
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error_message = 'Lỗi khi tải danh sách: ' . $e->getMessage();
}

// Lấy phòng để edit (nếu có)
$editRoom = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM PHONG WHERE MaPhong = ?");
    $stmt->execute([$_GET['edit']]);
    $editRoom = $stmt->fetch(PDO::FETCH_ASSOC);
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
        <h2 class="admin-card-title">Quản lý Phòng chiếu</h2>
        <button class="btn btn-primary" onclick="openModal('roomModal')">
            <ion-icon name="add-outline"></ion-icon>
            Thêm phòng
        </button>
    </div>

    <div class="admin-card-body">
        <?php if (empty($rooms)): ?>
            <p style="text-align: center; color: var(--admin-text-light); padding: 40px;">
                Chưa có phòng chiếu nào. Hãy thêm phòng đầu tiên!
            </p>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Mã</th>
                        <th>Tên phòng</th>
                        <th>Số ghế</th>
                        <th>Loại phòng</th>
                        <th>Trạng thái</th>
                        <th>Số suất chiếu</th>
                        <th>Ngày tạo</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rooms as $room): ?>
                        <tr>
                            <td><?php echo $room['MaPhong']; ?></td>
                            <td><strong><?php echo htmlspecialchars($room['TenPhong']); ?></strong></td>
                            <td><?php echo $room['SoGhe']; ?> ghế</td>
                            <td>
                                <span class="badge badge-info"><?php echo $room['LoaiPhong']; ?></span>
                            </td>
                            <td>
                                <?php
                                $statusClass = 'badge-success';
                                $statusText = 'Hoạt động';
                                if ($room['TrangThai'] === 'BaoTri') {
                                    $statusClass = 'badge-warning';
                                    $statusText = 'Bảo trì';
                                } elseif ($room['TrangThai'] === 'NgungHoatDong') {
                                    $statusClass = 'badge-danger';
                                    $statusText = 'Ngừng hoạt động';
                                }
                                ?>
                                <span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                            </td>
                            <td><?php echo $room['SoSuatChieu']; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($room['NgayTao'])); ?></td>
                            <td>
                                <a href="?edit=<?php echo $room['MaPhong']; ?>" 
                                   class="btn btn-outline" style="padding: 6px 12px; font-size: 0.85rem;">
                                    <ion-icon name="create-outline"></ion-icon>
                                    Sửa
                                </a>
                                <form method="POST" style="display: inline;" 
                                      onsubmit="return confirm('Bạn có chắc chắn muốn xóa phòng này?');">
                                    <input type="hidden" name="action" value="delete_room">
                                    <input type="hidden" name="ma_phong" value="<?php echo $room['MaPhong']; ?>">
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
        <?php endif; ?>
    </div>
</div>

<!-- Modal Add/Edit Room -->
<div id="roomModal" class="modal <?php echo $editRoom ? 'show' : ''; ?>">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title"><?php echo $editRoom ? 'Sửa phòng chiếu' : 'Thêm phòng chiếu mới'; ?></h3>
            <button class="modal-close" onclick="closeModal('roomModal')">
                <ion-icon name="close-outline"></ion-icon>
            </button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="action" value="<?php echo $editRoom ? 'update_room' : 'add_room'; ?>">
                <?php if ($editRoom): ?>
                    <input type="hidden" name="ma_phong" value="<?php echo $editRoom['MaPhong']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="ten_phong" class="form-label">Tên phòng *</label>
                    <input type="text" id="ten_phong" name="ten_phong" class="form-input" 
                           value="<?php echo $editRoom ? htmlspecialchars($editRoom['TenPhong']) : ''; ?>" 
                           placeholder="Ví dụ: Phòng 1, Phòng VIP 1..." required>
                </div>
                
                <div class="form-group">
                    <label for="so_ghe" class="form-label">Số ghế *</label>
                    <input type="number" id="so_ghe" name="so_ghe" class="form-input" 
                           value="<?php echo $editRoom ? $editRoom['SoGhe'] : ''; ?>" 
                           min="1" max="500" required>
                </div>
                
                <div class="form-group">
                    <label for="loai_phong" class="form-label">Loại phòng *</label>
                    <select id="loai_phong" name="loai_phong" class="form-select" required>
                        <option value="2D" <?php echo ($editRoom && $editRoom['LoaiPhong'] === '2D') ? 'selected' : ''; ?>>2D</option>
                        <option value="3D" <?php echo ($editRoom && $editRoom['LoaiPhong'] === '3D') ? 'selected' : ''; ?>>3D</option>
                        <option value="IMAX" <?php echo ($editRoom && $editRoom['LoaiPhong'] === 'IMAX') ? 'selected' : ''; ?>>IMAX</option>
                        <option value="VIP" <?php echo ($editRoom && $editRoom['LoaiPhong'] === 'VIP') ? 'selected' : ''; ?>>VIP</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="trang_thai" class="form-label">Trạng thái *</label>
                    <select id="trang_thai" name="trang_thai" class="form-select" required>
                        <option value="HoatDong" <?php echo ($editRoom && $editRoom['TrangThai'] === 'HoatDong') ? 'selected' : ''; ?>>Hoạt động</option>
                        <option value="BaoTri" <?php echo ($editRoom && $editRoom['TrangThai'] === 'BaoTri') ? 'selected' : ''; ?>>Bảo trì</option>
                        <option value="NgungHoatDong" <?php echo ($editRoom && $editRoom['TrangThai'] === 'NgungHoatDong') ? 'selected' : ''; ?>>Ngừng hoạt động</option>
                    </select>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('roomModal')">Hủy</button>
                    <button type="submit" class="btn btn-primary"><?php echo $editRoom ? 'Cập nhật' : 'Thêm mới'; ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    <?php if ($editRoom): ?>
    // Auto open modal if editing
    document.addEventListener('DOMContentLoaded', function() {
        openModal('roomModal');
    });
    <?php endif; ?>
</script>

<?php require_once 'includes/admin_footer.php'; ?>

