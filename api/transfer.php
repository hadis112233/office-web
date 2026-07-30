<?php
error_reporting(0);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

while (ob_get_level() > 0) {
    ob_end_clean();
}
ob_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');
date_default_timezone_set('Asia/Shanghai');

function jsonOut($data, $status = 200) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$dir = __DIR__ . '/../data/transfer';
$index_file = $dir . '/index.json';
$max_size = 50 * 1024 * 1024;
$ttl = 10 * 60;

if (!is_dir($dir)) {
    @mkdir($dir, 0775, true);
}

// 所有读写索引的请求串行化，避免并发上传时覆盖彼此的提取码记录。
$lockHandle = @fopen($dir . '/.transfer.lock', 'c');
if (!$lockHandle || !@flock($lockHandle, LOCK_EX)) {
    jsonOut(['ok' => false, 'error' => '服务器正忙，请稍后重试']);
}
register_shutdown_function(function () use ($lockHandle) {
    @flock($lockHandle, LOCK_UN);
    @fclose($lockHandle);
});

function loadIndex($index_file) {
    if (!file_exists($index_file)) return [];
    $raw = @file_get_contents($index_file);
    if ($raw === false) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function saveIndex($index_file, $data) {
    try {
        $suffix = bin2hex(random_bytes(6));
    } catch (Throwable $e) {
        $suffix = str_replace('.', '', uniqid('', true));
    }
    $tmp = $index_file . '.tmp-' . $suffix;
    $ok = @file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    if ($ok === false) return false;
    if (@rename($tmp, $index_file)) return true;
    @unlink($tmp);
    return false;
}

function validStoredName($name) {
    return is_string($name) && preg_match('/^\d{4,6}_[a-f0-9]{16}$/', $name);
}

function cleanExpired($dir, $index_file, $ttl) {
    $now = time();
    $index = loadIndex($index_file);
    $changed = false;
    foreach ($index as $code => $item) {
        $valid = is_array($item)
            && validStoredName($item['file'] ?? '')
            && isset($item['time'])
            && is_numeric($item['time']);
        $filePath = $valid ? $dir . '/' . $item['file'] : '';
        if (!$valid || $now - (int)$item['time'] > $ttl || !is_file($filePath)) {
            if ($valid) @unlink($filePath);
            unset($index[$code]);
            $changed = true;
        }
    }
    if ($changed) saveIndex($index_file, $index);

    $referenced = [];
    foreach ($index as $item) {
        if (validStoredName($item['file'] ?? '')) $referenced[$item['file']] = true;
    }
    foreach (glob($dir . '/*') ?: [] as $path) {
        $name = basename($path);
        if (is_file($path)
            && validStoredName($name)
            && !isset($referenced[$name])
            && @filemtime($path) < $now - $ttl) {
            @unlink($path);
        }
    }
    return $index;
}

function enforceAttemptLimit($dir) {
    $now = time();
    $window = 5 * 60;
    $limit = 60;
    $client = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $file = $dir . '/attempts.json';
    $attempts = loadIndex($file);
    foreach ($attempts as $key => $item) {
        if (!is_array($item) || $now - (int)($item['start'] ?? 0) > $window) unset($attempts[$key]);
    }
    $item = $attempts[$client] ?? ['start' => $now, 'count' => 0];
    if ($now - (int)$item['start'] > $window) $item = ['start' => $now, 'count' => 0];
    $item['count'] = (int)$item['count'] + 1;
    $attempts[$client] = $item;
    if (!saveIndex($file, $attempts)) {
        jsonOut(['ok' => false, 'error' => '提取码限速状态保存失败，请稍后重试'], 503);
    }
    if ($item['count'] > $limit) {
        jsonOut(['ok' => false, 'error' => '提取码尝试过于频繁，请5分钟后再试'], 429);
    }
}

function sanitizeFilename($name) {
    if (!is_string($name) || $name === '') {
        return 'file';
    }
    $name = basename($name);
    if ($name === '' || $name === '.' || $name === '..') {
        return 'file';
    }
    $name = preg_replace('/[^a-zA-Z0-9\.\-\_\x80-\xff]/', '_', $name);
    if ($name === '' || $name === '.' || $name === '..') {
        return 'file';
    }
    return $name;
}

$action = $_GET['action'] ?? 'upload';

if ($action === 'upload') {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        jsonOut(['ok' => false, 'error' => '文件上传仅支持 POST 请求'], 405);
    }
    if (!isset($_FILES['file'])) {
        jsonOut(['ok' => false, 'error' => '未接收到文件']);
    }

    $file = $_FILES['file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errMsg = '文件上传失败';
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:   $errMsg = '文件超过 PHP upload_max_filesize 限制'; break;
            case UPLOAD_ERR_FORM_SIZE:  $errMsg = '文件超过表单 MAX_FILE_SIZE 限制'; break;
            case UPLOAD_ERR_PARTIAL:    $errMsg = '文件只有部分被上传'; break;
            case UPLOAD_ERR_NO_FILE:    $errMsg = '没有文件被上传'; break;
            case UPLOAD_ERR_NO_TMP_DIR: $errMsg = '服务器临时文件夹缺失'; break;
            case UPLOAD_ERR_CANT_WRITE: $errMsg = '文件写入失败，请检查目录权限'; break;
            case UPLOAD_ERR_EXTENSION:  $errMsg = 'PHP 扩展阻止了文件上传'; break;
        }
        jsonOut(['ok' => false, 'error' => $errMsg]);
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        jsonOut(['ok' => false, 'error' => '非法的上传文件']);
    }

    if ($file['size'] > $max_size) {
        jsonOut(['ok' => false, 'error' => '文件超过 50 MB']);
    }

    if ($file['size'] <= 0) {
        jsonOut(['ok' => false, 'error' => '文件为空']);
    }

    if (!is_dir($dir) || !is_writable($dir)) {
        @mkdir($dir, 0775, true);
        if (!is_writable($dir)) {
            jsonOut(['ok' => false, 'error' => '服务器存储目录不可写']);
        }
    }

    $index = cleanExpired($dir, $index_file, $ttl);

    $code = '';
    $used = [];
    foreach ($index as $k => $v) { $used[$k] = true; }
    for ($attempt = 0; $attempt < 200; $attempt++) {
        try {
            $candidate = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } catch (Throwable $e) {
            jsonOut(['ok' => false, 'error' => '安全提取码生成失败'], 500);
        }
        if (!isset($used[$candidate])) {
            $code = $candidate;
            break;
        }
    }
    if ($code === '') {
        jsonOut(['ok' => false, 'error' => '服务器繁忙，请稍后再试']);
    }

    $originalName = sanitizeFilename($file['name']);
    $rand = function_exists('random_bytes') ? bin2hex(random_bytes(8))
            : (function_exists('openssl_random_pseudo_bytes') ? bin2hex(openssl_random_pseudo_bytes(8))
            : substr(md5(uniqid('', true) . microtime(true)), 0, 16));
    $storedName = $code . '_' . $rand;
    $dest = $dir . '/' . $storedName;

    if (!@move_uploaded_file($file['tmp_name'], $dest)) {
        if (!@copy($file['tmp_name'], $dest)) {
            @unlink($file['tmp_name']);
            jsonOut(['ok' => false, 'error' => '文件保存失败，请检查目录权限']);
        }
        @unlink($file['tmp_name']);
    }

    $index = loadIndex($index_file);
    $index[$code] = [
        'file' => $storedName,
        'name' => $originalName,
        'size' => $file['size'],
        'time' => time(),
    ];
    if (!saveIndex($index_file, $index)) {
        @unlink($dest);
        jsonOut(['ok' => false, 'error' => '文件索引保存失败，请稍后重试']);
    }

    jsonOut([
        'ok' => true,
        'code' => $code,
        'name' => $originalName,
        'size' => $file['size'],
        'expires_in' => $ttl,
    ]);
}

