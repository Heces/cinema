<?php
/**
 * Admin Layout Component
 * Sidebar navigation và header cho admin panel
 */

require_once __DIR__ . '/auth.php';

$auth = new Auth();
$currentUser = $auth->getCurrentUser();

// Kiểm tra đăng nhập và quyền admin
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: admin_login.php');
    exit;
}

// Lấy trang hiện tại
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Cinema Management</title>
    <link rel="stylesheet" href="./assets/css/admin.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</head>
<body class="admin-body">
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="admin-sidebar-header">
                <a href="index.php" class="logo">
                    <img src="./assets/images/project-logo.png" alt="Logo">
                </a>
                <a href="index.php" class="admin-sidebar-header-title">
                    <h2>Cinema Admin</h2> 
                </a>
                
            </div>
            <nav class="admin-sidebar-nav">
                <a href="admin_dashboard.php" class="admin-nav-item <?php echo $currentPage === 'admin_dashboard.php' ? 'active' : ''; ?>">
                    <ion-icon name="home-outline"></ion-icon>
                    <span>Tổng quan</span>
                </a>
                <a href="admin_accounts.php" class="admin-nav-item <?php echo $currentPage === 'admin_accounts.php' ? 'active' : ''; ?>">
                    <ion-icon name="people-outline"></ion-icon>
                    <span>Quản lý Tài khoản</span>
                </a>
                <a href="admin_movies.php" class="admin-nav-item <?php echo $currentPage === 'admin_movies.php' ? 'active' : ''; ?>">
                    <ion-icon name="film-outline"></ion-icon>
                    <span>Quản lý Phim</span>
                </a>
                <a href="admin_genres.php" class="admin-nav-item <?php echo $currentPage === 'admin_genres.php' ? 'active' : ''; ?>">
                    <ion-icon name="pricetags-outline"></ion-icon>
                    <span>Thể loại Phim</span>
                </a>
                <a href="admin_rooms.php" class="admin-nav-item <?php echo $currentPage === 'admin_rooms.php' ? 'active' : ''; ?>">
                    <ion-icon name="business-outline"></ion-icon>
                    <span>Phòng chiếu</span>
                </a>
                <a href="admin_showtimes.php" class="admin-nav-item <?php echo $currentPage === 'admin_showtimes.php' ? 'active' : ''; ?>">
                    <ion-icon name="time-outline"></ion-icon>
                    <span>Suất chiếu</span>
                </a>
                <a href="admin_reports.php" class="admin-nav-item <?php echo $currentPage === 'admin_reports.php' ? 'active' : ''; ?>">
                    <ion-icon name="stats-chart-outline"></ion-icon>
                    <span>Thống kê - Báo cáo</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <!-- Header -->
            <header class="admin-header">
                <h1 class="admin-header-title" id="page-title">Dashboard</h1>
                <div class="admin-header-actions">
                    <div class="admin-user-info">
                        <div class="admin-user-avatar">
                            <?php echo strtoupper(substr($currentUser['fullname'], 0, 1)); ?>
                        </div>
                        <div>
                            <div style="font-weight: 600; font-size: 0.9rem;"><?php echo htmlspecialchars($currentUser['fullname']); ?></div>
                            <div style="font-size: 0.75rem; color: var(--admin-text-light);"><?php echo htmlspecialchars($currentUser['role']); ?></div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <div class="admin-content">

