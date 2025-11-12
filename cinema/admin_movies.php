<?php
require_once 'includes/admin_layout.php';
require_once 'includes/movie.php';

$phim = new Phim();

$thongBaoLoi = '';
$thongBaoThanhCong = '';

// Xu ly cac action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hanhDong = $_POST['action'] ?? '';
    
    switch ($hanhDong) {
        case 'add_movie':
            $ketQua = $phim->themPhim($_POST);
            if ($ketQua['thanhCong']) {
                $thongBaoThanhCong = $ketQua['thongBao'];
            } else {
                $thongBaoLoi = $ketQua['thongBao'];
            }
            break;
            
        case 'update_movie':
            $id = $_POST['movie_id'] ?? 0;
            $ketQua = $phim->capNhatPhim($id, $_POST);
            if ($ketQua['thanhCong']) {
                $thongBaoThanhCong = $ketQua['thongBao'];
            } else {
                $thongBaoLoi = $ketQua['thongBao'];
            }
            break;
            
        case 'delete_movie':
            $id = $_POST['movie_id'] ?? 0;
            $ketQua = $phim->xoaPhim($id);
            if ($ketQua['thanhCong']) {
                $thongBaoThanhCong = $ketQua['thongBao'];
            } else {
                $thongBaoLoi = $ketQua['thongBao'];
            }
            break;
    }
}

// Lay danh sach phim
$danhSachPhim = $phim->layTatCaPhim();
$danhSachTheLoai = $phim->layTheLoai();

// Lay phim de edit (neu co)
$phimChinhSua = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $phimChinhSua = $phim->layPhimTheoId($_GET['edit']);
}
?>

<?php if ($thongBaoLoi): ?>
    <div class="alert alert-error">
        <ion-icon name="alert-circle"></ion-icon>
        <span><?php echo htmlspecialchars($thongBaoLoi); ?></span>
    </div>
<?php endif; ?>

<?php if ($thongBaoThanhCong): ?>
    <div class="alert alert-success">
        <ion-icon name="checkmark-circle"></ion-icon>
        <span><?php echo htmlspecialchars($thongBaoThanhCong); ?></span>
    </div>
<?php endif; ?>

<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title">Quản lý Phim</h2>
        <button class="btn btn-primary" onclick="openModal('movieModal')">
            <ion-icon name="add-outline"></ion-icon>
            Thêm phim
        </button>
    </div>

    <div class="admin-card-body">
        <?php if (empty($danhSachPhim) || isset($danhSachPhim['loi'])): ?>
            <p style="text-align: center; color: var(--admin-text-light); padding: 40px;">
                Chưa có phim nào. Hãy thêm phim đầu tiên!
            </p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Mã</th>
                            <th>Ảnh</th>
                            <th>Tên phim</th>
                            <th>Thời lượng</th>
                            <th>Năm</th>
                            <th>Thể loại</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($danhSachPhim as $phimItem): ?>
                            <tr>
                                <td><?php echo $phimItem['MaPhim']; ?></td>
                                <td>
                                    <img src="<?php echo htmlspecialchars($phimItem['AnhBia'] ?: './assets/images/movie-1.png'); ?>" 
                                         alt="<?php echo htmlspecialchars($phimItem['TenPhim']); ?>" 
                                         style="width: 60px; height: 80px; object-fit: cover; border-radius: 4px;">
                                </td>
                                <td><strong><?php echo htmlspecialchars($phimItem['TenPhim']); ?></strong></td>
                                <td><?php echo $phimItem['ThoiLuong']; ?> phút</td>
                                <td><?php echo $phimItem['NamSanXuat']; ?></td>
                                <td><?php echo htmlspecialchars($phimItem['TheLoai'] ?: 'Chưa phân loại'); ?></td>
                                <td>
                                    <?php
                                    $lopTrangThai = 'badge-warning';
                                    $vanBanTrangThai = 'Sắp chiếu';
                                    if ($phimItem['TrangThai'] === 'DangChieu') {
                                        $lopTrangThai = 'badge-success';
                                        $vanBanTrangThai = 'Đang chiếu';
                                    } elseif ($phimItem['TrangThai'] === 'KetThuc') {
                                        $lopTrangThai = 'badge-danger';
                                        $vanBanTrangThai = 'Kết thúc';
                                    }
                                    ?>
                                    <span class="badge <?php echo $lopTrangThai; ?>"><?php echo $vanBanTrangThai; ?></span>
                                </td>
                                <td>
                                    <a href="?edit=<?php echo $phimItem['MaPhim']; ?>" 
                                       class="btn btn-outline" style="padding: 6px 12px; font-size: 0.85rem;">
                                        <ion-icon name="create-outline"></ion-icon>
                                        Sửa
                                    </a>
                                    <form method="POST" style="display: inline;" 
                                          onsubmit="return confirm('Bạn có chắc chắn muốn xóa phim này?');">
                                        <input type="hidden" name="action" value="delete_movie">
                                        <input type="hidden" name="movie_id" value="<?php echo $phimItem['MaPhim']; ?>">
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

