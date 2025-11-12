<?php
/**
 * Cấu hình kết nối database
 * Sử dụng PDO để kết nối an toàn với MySQL
 */

class CoSoDuLieu {
    private $mayChu = "localhost:3307";
    private $tenCSDL = "cinema";
    private $tenDangNhap = "root";
    private $matKhau = "";
    private $bangMa = "utf8mb4";
    public $ketNoi;

    /**
     * Lấy kết nối database
     * @return PDO|null
     */
    public function layKetNoi() {
        $this->ketNoi = null;

        try {
            $dsn = "mysql:host=" . $this->mayChu . ";dbname=" . $this->tenCSDL . ";charset=" . $this->bangMa;
            $tuyChon = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            $this->ketNoi = new PDO($dsn, $this->tenDangNhap, $this->matKhau, $tuyChon);
            
        } catch(PDOException $loi) {
            echo "Lỗi kết nối: " . $loi->getMessage();
            die();
        }

        return $this->ketNoi;
    }

    /**
     * Kiểm tra kết nối database
     * @return bool
     */
    public function kiemTraKetNoi() {
        try {
            $this->layKetNoi();
            return true;
        } catch(Exception $e) {
            return false;
        }
    }

    /**
     * Đóng kết nối database
     */
    public function dongKetNoi() {
        $this->ketNoi = null;
    }
    
    // Giữ lại các hàm cũ để tương thích ngược
    public function getConnection() {
        return $this->layKetNoi();
    }
    
    public function testConnection() {
        return $this->kiemTraKetNoi();
    }
    
    public function closeConnection() {
        return $this->dongKetNoi();
    }
}

// Alias cho tên cũ
class Database extends CoSoDuLieu {}

// Hàm tiện ích để lấy kết nối nhanh
function layKetNoiCSDL() {
    $coSoDuLieu = new CoSoDuLieu();
    return $coSoDuLieu->layKetNoi();
}

function kiemTraKetNoiCSDL() {
    $coSoDuLieu = new CoSoDuLieu();
    return $coSoDuLieu->kiemTraKetNoi();
}

// Giữ lại các hàm cũ
function getDBConnection() {
    return layKetNoiCSDL();
}

function checkDatabaseConnection() {
    return kiemTraKetNoiCSDL();
}

// Cấu hình timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Cấu hình error reporting (chỉ trong development)
if (defined('DEVELOPMENT') && DEVELOPMENT) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>
