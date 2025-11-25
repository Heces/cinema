<?php
/**
 * Class quản lý phim
 * Xử lý CRUD operations cho phim
 */

require_once 'config/database.php';
require_once 'security.php';

class Movie {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Lấy danh sách tất cả phim
     */
    public function getAllMovies($limit = null, $offset = 0) {
        try {
            $sql = "
                SELECT p.*, 
                       GROUP_CONCAT(DISTINCT t.TheLoai SEPARATOR ', ') AS TheLoai,
                       GROUP_CONCAT(DISTINCT n.TenNSX SEPARATOR ', ') AS NhaSanXuat
                FROM PHIM p
                LEFT JOIN PHIM_THELOAI pt ON p.MaPhim = pt.MaPhim
                LEFT JOIN THELOAI t ON pt.MaTheloai = t.MaTheloai
                LEFT JOIN PHIM_NSX pn ON p.MaPhim = pn.MaPhim
                LEFT JOIN NSX n ON pn.MaNSX = n.MaNSX
                GROUP BY p.MaPhim
                ORDER BY p.NgayTao DESC
            ";
            
            if ($limit) {
                $sql .= " LIMIT $limit OFFSET $offset";
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            return ['error' => 'Lỗi khi lấy danh sách phim: ' . $e->getMessage()];
        }
    }
    
    /**
     * Lấy thông tin phim theo ID
     */
    public function getMovieById($id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT p.*, 
                       GROUP_CONCAT(DISTINCT t.MaTheloai) AS MaTheLoai,
                       GROUP_CONCAT(DISTINCT t.TheLoai SEPARATOR ', ') AS TheLoai,
                       GROUP_CONCAT(DISTINCT n.MaNSX) AS MaNSX,
                       GROUP_CONCAT(DISTINCT n.TenNSX SEPARATOR ', ') AS NhaSanXuat
                FROM PHIM p
                LEFT JOIN PHIM_THELOAI pt ON p.MaPhim = pt.MaPhim
                LEFT JOIN THELOAI t ON pt.MaTheloai = t.MaTheloai
                LEFT JOIN PHIM_NSX pn ON p.MaPhim = pn.MaPhim
                LEFT JOIN NSX n ON pn.MaNSX = n.MaNSX
                WHERE p.MaPhim = ?
                GROUP BY p.MaPhim
            ");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            return ['error' => 'Lỗi khi lấy thông tin phim: ' . $e->getMessage()];
        }
    }
    
