<?php
// clear.php - 清空数据文件（带API Key验证）

define('API_KEY', 'your-secret-key-2026');

// 验证API Key
$apiKey = $_GET['api_key'] ?? $_POST['api_key'] ?? '';
$headers = getallheaders();
if (empty($apiKey)) {
    $apiKey = $headers['X-API-Key'] ?? $headers['x-api-key'] ?? '';
}

if ($apiKey !== API_KEY) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid API Key']);
    exit;
}

// 清空数据
$dataFile = __DIR__ . '/data.json';
if (file_exists($dataFile)) {
    unlink($dataFile);
}

http_response_code(200);
echo json_encode(['success' => true]);
?>