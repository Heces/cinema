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

// Xử lý đăng nhập
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $result = $auth->login($_POST['username'], $_POST['password']);
    
    if ($result['success']) {
        header('Location: index.php');
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
    <title>Đăng nhập - Filmlane Cinema</title>

    <!-- favicon -->
    <link rel="shortcut icon" href="./favicon.svg" type="image/svg+xml">

    <!-- custom css link -->
    <link rel="stylesheet" href="./assets/css/style.css">
    <link rel="stylesheet" href="./assets/css/auth.css">

    <!-- google font link -->
    
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
                <a href="./register.php" class="btn btn-primary">Đăng ký</a>
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
        <section class="auth-section">
            <div class="container">
                <div class="auth-container">
                    <div class="auth-card">
                        <div class="auth-header">
                            <h1 class="auth-title">Đăng nhập</h1>
                            <p class="auth-subtitle">Chào mừng bạn quay trở lại!</p>
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

                        <form id="loginForm" class="auth-form" method="POST" action="">
                            <input type="hidden" name="action" value="login">
                            
                            <div class="form-group">
                                <label for="username" class="form-label">Tên đăng nhập</label>
                                <div class="input-group">
                                    <ion-icon name="person-outline" class="input-icon"></ion-icon>
                                    <input type="text" id="username" name="username" class="form-input" 
                                           placeholder="Nhập tên đăng nhập" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="password" class="form-label">Mật khẩu</label>
                                <div class="input-group">
                                    <ion-icon name="lock-closed-outline" class="input-icon"></ion-icon>
                                    <input type="password" id="password" name="password" class="form-input" 
                                           placeholder="Nhập mật khẩu" required>
                                    <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                        <ion-icon name="eye-outline" id="password-icon"></ion-icon>
                                    </button>
                                </div>
                            </div>
<!-- 
                            <div class="form-options">
                                <label class="checkbox-wrapper">
                                    <input type="checkbox" id="remember" name="remember">
                                    <span class="checkmark"></span>
                                    <span class="checkbox-label">Ghi nhớ đăng nhập</span>
                                </label>
                                <a href="#" class="forgot-password">Quên mật khẩu?</a>
                            </div> -->

                            <button type="submit" class="btn btn-primary auth-btn">
                                <ion-icon name="log-in-outline"></ion-icon>
                                <span>Đăng nhập</span>
                            </button>
                        </form>

                        <div class="auth-footer">
                            <p>Chưa có tài khoản? <a href="register.php" class="auth-link">Đăng ký ngay</a></p>
                        </div>
                    </div>

                    <div class="auth-image">
                        <img src="./assets/images/anime.jpg" alt="Cinema Background">
                        <div class="auth-overlay">
                            <h2>Khám phá thế giới điện ảnh</h2>
                            <p>Hàng ngàn bộ phim hay đang chờ bạn</p>
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
