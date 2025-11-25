<?php
/**
 * Hệ thống xác thực (Authentication System)
 * Quản lý đăng nhập, đăng ký, đăng xuất và session
 */

require_once 'config/database.php';
require_once 'security.php';

class Auth {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Đăng ký tài khoản mới
     */
    public function register($data) {
        try {
            // Kiểm tra rate limiting
            if (!Security::checkRateLimit('register', 3, 3600)) {
                Security::logSecurityEvent('REGISTER_RATE_LIMIT', 'IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
                return ['success' => false, 'message' => 'Quá nhiều lần thử. Vui lòng thử lại sau 1 giờ'];
            }
            
            // Làm sạch dữ liệu đầu vào
            $data = Security::sanitizeInput($data);
            
            // Xác thực dữ liệu
            if (!Security::validateEmail($data['email'])) {
                return ['success' => false, 'message' => 'Email không hợp lệ'];
            }
            
            if (isset($data['phone']) && !Security::validatePhone($data['phone'])) {
                return ['success' => false, 'message' => 'Số điện thoại không hợp lệ'];
            }
            
            $passwordErrors = Security::validatePassword($data['password']);
            if (!empty($passwordErrors)) {
                return ['success' => false, 'message' => implode(', ', $passwordErrors)];
            }
            
            // Kiểm tra email đã tồn tại chưa
            if ($this->emailExists($data['email'])) {
                Security::logSecurityEvent('REGISTER_DUPLICATE_EMAIL', 'Email: ' . $data['email']);
                return ['success' => false, 'message' => 'Email đã được sử dụng'];
            }
            
            // Kiểm tra tên đăng nhập đã tồn tại chưa
            if ($this->usernameExists($data['username'])) {
                Security::logSecurityEvent('REGISTER_DUPLICATE_USERNAME', 'Username: ' . $data['username']);
                return ['success' => false, 'message' => 'Tên đăng nhập đã được sử dụng'];
            }
            
            // Bắt đầu transaction
            $this->conn->beginTransaction();
            
            // Thêm khách hàng
            $stmt = $this->conn->prepare("
                INSERT INTO KHACHHANG (TenKH, SDT, Email, DiaChi, NgaySinh, GioiTinh) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['fullname'],
                $data['phone'],
                $data['email'],
                $data['address'] ?? '',
                $data['birthday'] ?? null,
                $data['gender'] ?? null
            ]);
            
            $maKH = $this->conn->lastInsertId();
            
            // Thêm tài khoản
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            $stmt = $this->conn->prepare("
                INSERT INTO TAIKHOANKH (MaKH, TenTK, Matkhau) 
                VALUES (?, ?, ?)
            ");
            
            $stmt->execute([
                $maKH,
                $data['username'],
                $hashedPassword
            ]);
            
            $this->conn->commit();
            
            Security::logSecurityEvent('USER_REGISTERED', 'User: ' . $data['username'] . ', Email: ' . $data['email']);
            
            return ['success' => true, 'message' => 'Đăng ký thành công!'];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()];
        }
    }
    
