<?php
/**
 * Class quan ly suat chieu
 * Xu ly CRUD operations cho suat chieu
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/security.php';

class SuatChieu {
    private $ketNoi;
    
    public function __construct() {
        $coSoDuLieu = new CoSoDuLieu();
        $this->ketNoi = $coSoDuLieu->layKetNoi();
    }
    
    /**
     * Lay danh sach suat chieu theo phim
     */
    public function laySuatChieuTheoPhim($maPhim) {
        try {
            $cauLenh = $this->ketNoi->prepare("
                SELECT sc.*, p.TenPhong, p.LoaiPhong, ph.TenPhim
                FROM SUATCHIEU sc
                JOIN PHONG p ON sc.MaPhong = p.MaPhong
                JOIN PHIM ph ON sc.MaPhim = ph.MaPhim
                WHERE sc.MaPhim = ?
                ORDER BY sc.ThoiGian ASC
            ");
            $cauLenh->execute([$maPhim]);
            return $cauLenh->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $loi) {
            return ['error' => 'Loi khi lay danh sach suat chieu: ' . $loi->getMessage()];
        }
    }
    
    /**
     * Lay thong tin suat chieu theo ID
     */
    public function laySuatChieuTheoId($maSuat) {
        try {
            $cauLenh = $this->ketNoi->prepare("
                SELECT sc.*, p.TenPhong, p.LoaiPhong, ph.TenPhim
                FROM SUATCHIEU sc
                JOIN PHONG p ON sc.MaPhong = p.MaPhong
                JOIN PHIM ph ON sc.MaPhim = ph.MaPhim
                WHERE sc.MaSuat = ?
            ");
            $cauLenh->execute([$maSuat]);
            return $cauLenh->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $loi) {
            return ['error' => 'Loi khi lay thong tin suat chieu: ' . $loi->getMessage()];
        }
    }
    
    /**
     * Them suat chieu moi
     */
    public function themSuatChieu($duLieu) {
        try {
            $cauLenh = $this->ketNoi->prepare("
                INSERT INTO SUATCHIEU (MaPhim, MaPhong, ThoiGian, GiaBan, TrangThai) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $cauLenh->execute([
                $duLieu['ma_phim'],
                $duLieu['ma_phong'],
                $duLieu['thoi_gian'],
                $duLieu['gia_ban'],
                $duLieu['trang_thai'] ?? 'ConVe'
            ]);
            
            BaoMat::ghiLog('THEM_SUAT_CHIEU', 'Suat chieu cho phim: ' . $duLieu['ma_phim']);
            
            return ['thanhCong' => true, 'thongBao' => 'Them suat chieu thanh cong!', 'id' => $this->ketNoi->lastInsertId()];
            
        } catch (Exception $loi) {
            return ['thanhCong' => false, 'thongBao' => 'Loi khi them suat chieu: ' . $loi->getMessage()];
        }
    }
    
    /**
     * Cap nhat suat chieu
     */
    public function capNhatSuatChieu($maSuat, $duLieu) {
        try {
            $cauLenh = $this->ketNoi->prepare("
                UPDATE SUATCHIEU 
                SET MaPhong = ?, ThoiGian = ?, GiaBan = ?, TrangThai = ?
                WHERE MaSuat = ?
            ");
            
            $cauLenh->execute([
                $duLieu['ma_phong'],
                $duLieu['thoi_gian'],
                $duLieu['gia_ban'],
                $duLieu['trang_thai'] ?? 'ConVe',
                $maSuat
            ]);
            
            BaoMat::ghiLog('CAP_NHAT_SUAT_CHIEU', 'Ma suat: ' . $maSuat);
            
            return ['thanhCong' => true, 'thongBao' => 'Cap nhat suat chieu thanh cong!'];
            
        } catch (Exception $loi) {
            return ['thanhCong' => false, 'thongBao' => 'Loi khi cap nhat suat chieu: ' . $loi->getMessage()];
        }
    }
    
    /**
     * Xoa suat chieu
     */
    public function xoaSuatChieu($maSuat) {
        try {
            // Kiem tra xem co ve nao da dat cho suat chieu nay khong
            $cauLenh = $this->ketNoi->prepare("
                SELECT COUNT(*) as soLuong 
                FROM VE 
                WHERE MaSuat = ? AND TrangThai != 'Huy'
            ");
            $cauLenh->execute([$maSuat]);
            $soVe = $cauLenh->fetch(PDO::FETCH_ASSOC)['soLuong'];
            
            if ($soVe > 0) {
                return ['thanhCong' => false, 'thongBao' => 'Khong the xoa suat chieu da co ve duoc dat!'];
            }
            
            $cauLenh = $this->ketNoi->prepare("DELETE FROM SUATCHIEU WHERE MaSuat = ?");
            $cauLenh->execute([$maSuat]);
            
            if ($cauLenh->rowCount() === 0) {
                return ['thanhCong' => false, 'thongBao' => 'Khong tim thay suat chieu de xoa!'];
            }
            
            BaoMat::ghiLog('XOA_SUAT_CHIEU', 'Ma suat: ' . $maSuat);
            
            return ['thanhCong' => true, 'thongBao' => 'Xoa suat chieu thanh cong!'];
            
        } catch (Exception $loi) {
            return ['thanhCong' => false, 'thongBao' => 'Loi khi xoa suat chieu: ' . $loi->getMessage()];
        }
    }
    
    /**
     * Lay danh sach phong
     */
    public function layPhong() {
        try {
            $cauLenh = $this->ketNoi->prepare("SELECT * FROM PHONG ORDER BY TenPhong");
            $cauLenh->execute();
            return $cauLenh->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $loi) {
            return ['error' => 'Loi khi lay danh sach phong: ' . $loi->getMessage()];
        }
    }
    
    /**
     * Lay danh sach phim dang chieu
     */
    public function layPhimDangChieu() {
        try {
            $cauLenh = $this->ketNoi->prepare("
                SELECT MaPhim, TenPhim 
                FROM PHIM 
                WHERE TrangThai = 'DangChieu' 
                ORDER BY TenPhim
            ");
            $cauLenh->execute();
            return $cauLenh->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $loi) {
            return ['error' => 'Loi khi lay danh sach phim: ' . $loi->getMessage()];
        }
    }
    
    /**
     * Lay suat chieu co san cho dat ve
     */
    public function laySuatChieuCoSan($maPhim) {
        try {
            $cauLenh = $this->ketNoi->prepare("
                SELECT sc.*, p.TenPhong, p.LoaiPhong, p.SoGhe
                FROM SUATCHIEU sc
                JOIN PHONG p ON sc.MaPhong = p.MaPhong
                WHERE sc.MaPhim = ? AND sc.TrangThai = 'ConVe'
                AND sc.ThoiGian > NOW()
                ORDER BY sc.ThoiGian ASC
            ");
            $cauLenh->execute([$maPhim]);
            return $cauLenh->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $loi) {
            return ['error' => 'Loi khi lay suat chieu: ' . $loi->getMessage()];
        }
    }
    
    // Giữ lại các hàm cũ để tương thích ngược
    public function getShowtimesByMovie($movieId) {
        return $this->laySuatChieuTheoPhim($movieId);
    }
    
    public function getShowtimeById($id) {
        return $this->laySuatChieuTheoId($id);
    }
    
    public function addShowtime($data) {
        $ketQua = $this->themSuatChieu($data);
        return ['success' => $ketQua['thanhCong'], 'message' => $ketQua['thongBao'], 'id' => $ketQua['id'] ?? null];
    }
    
    public function updateShowtime($id, $data) {
        $ketQua = $this->capNhatSuatChieu($id, $data);
        return ['success' => $ketQua['thanhCong'], 'message' => $ketQua['thongBao']];
    }
    
    public function deleteShowtime($id) {
        $ketQua = $this->xoaSuatChieu($id);
        return ['success' => $ketQua['thanhCong'], 'message' => $ketQua['thongBao']];
    }
    
    public function getRooms() {
        return $this->layPhong();
    }
    
    public function getNowShowingMovies() {
        return $this->layPhimDangChieu();
    }
    
    public function getAvailableShowtimes($movieId) {
        return $this->laySuatChieuCoSan($movieId);
    }
}

// Alias cho tên cũ
class Showtime extends SuatChieu {}
?>
