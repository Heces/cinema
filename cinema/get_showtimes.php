<?php
/**
 * AJAX endpoint de lay danh sach suat chieu cua mot phim
 */

require_once 'includes/auth.php';
require_once 'includes/showtime.php';

$xacThuc = new XacThuc();

// Kiem tra dang nhap va quyen admin
if (!$xacThuc->daDangNhap() || !$xacThuc->laQuanTri()) {
    http_response_code(403);
    echo json_encode(['loi' => 'Unauthorized']);
    exit;
}

$maPhim = $_GET['movie_id'] ?? 0;

if (!is_numeric($maPhim) || $maPhim <= 0) {
    http_response_code(400);
    echo json_encode(['loi' => 'Invalid movie ID']);
    exit;
}

$suatChieu = new SuatChieu();
$danhSachSuatChieu = $suatChieu->laySuatChieuTheoPhim($maPhim);

if (isset($danhSachSuatChieu['loi'])) {
    http_response_code(500);
    echo json_encode(['loi' => $danhSachSuatChieu['loi']]);
    exit;
}

// Format showtimes for display
$danhSachDaDinhDang = [];
foreach ($danhSachSuatChieu as $suatChieuItem) {
    $danhSachDaDinhDang[] = [
        'id' => $suatChieuItem['MaSuat'],
        'room' => $suatChieuItem['TenPhong'],
        'room_type' => $suatChieuItem['LoaiPhong'],
        'datetime' => $suatChieuItem['ThoiGian'],
        'formatted_datetime' => date('d/m/Y H:i', strtotime($suatChieuItem['ThoiGian'])),
        'price' => number_format($suatChieuItem['GiaBan']),
        'status' => $suatChieuItem['TrangThai'],
        'status_text' => [
            'ConVe' => 'Còn vé',
            'HetVe' => 'Hết vé',
            'Huy' => 'Hủy'
        ][$suatChieuItem['TrangThai']] ?? $suatChieuItem['TrangThai']
    ];
}

header('Content-Type: application/json');
echo json_encode([
    'thanhCong' => true,
    'showtimes' => $danhSachDaDinhDang
]);
?>
