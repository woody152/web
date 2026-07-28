<?php
require('../_tgprivate.php');
// receiver.php - 带API Key验证的接收端

header('Content-Type: application/json');

// ==========================================
// API Key 配置
// ==========================================
//define('API_KEY', 'your-secret-key-2026'); // 请修改为复杂密钥
define('MAX_DATA_SIZE', 10 * 1024 * 1024); // 10MB限制

// ==========================================
// 验证 API Key
// ==========================================
$headers = getallheaders();
$apiKey = $headers['X-API-Key'] ?? $headers['x-api-key'] ?? '';

if (empty($apiKey)) {
    http_response_code(401);
    echo json_encode(['error' => 'Missing API Key']);
    exit;
}

if ($apiKey !== WECHAT_QMT_KEY) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid API Key']);
    exit;
}

// ==========================================
// 验证数据大小
// ==========================================
$jsonInput = file_get_contents('php://input');
if (strlen($jsonInput) > MAX_DATA_SIZE) {
    http_response_code(413);
    echo json_encode(['error' => 'Data too large (max 10MB)']);
    exit;
}

// ==========================================
// 验证JSON格式
// ==========================================
$decoded = json_decode($jsonInput, true);
if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON: ' . json_last_error_msg()]);
    exit;
}

// ==========================================
// 保存数据（原子写入）
// ==========================================
$dataFile = __DIR__ . '/data.json';
$tempFile = __DIR__ . '/data.tmp';

if (file_put_contents($tempFile, $jsonInput) !== false) {
    if (rename($tempFile, $dataFile)) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Data saved successfully',
            'rows' => isset($decoded['data']) ? count($decoded['data']) : 
                     (isset($decoded['columns']) && isset($decoded['data']) ? count($decoded['data']) : 0),
            'size_bytes' => strlen($jsonInput)
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to rename file']);
    }
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to write file']);
}
?>