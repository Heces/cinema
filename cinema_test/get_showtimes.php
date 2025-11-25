<?php
/**
 * AJAX endpoint để lấy danh sách suất chiếu của một phim
 */

require_once 'includes/auth.php';
require_once 'includes/showtime.php';

$auth = new Auth();

// Kiểm tra đăng nhập và quyền admin
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$movieId = $_GET['movie_id'] ?? 0;

if (!is_numeric($movieId) || $movieId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid movie ID']);
    exit;
}

$showtime = new Showtime();
$showtimes = $showtime->getShowtimesByMovie($movieId);

if (isset($showtimes['error'])) {
    http_response_code(500);
    echo json_encode(['error' => $showtimes['error']]);
    exit;
}

// Format showtimes for display
$formattedShowtimes = [];
foreach ($showtimes as $showtime) {
    $formattedShowtimes[] = [
        'id' => $showtime['MaSuat'],
        'room' => $showtime['TenPhong'],
        'room_type' => $showtime['LoaiPhong'],
        'datetime' => $showtime['ThoiGian'],
        'formatted_datetime' => date('d/m/Y H:i', strtotime($showtime['ThoiGian'])),
        'price' => number_format($showtime['GiaBan']),
        'status' => $showtime['TrangThai'],
        'status_text' => [
            'ConVe' => 'Còn vé',
            'HetVe' => 'Hết vé',
            'Huy' => 'Hủy'
        ][$showtime['TrangThai']] ?? $showtime['TrangThai']
    ];
}

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'showtimes' => $formattedShowtimes
]);
?>
