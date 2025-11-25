<?php
require_once 'includes/admin_layout.php';
require_once 'includes/showtime.php';
require_once 'includes/movie.php';

$showtime = new Showtime();
$movie = new Movie();

$error_message = '';
$success_message = '';

// Xử lý các action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_showtime':
            $result = $showtime->addShowtime($_POST);
            if ($result['success']) {
                $success_message = $result['message'];
            } else {
                $error_message = $result['message'];
            }
            break;
            
        case 'update_showtime':
            $id = $_POST['showtime_id'] ?? 0;
            $result = $showtime->updateShowtime($id, $_POST);
            if ($result['success']) {
                $success_message = $result['message'];
            } else {
                $error_message = $result['message'];
            }
            break;
            
        case 'delete_showtime':
            $id = $_POST['showtime_id'] ?? 0;
            $result = $showtime->deleteShowtime($id);
            if ($result['success']) {
                $success_message = $result['message'];
            } else {
                $error_message = $result['message'];
            }
            break;
    }
}

// Lấy danh sách suất chiếu
$showtimes = [];
try {
    require_once 'config/database.php';
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->query("
        SELECT sc.*, p.TenPhim, ph.TenPhong, ph.LoaiPhong,
               (SELECT COUNT(*) FROM VE WHERE MaSuat = sc.MaSuat AND TrangThai != 'Huy') as SoVeDaBan
        FROM SUATCHIEU sc
        JOIN PHIM p ON sc.MaPhim = p.MaPhim
        JOIN PHONG ph ON sc.MaPhong = ph.MaPhong
        ORDER BY sc.ThoiGian DESC
        LIMIT 100
    ");
    $showtimes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error_message = 'Lỗi khi tải danh sách: ' . $e->getMessage();
}

$movies = $movie->getAllMovies();
$rooms = $showtime->getRooms();
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
        <h2 class="admin-card-title">Quản lý Suất chiếu</h2>
        <button class="btn btn-primary" onclick="openModal('showtimeModal')">
            <ion-icon name="add-outline"></ion-icon>
            Thêm suất chiếu
        </button>
    </div>

    <div class="admin-card-body">
        <?php if (empty($showtimes)): ?>
            <p style="text-align: center; color: var(--admin-text-light); padding: 40px;">
                Chưa có suất chiếu nào. Hãy thêm suất chiếu đầu tiên!
            </p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Mã</th>
                            <th>Phim</th>
                            <th>Phòng</th>
                            <th>Thời gian</th>
                            <th>Giá vé</th>
                            <th>Vé đã bán</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($showtimes as $st): ?>
                            <tr>
                                <td><?php echo $st['MaSuat']; ?></td>
                                <td><strong><?php echo htmlspecialchars($st['TenPhim']); ?></strong></td>
                                <td><?php echo htmlspecialchars($st['TenPhong']); ?> (<?php echo $st['LoaiPhong']; ?>)</td>
                                <td><?php echo date('d/m/Y H:i', strtotime($st['ThoiGian'])); ?></td>
                                <td><?php echo number_format($st['GiaBan'], 0, ',', '.'); ?>đ</td>
                                <td><?php echo $st['SoVeDaBan']; ?></td>
                                <td>
                                    <?php
                                    $statusClass = 'badge-success';
                                    $statusText = 'Còn vé';
                                    if ($st['TrangThai'] === 'HetVe') {
                                        $statusClass = 'badge-danger';
                                        $statusText = 'Hết vé';
                                    } elseif ($st['TrangThai'] === 'Huy') {
                                        $statusClass = 'badge-warning';
                                        $statusText = 'Hủy';
                                    }
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;" 
                                          onsubmit="return confirm('Bạn có chắc chắn muốn xóa suất chiếu này?');">
                                        <input type="hidden" name="action" value="delete_showtime">
                                        <input type="hidden" name="showtime_id" value="<?php echo $st['MaSuat']; ?>">
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

<!-- Modal Add Showtime -->
<div id="showtimeModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Thêm suất chiếu mới</h3>
            <button class="modal-close" onclick="closeModal('showtimeModal')">
                <ion-icon name="close-outline"></ion-icon>
            </button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="action" value="add_showtime">
                
                <div class="form-group">
                    <label for="ma_phim" class="form-label">Phim *</label>
                    <select id="ma_phim" name="ma_phim" class="form-select" required>
                        <option value="">-- Chọn phim --</option>
                        <?php foreach ($movies as $m): ?>
                            <option value="<?php echo $m['MaPhim']; ?>">
                                <?php echo htmlspecialchars($m['TenPhim']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="ma_phong" class="form-label">Phòng chiếu *</label>
                    <select id="ma_phong" name="ma_phong" class="form-select" required>
                        <option value="">-- Chọn phòng --</option>
                        <?php foreach ($rooms as $room): ?>
                            <option value="<?php echo $room['MaPhong']; ?>">
                                <?php echo htmlspecialchars($room['TenPhong']); ?> (<?php echo $room['LoaiPhong']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="thoi_gian" class="form-label">Thời gian chiếu *</label>
                    <input type="datetime-local" id="thoi_gian" name="thoi_gian" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label for="gia_ban" class="form-label">Giá vé (VNĐ) *</label>
                    <input type="number" id="gia_ban" name="gia_ban" class="form-input" 
                           min="0" step="1000" required>
                </div>
                
                <div class="form-group">
                    <label for="trang_thai" class="form-label">Trạng thái</label>
                    <select id="trang_thai" name="trang_thai" class="form-select">
                        <option value="ConVe">Còn vé</option>
                        <option value="HetVe">Hết vé</option>
                        <option value="Huy">Hủy</option>
                    </select>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('showtimeModal')">Hủy</button>
                    <button type="submit" class="btn btn-primary">Thêm mới</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/admin_footer.php'; ?>

