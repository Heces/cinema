<?php
require_once 'includes/auth.php';
require_once 'includes/movie.php';

$xacThuc = new XacThuc();
$phim = new Phim();
$nguoiDung = $xacThuc->layNguoiDungHienTai();

// Xu ly dang xuat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'logout') {
    $xacThuc->dangXuat();
    header('Location: index.php');
    exit;
}

// Lay du lieu phim
$phimDangChieu = $phim->layPhimTheoTrangThai('DangChieu', 8);
$phimSapChieu = $phim->layPhimTheoTrangThai('SapChieu', 8);
$phimKetThuc = $phim->layPhimTheoTrangThai('KetThuc', 8);
$phimXemNhieu = $phim->layTatCaPhim(6);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>4TG Cinema</title>

  <!-- 
    - favicon
  -->
  <link rel="shortcut icon" href="./favicon.svg" type="image/svg+xml">

  <!-- 
    - custom css link
  -->
  <link rel="stylesheet" href="./assets/css/style.css">

  <!-- 
    - google font link
  -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body id="top">

  <!-- 
    - #HEADER
  -->

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

        <?php if ($xacThuc->daDangNhap()): ?>
          <!-- User Menu for logged in users -->
          <div class="user-menu">
            <button class="user-btn" data-user-menu>
              <ion-icon name="person-circle"></ion-icon>
              <span><?php echo htmlspecialchars($nguoiDung['hoTen']); ?></span>
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
              <?php if ($xacThuc->laQuanTri()): ?>
              <div class="dropdown-divider"></div>
              <a href="admin_movies.php" class="dropdown-link">
                <ion-icon name="film-outline"></ion-icon>
                <span>Quản lý tổng</span>
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
          <!-- Login/Register buttons for guests -->
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

          <li>
            <a href="#" class="navbar-social-link">
              <ion-icon name="logo-twitter"></ion-icon>
            </a>
          </li>

          <li>
            <a href="#" class="navbar-social-link">
              <ion-icon name="logo-facebook"></ion-icon>
            </a>
          </li>

          <li>
            <a href="#" class="navbar-social-link">
              <ion-icon name="logo-pinterest"></ion-icon>
            </a>
          </li>

          <li>
            <a href="#" class="navbar-social-link">
              <ion-icon name="logo-instagram"></ion-icon>
            </a>
          </li>

          <li>
            <a href="#" class="navbar-social-link">
              <ion-icon name="logo-youtube"></ion-icon>
            </a>
          </li>

        </ul>

      </nav>
    </div>
  </header>




  <main>
    <article>

      <!-- 
        - #HERO
      -->

      <section class="hero">
        <div class="container">

          <div class="hero-content">

            <p class="hero-subtitle">4TG</p>

            <h1 class="h1 hero-title">
              Nơi gặp gỡ những người <strong> yêu phim</strong> 
            </h1>

            

          </div>

        </div>
      </section>

      <!-- 
        - #PHIM ĐANG CHIẾU
      -->

      <section class="top-rated">
        <div class="container">

          <p class="section-subtitle">Phim đang chiếu</p>

          <h2 class="h2 section-title">Phim đang chiếu</h2>

          <ul class="movies-list">
            <?php if (!empty($phimDangChieu) && !isset($phimDangChieu['loi'])): ?>
              <?php foreach ($phimDangChieu as $phimItem): ?>
                <li>
                  <div class="movie-card">
                    <a href="./movie-details.php?id=<?php echo $phimItem['MaPhim']; ?>">
                      <figure class="card-banner">
                        <img src="<?php echo htmlspecialchars($phimItem['AnhBia'] ?: './assets/images/movie-1.png'); ?>" 
                             alt="<?php echo htmlspecialchars($phimItem['TenPhim']); ?> movie poster">
                      </figure>
                    </a>

                    <div class="title-wrapper">
                      <a href="./movie-details.php?id=<?php echo $phimItem['MaPhim']; ?>">
                        <h3 class="card-title"><?php echo htmlspecialchars($phimItem['TenPhim']); ?></h3>
                      </a>
                      <time datetime="<?php echo $phimItem['NamSanXuat']; ?>"><?php echo $phimItem['NamSanXuat']; ?></time>
                    </div>

                    <div class="card-meta">
                      <div class="badge badge-outline">HD</div>
                      <div class="duration">
                        <ion-icon name="time-outline"></ion-icon>
                        <time datetime="PT<?php echo $phimItem['ThoiLuong']; ?>M"><?php echo $phimItem['ThoiLuong']; ?> phút</time>
                      </div>
                      <div class="rating">
                        <ion-icon name="star"></ion-icon>
                        <data>8.5</data>
                      </div>
                    </div>
                  </div>
                </li>
              <?php endforeach; ?>
            <?php else: ?>
              <li>
                <div class="movie-card">
                  <div class="title-wrapper">
                    <h3 class="card-title">Chưa có phim đang chiếu</h3>
                    <p>Hãy thêm phim mới từ trang quản lý admin</p>
                  </div>
                </div>
              </li>
            <?php endif; ?>
          </ul>

        </div>
      </section>

      <!-- 
        - #PHIM SẮP CHIẾU
      -->

      <section class="top-rated">
        <div class="container">

          <p class="section-subtitle">Phim sắp chiếu</p>

          <h2 class="h2 section-title">Sắp ra mắt</h2>

          <ul class="movies-list">
            <?php if (!empty($phimSapChieu) && !isset($phimSapChieu['loi'])): ?>
              <?php foreach ($phimSapChieu as $phimItem): ?>
                <li>
                  <div class="movie-card">
                    <a href="./movie-details.php?id=<?php echo $phimItem['MaPhim']; ?>">
                      <figure class="card-banner">
                        <img src="<?php echo htmlspecialchars($phimItem['AnhBia'] ?: './assets/images/upcoming-1.png'); ?>" 
                             alt="<?php echo htmlspecialchars($phimItem['TenPhim']); ?> movie poster">
                      </figure>
                    </a>

                    <div class="title-wrapper">
                      <a href="./movie-details.php?id=<?php echo $phimItem['MaPhim']; ?>">
                        <h3 class="card-title"><?php echo htmlspecialchars($phimItem['TenPhim']); ?></h3>
                      </a>
                      <time datetime="<?php echo $phimItem['NamSanXuat']; ?>"><?php echo $phimItem['NamSanXuat']; ?></time>
                    </div>

                    <div class="card-meta">
                      <div class="badge badge-outline">HD</div>
                      <div class="duration">
                        <ion-icon name="time-outline"></ion-icon>
                        <time datetime="PT<?php echo $phimItem['ThoiLuong']; ?>M"><?php echo $phimItem['ThoiLuong']; ?> phút</time>
                      </div>
                      <div class="rating">
                        <ion-icon name="star"></ion-icon>
                        <data>8.0</data>
                      </div>
                    </div>
                  </div>
                </li>
              <?php endforeach; ?>
            <?php else: ?>
              <li>
                <div class="movie-card">
                  <div class="title-wrapper">
                    <h3 class="card-title">Chưa có phim sắp chiếu</h3>
                    <p>Hãy thêm phim mới từ trang quản lý admin</p>
                  </div>
                </div>
              </li>
            <?php endif; ?>
          </ul>

        </div>
      </section>

      <!-- 
        - #PHIM KẾT THÚC
      -->

      <section class="tv-series">
        <div class="container">

          <p class="section-subtitle">Phim kết thúc</p>

          <h2 class="h2 section-title">Phim đã kết thúc</h2>

          <ul class="movies-list">
            <?php if (!empty($phimKetThuc) && !isset($phimKetThuc['loi'])): ?>
              <?php foreach ($phimKetThuc as $phimItem): ?>
                <li>
                  <div class="movie-card">
                    <a href="./movie-details.php?id=<?php echo $phimItem['MaPhim']; ?>">
                      <figure class="card-banner">
                        <img src="<?php echo htmlspecialchars($phimItem['AnhBia'] ?: './assets/images/series-1.png'); ?>" 
                             alt="<?php echo htmlspecialchars($phimItem['TenPhim']); ?> movie poster">
                      </figure>
                    </a>

                    <div class="title-wrapper">
                      <a href="./movie-details.php?id=<?php echo $phimItem['MaPhim']; ?>">
                        <h3 class="card-title"><?php echo htmlspecialchars($phimItem['TenPhim']); ?></h3>
                      </a>
                      <time datetime="<?php echo $phimItem['NamSanXuat']; ?>"><?php echo $phimItem['NamSanXuat']; ?></time>
                    </div>

                    <div class="card-meta">
                      <div class="badge badge-outline">HD</div>
                      <div class="duration">
                        <ion-icon name="time-outline"></ion-icon>
                        <time datetime="PT<?php echo $phimItem['ThoiLuong']; ?>M"><?php echo $phimItem['ThoiLuong']; ?> phút</time>
                      </div>
                      <div class="rating">
                        <ion-icon name="star"></ion-icon>
                        <data>8.0</data>
                      </div>
                    </div>
                  </div>
                </li>
              <?php endforeach; ?>
            <?php else: ?>
              <li>
                <div class="movie-card">
                  <div class="title-wrapper">
                    <h3 class="card-title">Chưa có phim kết thúc</h3>
                    <p>Hãy thêm phim mới từ trang quản lý admin</p>
                  </div>
                </div>
              </li>
            <?php endif; ?>
          </ul>

        </div>
      </section>

    </article>
  </main>


  <!-- 
    - #GO TO TOP
  -->

  <a href="#top" class="go-top" data-go-top>
    <ion-icon name="chevron-up"></ion-icon>
  </a>




  <!-- 
    - custom js link
  -->
  <script src="./assets/js/script.js"></script>
  <script src="./assets/js/index.js"></script>

  <!-- 
    - ionicon link
  -->
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>

</body>

</html>
//me tùng béo//
