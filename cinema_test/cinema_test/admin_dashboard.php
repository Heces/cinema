<?php
require_once 'includes/admin_layout.php';

// Lấy thống kê nhanh
require_once 'config/database.php';
$db = new Database();
$conn = $db->getConnection();

$stats = [];

// Tổng số phim
$stmt = $conn->query("SELECT COUNT(*) as total FROM PHIM");
$stats['total_movies'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Tổng số khách hàng
$stmt = $conn->query("SELECT COUNT(*) as total FROM KHACHHANG");
$stats['total_customers'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Tổng số vé đã bán
$stmt = $conn->query("SELECT COUNT(*) as total FROM VE WHERE TrangThai != 'Huy'");
$stats['total_tickets'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Tổng doanh thu
$stmt = $conn->query("SELECT SUM(GiaVe) as total FROM VE WHERE TrangThai != 'Huy'");
$revenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$stats['total_revenue'] = $revenue ? number_format($revenue, 0, ',', '.') : '0';

// Phim đang chiếu
$stmt = $conn->query("SELECT COUNT(*) as total FROM PHIM WHERE TrangThai = 'DangChieu'");
$stats['now_showing'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Suất chiếu hôm nay
$stmt = $conn->query("SELECT COUNT(*) as total FROM SUATCHIEU WHERE DATE(ThoiGian) = CURDATE()");
$stats['today_showtimes'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-card-title">Tổng số phim</span>
            <div class="stat-card-icon primary">
                <ion-icon name="film-outline"></ion-icon>
            </div>
        </div>
        <div class="stat-card-value"><?php echo $stats['total_movies']; ?></div>
        <div class="stat-card-change positive">
            <ion-icon name="arrow-up-outline"></ion-icon>
            <span><?php echo $stats['now_showing']; ?> đang chiếu</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-card-title">Tổng số khách hàng</span>
            <div class="stat-card-icon success">
                <ion-icon name="people-outline"></ion-icon>
            </div>
        </div>
        <div class="stat-card-value"><?php echo $stats['total_customers']; ?></div>
    </div>

    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-card-title">Tổng số vé đã bán</span>
            <div class="stat-card-icon warning">
                <ion-icon name="ticket-outline"></ion-icon>
            </div>
        </div>
        <div class="stat-card-value"><?php echo $stats['total_tickets']; ?></div>
    </div>

    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-card-title">Tổng doanh thu</span>
            <div class="stat-card-icon success">
                <ion-icon name="cash-outline"></ion-icon>
            </div>
        </div>
        <div class="stat-card-value"><?php echo $stats['total_revenue']; ?>đ</div>
    </div>
</div>

<?php require_once 'includes/admin_footer.php'; ?>

