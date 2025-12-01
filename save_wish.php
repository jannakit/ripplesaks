<?php
// save_wish.php
// Ghi lời chúc vào file loichuc.log ở cùng thư mục với file này.

// Cho phép CORS tối thiểu nếu bạn test qua file:// hoặc domain khác
header('Content-Type: application/json; charset=utf-8');

// Chỉ chấp nhận POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

// Đọc body JSON
$raw = file_get_contents('php://input');
if ($raw === false || $raw === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Empty body']);
    exit;
}

$data = json_decode($raw, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid JSON']);
    exit;
}

$name = isset($data['name']) ? trim($data['name']) : 'Ẩn danh';
$content = isset($data['content']) ? trim($data['content']) : '';

if ($content === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Empty content']);
    exit;
}

// Chuẩn bị dòng ghi log
$time = date('Y-m-d H:i:s');
$line = "[$time] {$name}: {$content}\n";

// Ghi append vào loichuc.log (tạo file nếu chưa có)
$logFile = __DIR__ . DIRECTORY_SEPARATOR . 'loichuc.log';

if (@file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX) === false) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Cannot write loichuc.log']);
    exit;
}

echo json_encode(['ok' => true]);