    /**
     * Thêm phim mới
     */
    public function addMovie($data) {
        try {
            $this->conn->beginTransaction();
            
            // Thêm phim
            $stmt = $this->conn->prepare("
                INSERT INTO PHIM (TenPhim, ThoiLuong, AnhBia, MoTa, NamSanXuat, TrangThai) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['ten_phim'],
                $data['thoi_luong'],
                $data['anh_bia'] ?? '',
                $data['mo_ta'] ?? '',
                $data['nam_san_xuat'],
                $data['trang_thai'] ?? 'SapChieu'
            ]);
            
            $maPhim = $this->conn->lastInsertId();
            
            // Thêm thể loại
            if (!empty($data['the_loai'])) {
                $this->addMovieGenres($maPhim, $data['the_loai']);
            }
            
            // Thêm nhà sản xuất
            if (!empty($data['nha_san_xuat'])) {
                $this->addMovieProducers($maPhim, $data['nha_san_xuat']);
            }
            
            // Không tự động tạo suất chiếu - admin sẽ quản lý thủ công
            
            $this->conn->commit();
            Security::logSecurityEvent('MOVIE_ADDED', 'Movie: ' . $data['ten_phim']);
            
            return ['success' => true, 'message' => 'Thêm phim thành công!', 'id' => $maPhim];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['success' => false, 'message' => 'Lỗi khi thêm phim: ' . $e->getMessage()];
        }
    }
    
    /**
     * Cập nhật phim
     */
    public function updateMovie($id, $data) {
        try {
            $this->conn->beginTransaction();
            
            // Lấy trạng thái hiện tại của phim
            $stmt = $this->conn->prepare("SELECT TrangThai FROM PHIM WHERE MaPhim = ?");
            $stmt->execute([$id]);
            $currentStatus = $stmt->fetch(PDO::FETCH_ASSOC)['TrangThai'];
            $newStatus = $data['trang_thai'] ?? 'SapChieu';
            
            // Cập nhật thông tin phim
            $stmt = $this->conn->prepare("
                UPDATE PHIM 
                SET TenPhim = ?, ThoiLuong = ?, AnhBia = ?, MoTa = ?, 
                    NamSanXuat = ?, TrangThai = ?, NgayCapNhat = CURRENT_TIMESTAMP
                WHERE MaPhim = ?
            ");
            
            $stmt->execute([
                $data['ten_phim'],
                $data['thoi_luong'],
                $data['anh_bia'] ?? '',
                $data['mo_ta'] ?? '',
                $data['nam_san_xuat'],
                $newStatus,
                $id
            ]);
            
            // Xóa thể loại cũ
            $stmt = $this->conn->prepare("DELETE FROM PHIM_THELOAI WHERE MaPhim = ?");
            $stmt->execute([$id]);
            
            // Thêm thể loại mới
            if (!empty($data['the_loai'])) {
                $this->addMovieGenres($id, $data['the_loai']);
            }
            
            // Xóa nhà sản xuất cũ
            $stmt = $this->conn->prepare("DELETE FROM PHIM_NSX WHERE MaPhim = ?");
            $stmt->execute([$id]);
            
            // Thêm nhà sản xuất mới
            if (!empty($data['nha_san_xuat'])) {
                $this->addMovieProducers($id, $data['nha_san_xuat']);
            }
            
            // Không tự động tạo suất chiếu - admin sẽ quản lý thủ công
            
            $this->conn->commit();
            Security::logSecurityEvent('MOVIE_UPDATED', 'Movie ID: ' . $id);
            
            return ['success' => true, 'message' => 'Cập nhật phim thành công!'];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['success' => false, 'message' => 'Lỗi khi cập nhật phim: ' . $e->getMessage()];
        }
    }
    
    /**
     * Xóa phim
     */
    public function deleteMovie($id) {
        try {
            // Kiểm tra ID hợp lệ
            if (!is_numeric($id) || $id <= 0) {
                return ['success' => false, 'message' => 'ID phim không hợp lệ!'];
            }
            
            // Lấy tên phim để log
            $movie = $this->getMovieById($id);
            if (isset($movie['error'])) {
                return ['success' => false, 'message' => 'Không thể lấy thông tin phim: ' . $movie['error']];
            }
            
            if (empty($movie)) {
                return ['success' => false, 'message' => 'Phim không tồn tại!'];
            }
            
            $movieName = $movie['TenPhim'] ?? 'Unknown';
            error_log("Deleting movie: " . $movieName . " (ID: " . $id . ")");
            
            $this->conn->beginTransaction();
            
            // Xóa phim (cascade sẽ xóa các bảng liên quan)
            $stmt = $this->conn->prepare("DELETE FROM PHIM WHERE MaPhim = ?");
            $result = $stmt->execute([$id]);
            
            if ($stmt->rowCount() === 0) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Không có phim nào được xóa!'];
            }
            
            $this->conn->commit();
            Security::logSecurityEvent('MOVIE_DELETED', 'Movie: ' . $movieName);
            
            return ['success' => true, 'message' => 'Xóa phim "' . $movieName . '" thành công!'];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error deleting movie: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi khi xóa phim: ' . $e->getMessage()];
        }
    }
    
    /**
     * Lấy danh sách thể loại
     */
    public function getGenres() {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM THELOAI ORDER BY TheLoai");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return ['error' => 'Lỗi khi lấy danh sách thể loại: ' . $e->getMessage()];
        }
    }
    
    /**
     * Lấy danh sách nhà sản xuất
     */
    public function getProducers() {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM NSX ORDER BY TenNSX");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return ['error' => 'Lỗi khi lấy danh sách nhà sản xuất: ' . $e->getMessage()];
        }
    }
    
    /**
     * Thêm thể loại cho phim
     */
    private function addMovieGenres($maPhim, $theLoaiIds) {
        if (is_array($theLoaiIds)) {
            $stmt = $this->conn->prepare("INSERT INTO PHIM_THELOAI (MaPhim, MaTheloai) VALUES (?, ?)");
            foreach ($theLoaiIds as $theLoaiId) {
                $stmt->execute([$maPhim, $theLoaiId]);
            }
        }
    }
    
    /**
     * Thêm nhà sản xuất cho phim
     */
    private function addMovieProducers($maPhim, $nsxIds) {
        if (is_array($nsxIds)) {
            $stmt = $this->conn->prepare("INSERT INTO PHIM_NSX (MaPhim, MaNSX, VaiTro) VALUES (?, ?, 'SanXuat')");
            foreach ($nsxIds as $nsxId) {
                $stmt->execute([$maPhim, $nsxId]);
            }
        }
    }
    
    /**
     * Lấy phim theo trạng thái
     */
    public function getMoviesByStatus($status, $limit = 10) {
        try {
            $stmt = $this->conn->prepare("
                SELECT p.*, 
                       GROUP_CONCAT(DISTINCT t.TheLoai SEPARATOR ', ') AS TheLoai,
                       GROUP_CONCAT(DISTINCT n.TenNSX SEPARATOR ', ') AS NhaSanXuat
                FROM PHIM p
                LEFT JOIN PHIM_THELOAI pt ON p.MaPhim = pt.MaPhim
                LEFT JOIN THELOAI t ON pt.MaTheloai = t.MaTheloai
                LEFT JOIN PHIM_NSX pn ON p.MaPhim = pn.MaPhim
                LEFT JOIN NSX n ON pn.MaNSX = n.MaNSX
                WHERE p.TrangThai = ?
                GROUP BY p.MaPhim
                ORDER BY p.NgayTao DESC
                LIMIT ?
            ");
            
            $stmt->execute([$status, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            return ['error' => 'Lỗi khi lấy phim theo trạng thái: ' . $e->getMessage()];
        }
    }
    
    
    
    /**
     * Tạo suất chiếu mặc định cho phim đang chiếu
     */
    private function createDefaultShowtimes($maPhim) {
        try {
            // Lấy danh sách phòng
            $stmt = $this->conn->query("SELECT MaPhong, TenPhong FROM PHONG ORDER BY MaPhong");
            $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($rooms)) {
                return; // Không có phòng nào
            }
            
            $createdCount = 0;
            $maxAttempts = 50; // Tối đa 50 lần thử
            $attempt = 0;
            
            // Tạo ít nhất 3 suất chiếu cho phim
            while ($createdCount < 3 && $attempt < $maxAttempts) {
                $attempt++;
                
                // Tạo thời gian ngẫu nhiên trong 7 ngày tới
                $day = rand(0, 6);
                $hour = rand(8, 22); // Từ 8h đến 22h
                $minute = rand(0, 3) * 15; // 0, 15, 30, 45
                
                $date = date('Y-m-d', strtotime("+$day days"));
                $datetime = $date . ' ' . sprintf('%02d:%02d:00', $hour, $minute);
                
                // Chọn phòng ngẫu nhiên
                $room = $rooms[array_rand($rooms)];
                
                // Giá vé khác nhau theo loại phòng
                $prices = [80000, 100000, 120000, 150000, 180000];
                $price = $prices[array_rand($prices)];
                
                try {
                    $stmt = $this->conn->prepare("
                        INSERT INTO SUATCHIEU (MaPhim, MaPhong, ThoiGian, GiaBan, TrangThai) 
                        VALUES (?, ?, ?, ?, 'ConVe')
                    ");
                    $stmt->execute([
                        $maPhim, 
                        $room['MaPhong'], 
                        $datetime,
                        $price
                    ]);
                    
                    $createdCount++;
                    error_log("Created showtime for movie $maPhim: $datetime at {$room['TenPhong']}");
                    
                } catch (Exception $e) {
                    // Bỏ qua lỗi duplicate entry, thử lại
                    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                        continue;
                    } else {
                        error_log("Error creating showtime: " . $e->getMessage());
                        break;
                    }
                }
            }
            
            error_log("Created $createdCount showtimes for movie $maPhim");
            
        } catch (Exception $e) {
            // Log lỗi nhưng không làm fail transaction
            error_log("Error creating default showtimes for movie $maPhim: " . $e->getMessage());
        }
    }
}
?>