if ($action === 'check') {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
        jsonOut(['ok' => false, 'error' => '提取码查询仅支持 GET 请求'], 405);
    }
    enforceAttemptLimit($dir);
    $code = trim($_GET['code'] ?? '');
    if (!preg_match('/^\d{6}$/', $code)) {
        jsonOut(['ok' => false, 'error' => '提取码必须是 6 位数字'], 400);
    }
    $index = cleanExpired($dir, $index_file, $ttl);
    if (!isset($index[$code])) {
        jsonOut(['ok' => false, 'error' => '提取码无效或已过期']);
    }
    $item = $index[$code];
    if (!file_exists($dir . '/' . $item['file'])) {
        jsonOut(['ok' => false, 'error' => '文件不存在']);
    }
    jsonOut([
        'ok' => true,
        'name' => $item['name'],
        'size' => $item['size'],
    ]);
}

if ($action === 'download') {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
        while (ob_get_level() > 0) { ob_end_clean(); }
        http_response_code(405);
        echo '文件下载仅支持 GET 请求';
        exit;
    }
    enforceAttemptLimit($dir);
    $code = trim($_GET['code'] ?? '');
    if (!preg_match('/^\d{6}$/', $code)) {
        while (ob_get_level() > 0) { ob_end_clean(); }
        http_response_code(400);
        echo '无效的提取码';
        exit;
    }
    $index = cleanExpired($dir, $index_file, $ttl);
    if (!isset($index[$code])) {
        while (ob_get_level() > 0) { ob_end_clean(); }
        http_response_code(404);
        echo '提取码无效或已过期';
        exit;
    }
    $item = $index[$code];
    $filePath = $dir . '/' . $item['file'];
    if (!file_exists($filePath)) {
        while (ob_get_level() > 0) { ob_end_clean(); }
        http_response_code(404);
        echo '文件不存在';
        exit;
    }

    $name = $item['name'];
    $size = filesize($filePath);

    $mime = 'application/octet-stream';
    if (function_exists('mime_content_type')) {
        $m = @mime_content_type($filePath);
        if ($m) $mime = $m;
    }

    unset($index[$code]);
    if (!saveIndex($index_file, $index)) {
        while (ob_get_level() > 0) { ob_end_clean(); }
        http_response_code(500);
        echo '文件状态更新失败，请重试下载';
        exit;
    }

    // 下载期间无需继续占用索引锁，允许其他用户上传或查询文件。
    @flock($lockHandle, LOCK_UN);

    while (ob_get_level() > 0) { ob_end_clean(); }
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . $size);
    header('Content-Disposition: attachment; filename="' . rawurlencode($name) . '"; filename*=UTF-8\'\'' . rawurlencode($name));
    header('Cache-Control: no-store, no-cache, must-revalidate');
    readfile($filePath);
    @unlink($filePath);
    exit;
}

jsonOut(['ok' => false, 'error' => 'unknown action']);
