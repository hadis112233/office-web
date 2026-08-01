<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

const MAX_IMAGE_BYTES = 12 * 1024 * 1024;
const MAX_IMAGE_PIXELS = 40000000;
const MAX_IMAGE_SIDE = 16384;
const RATE_WINDOW = 10 * 60;
const RATE_MAX_UPLOADS = 20;
const IMAGE_TTL = 30 * 60;

function respond($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function validSession($session) {
    return is_string($session) && preg_match('/^[a-f0-9]{32}$/', $session);
}

function validFilename($filename) {
    return is_string($filename) && preg_match('/^(front|back)-[a-f0-9]{32}\.(jpg|png)$/', $filename);
}

function imageType($filename) {
    return preg_match('/^(front|back)-/', $filename, $matches) ? $matches[1] : null;
}

function loadRates($path) {
    if (!is_file($path)) return [];
    $data = json_decode((string)@file_get_contents($path), true);
    return is_array($data) ? $data : [];
}

function saveRates($path, $rates) {
    try {
        $suffix = bin2hex(random_bytes(6));
    } catch (Throwable $error) {
        $suffix = str_replace('.', '', uniqid('', true));
    }
    $temporary = $path . '.tmp-' . $suffix;
    if (@file_put_contents($temporary, json_encode($rates), LOCK_EX) === false) return false;
    if (@rename($temporary, $path)) return true;
    @unlink($temporary);
    return false;
}

function enforceUploadRate($uploadRoot) {
    $now = time();
    $client = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $path = $uploadRoot . '.upload-rates.json';
    $rates = loadRates($path);
    foreach ($rates as $key => $item) {
        if (!is_array($item) || $now - (int)($item['start'] ?? 0) > RATE_WINDOW) unset($rates[$key]);
    }
    $item = $rates[$client] ?? ['start' => $now, 'count' => 0];
    if ($now - (int)$item['start'] > RATE_WINDOW) $item = ['start' => $now, 'count' => 0];
    $item['count'] = (int)$item['count'] + 1;
    $rates[$client] = $item;
    if (!saveRates($path, $rates)) respond(['ok' => false, 'error' => '上传限速状态保存失败'], 503);
    if ($item['count'] > RATE_MAX_UPLOADS) respond(['ok' => false, 'error' => '上传过于频繁，请 10 分钟后再试'], 429);
}

$action = $_GET['action'] ?? '';
$session = $_POST['session'] ?? $_GET['session'] ?? '';
if (!validSession($session)) {
    respond(['ok' => false, 'error' => '上传配对码无效，请重新扫描电脑上的二维码'], 400);
}

$uploadRoot = __DIR__ . '/../data/uploads/';
if (!is_dir($uploadRoot) && !@mkdir($uploadRoot, 0775, true)) {
    respond(['ok' => false, 'error' => '图片存储目录创建失败'], 500);
}

$lockHandle = @fopen($uploadRoot . '.upload.lock', 'c');
if (!$lockHandle || !@flock($lockHandle, LOCK_EX)) {
    respond(['ok' => false, 'error' => '图片服务正忙，请稍后重试'], 503);
}
register_shutdown_function(function () use ($lockHandle) {
    @flock($lockHandle, LOCK_UN);
    @fclose($lockHandle);
});

$expireBefore = time() - IMAGE_TTL;
foreach (glob($uploadRoot . '*') ?: [] as $sessionPath) {
    if (is_file($sessionPath)
        && preg_match('/^[a-f0-9]{13,32}\.(jpg|jpeg|png)$/', basename($sessionPath))
        && @filemtime($sessionPath) < $expireBefore) {
        @unlink($sessionPath);
        continue;
    }
    if (!is_dir($sessionPath) || !validSession(basename($sessionPath))) continue;
    foreach (glob($sessionPath . '/*') ?: [] as $path) {
        if (is_file($path) && validFilename(basename($path)) && @filemtime($path) < $expireBefore) {
            @unlink($path);
        }
    }
    if (!(glob($sessionPath . '/*') ?: [])) {
        @rmdir($sessionPath);
    }
}

$uploadDir = $uploadRoot . $session . '/';
if (!is_dir($uploadDir) && !@mkdir($uploadDir, 0775, true)) {
    respond(['ok' => false, 'error' => '本次上传目录创建失败'], 500);
}

if ($action === 'upload') {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        respond(['ok' => false, 'error' => '上传仅支持 POST 请求'], 405);
    }
    if (!isset($_FILES['file'])) {
        respond(['ok' => false, 'error' => '没有收到图片'], 400);
    }
    enforceUploadRate($uploadRoot);
    $file = $_FILES['file'];
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        respond(['ok' => false, 'error' => '图片上传失败'], 400);
    }
    if ($file['size'] <= 0 || $file['size'] > MAX_IMAGE_BYTES) {
        respond(['ok' => false, 'error' => '图片大小需在 12 MB 以内'], 400);
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $extensionMap = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
    if (!isset($extensionMap[$mime])) {
        respond(['ok' => false, 'error' => '仅支持真实的 JPG 或 PNG 图片'], 415);
    }
    $dimensions = @getimagesize($file['tmp_name']);
    $width = is_array($dimensions) ? (int)($dimensions[0] ?? 0) : 0;
    $height = is_array($dimensions) ? (int)($dimensions[1] ?? 0) : 0;
    if ($width < 1 || $height < 1 || $width > MAX_IMAGE_SIDE || $height > MAX_IMAGE_SIDE || $width * $height > MAX_IMAGE_PIXELS) {
        respond(['ok' => false, 'error' => '图片尺寸过大，单边最多 16384 像素且总计不超过 4000 万像素'], 413);
    }

    $type = $_POST['type'] ?? '';
    if (!in_array($type, ['front', 'back'], true)) {
        respond(['ok' => false, 'error' => '请标明身份证正面或反面'], 400);
    }
    $filename = $type . '-' . bin2hex(random_bytes(16)) . '.' . $extensionMap[$mime];
    if (!@move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        respond(['ok' => false, 'error' => '图片保存失败'], 500);
    }
    respond(['ok' => true, 'filename' => $filename, 'type' => $type]);
}

