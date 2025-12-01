<?php
// save_rsvp.php
// Nhận thông tin xác nhận tham dự và ghi vào file rsvp.log

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

$name = isset($data['name']) ? trim($data['name']) : 'Khách';
$phone = isset($data['phone']) ? trim($data['phone']) : '';
$status = isset($data['status']) ? trim($data['status']) : '';
$note = isset($data['note']) ? trim($data['note']) : '';

if ($status === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing status']);
    exit;
}

$statusLabel = [
    'yes' => 'Tham dự',
    'no' => 'Vắng mặt',
    'maybe' => 'Cân nhắc',
][$status] ?? $status;

$time = date('Y-m-d H:i:s');
$lineParts = [
    "[$time]",
    $name,
    "({$statusLabel})",
];

if ($phone !== '') {
    $lineParts[] = "SĐT: {$phone}";
}
if ($note !== '') {
    $lineParts[] = "Ghi chú: {$note}";
}

$line = implode(' | ', $lineParts) . "\n";

$logFile = __DIR__ . DIRECTORY_SEPARATOR . 'rsvp.log';
if (@file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX) === false) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Cannot write rsvp.log']);
    exit;
}

echo json_encode(['ok' => true]);

