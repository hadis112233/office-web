<?php
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

// 使用绝对路径（基于api目录自身位置），确保可靠
$uploadDir = __DIR__ . '/../data/uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// 清理过期文件（超过30分钟的）- 扫描所有扩展名
$expireTime = time() - 30 * 60;
foreach (glob($uploadDir . '*.{jpg,jpeg,png}', GLOB_BRACE) as $file) {
    if (filemtime($file) < $expireTime) {
        @unlink($file);
    }
}

if ($action === 'upload') {
    if (!isset($_FILES['file'])) {
        echo json_encode(['ok' => false, 'error' => '没有上传文件']);
        exit;
    }
    $file = $_FILES['file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['ok' => false, 'error' => '上传失败']);
        exit;
    }
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    if (!in_array(strtolower($ext), ['jpg', 'jpeg', 'png'])) {
        echo json_encode(['ok' => false, 'error' => '只支持JPG/PNG格式']);
        exit;
    }
    $filename = uniqid() . '.' . $ext;
    $savePath = $uploadDir . $filename;
    if (move_uploaded_file($file['tmp_name'], $savePath)) {
        echo json_encode(['ok' => true, 'filename' => $filename]);
    } else {
        echo json_encode(['ok' => false, 'error' => '保存失败']);
    }
    exit;
} elseif ($action === 'list') {
    $files = glob($uploadDir . '*.{jpg,jpeg,png}', GLOB_BRACE);
    $result = [];
    foreach ($files as $file) {
        $filename = basename($file);
        $result[] = [
            'filename' => $filename,
            'timestamp' => filemtime($file),
            'url' => 'data/uploads/' . $filename
        ];
    }
    usort($result, function($a, $b) { return $b['timestamp'] - $a['timestamp']; });
    echo json_encode(['ok' => true, 'files' => $result]);
    exit;
} elseif ($action === 'get') {
    $filename = $_GET['filename'] ?? '';
    if (!preg_match('/^[a-f0-9]+\.(jpg|jpeg|png)$/', $filename)) {
        echo json_encode(['ok' => false, 'error' => '无效文件名']);
        exit;
    }
    $filePath = $uploadDir . $filename;
    if (!file_exists($filePath)) {
        echo json_encode(['ok' => false, 'error' => '文件不存在']);
        exit;
    }
    $data = base64_encode(file_get_contents($filePath));
    echo json_encode(['ok' => true, 'data' => $data]);
    exit;
} elseif ($action === 'delete') {
    $filename = $_GET['filename'] ?? '';
    if (!preg_match('/^[a-f0-9]+\.(jpg|jpeg|png)$/', $filename)) {
        echo json_encode(['ok' => false, 'error' => '无效文件名']);
        exit;
    }
    $filePath = $uploadDir . $filename;
    if (file_exists($filePath)) {
        unlink($filePath);
        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['ok' => false, 'error' => '文件不存在']);
    }
    exit;
}

echo json_encode(['ok' => false, 'error' => '未知操作']);
?>