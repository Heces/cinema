<?php
/**
 * Security Functions
 * Các hàm bảo mật và quản lý session
 */

// Cấu hình session
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');

// Bắt đầu session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class Security {
    
    /**
     * Tạo CSRF token
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Xác thực CSRF token
     */
    public static function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Làm sạch dữ liệu đầu vào
     */
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Xác thực email
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Xác thực số điện thoại Việt Nam
     */
    public static function validatePhone($phone) {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        return preg_match('/^(0[3|5|7|8|9])[0-9]{8}$/', $phone);
    }
    
    /**
     * Kiểm tra độ mạnh mật khẩu
     */
    public static function validatePassword($password) {
        $errors = [];
        
        return $errors;
    }
    
    /**
     * Tạo mật khẩu ngẫu nhiên
     */
    public static function generateRandomPassword($length = 12) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $password;
    }
    
    /**
     * Ghi log bảo mật
     */
    public static function logSecurityEvent($event, $details = '') {
        $logFile = __DIR__ . '/../logs/security.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $userId = $_SESSION['user_id'] ?? 'guest';
        
        $logEntry = "[$timestamp] IP: $ip | User: $userId | Event: $event | Details: $details | UA: $userAgent" . PHP_EOL;
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Kiểm tra rate limiting
     */
    public static function checkRateLimit($action, $maxAttempts = 5, $timeWindow = 300) {
        $key = $action . '_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        
        if (!isset($_SESSION['rate_limit'])) {
            $_SESSION['rate_limit'] = [];
        }
        
        $now = time();
        $attempts = $_SESSION['rate_limit'][$key] ?? [];
        
        // Xóa các attempt cũ
        $attempts = array_filter($attempts, function($timestamp) use ($now, $timeWindow) {
            return ($now - $timestamp) < $timeWindow;
        });
        
        if (count($attempts) >= $maxAttempts) {
            self::logSecurityEvent('RATE_LIMIT_EXCEEDED', "Action: $action, Attempts: " . count($attempts));
            return false;
        }
        
        $attempts[] = $now;
        $_SESSION['rate_limit'][$key] = $attempts;
        
        return true;
    }
    
    /**
     * Xác thực session
     */
    public static function validateSession() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in'])) {
            return false;
        }
        
        // Kiểm tra session timeout (24 giờ)
        $sessionTimeout = 24 * 60 * 60; // 24 hours
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $sessionTimeout) {
            self::logSecurityEvent('SESSION_TIMEOUT', 'User: ' . ($_SESSION['user_id'] ?? 'unknown'));
            session_destroy();
            return false;
        }
        
        // Cập nhật last activity
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    /**
     * Xác thực quyền truy cập
     */
    public static function checkPermission($requiredPermission = null) {
        if (!self::validateSession()) {
            return false;
        }
        
        if ($requiredPermission === null) {
            return true;
        }
        
        $userPermissions = $_SESSION['permissions'] ?? [];
        return in_array($requiredPermission, $userPermissions);
    }
    
    /**
     * Chuyển hướng an toàn
     */
    public static function redirect($url, $statusCode = 302) {
        // Làm sạch URL
        $url = filter_var($url, FILTER_SANITIZE_URL);
        
        // Kiểm tra URL hợp lệ
        if (!filter_var($url, FILTER_VALIDATE_URL) && !preg_match('/^\/[^\/]/', $url)) {
            $url = '/';
        }
        
        header("Location: $url", true, $statusCode);
        exit;
    }
    
    /**
     * Xử lý lỗi bảo mật
     */
    public static function handleSecurityError($message, $logEvent = true) {
        if ($logEvent) {
            self::logSecurityEvent('SECURITY_ERROR', $message);
        }
        
        // Có thể gửi email thông báo cho admin
        // self::sendSecurityAlert($message);
        
        // Hiển thị lỗi generic cho user
        return 'Có lỗi xảy ra. Vui lòng thử lại sau.';
    }
    
    /**
     * Mã hóa dữ liệu nhạy cảm
     */
    public static function encrypt($data, $key = null) {
        if ($key === null) {
            $key = $_ENV['ENCRYPTION_KEY'] ?? 'default_key_change_this';
        }
        
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Giải mã dữ liệu
     */
    public static function decrypt($data, $key = null) {
        if ($key === null) {
            $key = $_ENV['ENCRYPTION_KEY'] ?? 'default_key_change_this';
        }
        
        $data = base64_decode($data);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }
    
    /**
     * Tạo hash an toàn
     */
    public static function createHash($data) {
        return password_hash($data, PASSWORD_ARGON2ID);
    }
    
    /**
     * Xác thực hash
     */
    public static function verifyHash($data, $hash) {
        return password_verify($data, $hash);
    }
    
    /**
     * Làm sạch session
     */
    public static function cleanSession() {
        // Xóa các session cũ
        if (isset($_SESSION['rate_limit'])) {
            $now = time();
            foreach ($_SESSION['rate_limit'] as $key => $attempts) {
                $_SESSION['rate_limit'][$key] = array_filter($attempts, function($timestamp) use ($now) {
                    return ($now - $timestamp) < 3600; // 1 hour
                });
            }
        }
    }
    
    /**
     * Kiểm tra IP có bị chặn không
     */
    public static function isIPBlocked($ip = null) {
        if ($ip === null) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        }
        
        $blockedIPs = [
            // Thêm các IP bị chặn vào đây
        ];
        
        return in_array($ip, $blockedIPs);
    }
}

// Tự động làm sạch session
Security::cleanSession();

// Kiểm tra IP bị chặn
if (Security::isIPBlocked()) {
    Security::logSecurityEvent('BLOCKED_IP_ACCESS', 'IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    http_response_code(403);
    die('Access denied');
}
?>
