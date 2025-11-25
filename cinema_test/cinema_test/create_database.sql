-- Tạo database với charset utf8mb4_unicode_ci
CREATE DATABASE IF NOT EXISTS cinema 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE cinema;

-- 1. Bảng THELOAI
CREATE TABLE THELOAI (
    MaTheloai INT AUTO_INCREMENT PRIMARY KEY,
    TheLoai VARCHAR(100) NOT NULL UNIQUE,
    NgayTao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    NgayCapNhat TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Bảng NSX (Nhà sản xuất)
CREATE TABLE NSX (
    MaNSX INT AUTO_INCREMENT PRIMARY KEY,
    TenNSX VARCHAR(200) NOT NULL,
    NgayTao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    NgayCapNhat TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Bảng PHIM
CREATE TABLE PHIM (
    MaPhim INT AUTO_INCREMENT PRIMARY KEY,
    TenPhim VARCHAR(200) NOT NULL,
    ThoiLuong INT NOT NULL COMMENT 'Thời lượng tính bằng phút',
    AnhBia VARCHAR(500) COMMENT 'Đường dẫn ảnh bìa',
    MoTa TEXT COMMENT 'Mô tả phim',
    NamSanXuat YEAR COMMENT 'Năm sản xuất',
    TrangThai ENUM('DangChieu', 'SapChieu', 'KetThuc') DEFAULT 'SapChieu',
    NgayTao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    NgayCapNhat TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Bảng PHONG
CREATE TABLE PHONG (
    MaPhong INT AUTO_INCREMENT PRIMARY KEY,
    TenPhong VARCHAR(100) NOT NULL,
    SoGhe INT NOT NULL,
    LoaiPhong ENUM('2D', '3D', 'IMAX', 'VIP') DEFAULT '2D',
    TrangThai ENUM('HoatDong', 'BaoTri', 'NgungHoatDong') DEFAULT 'HoatDong',
    NgayTao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    NgayCapNhat TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Bảng SUATCHIEU
CREATE TABLE SUATCHIEU (
    MaSuat INT AUTO_INCREMENT PRIMARY KEY,
    MaPhim INT NOT NULL,
    MaPhong INT NOT NULL,
    ThoiGian DATETIME NOT NULL,
    GiaBan DECIMAL(10,2) NOT NULL,
    TrangThai ENUM('ConVe', 'HetVe', 'Huy') DEFAULT 'ConVe',
    NgayTao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    NgayCapNhat TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (MaPhim) REFERENCES PHIM(MaPhim) ON DELETE CASCADE,
    FOREIGN KEY (MaPhong) REFERENCES PHONG(MaPhong) ON DELETE CASCADE,
    UNIQUE KEY unique_phong_thoigian (MaPhong, ThoiGian)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Bảng KHACHHANG
CREATE TABLE KHACHHANG (
    MaKH INT AUTO_INCREMENT PRIMARY KEY,
    TenKH VARCHAR(200) NOT NULL,
    SDT VARCHAR(15) NOT NULL UNIQUE,
    Email VARCHAR(100) NOT NULL UNIQUE,
    DiaChi TEXT,
    NgaySinh DATE,
    GioiTinh ENUM('Nam', 'Nu', 'Khac'),
    NgayDangKy TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    TrangThai ENUM('HoatDong', 'Khoa') DEFAULT 'HoatDong',
    NgayCapNhat TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Bảng TAIKHOANKH
CREATE TABLE TAIKHOANKH (
    MaTK INT AUTO_INCREMENT PRIMARY KEY,
    MaKH INT NOT NULL UNIQUE,
    TenTK VARCHAR(50) NOT NULL UNIQUE,
    Matkhau VARCHAR(255) NOT NULL,
    Quyen ENUM('User', 'Admin') DEFAULT 'User',
    TrangThai ENUM('HoatDong', 'Khoa') DEFAULT 'HoatDong',
    NgayTao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    NgayCapNhat TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (MaKH) REFERENCES KHACHHANG(MaKH) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Bảng VE
CREATE TABLE VE (
    MaVe INT AUTO_INCREMENT PRIMARY KEY,
    MaKH INT NOT NULL,
    MaSuat INT NOT NULL,
    SoGhe VARCHAR(10) NOT NULL COMMENT 'Vị trí ghế (VD: A1, B5)',
    GiaVe DECIMAL(10,2) NOT NULL,
    TrangThai ENUM('ChuaSuDung', 'DaSuDung', 'Huy') DEFAULT 'ChuaSuDung',
    NgayMua TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    NgaySuDung TIMESTAMP NULL,
    FOREIGN KEY (MaKH) REFERENCES KHACHHANG(MaKH) ON DELETE CASCADE,
    FOREIGN KEY (MaSuat) REFERENCES SUATCHIEU(MaSuat) ON DELETE CASCADE,
    UNIQUE KEY unique_ghe_suat (MaSuat, SoGhe)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng trung gian cho quan hệ n-n: PHIM - THELOAI
CREATE TABLE PHIM_THELOAI (
    MaPhim INT NOT NULL,
    MaTheloai INT NOT NULL,
    NgayTao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (MaPhim, MaTheloai),
    FOREIGN KEY (MaPhim) REFERENCES PHIM(MaPhim) ON DELETE CASCADE,
    FOREIGN KEY (MaTheloai) REFERENCES THELOAI(MaTheloai) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng trung gian cho quan hệ n-n: PHIM - NSX
CREATE TABLE PHIM_NSX (
    MaPhim INT NOT NULL,
    MaNSX INT NOT NULL,
    VaiTro ENUM('SanXuat', 'PhanPhoi', 'TruyenThong') DEFAULT 'SanXuat',
    NgayTao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (MaPhim, MaNSX),
    FOREIGN KEY (MaPhim) REFERENCES PHIM(MaPhim) ON DELETE CASCADE,
    FOREIGN KEY (MaNSX) REFERENCES NSX(MaNSX) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tạo indexes để tối ưu hiệu suất
CREATE INDEX idx_phim_ten ON PHIM(TenPhim);
CREATE INDEX idx_phim_trangthai ON PHIM(TrangThai);
CREATE INDEX idx_suatchieu_thoigian ON SUATCHIEU(ThoiGian);
CREATE INDEX idx_suatchieu_phim ON SUATCHIEU(MaPhim);
CREATE INDEX idx_ve_khachhang ON VE(MaKH);
CREATE INDEX idx_ve_suat ON VE(MaSuat);
CREATE INDEX idx_khachhang_sdt ON KHACHHANG(SDT);
CREATE INDEX idx_khachhang_email ON KHACHHANG(Email);

-- Thêm dữ liệu mẫu
INSERT INTO THELOAI (TheLoai) VALUES 
('Hành động'),
('Tình cảm'),
('Hài hước'),
('Kinh dị'),
('Khoa học viễn tưởng'),
('Hoạt hình'),
('Tài liệu'),
('Phiêu lưu');

INSERT INTO NSX (TenNSX) VALUES 
('Marvel Studios'),
('Disney'),
('Warner Bros'),
('Universal Pictures'),
('Sony Pictures'),
('Paramount Pictures'),
('20th Century Studios'),
('Lionsgate');

INSERT INTO PHONG (TenPhong, SoGhe, LoaiPhong) VALUES 
('Phòng 1', 100, '2D'),
('Phòng 2', 80, '3D'),
('Phòng 3', 120, 'IMAX'),
('Phòng VIP 1', 50, 'VIP'),
('Phòng VIP 2', 50, 'VIP');

-- Tạo view để dễ dàng truy vấn
CREATE VIEW vw_phim_chi_tiet AS
SELECT 
    p.MaPhim,
    p.TenPhim,
    p.ThoiLuong,
    p.AnhBia,
    p.MoTa,
    p.NamSanXuat,
    p.TrangThai,
    GROUP_CONCAT(DISTINCT t.TheLoai SEPARATOR ', ') AS TheLoai,
    GROUP_CONCAT(DISTINCT n.TenNSX SEPARATOR ', ') AS NhaSanXuat
FROM PHIM p
LEFT JOIN PHIM_THELOAI pt ON p.MaPhim = pt.MaPhim
LEFT JOIN THELOAI t ON pt.MaTheloai = t.MaTheloai
LEFT JOIN PHIM_NSX pn ON p.MaPhim = pn.MaPhim
LEFT JOIN NSX n ON pn.MaNSX = n.MaNSX
GROUP BY p.MaPhim;

CREATE VIEW vw_ve_chi_tiet AS
SELECT 
    v.MaVe,
    v.SoGhe,
    v.GiaVe,
    v.TrangThai,
    v.NgayMua,
    v.NgaySuDung,
    kh.TenKH,
    kh.SDT,
    kh.Email,
    p.TenPhim,
    ph.TenPhong,
    sc.ThoiGian,
    sc.GiaBan
FROM VE v
JOIN KHACHHANG kh ON v.MaKH = kh.MaKH
JOIN SUATCHIEU sc ON v.MaSuat = sc.MaSuat
JOIN PHIM p ON sc.MaPhim = p.MaPhim
JOIN PHONG ph ON sc.MaPhong = ph.MaPhong;

INSERT INTO KHACHHANG (TenKH, SDT, Email, DiaChi, NgaySinh, GioiTinh)
VALUES ('Admin', '0909090909', 'admin@gmail.com', 'Hà Nội', '1990-01-01', 'Nam');

INSERT INTO TAIKHOANKH (MaKH, TenTK, Matkhau, Quyen)
VALUES (1, 'admin', 'admin', 'Admin');









