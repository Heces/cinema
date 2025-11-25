<?php
require_once 'includes/auth.php';
require_once 'includes/movie.php';
require_once 'includes/showtime.php';
require_once 'config/database.php';

$auth = new Auth();
$movie = new Movie();
$showtime = new Showtime();
$user = $auth->getCurrentUser();

// Lấy ID phim từ URL
$movieId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($movieId <= 0) {
    header('Location: index.php');
    exit;
}

// Lấy thông tin phim
$movieData = $movie->getMovieById($movieId);

if (empty($movieData) || isset($movieData['error'])) {
    header('Location: index.php');
    exit;
}

// Lấy danh sách suất chiếu cho phim này - sử dụng method từ class Showtime
$showtimes = $showtime->getAvailableShowtimes($movieId);

// Kiểm tra nếu có lỗi, set về mảng rỗng
if (isset($showtimes['error'])) {
    $showtimes = [];
}

// Xử lý đặt vé
$bookingMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'book_ticket') {
    if (!$auth->isLoggedIn()) {
        $bookingMessage = '<div class="alert alert-error">Vui lòng đăng nhập để đặt vé!</div>';
    } else {
        $suatId = (int)$_POST['suat_id'];
			$soGhe = trim($_POST['so_ghe']);
        $giaVe = (float)$_POST['gia_ve'];
        
			// Validate định dạng ghế: A-E và 1-10 (A1..E10)
			$validSeat = preg_match('/^[A-E](10|[1-9])$/i', $soGhe) === 1;
			
			if ($suatId > 0 && $validSeat && $giaVe > 0) {
            try {
                $conn->beginTransaction();
                
                // Kiểm tra ghế có trống không
                $stmt = $conn->prepare("
                    SELECT COUNT(*) as count FROM VE 
                    WHERE MaSuat = ? AND SoGhe = ? AND TrangThai != 'Huy'
                ");
                $stmt->execute([$suatId, $soGhe]);
                $seatCheck = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($seatCheck['count'] > 0) {
                    throw new Exception('Ghế này đã được đặt!');
                }
                
                // Đặt vé
                $stmt = $conn->prepare("
                    INSERT INTO VE (MaKH, MaSuat, SoGhe, GiaVe, TrangThai) 
                    VALUES (?, ?, ?, ?, 'ChuaSuDung')
                ");
                $stmt->execute([$user['id'], $suatId, $soGhe, $giaVe]);
                
                $conn->commit();
                $bookingMessage = '<div class="alert alert-success">Đặt vé thành công! Mã vé: ' . $conn->lastInsertId() . '</div>';
                
            } catch (Exception $e) {
                $conn->rollBack();
                $bookingMessage = '<div class="alert alert-error">Lỗi: ' . $e->getMessage() . '</div>';
            }
			} else {
				$bookingMessage = '<div class="alert alert-error">Vui lòng chọn suất và ghế hợp lệ (A1 - E10)!</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($movieData['TenPhim']); ?> - Filmlane</title>
    
    <link rel="shortcut icon" href="./favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="./assets/css/style.css">
    <link rel="stylesheet" href="./assets/css/movie-details.css">
    
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
                
                <div class="lang-wrapper">
                    <label for="language">
                        <ion-icon name="globe-outline"></ion-icon>
                    </label>
                    <select name="language" id="language">
                        <option value="en">VN</option>
                    </select>
                </div>

                <?php if ($auth->isLoggedIn()): ?>
                    <div class="user-menu">
                        <button class="user-btn" data-user-menu>
                            <ion-icon name="person-circle"></ion-icon>
                            <span><?php echo htmlspecialchars($user['fullname']); ?></span>
                            <ion-icon name="chevron-down"></ion-icon>
                        </button>
                        <div class="user-dropdown" data-user-dropdown>
                            <a href="dashboard.php" class="dropdown-link">
                                <ion-icon name="person-outline"></ion-icon>
                                <span>Dashboard</span>
                            </a>
                            <a href="my_tickets.php" class="dropdown-link">
                                <ion-icon name="ticket-outline"></ion-icon>
                                <span>Vé của tôi</span>
                            </a>
                            <?php if ($auth->isAdmin()): ?>
                            <div class="dropdown-divider"></div>
                            <a href="admin_movies.php" class="dropdown-link">
                                <ion-icon name="film-outline"></ion-icon>
                                <span>Quản lý phim</span>
                            </a>
                            <?php endif; ?>
                            <div class="dropdown-divider"></div>
                            <form method="POST" action="index.php" style="margin: 0;">
                                <input type="hidden" name="action" value="logout">
                                <button type="submit" class="dropdown-link logout-btn">
                                    <ion-icon name="log-out-outline"></ion-icon>
                                    <span>Đăng xuất</span>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn btn-primary">Đăng nhập</a>
                    <a href="register.php" class="btn btn-outline">Đăng ký</a>
                <?php endif; ?>
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

    <main>
        <article>
            <!-- MOVIE DETAILS HERO -->
            <section class="movie-details-hero">
                <div class="container">
                    <div class="movie-details-content">
                        <div class="movie-poster">
                            <img src="<?php echo htmlspecialchars($movieData['AnhBia'] ?: './assets/images/movie-1.png'); ?>" 
                                 alt="<?php echo htmlspecialchars($movieData['TenPhim']); ?>">
                        </div>
                        
                        <div class="movie-info">
                            <h1 class="movie-title"><?php echo htmlspecialchars($movieData['TenPhim']); ?></h1>
                            
                            <div class="movie-meta">
                                <div class="meta-item">
                                    <ion-icon name="calendar-outline"></ion-icon>
                                    <span><?php echo $movieData['NamSanXuat']; ?></span>
                                </div>
                                <div class="meta-item">
                                    <ion-icon name="time-outline"></ion-icon>
                                    <span><?php echo $movieData['ThoiLuong']; ?> phút</span>
                                </div>
                                
                                <div class="meta-item">
                                    <ion-icon name="film-outline"></ion-icon>
                                    <span><?php echo htmlspecialchars($movieData['TheLoai'] ?: 'Chưa phân loại'); ?></span>
                                </div>
                            </div>
                            
                            <div class="movie-description">
                                <h3>Mô tả</h3>
                                <p><?php echo htmlspecialchars($movieData['MoTa'] ?: 'Chưa có mô tả cho bộ phim này.'); ?></p>
                            </div>
                            
                            <div class="movie-status">
                                <span class="status-badge status-<?php echo strtolower($movieData['TrangThai']); ?>">
                                    <?php 
                                    switch($movieData['TrangThai']) {
                                        case 'DangChieu': echo 'Đang chiếu'; break;
                                        case 'SapChieu': echo 'Sắp chiếu'; break;
                                        case 'KetThuc': echo 'Kết thúc'; break;
                                        default: echo $movieData['TrangThai'];
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- BOOKING SECTION -->
            <?php if ($movieData['TrangThai'] === 'DangChieu'): ?>
            <section class="booking-section">
                <div class="container">
                    <h2 class="section-title">Đặt vé xem phim</h2>
                    
                    <?php echo $bookingMessage; ?>
                    
                    <?php if (empty($showtimes)): ?>
                        <div class="alert alert-warning" style="margin: 20px 0; padding: 20px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px;">
                            <ion-icon name="information-circle"></ion-icon>
                            <span>Hiện tại chưa có suất chiếu nào cho phim này. Vui lòng quay lại sau hoặc liên hệ rạp để biết thêm thông tin.</span>
                        </div>
                    <?php else: ?>
                    <div class="booking-form-container">
                        <form method="POST" class="booking-form">
                            <input type="hidden" name="action" value="book_ticket">
                            
                            <div class="form-group">
                                <label for="suat_id">Chọn suất chiếu:</label>
                                <select name="suat_id" id="suat_id" required>
                                    <option value="">-- Chọn suất chiếu --</option>
                                    <?php foreach ($showtimes as $showtime): ?>
                                        <option value="<?php echo $showtime['MaSuat']; ?>" 
                                                data-price="<?php echo $showtime['GiaBan']; ?>"
                                                data-room="<?php echo htmlspecialchars($showtime['TenPhong']); ?>"
                                                data-type="<?php echo $showtime['LoaiPhong']; ?>"
                                                data-seat-total="50">
                                            <?php echo date('d/m/Y H:i', strtotime($showtime['ThoiGian'])); ?> - 
                                            <?php echo htmlspecialchars($showtime['TenPhong']); ?> (<?php echo $showtime['LoaiPhong']; ?>) - 
                                            <?php echo number_format($showtime['GiaBan']); ?>đ
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="so_ghe">Chọn ghế:</label>
                                <input type="text" name="so_ghe" id="so_ghe" placeholder="A1 - E10" readonly required>
                                <small>Chọn ghế bằng cách nhấn vào sơ đồ bên dưới</small>
                                <div class="seat-map-wrapper">
                                    <div class="seat-legend">
                                        <span><span class="swatch" style="background:#fff;border:1px solid #e0e0e0"></span>Trống</span>
                                        <span><span class="swatch" style="background:#f1f3f5"></span>Đã đặt</span>
                                        <span><span class="swatch" style="background:#ff6b35"></span>Đang chọn</span>
                                    </div>
                                    <div id="seatGrid" class="seat-grid"></div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="gia_ve">Giá vé:</label>
                                <input type="number" name="gia_ve" id="gia_ve" readonly>
                                <small>Giá vé sẽ được cập nhật khi bạn chọn suất chiếu</small>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary btn-large">
                                    <ion-icon name="ticket-outline"></ion-icon>
                                    Đặt vé ngay
                                </button>
                            </div>
                        </form>
                        
                        <div class="booking-info">
                            <h3>Thông tin đặt vé</h3>
                            <ul>
                                <li><strong>Phim:</strong> <?php echo htmlspecialchars($movieData['TenPhim']); ?></li>
                                <li><strong>Thời lượng:</strong> <?php echo $movieData['ThoiLuong']; ?> phút</li>
                                <li><strong>Thể loại:</strong> <?php echo htmlspecialchars($movieData['TheLoai'] ?: 'Chưa phân loại'); ?></li>
                                <!-- <li><strong>Nhà sản xuất:</strong> <?php echo htmlspecialchars($movieData['NhaSanXuat'] ?: 'Chưa có thông tin'); ?></li> -->
                            </ul>
                            
                            <div class="booking-note">
                                <h4>Lưu ý:</h4>
                                <ul>
                                    <li>Vui lòng đến rạp trước 15 phút so với giờ chiếu</li>
                                    <li>Mang theo giấy tờ tùy thân khi đến rạp</li>
                                    <li>Vé không thể hoàn lại sau khi đặt</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </section>
            <?php elseif ($movieData['TrangThai'] === 'SapChieu'): ?>
            <section class="booking-section">
                <div class="container">
                    <div class="coming-soon-notice">
                        <h2>Phim sắp chiếu</h2>
                        <p>Bộ phim này sẽ sớm ra mắt. Hãy quay lại sau để đặt vé!</p>
                        <a href="index.php" class="btn btn-primary">Quay về trang chủ</a>
                    </div>
                </div>
            </section>
            <?php else: ?>
            <section class="booking-section">
                <div class="container">
                    <div class="ended-notice">
                        <h2>Phim đã kết thúc</h2>
                        <p>Bộ phim này đã kết thúc chiếu. Hãy xem các phim khác!</p>
                        <a href="index.php" class="btn btn-primary">Xem phim khác</a>
                    </div>
                </div>
            </section>
            <?php endif; ?>
        </article>
    </main>

    <a href="#top" class="go-top" data-go-top>
        <ion-icon name="chevron-up"></ion-icon>
    </a>

    <script src="./assets/js/script.js"></script>
    <script src="./assets/js/movie-details.js"></script>
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>
</html>
