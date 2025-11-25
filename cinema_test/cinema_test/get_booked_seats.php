<?php
/**
 * Trả về danh sách ghế đã được đặt của một suất chiếu (không bao gồm vé đã hủy)
 * GET params: suat_id
 * Response: { success: bool, booked: string[] }
 */

require_once 'config/database.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_GET['suat_id']) || !is_numeric($_GET['suat_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Thiếu hoặc suat_id không hợp lệ']);
    exit;
}

$suatId = (int) $_GET['suat_id'];

try {
    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("SELECT SoGhe FROM VE WHERE MaSuat = ? AND TrangThai != 'Huy'");
    $stmt->execute([$suatId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $booked = array_map(function($r){ return $r['SoGhe']; }, $rows);

    echo json_encode(['success' => true, 'booked' => $booked]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Lỗi máy chủ']);
}
?>


