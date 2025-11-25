<?php
/**
 * Class quản lý suất chiếu
 * Xử lý CRUD operations cho suất chiếu
 */

require_once 'config/database.php';
require_once 'security.php';

class Showtime {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Lấy danh sách suất chiếu theo phim
     */
    public function getShowtimesByMovie($movieId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT sc.*, p.TenPhong, p.LoaiPhong, ph.TenPhim
                FROM SUATCHIEU sc
                JOIN PHONG p ON sc.MaPhong = p.MaPhong
                JOIN PHIM ph ON sc.MaPhim = ph.MaPhim
                WHERE sc.MaPhim = ?
                ORDER BY sc.ThoiGian ASC
            ");
            $stmt->execute([$movieId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            return ['error' => 'Lỗi khi lấy danh sách suất chiếu: ' . $e->getMessage()];
        }
    }
    
    /**
     * Lấy thông tin suất chiếu theo ID
     */
    public function getShowtimeById($id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT sc.*, p.TenPhong, p.LoaiPhong, ph.TenPhim
                FROM SUATCHIEU sc
                JOIN PHONG p ON sc.MaPhong = p.MaPhong
                JOIN PHIM ph ON sc.MaPhim = ph.MaPhim
                WHERE sc.MaSuat = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            return ['error' => 'Lỗi khi lấy thông tin suất chiếu: ' . $e->getMessage()];
        }
    }
    
    /**
     * Thêm suất chiếu mới
     */
    public function addShowtime($data) {
        try {
            // Chuyển đổi format datetime từ datetime-local (YYYY-MM-DDTHH:mm) sang MySQL DATETIME (YYYY-MM-DD HH:mm:ss)
            $thoiGian = $data['thoi_gian'];
            if (strpos($thoiGian, 'T') !== false) {
                // Format từ datetime-local: YYYY-MM-DDTHH:mm
                $thoiGian = str_replace('T', ' ', $thoiGian) . ':00';
            }
            
            // Validate datetime format
            $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', $thoiGian);
            if (!$dateTime) {
                // Thử format khác: Y-m-d H:i
                $dateTime = DateTime::createFromFormat('Y-m-d H:i', $thoiGian);
                if ($dateTime) {
                    $thoiGian = $dateTime->format('Y-m-d H:i:s');
                } else {
                    return ['success' => false, 'message' => 'Định dạng thời gian không hợp lệ!'];
                }
            } else {
                $thoiGian = $dateTime->format('Y-m-d H:i:s');
            }
            
            // Kiểm tra thời gian phải trong tương lai (ít nhất 30 phút từ bây giờ)
            $now = new DateTime();
            $showtimeDateTime = DateTime::createFromFormat('Y-m-d H:i:s', $thoiGian);
            $minTime = clone $now;
            $minTime->modify('+30 minutes');
            
            if ($showtimeDateTime < $minTime) {
                return ['success' => false, 'message' => 'Thời gian suất chiếu phải ít nhất 30 phút từ bây giờ!'];
            }
            
            $stmt = $this->conn->prepare("
                INSERT INTO SUATCHIEU (MaPhim, MaPhong, ThoiGian, GiaBan, TrangThai) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['ma_phim'],
                $data['ma_phong'],
                $thoiGian,
                $data['gia_ban'],
                $data['trang_thai'] ?? 'ConVe'
            ]);
            
            Security::logSecurityEvent('SHOWTIME_ADDED', 'Showtime for movie: ' . $data['ma_phim'] . ' at ' . $thoiGian);
            
            return ['success' => true, 'message' => 'Thêm suất chiếu thành công!', 'id' => $this->conn->lastInsertId()];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Lỗi khi thêm suất chiếu: ' . $e->getMessage()];
        }
    }
    
    /**
     * Cập nhật suất chiếu
     */
    public function updateShowtime($id, $data) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE SUATCHIEU 
                SET MaPhong = ?, ThoiGian = ?, GiaBan = ?, TrangThai = ?
                WHERE MaSuat = ?
            ");
            
            $stmt->execute([
                $data['ma_phong'],
                $data['thoi_gian'],
                $data['gia_ban'],
                $data['trang_thai'] ?? 'ConVe',
                $id
            ]);
            
            Security::logSecurityEvent('SHOWTIME_UPDATED', 'Showtime ID: ' . $id);
            
            return ['success' => true, 'message' => 'Cập nhật suất chiếu thành công!'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Lỗi khi cập nhật suất chiếu: ' . $e->getMessage()];
        }
    }
    
    /**
     * Xóa suất chiếu
     */
    public function deleteShowtime($id) {
        try {
            // Kiểm tra xem có vé nào đã đặt cho suất chiếu này không
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count 
                FROM VE 
                WHERE MaSuat = ? AND TrangThai != 'Huy'
            ");
            $stmt->execute([$id]);
            $ticketCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            if ($ticketCount > 0) {
                return ['success' => false, 'message' => 'Không thể xóa suất chiếu đã có vé được đặt!'];
            }
            
            $stmt = $this->conn->prepare("DELETE FROM SUATCHIEU WHERE MaSuat = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() === 0) {
                return ['success' => false, 'message' => 'Không tìm thấy suất chiếu để xóa!'];
            }
            
            Security::logSecurityEvent('SHOWTIME_DELETED', 'Showtime ID: ' . $id);
            
            return ['success' => true, 'message' => 'Xóa suất chiếu thành công!'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Lỗi khi xóa suất chiếu: ' . $e->getMessage()];
        }
    }
    
    /**
     * Lấy danh sách phòng
     */
    public function getRooms() {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM PHONG ORDER BY TenPhong");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return ['error' => 'Lỗi khi lấy danh sách phòng: ' . $e->getMessage()];
        }
    }
    
    /**
     * Lấy danh sách phim đang chiếu
     */
    public function getNowShowingMovies() {
        try {
            $stmt = $this->conn->prepare("
                SELECT MaPhim, TenPhim 
                FROM PHIM 
                WHERE TrangThai = 'DangChieu' 
                ORDER BY TenPhim
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return ['error' => 'Lỗi khi lấy danh sách phim: ' . $e->getMessage()];
        }
    }
    
    /**
     * Lấy suất chiếu có sẵn cho đặt vé
     */
    public function getAvailableShowtimes($movieId) {
        try {
            // Lấy suất chiếu có trạng thái 'ConVe' và thời gian >= hiện tại
            // Cho phép đặt vé cho suất chiếu trong tương lai (>= hiện tại)
            $stmt = $this->conn->prepare("
                SELECT sc.*, p.TenPhong, p.LoaiPhong, p.SoGhe
                FROM SUATCHIEU sc
                JOIN PHONG p ON sc.MaPhong = p.MaPhong
                WHERE sc.MaPhim = ? 
                AND sc.TrangThai = 'ConVe'
                AND sc.ThoiGian >= NOW()
                ORDER BY sc.ThoiGian ASC
            ");
            $stmt->execute([$movieId]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $result;
            
        } catch (Exception $e) {
            return ['error' => 'Lỗi khi lấy suất chiếu: ' . $e->getMessage()];
        }
    }
}
?>
