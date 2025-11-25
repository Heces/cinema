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
        case 'add_genre':
            $theLoai = trim($_POST['the_loai'] ?? '');
            
            if (empty($theLoai)) {
                $error_message = 'Vui lòng nhập tên thể loại!';
            } else {
                try {
                    $stmt = $conn->prepare("INSERT INTO THELOAI (TheLoai) VALUES (?)");
                    $stmt->execute([$theLoai]);
                    $success_message = 'Thêm thể loại thành công!';
                } catch (Exception $e) {
                    if (strpos($e->getMessage(), 'Duplicate') !== false) {
                        $error_message = 'Thể loại đã tồn tại!';
                    } else {
                        $error_message = 'Lỗi: ' . $e->getMessage();
                    }
                }
            }
            break;
            
        case 'update_genre':
            $maTheloai = (int)$_POST['ma_theloai'];
            $theLoai = trim($_POST['the_loai'] ?? '');
            
            if (empty($theLoai)) {
                $error_message = 'Vui lòng nhập tên thể loại!';
            } else {
                try {
                    $stmt = $conn->prepare("UPDATE THELOAI SET TheLoai = ? WHERE MaTheloai = ?");
                    $stmt->execute([$theLoai, $maTheloai]);
                    $success_message = 'Cập nhật thể loại thành công!';
                } catch (Exception $e) {
                    if (strpos($e->getMessage(), 'Duplicate') !== false) {
                        $error_message = 'Thể loại đã tồn tại!';
                    } else {
                        $error_message = 'Lỗi: ' . $e->getMessage();
                    }
                }
            }
            break;
            
        case 'delete_genre':
            $maTheloai = (int)$_POST['ma_theloai'];
            
            try {
                // Kiểm tra xem thể loại có đang được sử dụng không
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM PHIM_THELOAI WHERE MaTheloai = ?");
                $stmt->execute([$maTheloai]);
                $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                
                if ($count > 0) {
                    $error_message = 'Không thể xóa thể loại đang được sử dụng!';
                } else {
                    $stmt = $conn->prepare("DELETE FROM THELOAI WHERE MaTheloai = ?");
                    $stmt->execute([$maTheloai]);
                    $success_message = 'Xóa thể loại thành công!';
                }
            } catch (Exception $e) {
                $error_message = 'Lỗi: ' . $e->getMessage();
            }
            break;
    }
}

// Lấy danh sách thể loại
$genres = [];
try {
    $sql = "
        SELECT t.*, COUNT(pt.MaPhim) as SoPhim
        FROM THELOAI t
        LEFT JOIN PHIM_THELOAI pt ON t.MaTheloai = pt.MaTheloai
    ";
    $params = [];
    
    if ($searchQuery !== '') {
        $sql .= " WHERE t.TheLoai LIKE :search ";
        $params[':search'] = '%' . $searchQuery . '%';
    }
    
    $sql .= "
        GROUP BY t.MaTheloai
        ORDER BY t.TheLoai
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $genres = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error_message = 'Lỗi khi tải danh sách: ' . $e->getMessage();
}

// Lấy thể loại để edit (nếu có)
$editGenre = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM THELOAI WHERE MaTheloai = ?");
    $stmt->execute([$_GET['edit']]);
    $editGenre = $stmt->fetch(PDO::FETCH_ASSOC);
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
        <h2 class="admin-card-title" style="margin-right: auto;">Quản lý Thể loại Phim</h2>
        <form method="GET" class="admin-search-form" style="display: flex; gap: 8px; align-items: center;">
            <input type="text" name="search" placeholder="Tìm theo tên thể loại..." 
                   value="<?php echo htmlspecialchars($searchQuery); ?>"
                   style="padding: 8px 12px; border: 1px solid var(--admin-border); border-radius: 6px; min-width: 220px;">
            <?php if ($searchQuery !== ''): ?>
                <a href="admin_genres.php" class="btn btn-outline" style="padding: 8px 12px;">Xóa lọc</a>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary" style="padding: 8px 16px;">
                <ion-icon name="search-outline"></ion-icon>
                Tìm kiếm
            </button>
        </form>
        <button class="btn btn-primary" onclick="openModal('genreModal')" style="white-space: nowrap;">
            <ion-icon name="add-outline"></ion-icon>
            Thêm thể loại
        </button>
    </div>

    <div class="admin-card-body">
        <?php if (empty($genres)): ?>
            <p style="text-align: center; color: var(--admin-text-light); padding: 40px;">
                Chưa có thể loại nào. Hãy thêm thể loại đầu tiên!
            </p>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Mã</th>
                        <th>Tên thể loại</th>
                        <th>Số phim</th>
                        <th>Ngày tạo</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($genres as $genre): ?>
                        <tr>
                            <td><?php echo $genre['MaTheloai']; ?></td>
                            <td><strong><?php echo htmlspecialchars($genre['TheLoai']); ?></strong></td>
                            <td>
                                <span class="badge badge-info"><?php echo $genre['SoPhim']; ?> phim</span>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($genre['NgayTao'])); ?></td>
                            <td>
                                <?php
                                    $genreEditParams = ['edit' => $genre['MaTheloai']];
                                    if ($searchQuery !== '') {
                                        $genreEditParams['search'] = $searchQuery;
                                    }
                                    $genreEditUrl = '?' . http_build_query($genreEditParams);
                                ?>
                                <a href="<?php echo $genreEditUrl; ?>" 
                                   class="btn btn-outline" style="padding: 6px 12px; font-size: 0.85rem;">
                                    <ion-icon name="create-outline"></ion-icon>
                                    Sửa
                                </a>
                                <form method="POST" style="display: inline;" 
                                      onsubmit="return confirm('Bạn có chắc chắn muốn xóa thể loại này?');">
                                    <input type="hidden" name="action" value="delete_genre">
                                    <input type="hidden" name="ma_theloai" value="<?php echo $genre['MaTheloai']; ?>">
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

<!-- Modal Add/Edit Genre -->
<div id="genreModal" class="modal <?php echo $editGenre ? 'show' : ''; ?>">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title"><?php echo $editGenre ? 'Sửa thể loại' : 'Thêm thể loại mới'; ?></h3>
            <button class="modal-close" onclick="closeModal('genreModal')">
                <ion-icon name="close-outline"></ion-icon>
            </button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="action" value="<?php echo $editGenre ? 'update_genre' : 'add_genre'; ?>">
                <?php if ($editGenre): ?>
                    <input type="hidden" name="ma_theloai" value="<?php echo $editGenre['MaTheloai']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="the_loai" class="form-label">Tên thể loại *</label>
                    <input type="text" id="the_loai" name="the_loai" class="form-input" 
                           value="<?php echo $editGenre ? htmlspecialchars($editGenre['TheLoai']) : ''; ?>" 
                           placeholder="Ví dụ: Hành động, Tình cảm..." required>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('genreModal')">Hủy</button>
                    <button type="submit" class="btn btn-primary"><?php echo $editGenre ? 'Cập nhật' : 'Thêm mới'; ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    <?php if ($editGenre): ?>
    // Auto open modal if editing
    document.addEventListener('DOMContentLoaded', function() {
        openModal('genreModal');
    });
    <?php endif; ?>
</script>

<?php require_once 'includes/admin_footer.php'; ?>

