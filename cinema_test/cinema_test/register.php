<?php
require_once 'includes/auth.php';

$auth = new Auth();

// Nếu đã đăng nhập, chuyển hướng về trang chủ
if ($auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error_message = '';
$success_message = '';

// Xử lý đăng ký
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $result = $auth->register($_POST);
    
    if ($result['success']) {
        $success_message = $result['message'];
        // Có thể chuyển hướng đến trang đăng nhập hoặc tự động đăng nhập
        header('Location: login.php?success=1');
        exit;
    } else {
        $error_message = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - Filmlane Cinema</title>

    <!-- favicon -->
    <link rel="shortcut icon" href="./favicon.svg" type="image/svg+xml">

    <!-- custom css link -->
    <link rel="stylesheet" href="./assets/css/style.css">
    <link rel="stylesheet" href="./assets/css/auth.css">

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
                <a href="./login.php" class="btn btn-primary">Đăng nhập</a>
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

                <ul class="navbar-list">
                    <li><a href="./index.php" class="navbar-link">Trang chủ</a></li>
                    <li><a href="#" class="navbar-link">Phim</a></li>
                    <li><a href="#" class="navbar-link">TV Show</a></li>
                    <li><a href="#" class="navbar-link">Web Series</a></li>
                    <li><a href="#" class="navbar-link">Giá vé</a></li>
                </ul>

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
        <section class="auth-section">
            <div class="container">
                <div class="auth-container">
                    <div class="auth-card">
                        <div class="auth-header">
                            <h1 class="auth-title">Đăng ký tài khoản</h1>
                            <p class="auth-subtitle">Tạo tài khoản để trải nghiệm tốt nhất</p>
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

                        <form class="auth-form" method="POST" action="" id="registerForm">
                            <input type="hidden" name="action" value="register">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="fullname" class="form-label">Họ và tên *</label>
                                    <div class="input-group">
                                        <ion-icon name="person-outline" class="input-icon"></ion-icon>
                                        <input type="text" id="fullname" name="fullname" class="form-input" 
                                               placeholder="Nhập họ và tên" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="username" class="form-label">Tên đăng nhập *</label>
                                    <div class="input-group">
                                        <ion-icon name="at-outline" class="input-icon"></ion-icon>
                                        <input type="text" id="username" name="username" class="form-input" 
                                               placeholder="Nhập tên đăng nhập" required>
                                    </div>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="email" class="form-label">Email *</label>
                                    <div class="input-group">
                                        <ion-icon name="mail-outline" class="input-icon"></ion-icon>
                                        <input type="email" id="email" name="email" class="form-input" 
                                               placeholder="Nhập email" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="phone" class="form-label">Số điện thoại *</label>
                                    <div class="input-group">
                                        <ion-icon name="call-outline" class="input-icon"></ion-icon>
                                        <input type="tel" id="phone" name="phone" class="form-input" 
                                               placeholder="Nhập số điện thoại" required>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="address" class="form-label">Địa chỉ</label>
                                <div class="input-group">
                                    <ion-icon name="location-outline" class="input-icon"></ion-icon>
                                    <input type="text" id="address" name="address" class="form-input" 
                                           placeholder="Nhập địa chỉ">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="birthday" class="form-label">Ngày sinh</label>
                                    <div class="input-group">
                                        <ion-icon name="calendar-outline" class="input-icon"></ion-icon>
                                        <input type="date" id="birthday" name="birthday" class="form-input">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="gender" class="form-label">Giới tính</label>
                                    <div class="input-group">
                                        <ion-icon name="person-outline" class="input-icon"></ion-icon>
                                        <select id="gender" name="gender" class="form-input">
                                            <option value="Nam">Nam</option>
                                            <option value="Nu">Nữ</option>
                                            <option value="Khac">Khác</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="password" class="form-label">Mật khẩu *</label>
                                    <div class="input-group">
                                        <ion-icon name="lock-closed-outline" class="input-icon"></ion-icon>
                                        <input type="password" id="password" name="password" class="form-input" 
                                               placeholder="Nhập mật khẩu" required>
                                        <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                            <ion-icon name="eye-outline" id="password-icon"></ion-icon>
                                        </button>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="confirm_password" class="form-label">Xác nhận mật khẩu *</label>
                                    <div class="input-group">
                                        <ion-icon name="lock-closed-outline" class="input-icon"></ion-icon>
                                        <input type="password" id="confirm_password" name="confirm_password" class="form-input" 
                                               placeholder="Nhập lại mật khẩu" required>
                                        <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                            <ion-icon name="eye-outline" id="confirm_password-icon"></ion-icon>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="checkbox-wrapper">
                                    <input type="checkbox" id="terms" name="terms" required>
                                    <span class="checkmark"></span>
                                    <span class="checkbox-label">Tôi đồng ý với <a href="#" class="terms-link">Điều khoản sử dụng</a> và <a href="#" class="terms-link">Chính sách bảo mật</a></span>
                                </label>
                            </div>

                            <button type="submit" class="btn btn-primary auth-btn">
                                <ion-icon name="person-add-outline"></ion-icon>
                                <span>Đăng ký</span>
                            </button>
                        </form>

                        <div class="auth-footer">
                            <p>Đã có tài khoản? <a href="login.php" class="auth-link">Đăng nhập ngay</a></p>
                        </div>
                    </div>

                    <div class="auth-image">
                        <img src="assets/images/anime.jpg" alt="Cinema Background">
                        <div class="auth-overlay">
                            <h2>Tham gia cộng đồng</h2>
                            <p>Hàng ngàn thành viên đang tận hưởng trải nghiệm xem phim tuyệt vời</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- GO TO TOP -->
    <a href="#top" class="go-top" data-go-top>
        <ion-icon name="chevron-up"></ion-icon>
    </a>

    <!-- custom js link -->
    <script src="./assets/js/script.js"></script>
    <script src="./assets/js/auth.js"></script>

    <!-- ionicon link -->
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>

</body>

</html>
