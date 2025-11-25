<?php
require_once 'includes/auth.php';

$auth = new Auth();

// Kiểm tra đăng nhập
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = $auth->getCurrentUser();
$error_message = '';
$success_message = '';

// Xử lý cập nhật profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'update_profile':
            $result = $auth->updateProfile($_POST);
            if ($result['success']) {
                $success_message = $result['message'];
                $user = $auth->getCurrentUser(); // Refresh user data
            } else {
                $error_message = $result['message'];
            }
            break;
            
        case 'change_password':
            $result = $auth->changePassword($_POST['current_password'], $_POST['new_password']);
            if ($result['success']) {
                $success_message = $result['message'];
            } else {
                $error_message = $result['message'];
            }
            break;
            
        case 'logout':
            $auth->logout();
            header('Location: index.php');
            exit;
            break;
    }
}

// Lấy thông tin cá nhân từ cơ sở dữ liệu (không dùng dữ liệu session để hiển thị)
$db = new Database();
$conn = $db->getConnection();
$stmt = $conn->prepare("
    SELECT kh.TenKH, kh.Email, kh.SDT, kh.DiaChi, kh.NgaySinh, kh.GioiTinh, tk.TenTK
    FROM KHACHHANG kh
    JOIN TAIKHOANKH tk ON tk.MaKH = kh.MaKH
    WHERE kh.MaKH = ?
");
$stmt->execute([$user['id']]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Filmlane Cinema</title>

    <!-- favicon -->
    <link rel="shortcut icon" href="./favicon.svg" type="image/svg+xml">

    <!-- custom css link -->
    <link rel="stylesheet" href="./assets/css/style.css">
    <link rel="stylesheet" href="./assets/css/dashboard.css">

    <!-- google font link -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body id="top">

    <!-- HEADER -->
    <header class="header" data-header>
        <div class="container">
            <div class="overlay" data-overlay></div>

            <a href="./index.php" class="logo">
                <img src="./assets/images/project-logo.png" alt="Filmlane logo">
            </a>

            <div class="header-actions">
                <div class="user-menu">
                    <button class="user-btn" data-user-menu>
                        <ion-icon name="person-circle"></ion-icon>
                        <span><?php echo htmlspecialchars($user['fullname']); ?></span>
                        <ion-icon name="chevron-down"></ion-icon>
                    </button>
                    <div class="user-dropdown" data-user-dropdown>
                        <a href="dashboard.php" class="dropdown-link">
                            <ion-icon name="person-outline"></ion-icon>
                            <span>Thông tin cá nhân</span>
                        </a>
              <a href="my_tickets.php" class="dropdown-link">
                            <ion-icon name="ticket-outline"></ion-icon>
                            <span>Vé của tôi</span>
                        </a>
                    
                        <div class="dropdown-divider"></div>
                        <form method="POST" style="margin: 0;">
                            <input type="hidden" name="action" value="logout">
                            <button type="submit" class="dropdown-link logout-btn">
                                <ion-icon name="log-out-outline"></ion-icon>
                                <span>Đăng xuất</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <button class="menu-open-btn" data-menu-open-btn>
                <ion-icon name="reorder-two"></ion-icon>
            </button>

            <nav class="navbar" data-navbar>
                <div class="navbar-top">
                    <a href="./index.php" class="logo">
                        <img src="./assets/images/project-logo.png" alt="Filmlane logo">
                    </a>
                    <button class="menu-close-btn" data-menu-close-btn>
                        <ion-icon name="close-outline"></ion-icon>
                    </button>
                </div>
               
                <ul class="navbar-social-list">
                    <li><a href="#" class="navbar-social-link"><ion-icon name="logo-twitter"></ion-icon></a></li>
                    <li><a href="#" class="navbar-social-link"><ion-icon name="logo-facebook"></ion-icon></a></li>
                    <li><a href="#" class="navbar-social-link"><ion-icon name="logo-pinterest"></ion-icon></a></li>
                    <li><a href="#" class="navbar-social-link"><ion-icon name="logo-instagram"></ion-icon></a></li>
                    <li><a href="#" class="navbar-social-link"><ion-icon name="logo-youtube"></ion-icon></a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- MAIN CONTENT -->
    <main>
        <section class="dashboard-section">
            <div class="container">
                <div class="dashboard-header">
                    <h1 class="dashboard-title">Chào mừng, <?php echo htmlspecialchars($user['fullname']); ?>!</h1>
                    <p class="dashboard-subtitle">Quản lý thông tin và vé của bạn</p>
                </div>

                <?php if ($error_message): ?>
                    <div class="alert alert-error">
                        <ion-icon name="alert-circle"></ion-icon>
                        <span><?php echo htmlspecialchars($error_message); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <ion-icon name="checkmark-circle"></ion-icon>
                        <span><?php echo htmlspecialchars($success_message); ?></span>
                    </div>
                <?php endif; ?>

                <div class="dashboard-grid">
                    <!-- Profile Only -->
                    <div class="dashboard-card dashboard-card--narrow" style="grid-column: 1 / -1;">
                        <div class="card-header">
                            <h3 class="card-title">
                                <ion-icon name="person-outline"></ion-icon>
                                Thông tin cá nhân
                            </h3>
                            <button class="edit-btn" data-edit-profile>
                                <ion-icon name="create-outline"></ion-icon>
                            </button>
                        </div>
                        <div class="card-content">
                            <div class="profile-info">
                                <div class="info-item">
                                    <span class="info-label">Họ tên:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($profile['TenKH'] ?? ''); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Email:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($profile['Email'] ?? ''); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Số điện thoại:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($profile['SDT'] ?? ''); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Địa chỉ:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($profile['DiaChi'] ?? ''); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Ngày sinh:</span>
                                    <span class="info-value"><?php echo isset($profile['NgaySinh']) && $profile['NgaySinh'] ? date('d/m/Y', strtotime($profile['NgaySinh'])) : ''; ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Giới tính:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($profile['GioiTinh'] ?? ''); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Tên đăng nhập:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($profile['TenTK'] ?? ''); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Profile Edit Modal -->
    <div class="modal" id="profileModal" data-modal>
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Chỉnh sửa thông tin</h3>
                <button class="modal-close" data-modal-close>
                    <ion-icon name="close-outline"></ion-icon>
                </button>
            </div>
            <div class="modal-body">
                <form class="profile-form" method="POST" action="">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="form-group">
                        <label for="fullname" class="form-label">Họ và tên</label>
                        <input type="text" id="fullname" name="fullname" class="form-input" 
                               value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" id="email" name="email" class="form-input" 
                               value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone" class="form-label">Số điện thoại</label>
                        <input type="tel" id="phone" name="phone" class="form-input" 
                               value="<?php echo htmlspecialchars($profile['SDT'] ?? ''); ?>"
                               placeholder="Nhập số điện thoại">
                    </div>
                    
                    <div class="form-group">
                        <label for="address" class="form-label">Địa chỉ</label>
                        <textarea id="address" name="address" class="form-textarea" 
                                  placeholder="Nhập địa chỉ"><?php echo htmlspecialchars($profile['DiaChi'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="birthday" class="form-label">Ngày sinh</label>
                        <input type="date" id="birthday" name="birthday" class="form-input" 
                               value="<?php echo isset($profile['NgaySinh']) && $profile['NgaySinh'] ? date('Y-m-d', strtotime($profile['NgaySinh'])) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="gender" class="form-label">Giới tính</label>
                        <select id="gender" name="gender" class="form-select">
                            <option value="Nam" <?php echo ($profile['GioiTinh'] ?? '') === 'Nam' ? 'selected' : ''; ?>>Nam</option>
                            <option value="Nu" <?php echo ($profile['GioiTinh'] ?? '') === 'Nu' ? 'selected' : ''; ?>>Nữ</option>
                            <option value="Khac" <?php echo ($profile['GioiTinh'] ?? '') === 'Khac' ? 'selected' : ''; ?>>Khác</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" data-modal-close>Hủy</button>
                        <button type="submit" class="btn btn-primary">Cập nhật</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    
    <!-- GO TO TOP -->
    <a href="#top" class="go-top" data-go-top>
        <ion-icon name="chevron-up"></ion-icon>
    </a>

    <!-- custom js link -->
    <script src="./assets/js/script.js"></script>
    <script src="./assets/js/dashboard.js"></script>

    <!-- ionicon link -->
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>

</body>

</html>
