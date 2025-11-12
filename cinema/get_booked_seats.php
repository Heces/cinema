<?php
/**
 * Tra ve danh sach ghe da duoc dat cua mot suat chieu (khong bao gom ve da huy)
 * GET params: suat_id
 * Response: { thanhCong: bool, booked: string[] }
 */

require_once 'config/database.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_GET['suat_id']) || !is_numeric($_GET['suat_id'])) {
    http_response_code(400);
    echo json_encode(['thanhCong' => false, 'loi' => 'Thiếu hoặc suat_id không hợp lệ']);
    exit;
}

$maSuat = (int) $_GET['suat_id'];

try {
    $coSoDuLieu = new CoSoDuLieu();
    $ketNoi = $coSoDuLieu->layKetNoi();

    $cauTruyVan = $ketNoi->prepare("SELECT SoGhe FROM VE WHERE MaSuat = ? AND TrangThai != 'Huy'");
    $cauTruyVan->execute([$maSuat]);
    $cacHang = $cauTruyVan->fetchAll(PDO::FETCH_ASSOC);
    $gheDaDat = array_map(function($r){ return $r['SoGhe']; }, $cacHang);

    echo json_encode(['thanhCong' => true, 'booked' => $gheDaDat]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['thanhCong' => false, 'loi' => 'Lỗi máy chủ']);
}
?>


