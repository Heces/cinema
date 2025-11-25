<?php
require_once 'includes/admin_layout.php';
require_once 'includes/movie.php';

$movie = new Movie();

$error_message = '';
$success_message = '';
$searchQuery = trim($_GET['search'] ?? '');

// Xử lý các action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_movie':
            $result = $movie->addMovie($_POST);
            if ($result['success']) {
                $success_message = $result['message'];
            } else {
                $error_message = $result['message'];
            }
            break;
            
        case 'update_movie':
            $id = $_POST['movie_id'] ?? 0;
            $result = $movie->updateMovie($id, $_POST);
            if ($result['success']) {
                $success_message = $result['message'];
            } else {
                $error_message = $result['message'];
            }
            break;
            
        case 'delete_movie':
            $id = $_POST['movie_id'] ?? 0;
            $result = $movie->deleteMovie($id);
            if ($result['success']) {
                $success_message = $result['message'];
            } else {
                $error_message = $result['message'];
            }
            break;
    }
}

// Lấy danh sách phim
$movies = $movie->getAllMovies(null, 0, !empty($searchQuery) ? $searchQuery : null);
$genres = $movie->getGenres();

// Lấy phim để edit (nếu có)
$editMovie = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editMovie = $movie->getMovieById($_GET['edit']);
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
        <h2 class="admin-card-title" style="margin-right: auto;">Quản lý Phim</h2>
        <form method="GET" class="admin-search-form" style="display: flex; gap: 8px; align-items: center;">
            <input type="text" name="search" placeholder="Tìm theo tên phim, thể loại..." 
                   value="<?php echo htmlspecialchars($searchQuery); ?>"
                   style="padding: 8px 12px; border: 1px solid var(--admin-border); border-radius: 6px; min-width: 260px;">
            <?php if ($searchQuery !== ''): ?>
                <a href="admin_movies.php" class="btn btn-outline" style="padding: 8px 12px;">Xóa lọc</a>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary" style="padding: 8px 16px;">
                <ion-icon name="search-outline"></ion-icon>
                Tìm kiếm
            </button>
        </form>
        <button class="btn btn-primary" onclick="openModal('movieModal')" style="white-space: nowrap;">
            <ion-icon name="add-outline"></ion-icon>
            Thêm phim
        </button>
    </div>

    <div class="admin-card-body">
        <?php if (isset($movies['error'])): ?>
            <p style="text-align: center; color: var(--admin-text-light); padding: 40px;">
                <?php echo htmlspecialchars($movies['error']); ?>
            </p>
        <?php elseif (empty($movies)): ?>
            <p style="text-align: center; color: var(--admin-text-light); padding: 40px;">
                <?php if ($searchQuery !== ''): ?>
                    Không tìm thấy phim nào với từ khóa "<?php echo htmlspecialchars($searchQuery); ?>"
                <?php else: ?>
                    Chưa có phim nào. Hãy thêm phim đầu tiên!
                <?php endif; ?>
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
                        <?php foreach ($movies as $m): ?>
                            <tr>
                                <td><?php echo $m['MaPhim']; ?></td>
                                <td>
                                    <img src="<?php echo htmlspecialchars($m['AnhBia'] ?: './assets/images/movie-1.png'); ?>" 
                                         alt="<?php echo htmlspecialchars($m['TenPhim']); ?>" 
                                         style="width: 60px; height: 80px; object-fit: cover; border-radius: 4px;">
                                </td>
                                <td><strong><?php echo htmlspecialchars($m['TenPhim']); ?></strong></td>
                                <td><?php echo $m['ThoiLuong']; ?> phút</td>
                                <td><?php echo $m['NamSanXuat']; ?></td>
                                <td><?php echo htmlspecialchars($m['TheLoai'] ?: 'Chưa phân loại'); ?></td>
                                <td>
                                    <?php
                                    $statusClass = 'badge-warning';
                                    $statusText = 'Sắp chiếu';
                                    if ($m['TrangThai'] === 'DangChieu') {
                                        $statusClass = 'badge-success';
                                        $statusText = 'Đang chiếu';
                                    } elseif ($m['TrangThai'] === 'KetThuc') {
                                        $statusClass = 'badge-danger';
                                        $statusText = 'Kết thúc';
                                    }
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                </td>
                                <td>
                                    <?php
                                        $movieEditParams = ['edit' => $m['MaPhim']];
                                        if ($searchQuery !== '') {
                                            $movieEditParams['search'] = $searchQuery;
                                        }
                                        $movieEditUrl = '?' . http_build_query($movieEditParams);
                                    ?>
                                    <a href="<?php echo $movieEditUrl; ?>" 
                                       class="btn btn-outline" style="padding: 6px 12px; font-size: 0.85rem;">
                                        <ion-icon name="create-outline"></ion-icon>
                                        Sửa
                                    </a>
                                    <form method="POST" style="display: inline;" 
                                          onsubmit="return confirm('Bạn có chắc chắn muốn xóa phim này?');">
                                        <input type="hidden" name="action" value="delete_movie">
                                        <input type="hidden" name="movie_id" value="<?php echo $m['MaPhim']; ?>">
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
<div id="movieModal" class="modal <?php echo $editMovie ? 'show' : ''; ?>">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title"><?php echo $editMovie ? 'Sửa phim' : 'Thêm phim mới'; ?></h3>
            <button class="modal-close" onclick="closeModal('movieModal')">
                <ion-icon name="close-outline"></ion-icon>
            </button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="action" value="<?php echo $editMovie ? 'update_movie' : 'add_movie'; ?>">
                <?php if ($editMovie): ?>
                    <input type="hidden" name="movie_id" value="<?php echo $editMovie['MaPhim']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="ten_phim" class="form-label">Tên phim *</label>
                    <input type="text" id="ten_phim" name="ten_phim" class="form-input" 
                           value="<?php echo $editMovie ? htmlspecialchars($editMovie['TenPhim']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="thoi_luong" class="form-label">Thời lượng (phút) *</label>
                    <input type="number" id="thoi_luong" name="thoi_luong" class="form-input" 
                           value="<?php echo $editMovie ? $editMovie['ThoiLuong'] : ''; ?>" min="1" required>
                </div>
                
                <div class="form-group">
                    <label for="anh_bia" class="form-label">URL ảnh bìa</label>
                    <input type="url" id="anh_bia" name="anh_bia" class="form-input" 
                           value="<?php echo $editMovie ? htmlspecialchars($editMovie['AnhBia']) : ''; ?>" 
                           placeholder="https://example.com/poster.jpg">
                </div>
                
                <div class="form-group">
                    <label for="nam_san_xuat" class="form-label">Năm sản xuất *</label>
                    <input type="number" id="nam_san_xuat" name="nam_san_xuat" class="form-input" 
                           value="<?php echo $editMovie ? $editMovie['NamSanXuat'] : ''; ?>" 
                           min="1900" max="2030" required>
                </div>
                
                <div class="form-group">
                    <label for="trang_thai" class="form-label">Trạng thái</label>
                    <select id="trang_thai" name="trang_thai" class="form-select">
                        <option value="SapChieu" <?php echo ($editMovie && $editMovie['TrangThai'] === 'SapChieu') ? 'selected' : ''; ?>>Sắp chiếu</option>
                        <option value="DangChieu" <?php echo ($editMovie && $editMovie['TrangThai'] === 'DangChieu') ? 'selected' : ''; ?>>Đang chiếu</option>
                        <option value="KetThuc" <?php echo ($editMovie && $editMovie['TrangThai'] === 'KetThuc') ? 'selected' : ''; ?>>Kết thúc</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="the_loai" class="form-label">Thể loại</label>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; margin-top: 10px;">
                        <?php foreach ($genres as $genre): ?>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="the_loai[]" value="<?php echo $genre['MaTheloai']; ?>" 
                                       id="genre_<?php echo $genre['MaTheloai']; ?>"
                                       <?php 
                                       if ($editMovie && $editMovie['MaTheLoai']) {
                                           $genreIds = explode(',', $editMovie['MaTheLoai']);
                                           if (in_array($genre['MaTheloai'], $genreIds)) {
                                               echo 'checked';
                                           }
                                       }
                                       ?>>
                                <label for="genre_<?php echo $genre['MaTheloai']; ?>" style="margin: 0; font-weight: normal;">
                                    <?php echo htmlspecialchars($genre['TheLoai']); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="mo_ta" class="form-label">Mô tả</label>
                    <textarea id="mo_ta" name="mo_ta" class="form-textarea" 
                              placeholder="Mô tả ngắn về phim..."><?php echo $editMovie ? htmlspecialchars($editMovie['MoTa']) : ''; ?></textarea>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('movieModal')">Hủy</button>
                    <button type="submit" class="btn btn-primary"><?php echo $editMovie ? 'Cập nhật' : 'Thêm mới'; ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    <?php if ($editMovie): ?>
    // Auto open modal if editing
    document.addEventListener('DOMContentLoaded', function() {
        openModal('movieModal');
    });
    <?php endif; ?>
</script>

<?php require_once 'includes/admin_footer.php'; ?>

