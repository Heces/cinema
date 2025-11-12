<?php
/**
 * Class quan ly phim
 * Xu ly CRUD operations cho phim
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/security.php';

class Phim {
    private $ketNoi;
    
    public function __construct() {
        $coSoDuLieu = new CoSoDuLieu();
        $this->ketNoi = $coSoDuLieu->layKetNoi();
    }
    
    /**
     * Lay danh sach tat ca phim
     */
    public function layTatCaPhim($gioiHan = null, $viTri = 0) {
        try {
            $cauLenhSQL = "
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
            
            if ($gioiHan) {
                $cauLenhSQL .= " LIMIT $gioiHan OFFSET $viTri";
            }
            
            $cauLenh = $this->ketNoi->prepare($cauLenhSQL);
            $cauLenh->execute();
            return $cauLenh->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $loi) {
            return ['error' => 'Loi khi lay danh sach phim: ' . $loi->getMessage()];
        }
    }
    
    /**
     * Lay thong tin phim theo ID
     */
    public function layPhimTheoId($maPhim) {
        try {
            $cauLenh = $this->ketNoi->prepare("
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
            $cauLenh->execute([$maPhim]);
            return $cauLenh->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $loi) {
            return ['error' => 'Loi khi lay thong tin phim: ' . $loi->getMessage()];
        }
    }
    
    /**
     * Them phim moi
     */
    public function themPhim($duLieu) {
        try {
            $this->ketNoi->beginTransaction();
            
            // Them phim
            $cauLenh = $this->ketNoi->prepare("
                INSERT INTO PHIM (TenPhim, ThoiLuong, AnhBia, MoTa, NamSanXuat, TrangThai) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $cauLenh->execute([
                $duLieu['ten_phim'],
                $duLieu['thoi_luong'],
                $duLieu['anh_bia'] ?? '',
                $duLieu['mo_ta'] ?? '',
                $duLieu['nam_san_xuat'],
                $duLieu['trang_thai'] ?? 'SapChieu'
            ]);
            
            $maPhim = $this->ketNoi->lastInsertId();
            
            // Them the loai
            if (!empty($duLieu['the_loai'])) {
                $this->themTheLoaiChoPhim($maPhim, $duLieu['the_loai']);
            }
            
            // Them nha san xuat
            if (!empty($duLieu['nha_san_xuat'])) {
                $this->themNhaSanXuatChoPhim($maPhim, $duLieu['nha_san_xuat']);
            }
            
            $this->ketNoi->commit();
            BaoMat::ghiLog('THEM_PHIM', 'Phim: ' . $duLieu['ten_phim']);
            
            return ['thanhCong' => true, 'thongBao' => 'Them phim thanh cong!', 'id' => $maPhim];
            
        } catch (Exception $loi) {
            $this->ketNoi->rollBack();
            return ['thanhCong' => false, 'thongBao' => 'Loi khi them phim: ' . $loi->getMessage()];
        }
    }
    
    /**
     * Cap nhat phim
     */
    public function capNhatPhim($maPhim, $duLieu) {
        try {
            $this->ketNoi->beginTransaction();
            
            // Cap nhat thong tin phim
            $cauLenh = $this->ketNoi->prepare("
                UPDATE PHIM 
                SET TenPhim = ?, ThoiLuong = ?, AnhBia = ?, MoTa = ?, 
                    NamSanXuat = ?, TrangThai = ?, NgayCapNhat = CURRENT_TIMESTAMP
                WHERE MaPhim = ?
            ");
            
            $cauLenh->execute([
                $duLieu['ten_phim'],
                $duLieu['thoi_luong'],
                $duLieu['anh_bia'] ?? '',
                $duLieu['mo_ta'] ?? '',
                $duLieu['nam_san_xuat'],
                $duLieu['trang_thai'] ?? 'SapChieu',
                $maPhim
            ]);
            
            // Xoa the loai cu
            $cauLenh = $this->ketNoi->prepare("DELETE FROM PHIM_THELOAI WHERE MaPhim = ?");
            $cauLenh->execute([$maPhim]);
            
            // Them the loai moi
            if (!empty($duLieu['the_loai'])) {
                $this->themTheLoaiChoPhim($maPhim, $duLieu['the_loai']);
            }
            
            // Xoa nha san xuat cu
            $cauLenh = $this->ketNoi->prepare("DELETE FROM PHIM_NSX WHERE MaPhim = ?");
            $cauLenh->execute([$maPhim]);
            
            // Them nha san xuat moi
            if (!empty($duLieu['nha_san_xuat'])) {
                $this->themNhaSanXuatChoPhim($maPhim, $duLieu['nha_san_xuat']);
            }
            
            $this->ketNoi->commit();
            BaoMat::ghiLog('CAP_NHAT_PHIM', 'Ma phim: ' . $maPhim);
            
            return ['thanhCong' => true, 'thongBao' => 'Cap nhat phim thanh cong!'];
            
        } catch (Exception $loi) {
            $this->ketNoi->rollBack();
            return ['thanhCong' => false, 'thongBao' => 'Loi khi cap nhat phim: ' . $loi->getMessage()];
        }
    }
    
    /**
     * Xoa phim
     */
    public function xoaPhim($maPhim) {
        try {
            if (!is_numeric($maPhim) || $maPhim <= 0) {
                return ['thanhCong' => false, 'thongBao' => 'Ma phim khong hop le!'];
            }
            
            $phim = $this->layPhimTheoId($maPhim);
            if (isset($phim['error'])) {
                return ['thanhCong' => false, 'thongBao' => 'Khong the lay thong tin phim: ' . $phim['error']];
            }
            
            if (empty($phim)) {
                return ['thanhCong' => false, 'thongBao' => 'Phim khong ton tai!'];
            }
            
            $tenPhim = $phim['TenPhim'] ?? 'Unknown';
            
            $this->ketNoi->beginTransaction();
            
            $cauLenh = $this->ketNoi->prepare("DELETE FROM PHIM WHERE MaPhim = ?");
            $cauLenh->execute([$maPhim]);
            
            if ($cauLenh->rowCount() === 0) {
                $this->ketNoi->rollBack();
                return ['thanhCong' => false, 'thongBao' => 'Khong co phim nao duoc xoa!'];
            }
            
            $this->ketNoi->commit();
            BaoMat::ghiLog('XOA_PHIM', 'Phim: ' . $tenPhim);
            
            return ['thanhCong' => true, 'thongBao' => 'Xoa phim "' . $tenPhim . '" thanh cong!'];
            
        } catch (Exception $loi) {
            $this->ketNoi->rollBack();
            return ['thanhCong' => false, 'thongBao' => 'Loi khi xoa phim: ' . $loi->getMessage()];
        }
    }
    
    /**
     * Lay danh sach the loai
     */
    public function layTheLoai() {
        try {
            $cauLenh = $this->ketNoi->prepare("SELECT * FROM THELOAI ORDER BY TheLoai");
            $cauLenh->execute();
            return $cauLenh->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $loi) {
            return ['error' => 'Loi khi lay danh sach the loai: ' . $loi->getMessage()];
        }
    }
    
    /**
     * Lay danh sach nha san xuat
     */
    public function layNhaSanXuat() {
        try {
            $cauLenh = $this->ketNoi->prepare("SELECT * FROM NSX ORDER BY TenNSX");
            $cauLenh->execute();
            return $cauLenh->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $loi) {
            return ['error' => 'Loi khi lay danh sach nha san xuat: ' . $loi->getMessage()];
        }
    }
    
    /**
     * Them the loai cho phim
     */
    private function themTheLoaiChoPhim($maPhim, $danhSachTheLoai) {
        if (is_array($danhSachTheLoai)) {
            $cauLenh = $this->ketNoi->prepare("INSERT INTO PHIM_THELOAI (MaPhim, MaTheloai) VALUES (?, ?)");
            foreach ($danhSachTheLoai as $maTheLoai) {
                $cauLenh->execute([$maPhim, $maTheLoai]);
            }
        }
    }
    
    /**
     * Them nha san xuat cho phim
     */
    private function themNhaSanXuatChoPhim($maPhim, $danhSachNSX) {
        if (is_array($danhSachNSX)) {
            $cauLenh = $this->ketNoi->prepare("INSERT INTO PHIM_NSX (MaPhim, MaNSX, VaiTro) VALUES (?, ?, 'SanXuat')");
            foreach ($danhSachNSX as $maNSX) {
                $cauLenh->execute([$maPhim, $maNSX]);
            }
        }
    }
    
    /**
     * Lay phim theo trang thai
     */
    public function layPhimTheoTrangThai($trangThai, $gioiHan = 10) {
        try {
            $cauLenh = $this->ketNoi->prepare("
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
            
            $cauLenh->execute([$trangThai, $gioiHan]);
            return $cauLenh->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $loi) {
            return ['error' => 'Loi khi lay phim theo trang thai: ' . $loi->getMessage()];
        }
    }
    
    // Giữ lại các hàm cũ để tương thích ngược
    public function getAllMovies($limit = null, $offset = 0) {
        return $this->layTatCaPhim($limit, $offset);
    }
    
    public function getMovieById($id) {
        return $this->layPhimTheoId($id);
    }
    
    public function addMovie($data) {
        $ketQua = $this->themPhim($data);
        return ['success' => $ketQua['thanhCong'], 'message' => $ketQua['thongBao'], 'id' => $ketQua['id'] ?? null];
    }
    
    public function updateMovie($id, $data) {
        $ketQua = $this->capNhatPhim($id, $data);
        return ['success' => $ketQua['thanhCong'], 'message' => $ketQua['thongBao']];
    }
    
    public function deleteMovie($id) {
        $ketQua = $this->xoaPhim($id);
        return ['success' => $ketQua['thanhCong'], 'message' => $ketQua['thongBao']];
    }
    
    public function getGenres() {
        return $this->layTheLoai();
    }
    
    public function getProducers() {
        return $this->layNhaSanXuat();
    }
    
    public function getMoviesByStatus($status, $limit = 10) {
        return $this->layPhimTheoTrangThai($status, $limit);
    }
}

// Alias cho tên cũ
class Movie extends Phim {}
?>
