<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

$auth = new Auth();

// Yêu cầu đăng nhập
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = $auth->getCurrentUser();

// Lấy vé đã đặt của người dùng
$database = new Database();
$conn = $database->getConnection();

$tickets = [];
$error = '';

try {
    $stmt = $conn->prepare("
        SELECT v.MaVe, v.SoGhe, v.GiaVe, v.TrangThai AS TrangThaiVe, v.NgayMua, v.NgaySuDung,
               sc.MaSuat, sc.ThoiGian, sc.GiaBan,
               p.TenPhim, ph.TenPhong, ph.LoaiPhong
        FROM VE v
        JOIN SUATCHIEU sc ON v.MaSuat = sc.MaSuat
        JOIN PHIM p ON sc.MaPhim = p.MaPhim
        JOIN PHONG ph ON sc.MaPhong = ph.MaPhong
        WHERE v.MaKH = ?
        ORDER BY v.NgayMua DESC
    ");
    $stmt->execute([$user['id']]);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = 'Lỗi khi tải vé: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vé của tôi</title>
    <link rel="stylesheet" href="./assets/css/style.css">
    <link rel="stylesheet" href="./assets/css/dashboard.css">
</head>
<body>
    <header class="header" data-header>
        <div class="container">
            <div class="overlay" data-overlay></div>
            <a href="./index.php" class="logo">
                <img src="./assets/images/project-logo.png" alt="Filmlane logo">
            </a>
            <div class="header-actions">
                <a href="dashboard.php" class="btn btn-outline">Dashboard</a>
                <form method="POST" action="index.php" style="margin: 0;">
                    <input type="hidden" name="action" value="logout">
                    <button type="submit" class="btn btn-primary">Đăng xuất</button>
                </form>
            </div>
        </div>
    </header>

    <main class="container" style="padding: 40px 16px;">
        <h1 class="h2 section-title">Vé của tôi</h1>

        <?php if ($error): ?>
            <div class="alert alert-error" style="margin-top: 16px;"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (empty($tickets)): ?>
            <div class="empty-state" style="margin-top: 20px; background:#fff; border:1px solid #eee; padding:24px; border-radius:12px; color:var(--eerie-black);">
                <h3>Bạn chưa có vé nào</h3>
                <p>Hãy đặt vé cho bộ phim yêu thích ngay hôm nay!</p>
                <a href="index.php" class="btn btn-primary" style="margin-top: 10px;">Về trang chủ</a>
            </div>
        <?php else: ?>
            <div style="display:grid; gap:16px; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); margin-top: 20px;">
                <?php foreach ($tickets as $t): ?>
                    <?php 
                        $statusColor = '#6c757d';
                        if ($t['TrangThaiVe'] === 'ChuaSuDung') $statusColor = '#17a2b8';
                        if ($t['TrangThaiVe'] === 'DaSuDung') $statusColor = '#28a745';
                        if ($t['TrangThaiVe'] === 'Huy') $statusColor = '#dc3545';
                    ?>
                    <div style="background:#fff; border:1px solid #eee; border-radius:12px; overflow:hidden;">
                        <div style="padding:16px; border-bottom:1px dashed #eee; display:flex; justify-content:space-between; align-items:center;">
                            <div style="font-weight:600; color:#1a1a1a;"><?php echo htmlspecialchars($t['TenPhim']); ?></div>
                            <?php if ($t['TrangThaiVe'] !== 'ChuaSuDung'): ?>
                            <span style="padding:4px 10px; border-radius:16px; color:#fff; background: <?php echo $statusColor; ?>; font-size:.8rem; font-weight:600;">
                                <?php echo htmlspecialchars($t['TrangThaiVe']); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <div style="padding:16px; color:#444;">
                            <div><strong>Suất:</strong> <?php echo date('d/m/Y H:i', strtotime($t['ThoiGian'])); ?></div>
                            <div><strong>Phòng:</strong> <?php echo htmlspecialchars($t['TenPhong']); ?> (<?php echo htmlspecialchars($t['LoaiPhong']); ?>)</div>
                            <div><strong>Ghế:</strong> <?php echo htmlspecialchars($t['SoGhe']); ?></div>
                            <div><strong>Giá vé:</strong> <?php echo number_format($t['GiaVe']); ?>đ</div>
                            <div style="margin-top:8px; font-size:.9rem; color:#666;">
                                Mua lúc: <?php echo date('d/m/Y H:i', strtotime($t['NgayMua'])); ?>
                            </div>
                        </div>
                        <!-- Actions removed per request -->
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <script src="./assets/js/script.js"></script>
</body>
</html>


