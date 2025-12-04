<?php
// test_rsvp.php
// File test để kiểm tra API save_rsvp.php
// Chạy file này trong browser hoặc command line: php test_rsvp.php

// Tạo dữ liệu test
$testData = [
    'name' => 'Nguyễn Văn Test',
    'phone' => '0903123456',
    'status' => 'yes',
    'note' => 'Đi cùng 2 người'
];

// Chuẩn bị request
$url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/save_rsvp.php';
if (php_sapi_name() === 'cli') {
    // Nếu chạy từ command line
    $url = 'http://localhost' . dirname($_SERVER['SCRIPT_NAME']) . '/save_rsvp.php';
}

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

echo "Testing RSVP API...\n";
echo "URL: $url\n";
echo "Data: " . json_encode($testData, JSON_UNESCAPED_UNICODE) . "\n\n";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n\n";

$result = json_decode($response, true);
if ($result && isset($result['ok']) && $result['ok']) {
    echo "✓ Test thành công!\n";
    if (isset($result['file'])) {
        echo "File log: " . $result['file'] . "\n";
        if (file_exists($result['file'])) {
            echo "✓ File rsvp.log đã được tạo!\n";
            echo "\nNội dung file:\n";
            echo "---\n";
            echo file_get_contents($result['file']);
            echo "---\n";
        } else {
            echo "✗ File không tồn tại tại đường dẫn: " . $result['file'] . "\n";
        }
    }
} else {
    echo "✗ Test thất bại!\n";
    if ($result && isset($result['error'])) {
        echo "Lỗi: " . $result['error'] . "\n";
        if (isset($result['file'])) {
            echo "File: " . $result['file'] . "\n";
        }
        if (isset($result['php_error'])) {
            echo "PHP Error: " . $result['php_error'] . "\n";
        }
    }
}