    /**
     * Đăng nhập
     */
    public function login($username, $password) {
        try {
            // Kiểm tra rate limiting
            if (!Security::checkRateLimit('login', 5, 900)) {
                Security::logSecurityEvent('LOGIN_RATE_LIMIT', 'IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            
            }
            
            // Làm sạch dữ liệu đầu vào
            $username = Security::sanitizeInput($username);
            
            $stmt = $this->conn->prepare("
                SELECT tk.MaTK, tk.MaKH, tk.TenTK, tk.Matkhau, tk.Quyen, tk.TrangThai,
                       kh.TenKH, kh.Email, kh.SDT, kh.DiaChi
                FROM TAIKHOANKH tk
                JOIN KHACHHANG kh ON tk.MaKH = kh.MaKH
                WHERE tk.TenTK = ? AND tk.TrangThai = 'HoatDong'
            ");
            
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && (password_verify($password, $user['Matkhau']) || $user['Matkhau'] === $password)) {
                // Lưu thông tin user vào session
                $_SESSION['user_id'] = $user['MaKH'];
                $_SESSION['username'] = $user['TenTK'];
                $_SESSION['fullname'] = $user['TenKH'];
                $_SESSION['email'] = $user['Email'];
                $_SESSION['role'] = $user['Quyen'];
                $_SESSION['logged_in'] = true;
                $_SESSION['last_activity'] = time();
                $_SESSION['login_time'] = time();
                
                Security::logSecurityEvent('USER_LOGIN', 'User: ' . $username);
                
                return ['success' => true, 'message' => 'Đăng nhập thành công!'];
            } else {
                Security::logSecurityEvent('LOGIN_FAILED', 'Username: ' . $username . ', IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
                return ['success' => false, 'message' => 'Tên đăng nhập hoặc mật khẩu không đúng'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()];
        }
    }
    
    /**
     * Đăng xuất
     */
    public function logout() {
        $userId = $_SESSION['user_id'] ?? 'unknown';
        Security::logSecurityEvent('USER_LOGOUT', 'User: ' . $userId);
        
        // Xóa tất cả session data
        $_SESSION = array();
        
        // Xóa session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Hủy session
        session_destroy();
        
        return ['success' => true, 'message' => 'Đăng xuất thành công!'];
    }
    
    /**
     * Kiểm tra đăng nhập
     */
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && Security::validateSession();
    }
    
    /**
     * Lấy thông tin user hiện tại
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
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
     * Kiểm tra quyền admin
     */
    public function isAdmin() {
        return $this->isLoggedIn() && ($_SESSION['role'] ?? 'User') === 'Admin';
    }
    
    /**
     * Kiểm tra quyền truy cập admin
     */
    public function requireAdmin() {
        if (!$this->isAdmin()) {
            Security::logSecurityEvent('UNAUTHORIZED_ADMIN_ACCESS', 'User: ' . ($_SESSION['username'] ?? 'unknown'));
            header('Location: index.php');
            exit;
        }
    }
    
    /**
     * Kiểm tra email đã tồn tại
     */
    private function emailExists($email) {
        $stmt = $this->conn->prepare("SELECT MaKH FROM KHACHHANG WHERE Email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch() !== false;
    }
    
    /**
     * Kiểm tra username đã tồn tại
     */
    private function usernameExists($username) {
        $stmt = $this->conn->prepare("SELECT MaTK FROM TAIKHOANKH WHERE TenTK = ?");
        $stmt->execute([$username]);
        return $stmt->fetch() !== false;
    }
    
    /**
     * Cập nhật thông tin profile
     */
    public function updateProfile($data) {
        try {
            if (!$this->isLoggedIn()) {
                return ['success' => false, 'message' => 'Vui lòng đăng nhập'];
            }
            
            $stmt = $this->conn->prepare("
                UPDATE KHACHHANG 
                SET TenKH = ?, SDT = ?, Email = ?, DiaChi = ?, NgaySinh = ?, GioiTinh = ?
                WHERE MaKH = ?
            ");
            
            $stmt->execute([
                $data['fullname'],
                $data['phone'],
                $data['email'],
                $data['address'],
                $data['birthday'],
                $data['gender'],
                $_SESSION['user_id']
            ]);
            
            // Cập nhật session
            $_SESSION['fullname'] = $data['fullname'];
            $_SESSION['email'] = $data['email'];
            
            return ['success' => true, 'message' => 'Cập nhật thông tin thành công!'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()];
        }
    }
    
    /**
     * Đổi mật khẩu
     */
    public function changePassword($currentPassword, $newPassword) {
        try {
            if (!$this->isLoggedIn()) {
                return ['success' => false, 'message' => 'Vui lòng đăng nhập'];
            }
            
            // Kiểm tra mật khẩu hiện tại
            $stmt = $this->conn->prepare("
                SELECT Matkhau FROM TAIKHOANKH 
                WHERE MaKH = ?
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!password_verify($currentPassword, $user['Matkhau'])) {
                return ['success' => false, 'message' => 'Mật khẩu hiện tại không đúng'];
            }
            
            // Cập nhật mật khẩu mới
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $stmt = $this->conn->prepare("
                UPDATE TAIKHOANKH 
                SET Matkhau = ?
                WHERE MaKH = ?
            ");
            
            $stmt->execute([$hashedPassword, $_SESSION['user_id']]);
            
            return ['success' => true, 'message' => 'Đổi mật khẩu thành công!'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()];
        }
    }
}

?>
