<?php
/**
 * He thong xac thuc
 * Quan ly dang nhap, dang ky, dang xuat va session
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/security.php';

class XacThuc {
    private $ketNoi;
    
    public function __construct() {
        $coSoDuLieu = new CoSoDuLieu();
        $this->ketNoi = $coSoDuLieu->layKetNoi();
    }
    
    /**
     * Dang ky tai khoan moi
     */
    public function dangKy($duLieu) {
        try {
            // Kiem tra gioi han
            if (!BaoMat::kiemTraGioiHan('dangKy', 3, 3600)) {
                BaoMat::ghiLog('DANG_KY_GIOI_HAN', 'IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
                return ['thanhCong' => false, 'thongBao' => 'Qua nhieu lan thu. Vui long thu lai sau 1 gio'];
            }
            
            // Lam sach du lieu dau vao
            $duLieu = BaoMat::lamSachDuLieu($duLieu);
            
            // Xac thuc du lieu
            if (!BaoMat::kiemTraEmail($duLieu['email'])) {
                return ['thanhCong' => false, 'thongBao' => 'Email khong hop le'];
            }
            
            if (isset($duLieu['phone']) && !BaoMat::kiemTraSoDienThoai($duLieu['phone'])) {
                return ['thanhCong' => false, 'thongBao' => 'So dien thoai khong hop le'];
            }
            
            $loiMatKhau = BaoMat::kiemTraMatKhau($duLieu['password']);
            if (!empty($loiMatKhau)) {
                return ['thanhCong' => false, 'thongBao' => implode(', ', $loiMatKhau)];
            }
            
            // Kiem tra email da ton tai chua
            if ($this->emailDaTonTai($duLieu['email'])) {
                BaoMat::ghiLog('DANG_KY_EMAIL_TRUNG', 'Email: ' . $duLieu['email']);
                return ['thanhCong' => false, 'thongBao' => 'Email da duoc su dung'];
            }
            
            // Kiem tra ten dang nhap da ton tai chua
            if ($this->tenDangNhapDaTonTai($duLieu['username'])) {
                BaoMat::ghiLog('DANG_KY_TEN_DANG_NHAP_TRUNG', 'Username: ' . $duLieu['username']);
                return ['thanhCong' => false, 'thongBao' => 'Ten dang nhap da duoc su dung'];
            }
            
            // Bat dau transaction
            $this->ketNoi->beginTransaction();
            
            // Them khach hang
            $cauLenh = $this->ketNoi->prepare("
                INSERT INTO KHACHHANG (TenKH, SDT, Email, DiaChi, NgaySinh, GioiTinh) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $cauLenh->execute([
                $duLieu['fullname'],
                $duLieu['phone'],
                $duLieu['email'],
                $duLieu['address'] ?? '',
                $duLieu['birthday'] ?? null,
                $duLieu['gender'] ?? null
            ]);
            
            $maKH = $this->ketNoi->lastInsertId();
            
            // Them tai khoan
            $matKhauBam = password_hash($duLieu['password'], PASSWORD_DEFAULT);
            
            $cauLenh = $this->ketNoi->prepare("
                INSERT INTO TAIKHOANKH (MaKH, TenTK, Matkhau) 
                VALUES (?, ?, ?)
            ");
            
            $cauLenh->execute([
                $maKH,
                $duLieu['username'],
                $matKhauBam
            ]);
            
            $this->ketNoi->commit();
            
            BaoMat::ghiLog('NGUOI_DUNG_DANG_KY', 'User: ' . $duLieu['username'] . ', Email: ' . $duLieu['email']);
            
            return ['thanhCong' => true, 'thongBao' => 'Dang ky thanh cong!'];
            
        } catch (Exception $loi) {
            $this->ketNoi->rollBack();
            return ['thanhCong' => false, 'thongBao' => 'Loi he thong: ' . $loi->getMessage()];
        }
    }
    
    /**
     * Dang nhap
     */
    public function dangNhap($tenDangNhap, $matKhau) {
        try {
            // Kiem tra gioi han
            if (!BaoMat::kiemTraGioiHan('dangNhap', 5, 900)) {
                BaoMat::ghiLog('DANG_NHAP_GIOI_HAN', 'IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
                return ['thanhCong' => false, 'thongBao' => 'Qua nhieu lan thu. Vui long thu lai sau 15 phut'];
            }
            
            // Lam sach du lieu dau vao
            $tenDangNhap = BaoMat::lamSachDuLieu($tenDangNhap);
            
            $cauLenh = $this->ketNoi->prepare("
                SELECT tk.MaTK, tk.MaKH, tk.TenTK, tk.Matkhau, tk.Quyen, tk.TrangThai,
                       kh.TenKH, kh.Email, kh.SDT, kh.DiaChi
                FROM TAIKHOANKH tk
                JOIN KHACHHANG kh ON tk.MaKH = kh.MaKH
                WHERE tk.TenTK = ? AND tk.TrangThai = 'HoatDong'
            ");
            
            $cauLenh->execute([$tenDangNhap]);
            $nguoiDung = $cauLenh->fetch(PDO::FETCH_ASSOC);
            
            if ($nguoiDung && (password_verify($matKhau, $nguoiDung['Matkhau']) || $nguoiDung['Matkhau'] === $matKhau)) {
                // Luu thong tin user vao session
                $_SESSION['user_id'] = $nguoiDung['MaKH'];
                $_SESSION['username'] = $nguoiDung['TenTK'];
                $_SESSION['fullname'] = $nguoiDung['TenKH'];
                $_SESSION['email'] = $nguoiDung['Email'];
                $_SESSION['role'] = $nguoiDung['Quyen'];
                $_SESSION['logged_in'] = true;
                $_SESSION['last_activity'] = time();
                $_SESSION['login_time'] = time();
                
                BaoMat::ghiLog('NGUOI_DUNG_DANG_NHAP', 'User: ' . $tenDangNhap);
                
                return ['thanhCong' => true, 'thongBao' => 'Dang nhap thanh cong!'];
            } else {
                BaoMat::ghiLog('DANG_NHAP_THAT_BAI', 'Username: ' . $tenDangNhap . ', IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
                return ['thanhCong' => false, 'thongBao' => 'Ten dang nhap hoac mat khau khong dung'];
            }
            
        } catch (Exception $loi) {
            return ['thanhCong' => false, 'thongBao' => 'Loi he thong: ' . $loi->getMessage()];
        }
    }
    
    /**
     * Dang xuat
     */
    public function dangXuat() {
        $maNguoiDung = $_SESSION['user_id'] ?? 'unknown';
        BaoMat::ghiLog('NGUOI_DUNG_DANG_XUAT', 'User: ' . $maNguoiDung);
        
        // Xoa tat ca session data
        $_SESSION = array();
        
        // Xoa session cookie
        if (ini_get("session.use_cookies")) {
            $thamSo = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $thamSo["path"], $thamSo["domain"],
                $thamSo["secure"], $thamSo["httponly"]
            );
        }
        
        // Huy session
        session_destroy();
        
        return ['thanhCong' => true, 'thongBao' => 'Dang xuat thanh cong!'];
    }
    
    /**
     * Kiem tra dang nhap
     */
    public function daDangNhap() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && BaoMat::kiemTraSession();
    }
    
    /**
     * Lay thong tin nguoi dung hien tai
     */
    public function layNguoiDungHienTai() {
        if (!$this->daDangNhap()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'fullname' => $_SESSION['fullname'],
            'email' => $_SESSION['email'],
            'role' => $_SESSION['role'] ?? 'User'
        ];
    }
    
    /**
     * Kiem tra quyen admin
     */
    public function laAdmin() {
        return $this->daDangNhap() && ($_SESSION['role'] ?? 'User') === 'Admin';
    }
    
    /**
     * Yeu cau quyen admin
     */
    public function yeuCauAdmin() {
        if (!$this->laAdmin()) {
            BaoMat::ghiLog('TRUY_CAP_ADMIN_KHONG_HOP_LE', 'User: ' . ($_SESSION['username'] ?? 'unknown'));
            header('Location: index.php');
            exit;
        }
    }
    
    /**
     * Kiem tra email da ton tai
     */
    private function emailDaTonTai($email) {
        $cauLenh = $this->ketNoi->prepare("SELECT MaKH FROM KHACHHANG WHERE Email = ?");
        $cauLenh->execute([$email]);
        return $cauLenh->fetch() !== false;
    }
    
    /**
     * Kiem tra ten dang nhap da ton tai
     */
    private function tenDangNhapDaTonTai($tenDangNhap) {
        $cauLenh = $this->ketNoi->prepare("SELECT MaTK FROM TAIKHOANKH WHERE TenTK = ?");
        $cauLenh->execute([$tenDangNhap]);
        return $cauLenh->fetch() !== false;
    }
    
    /**
     * Cap nhat thong tin profile
     */
    public function capNhatThongTin($duLieu) {
        try {
            if (!$this->daDangNhap()) {
                return ['thanhCong' => false, 'thongBao' => 'Vui long dang nhap'];
            }
            
            $cauLenh = $this->ketNoi->prepare("
                UPDATE KHACHHANG 
                SET TenKH = ?, SDT = ?, Email = ?, DiaChi = ?, NgaySinh = ?, GioiTinh = ?
                WHERE MaKH = ?
            ");
            
            $cauLenh->execute([
                $duLieu['fullname'],
                $duLieu['phone'],
                $duLieu['email'],
                $duLieu['address'],
                $duLieu['birthday'],
                $duLieu['gender'],
                $_SESSION['user_id']
            ]);
            
            // Cap nhat session
            $_SESSION['fullname'] = $duLieu['fullname'];
            $_SESSION['email'] = $duLieu['email'];
            
            return ['thanhCong' => true, 'thongBao' => 'Cap nhat thong tin thanh cong!'];
            
        } catch (Exception $loi) {
            return ['thanhCong' => false, 'thongBao' => 'Loi he thong: ' . $loi->getMessage()];
        }
    }
    
    /**
     * Doi mat khau
     */
    public function doiMatKhau($matKhauHienTai, $matKhauMoi) {
        try {
            if (!$this->daDangNhap()) {
                return ['thanhCong' => false, 'thongBao' => 'Vui long dang nhap'];
            }
            
            // Kiem tra mat khau hien tai
            $cauLenh = $this->ketNoi->prepare("
                SELECT Matkhau FROM TAIKHOANKH 
                WHERE MaKH = ?
            ");
            $cauLenh->execute([$_SESSION['user_id']]);
            $nguoiDung = $cauLenh->fetch(PDO::FETCH_ASSOC);
            
            if (!password_verify($matKhauHienTai, $nguoiDung['Matkhau'])) {
                return ['thanhCong' => false, 'thongBao' => 'Mat khau hien tai khong dung'];
            }
            
            // Cap nhat mat khau moi
            $matKhauBam = password_hash($matKhauMoi, PASSWORD_DEFAULT);
            
            $cauLenh = $this->ketNoi->prepare("
                UPDATE TAIKHOANKH 
                SET Matkhau = ?
                WHERE MaKH = ?
            ");
            
            $cauLenh->execute([$matKhauBam, $_SESSION['user_id']]);
            
            return ['thanhCong' => true, 'thongBao' => 'Doi mat khau thanh cong!'];
            
        } catch (Exception $loi) {
            return ['thanhCong' => false, 'thongBao' => 'Loi he thong: ' . $loi->getMessage()];
        }
    }
    
    // Giữ lại các hàm cũ để tương thích ngược
    public function register($data) {
        $ketQua = $this->dangKy($data);
        return ['success' => $ketQua['thanhCong'], 'message' => $ketQua['thongBao']];
    }
    
    public function login($username, $password) {
        $ketQua = $this->dangNhap($username, $password);
        return ['success' => $ketQua['thanhCong'], 'message' => $ketQua['thongBao']];
    }
    
    public function logout() {
        $ketQua = $this->dangXuat();
        return ['success' => $ketQua['thanhCong'], 'message' => $ketQua['thongBao']];
    }
    
    public function isLoggedIn() {
        return $this->daDangNhap();
    }
    
    public function getCurrentUser() {
        return $this->layNguoiDungHienTai();
    }
    
    public function isAdmin() {
        return $this->laAdmin();
    }
    
    public function requireAdmin() {
        return $this->yeuCauAdmin();
    }
    
    public function updateProfile($data) {
        $ketQua = $this->capNhatThongTin($data);
        return ['success' => $ketQua['thanhCong'], 'message' => $ketQua['thongBao']];
    }
    
    public function changePassword($currentPassword, $newPassword) {
        $ketQua = $this->doiMatKhau($currentPassword, $newPassword);
        return ['success' => $ketQua['thanhCong'], 'message' => $ketQua['thongBao']];
    }
}

// Alias cho tên cũ
class Auth extends XacThuc {}
?>
