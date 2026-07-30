<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

const MAX_VIDEO_BYTES = 150 * 1024 * 1024;
const EXPIRE_SECONDS = 60 * 60;

$mediaDir = __DIR__ . '/../data/media/';
if (!is_dir($mediaDir) && !@mkdir($mediaDir, 0775, true)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => '视频存储目录创建失败'], JSON_UNESCAPED_UNICODE);
    exit;
}

function respond($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function clean_media($dir) {
    foreach (glob($dir . 'media_*') ?: [] as $path) {
        if (is_file($path) && @filemtime($path) < time() - EXPIRE_SECONDS) {
            @unlink($path);
        }
    }
}

clean_media($mediaDir);
$action = $_GET['action'] ?? 'process';

if ($action === 'download') {
    $token = $_GET['token'] ?? '';
    if (!preg_match('/^media_[a-f0-9]{32}\.(gif|mp4)$/', $token)) {
        respond(['ok' => false, 'error' => '无效下载地址'], 400);
    }
    $file = $mediaDir . $token;
    if (!is_file($file)) {
        respond(['ok' => false, 'error' => '文件已过期，请重新处理'], 404);
    }
    header_remove('Content-Type');
    header('Content-Type: ' . (str_ends_with($token, '.gif') ? 'image/gif' : 'video/mp4'));
    header('Content-Length: ' . filesize($file));
    header('Content-Disposition: attachment; filename="' . $token . '"');
    header('X-Content-Type-Options: nosniff');
    ignore_user_abort(true);
    register_shutdown_function(function () use ($file) { @unlink($file); });
    readfile($file);
    exit;
}

if ($action !== 'process' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['ok' => false, 'error' => '请求方式不正确'], 405);
}
if (!function_exists('exec')) {
    respond(['ok' => false, 'error' => '服务器未启用视频处理组件'], 503);
}
if (!isset($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($_FILES['video']['tmp_name'])) {
    respond(['ok' => false, 'error' => '视频上传失败'], 400);
}

$upload = $_FILES['video'];
if ($upload['size'] <= 0 || $upload['size'] > MAX_VIDEO_BYTES) {
    respond(['ok' => false, 'error' => '视频大小需在 150 MB 以内'], 400);
}
$allowedExtensions = ['mp4', 'webm', 'mov', 'm4v'];
$ext = strtolower(pathinfo($upload['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExtensions, true)) {
    respond(['ok' => false, 'error' => '仅支持 MP4、WebM、MOV 或 M4V 视频'], 400);
}
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($upload['tmp_name']);
$allowedMimes = ['video/mp4', 'video/webm', 'video/quicktime', 'video/x-m4v', 'application/mp4'];
if (!in_array($mime, $allowedMimes, true)) {
    respond(['ok' => false, 'error' => '文件内容不是受支持的视频格式'], 415);
}

$mode = $_POST['mode'] ?? 'gif';
if (!in_array($mode, ['gif', 'compress'], true)) {
    respond(['ok' => false, 'error' => '无效处理模式'], 400);
}
$token = bin2hex(random_bytes(16));
$source = $mediaDir . 'media_' . $token . '.input.' . $ext;
if (!move_uploaded_file($upload['tmp_name'], $source)) {
    respond(['ok' => false, 'error' => '无法保存上传的视频'], 500);
}
$sourceSize = filesize($source);
register_shutdown_function(function () use ($source) { if (is_file($source)) @unlink($source); });

// 同一容器一次只执行一个转码任务，防止多个大视频同时耗尽 CPU 和内存。
$transcodeLock = @fopen($mediaDir . '.transcode.lock', 'c');
if (!$transcodeLock || !@flock($transcodeLock, LOCK_EX | LOCK_NB)) {
    respond(['ok' => false, 'error' => '已有视频正在处理，请稍后再试'], 429);
}
register_shutdown_function(function () use ($transcodeLock) {
    @flock($transcodeLock, LOCK_UN);
    @fclose($transcodeLock);
});

function number_in_range($value, $min, $max, $fallback) {
    $number = filter_var($value, FILTER_VALIDATE_FLOAT);
    return $number !== false && $number >= $min && $number <= $max ? $number : $fallback;
}

if ($mode === 'gif') {
    $start = number_in_range($_POST['start'] ?? 0, 0, 3600, 0);
    $duration = number_in_range($_POST['duration'] ?? 5, 1, 20, 5);
    $fps = (int) number_in_range($_POST['fps'] ?? 10, 5, 20, 10);
    $width = (int) number_in_range($_POST['width'] ?? 480, 160, 960, 480);
    $outputName = 'media_' . $token . '.gif';
    $output = $mediaDir . $outputName;
    $filter = 'fps=' . $fps . ',scale=' . $width . ':-2:flags=lanczos,split[s0][s1];[s0]palettegen=max_colors=128[p];[s1][p]paletteuse=dither=bayer';
    $command = 'ffmpeg -hide_banner -loglevel error -y -ss ' . escapeshellarg((string)$start) . ' -t ' . escapeshellarg((string)$duration) . ' -i ' . escapeshellarg($source) . ' -vf ' . escapeshellarg($filter) . ' -threads 2 ' . escapeshellarg($output) . ' 2>&1';
} else {
    $qualityMap = ['high' => 23, 'standard' => 28, 'small' => 32];
    $quality = $_POST['quality'] ?? 'standard';
    $crf = $qualityMap[$quality] ?? $qualityMap['standard'];
    $outputName = 'media_' . $token . '.mp4';
    $output = $mediaDir . $outputName;
    $command = 'ffmpeg -hide_banner -loglevel error -y -i ' . escapeshellarg($source) . ' -c:v libx264 -threads 2 -preset medium -crf ' . $crf . ' -c:a aac -b:a 128k -movflags +faststart ' . escapeshellarg($output) . ' 2>&1';
}

$lines = [];
$exitCode = 1;
exec($command, $lines, $exitCode);
@unlink($source);
if ($exitCode !== 0 || !is_file($output) || filesize($output) === 0) {
    @unlink($output);
    error_log('media transcode failed [' . $token . ']: ' . implode(' | ', array_slice($lines, -3)));
    respond(['ok' => false, 'error' => '处理失败，请确认视频编码受支持'], 422);
}
$outputSize = filesize($output);

respond([
    'ok' => true,
    'name' => $mode === 'gif' ? 'office-tool.gif' : 'office-tool-compressed.mp4',
    'size' => $outputSize,
    'input_size' => $sourceSize,
    'saved_percent' => $sourceSize > 0 ? round(max(0, 1 - $outputSize / $sourceSize) * 100, 1) : 0,
    'download_url' => 'media.php?action=download&token=' . rawurlencode($outputName),
]);
