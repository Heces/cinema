<?php
/**
 * Cấu hình kết nối database
 * Sử dụng PDO để kết nối an toàn với MySQL
 */

class Database {
    private $host = "localhost:3307";
    private $db_name = "cinema";
    private $username = "root";
    private $password = "";
    private $charset = "utf8mb4";
    public $conn;

    /**
     * Lấy kết nối database
     * @return PDO|null
     */
    public function getConnection() {
        $this->conn = null;

        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch(PDOException $exception) {
            echo "Lỗi kết nối: " . $exception->getMessage();
            die();
        }

        return $this->conn;
    }

    /**
     * Kiểm tra kết nối database
     * @return bool
     */
    public function testConnection() {
        try {
            $this->getConnection();
            return true;
        } catch(Exception $e) {
            return false;
        }
    }

    /**
     * Đóng kết nối database
     */
    public function closeConnection() {
        $this->conn = null;
    }
}

// Hàm tiện ích để lấy kết nối nhanh
function getDBConnection() {
    $database = new Database();
    return $database->getConnection();
}

// Hàm kiểm tra kết nối
function checkDatabaseConnection() {
    $database = new Database();
    return $database->testConnection();
}


// Cấu hình error reporting (chỉ trong development)
if (defined('DEVELOPMENT') && DEVELOPMENT) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>
