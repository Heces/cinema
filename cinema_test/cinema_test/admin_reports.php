<?php
require_once 'includes/admin_layout.php';
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Lấy tham số filter
$startDate = $_GET['start_date'] ?? date('Y-m-01'); // Đầu tháng
$endDate = $_GET['end_date'] ?? date('Y-m-d'); // Hôm nay

// Thống kê doanh thu
$revenueStats = [];
$error_message = '';
try {
    $stmt = $conn->prepare("
        SELECT 
            DATE(v.NgayMua) as Ngay,
            COUNT(v.MaVe) as SoVe,
            SUM(v.GiaVe) as DoanhThu,
            COUNT(DISTINCT v.MaKH) as SoKhachHang
        FROM VE v
        WHERE v.TrangThai != 'Huy'
        AND DATE(v.NgayMua) BETWEEN ? AND ?
        GROUP BY DATE(v.NgayMua)
        ORDER BY Ngay DESC
    ");
    $stmt->execute([$startDate, $endDate]);
    $revenueStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error_message = 'Lỗi khi tải thống kê: ' . $e->getMessage();
}

// Tổng doanh thu
$totalRevenue = 0;
$totalTickets = 0;
foreach ($revenueStats as $stat) {
    $totalRevenue += $stat['DoanhThu'] ?? 0;
    $totalTickets += $stat['SoVe'] ?? 0;
}

// Tính số ngày trong khoảng thời gian
$startDateTime = new DateTime($startDate);
$endDateTime = new DateTime($endDate);
$endDateTime->modify('+1 day'); // Bao gồm cả ngày cuối
$interval = $startDateTime->diff($endDateTime);
$totalDays = $interval->days;

// Phim xem nhiều nhất
$topMovies = [];
try {
    $stmt = $conn->prepare("
        SELECT 
            p.MaPhim,
            p.TenPhim,
            COUNT(v.MaVe) as SoVeBan,
            SUM(v.GiaVe) as DoanhThu,
            COUNT(DISTINCT v.MaKH) as SoKhachHang
        FROM PHIM p
        INNER JOIN SUATCHIEU sc ON p.MaPhim = sc.MaPhim
        INNER JOIN VE v ON sc.MaSuat = v.MaSuat
        WHERE v.TrangThai != 'Huy'
        AND DATE(v.NgayMua) BETWEEN ? AND ?
        GROUP BY p.MaPhim, p.TenPhim
        HAVING SoVeBan > 0
        ORDER BY SoVeBan DESC
        LIMIT 10
    ");
    $stmt->execute([$startDate, $endDate]);
    $topMovies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error_message = 'Lỗi khi tải top phim: ' . $e->getMessage();
}

// Thống kê theo phòng
$roomStats = [];
try {
    $stmt = $conn->prepare("
        SELECT 
            ph.TenPhong,
            ph.LoaiPhong,
            COUNT(v.MaVe) as SoVeBan,
            SUM(v.GiaVe) as DoanhThu
        FROM PHONG ph
        INNER JOIN SUATCHIEU sc ON ph.MaPhong = sc.MaPhong
        INNER JOIN VE v ON sc.MaSuat = v.MaSuat
        WHERE v.TrangThai != 'Huy'
        AND DATE(v.NgayMua) BETWEEN ? AND ?
        GROUP BY ph.MaPhong, ph.TenPhong, ph.LoaiPhong
        HAVING SoVeBan > 0
        ORDER BY DoanhThu DESC
    ");
    $stmt->execute([$startDate, $endDate]);
    $roomStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error_message = 'Lỗi khi tải thống kê phòng: ' . $e->getMessage();
}
?>

<?php if ($error_message): ?>
    <div class="alert alert-error">
        <ion-icon name="alert-circle"></ion-icon>
        <span><?php echo htmlspecialchars($error_message); ?></span>
    </div>
<?php endif; ?>

<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title">Thống kê - Báo cáo</h2>
    </div>
    
    <form method="GET" style="display: flex; gap: 12px; margin-bottom: 20px; align-items: end;">
        <div class="form-group" style="margin: 0; flex: 1;">
            <label for="start_date" class="form-label">Từ ngày</label>
            <input type="date" id="start_date" name="start_date" class="form-input" 
                   value="<?php echo htmlspecialchars($startDate); ?>" required>
        </div>
        <div class="form-group" style="margin: 0; flex: 1;">
            <label for="end_date" class="form-label">Đến ngày</label>
            <input type="date" id="end_date" name="end_date" class="form-input" 
                   value="<?php echo htmlspecialchars($endDate); ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">
            <ion-icon name="search-outline"></ion-icon>
            Lọc
        </button>
    </form>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-card-title">Tổng doanh thu</span>
            <div class="stat-card-icon success">
                <ion-icon name="cash-outline"></ion-icon>
            </div>
        </div>
        <div class="stat-card-value"><?php echo number_format($totalRevenue, 0, ',', '.'); ?>đ</div>
    </div>

    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-card-title">Tổng số vé</span>
            <div class="stat-card-icon primary">
                <ion-icon name="ticket-outline"></ion-icon>
            </div>
        </div>
        <div class="stat-card-value"><?php echo $totalTickets; ?></div>
    </div>

    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-card-title">Số ngày</span>
            <div class="stat-card-icon warning">
                <ion-icon name="calendar-outline"></ion-icon>
            </div>
        </div>
        <div class="stat-card-value"><?php echo $totalDays; ?></div>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title">Doanh thu theo ngày</h2>
    </div>
    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Ngày</th>
                    <th>Số vé</th>
                    <th>Số khách hàng</th>
                    <th>Doanh thu</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($revenueStats)): ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 40px; color: var(--admin-text-light);">
                            Không có dữ liệu trong khoảng thời gian này
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($revenueStats as $stat): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($stat['Ngay'])); ?></td>
                            <td><?php echo $stat['SoVe']; ?></td>
                            <td><?php echo $stat['SoKhachHang']; ?></td>
                            <td><strong><?php echo number_format($stat['DoanhThu'] ?? 0, 0, ',', '.'); ?>đ</strong></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title">Top 10 Phim xem nhiều nhất</h2>
    </div>
    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Tên phim</th>
                    <th>Số vé bán</th>
                    <th>Số khách hàng</th>
                    <th>Doanh thu</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($topMovies)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px; color: var(--admin-text-light);">
                            Không có dữ liệu trong khoảng thời gian này
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($topMovies as $index => $movie): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><strong><?php echo htmlspecialchars($movie['TenPhim']); ?></strong></td>
                            <td><?php echo $movie['SoVeBan']; ?></td>
                            <td><?php echo $movie['SoKhachHang']; ?></td>
                            <td><strong><?php echo number_format($movie['DoanhThu'] ?? 0, 0, ',', '.'); ?>đ</strong></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (!empty($roomStats)): ?>
<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title">Doanh thu theo phòng</h2>
    </div>
    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Tên phòng</th>
                    <th>Loại phòng</th>
                    <th>Số vé bán</th>
                    <th>Doanh thu</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($roomStats as $room): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($room['TenPhong']); ?></strong></td>
                        <td><span class="badge badge-info"><?php echo $room['LoaiPhong']; ?></span></td>
                        <td><?php echo $room['SoVeBan']; ?></td>
                        <td><strong><?php echo number_format($room['DoanhThu'] ?? 0, 0, ',', '.'); ?>đ</strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once 'includes/admin_footer.php'; ?>

