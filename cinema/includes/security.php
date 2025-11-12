<?php
/**
 * Bao Mat - Phiên bản đơn giản
 * Các hàm bảo mật cơ bản
 */

// Bắt đầu session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class BaoMat {
    
    /**
     * Làm sạch dữ liệu đầu vào
     */
    public static function lamSachDuLieu($duLieu) {
        if (is_array($duLieu)) {
            return array_map([self::class, 'lamSachDuLieu'], $duLieu);
        }
        return htmlspecialchars(trim($duLieu), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Xác thực email
     */
    public static function kiemTraEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Xác thực số điện thoại Việt Nam
     */
    public static function kiemTraSoDienThoai($soDienThoai) {
        $soDienThoai = preg_replace('/[^0-9]/', '', $soDienThoai);
        return preg_match('/^(0[3|5|7|8|9])[0-9]{8}$/', $soDienThoai);
    }
    
    /**
     * Kiểm tra độ mạnh mật khẩu (đơn giản)
     */
    public static function kiemTraMatKhau($matKhau) {
        $loi = [];
        
        if (strlen($matKhau) < 6) {
            $loi[] = 'Mật khẩu phải có ít nhất 6 ký tự';
        }
        
        return $loi;
    }
    
    /**
     * Xác thực session
     */
    public static function kiemTraSession() {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            return false;
        }
        
        // Kiểm tra session timeout (24 giờ)
        $thoiGianHetHan = 24 * 60 * 60; // 24 hours
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $thoiGianHetHan) {
            session_destroy();
            return false;
        }
        
        // Cập nhật last activity
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    /**
     * Ghi log đơn giản (tùy chọn)
     */
    public static function ghiLog($suKien, $chiTiet = '') {
        // Có thể bật lại nếu cần
        // $logFile = __DIR__ . '/../logs/security.log';
        // $timestamp = date('Y-m-d H:i:s');
        // $logEntry = "[$timestamp] Event: $suKien | Details: $chiTiet" . PHP_EOL;
        // file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Kiểm tra rate limiting (đơn giản)
     */
    public static function kiemTraGioiHan($hanhDong, $soLanToiDa = 5, $thoiGian = 300) {
        // Luôn trả về true - tắt rate limiting
        return true;
    }
    
    // Giữ lại các hàm cũ để tương thích ngược (deprecated)
    public static function sanitizeInput($data) {
        return self::lamSachDuLieu($data);
    }
    
    public static function validateEmail($email) {
        return self::kiemTraEmail($email);
    }
    
    public static function validatePhone($phone) {
        return self::kiemTraSoDienThoai($phone);
    }
    
    public static function validatePassword($password) {
        return self::kiemTraMatKhau($password);
    }
    
    public static function validateSession() {
        return self::kiemTraSession();
    }
    
    public static function logSecurityEvent($event, $details = '') {
        return self::ghiLog($event, $details);
    }
    
    public static function checkRateLimit($action, $maxAttempts = 5, $timeWindow = 300) {
        return self::kiemTraGioiHan($action, $maxAttempts, $timeWindow);
    }
}

// Alias cho tên cũ
class Security extends BaoMat {}
?>
