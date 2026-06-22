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
date_default_timezone_set('Asia/Shanghai');

function jsonOut($data) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$dir = __DIR__ . '/../data/transfer';
$index_file = $dir . '/index.json';
$max_size = 50 * 1024 * 1024;
$ttl = 60;

if (!is_dir($dir)) {
    @mkdir($dir, 0777, true);
}

function loadIndex($index_file) {
    if (!file_exists($index_file)) return [];
    $raw = @file_get_contents($index_file);
    if ($raw === false) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function saveIndex($index_file, $data) {
    $tmp = $index_file . '.tmp';
    $ok = @file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    if ($ok === false) {
        return @file_put_contents($index_file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) !== false;
    }
    return @rename($tmp, $index_file);
}

function cleanExpired($dir, $index_file, $ttl) {
    $now = time();
    $index = loadIndex($index_file);
    $changed = false;
    foreach ($index as $code => $item) {
        if ($now - $item['time'] > $ttl || !file_exists($dir . '/' . $item['file'])) {
            @unlink($dir . '/' . $item['file']);
            unset($index[$code]);
            $changed = true;
        }
    }
    if ($changed) saveIndex($index_file, $index);
    return $index;
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
        @mkdir($dir, 0777, true);
        if (!is_writable($dir)) {
            jsonOut(['ok' => false, 'error' => '服务器存储目录不可写']);
        }
    }

    $index = cleanExpired($dir, $index_file, $ttl);

    $code = '';
    $used = [];
    foreach ($index as $k => $v) { $used[$k] = true; }
    for ($attempt = 0; $attempt < 200; $attempt++) {
        $candidate = str_pad((string)mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
        if (!isset($used[$candidate])) {
            $code = $candidate;
            break;
        }
    }
    if ($code === '') {
        for ($i = 0; $i <= 9999; $i++) {
            $candidate = str_pad((string)$i, 4, '0', STR_PAD_LEFT);
            if (!isset($used[$candidate])) { $code = $candidate; break; }
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
    saveIndex($index_file, $index);

    jsonOut([
        'ok' => true,
        'code' => $code,
        'name' => $originalName,
        'size' => $file['size'],
    ]);
}

if ($action === 'check') {
    $code = trim($_GET['code'] ?? '');
    if (!preg_match('/^\d{4}$/', $code)) {
        jsonOut(['ok' => false, 'error' => '提取码必须是 4 位数字']);
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
    $code = trim($_GET['code'] ?? '');
    if (!preg_match('/^\d{4}$/', $code)) {
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
    saveIndex($index_file, $index);

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
