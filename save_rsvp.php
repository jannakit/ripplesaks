<?php
// save_rsvp.php
// Nhận thông tin xác nhận tham dự và ghi vào file rsvp.log

// Cho phép CORS để có thể gọi từ client
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Max-Age: 3600');

// Xử lý CORS preflight request (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

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

// Lấy và validate dữ liệu
$name = isset($data['name']) ? trim($data['name']) : 'Khách';
$phone = isset($data['phone']) ? trim($data['phone']) : '';
$status = isset($data['status']) ? trim($data['status']) : '';
$note = isset($data['note']) ? trim($data['note']) : '';

if ($status === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing status']);
    exit;
}

// Validate status hợp lệ
$validStatuses = ['yes', 'no', 'maybe'];
if (!in_array($status, $validStatuses)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid status']);
    exit;
}

$statusLabel = [
    'yes' => 'Tham dự',
    'no' => 'Vắng mặt',
    'maybe' => 'Cân nhắc',
][$status];

// Lấy IP address của client (nếu có)
$ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
}

// Escape các ký tự đặc biệt để tránh làm hỏng format log
function cleanLogText($text) {
    return str_replace(["\n", "\r", "|"], [" ", " ", " "], $text);
}

$name = cleanLogText($name);
$phone = cleanLogText($phone);
$note = cleanLogText($note);

// Tạo dòng log với format dễ đọc
$time = date('Y-m-d H:i:s');
$lineParts = [
    "[$time]",
    "IP: $ip",
    "Tên: $name",
    "Trạng thái: {$statusLabel}",
];

if ($phone !== '') {
    $lineParts[] = "SĐT: $phone";
}
if ($note !== '') {
    $lineParts[] = "Ghi chú: $note";
}

$line = implode(' | ', $lineParts) . "\n";

// Ghi vào file log
$logFile = __DIR__ . DIRECTORY_SEPARATOR . 'rsvp.log';

// Kiểm tra và tạo thư mục nếu cần
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}

// Kiểm tra quyền ghi file
if (file_exists($logFile) && !is_writable($logFile)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'File rsvp.log is not writable', 'file' => $logFile]);
    exit;
}

// Kiểm tra quyền ghi thư mục
if (!is_writable($logDir)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Directory is not writable', 'dir' => $logDir]);
    exit;
}

// Ghi file với error handling tốt hơn
$result = @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
if ($result === false) {
    $error = error_get_last();
    http_response_code(500);
    echo json_encode([
        'ok' => false, 
        'error' => 'Cannot write rsvp.log',
        'file' => $logFile,
        'php_error' => $error ? $error['message'] : 'Unknown error'
    ]);
    exit;
}

echo json_encode(['ok' => true, 'message' => 'RSVP saved successfully', 'file' => $logFile]);