if ($action === 'list') {
    $files = [];
    foreach (glob($uploadDir . '*') ?: [] as $path) {
        $filename = basename($path);
        if (!is_file($path) || !validFilename($filename)) continue;
        $files[] = ['filename' => $filename, 'type' => imageType($filename), 'timestamp' => @filemtime($path) ?: 0];
    }
    usort($files, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);
    respond(['ok' => true, 'files' => $files]);
}

if ($action === 'get') {
    $filename = $_GET['filename'] ?? '';
    if (!validFilename($filename)) {
        respond(['ok' => false, 'error' => '无效文件名'], 400);
    }
    $filePath = $uploadDir . $filename;
    if (!is_file($filePath)) {
        respond(['ok' => false, 'error' => '图片不存在或已过期'], 404);
    }
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $mime = $extension === 'png' ? 'image/png' : 'image/jpeg';
    $size = @filesize($filePath);
    if ($size === false) respond(['ok' => false, 'error' => '图片读取失败'], 500);
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . $size);
    header('Content-Disposition: inline; filename="' . $filename . '"');
    if (@readfile($filePath) === false) respond(['ok' => false, 'error' => '图片读取失败'], 500);
    exit;
}

if ($action === 'delete') {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        respond(['ok' => false, 'error' => '删除仅支持 POST 请求'], 405);
    }
    $filename = $_POST['filename'] ?? '';
    if (!validFilename($filename)) {
        respond(['ok' => false, 'error' => '无效文件名'], 400);
    }
    $filePath = $uploadDir . $filename;
    if (is_file($filePath) && !@unlink($filePath)) {
        respond(['ok' => false, 'error' => '图片删除失败'], 500);
    }
    respond(['ok' => true]);
}

respond(['ok' => false, 'error' => '未知操作'], 404);