<!-- Modal Add/Edit Movie -->
<div id="movieModal" class="modal <?php echo $phimChinhSua ? 'show' : ''; ?>">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title"><?php echo $phimChinhSua ? 'Sửa phim' : 'Thêm phim mới'; ?></h3>
            <button class="modal-close" onclick="closeModal('movieModal')">
                <ion-icon name="close-outline"></ion-icon>
            </button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="action" value="<?php echo $phimChinhSua ? 'update_movie' : 'add_movie'; ?>">
                <?php if ($phimChinhSua): ?>
                    <input type="hidden" name="movie_id" value="<?php echo $phimChinhSua['MaPhim']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="ten_phim" class="form-label">Tên phim *</label>
                    <input type="text" id="ten_phim" name="ten_phim" class="form-input" 
                           value="<?php echo $phimChinhSua ? htmlspecialchars($phimChinhSua['TenPhim']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="thoi_luong" class="form-label">Thời lượng (phút) *</label>
                    <input type="number" id="thoi_luong" name="thoi_luong" class="form-input" 
                           value="<?php echo $phimChinhSua ? $phimChinhSua['ThoiLuong'] : ''; ?>" min="1" required>
                </div>
                
                <div class="form-group">
                    <label for="anh_bia" class="form-label">URL ảnh bìa</label>
                    <input type="url" id="anh_bia" name="anh_bia" class="form-input" 
                           value="<?php echo $phimChinhSua ? htmlspecialchars($phimChinhSua['AnhBia']) : ''; ?>" 
                           placeholder="https://example.com/poster.jpg">
                </div>
                
                <div class="form-group">
                    <label for="nam_san_xuat" class="form-label">Năm sản xuất *</label>
                    <input type="number" id="nam_san_xuat" name="nam_san_xuat" class="form-input" 
                           value="<?php echo $phimChinhSua ? $phimChinhSua['NamSanXuat'] : ''; ?>" 
                           min="1900" max="2030" required>
                </div>
                
                <div class="form-group">
                    <label for="trang_thai" class="form-label">Trạng thái</label>
                    <select id="trang_thai" name="trang_thai" class="form-select">
                        <option value="SapChieu" <?php echo ($phimChinhSua && $phimChinhSua['TrangThai'] === 'SapChieu') ? 'selected' : ''; ?>>Sắp chiếu</option>
                        <option value="DangChieu" <?php echo ($phimChinhSua && $phimChinhSua['TrangThai'] === 'DangChieu') ? 'selected' : ''; ?>>Đang chiếu</option>
                        <option value="KetThuc" <?php echo ($phimChinhSua && $phimChinhSua['TrangThai'] === 'KetThuc') ? 'selected' : ''; ?>>Kết thúc</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="the_loai" class="form-label">Thể loại</label>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; margin-top: 10px;">
                        <?php foreach ($danhSachTheLoai as $theLoai): ?>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="the_loai[]" value="<?php echo $theLoai['MaTheloai']; ?>" 
                                       id="theloai_<?php echo $theLoai['MaTheloai']; ?>"
                                       <?php 
                                       if ($phimChinhSua && $phimChinhSua['MaTheLoai']) {
                                           $maTheLoaiIds = explode(',', $phimChinhSua['MaTheLoai']);
                                           if (in_array($theLoai['MaTheloai'], $maTheLoaiIds)) {
                                               echo 'checked';
                                           }
                                       }
                                       ?>>
                                <label for="theloai_<?php echo $theLoai['MaTheloai']; ?>" style="margin: 0; font-weight: normal;">
                                    <?php echo htmlspecialchars($theLoai['TheLoai']); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="mo_ta" class="form-label">Mô tả</label>
                    <textarea id="mo_ta" name="mo_ta" class="form-textarea" 
                              placeholder="Mô tả ngắn về phim..."><?php echo $phimChinhSua ? htmlspecialchars($phimChinhSua['MoTa']) : ''; ?></textarea>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('movieModal')">Hủy</button>
                    <button type="submit" class="btn btn-primary"><?php echo $phimChinhSua ? 'Cập nhật' : 'Thêm mới'; ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    <?php if ($phimChinhSua): ?>
    // Auto open modal if editing
    document.addEventListener('DOMContentLoaded', function() {
        openModal('movieModal');
    });
    <?php endif; ?>
</script>

<?php require_once 'includes/admin_footer.php'; ?>

