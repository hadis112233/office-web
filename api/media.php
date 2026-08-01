<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

const MAX_VIDEO_BYTES = 150 * 1024 * 1024;
const MAX_OUTPUT_BYTES = 180 * 1024 * 1024;
const MAX_VIDEO_DURATION = 60 * 60;
const MAX_VIDEO_DIMENSION = 4096;
const MAX_VIDEO_PIXELS = 4096 * 2160;
const EXPIRE_SECONDS = 60 * 60;
const RATE_WINDOW_SECONDS = 10 * 60;
const RATE_MAX_ATTEMPTS = 6;

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

function media_binary($name) {
    foreach (["/usr/bin/$name", "/usr/local/bin/$name"] as $path) {
        if (is_executable($path)) return $path;
    }
    return null;
}

function enforce_media_rate($dir) {
    $path = $dir . '.process-rates.json';
    $handle = @fopen($path, 'c+');
    if (!$handle || !@flock($handle, LOCK_EX)) {
        if ($handle) @fclose($handle);
        return false;
    }
    rewind($handle);
    $stored = json_decode(stream_get_contents($handle) ?: '{}', true);
    $stored = is_array($stored) ? $stored : [];
    $now = time();
    foreach ($stored as $key => $attempts) {
        $stored[$key] = array_values(array_filter((array)$attempts, fn($time) => is_int($time) && $time > $now - RATE_WINDOW_SECONDS));
        if (!$stored[$key]) unset($stored[$key]);
    }
    $clientKey = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $attempts = $stored[$clientKey] ?? [];
    $allowed = count($attempts) < RATE_MAX_ATTEMPTS;
    if ($allowed) {
        $attempts[] = $now;
        $stored[$clientKey] = $attempts;
    }
    rewind($handle);
    ftruncate($handle, 0);
    fwrite($handle, json_encode($stored, JSON_UNESCAPED_SLASHES));
    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);
    return $allowed;
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
$ffmpegBinary = media_binary('ffmpeg');
$ffprobeBinary = media_binary('ffprobe');
$timeoutBinary = media_binary('timeout');
if (!$ffmpegBinary || !$ffprobeBinary || !$timeoutBinary) {
    respond(['ok' => false, 'error' => '服务器视频处理组件不完整'], 503);
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

if (!enforce_media_rate($mediaDir)) {
    header('Retry-After: ' . RATE_WINDOW_SECONDS);
    respond(['ok' => false, 'error' => '处理次数过多，请 10 分钟后再试'], 429);
}

$probeCommand = escapeshellarg($timeoutBinary) . ' --signal=TERM --kill-after=2s 10s ' .
    escapeshellarg($ffprobeBinary) . ' -v error -show_entries ' .
    escapeshellarg('format=duration:stream=codec_type,width,height') . ' -of json ' .
    escapeshellarg($source) . ' 2>&1';
$probeLines = [];
$probeExit = 1;
exec($probeCommand, $probeLines, $probeExit);
$probe = $probeExit === 0 ? json_decode(implode("\n", $probeLines), true) : null;
$videoStream = null;
foreach (($probe['streams'] ?? []) as $stream) {
    if (($stream['codec_type'] ?? '') === 'video') { $videoStream = $stream; break; }
}
$videoDuration = (float)($probe['format']['duration'] ?? 0);
$videoWidth = (int)($videoStream['width'] ?? 0);
$videoHeight = (int)($videoStream['height'] ?? 0);
if (!$videoStream || !is_finite($videoDuration) || $videoDuration <= 0 || $videoWidth <= 0 || $videoHeight <= 0) {
    respond(['ok' => false, 'error' => '无法读取视频时长或分辨率，请确认文件完整'], 422);
}
if ($videoDuration > MAX_VIDEO_DURATION) {
    respond(['ok' => false, 'error' => '视频时长不能超过 1 小时'], 413);
}
if ($videoWidth > MAX_VIDEO_DIMENSION || $videoHeight > MAX_VIDEO_DIMENSION || $videoWidth * $videoHeight > MAX_VIDEO_PIXELS) {
    respond(['ok' => false, 'error' => '视频分辨率过高，最高支持约 4K'], 413);
}

function number_in_range($value, $min, $max, $fallback) {
    $number = filter_var($value, FILTER_VALIDATE_FLOAT);
    return $number !== false && $number >= $min && $number <= $max ? $number : $fallback;
}

if ($mode === 'gif') {
    $start = number_in_range($_POST['start'] ?? 0, 0, 3600, 0);
    $duration = number_in_range($_POST['duration'] ?? 5, 1, 20, 5);
    $fps = (int) number_in_range($_POST['fps'] ?? 10, 5, 20, 10);
    $width = (int) number_in_range($_POST['width'] ?? 480, 160, 960, 480);
    if ($start >= $videoDuration || $start + $duration > $videoDuration + 0.5) {
        respond(['ok' => false, 'error' => '截取时间超出视频实际时长'], 400);
    }
    $outputName = 'media_' . $token . '.gif';
    $output = $mediaDir . $outputName;
    $filter = 'fps=' . $fps . ',scale=' . $width . ':-2:flags=lanczos,split[s0][s1];[s0]palettegen=max_colors=128[p];[s1][p]paletteuse=dither=bayer';
    $command = escapeshellarg($timeoutBinary) . ' --signal=TERM --kill-after=5s 115s ' . escapeshellarg($ffmpegBinary) . ' -nostdin -hide_banner -loglevel error -y -ss ' . escapeshellarg((string)$start) . ' -t ' . escapeshellarg((string)$duration) . ' -i ' . escapeshellarg($source) . ' -vf ' . escapeshellarg($filter) . ' -threads 2 -fs ' . MAX_OUTPUT_BYTES . ' ' . escapeshellarg($output) . ' 2>&1';
} else {
    $qualityMap = ['high' => 23, 'standard' => 28, 'small' => 32];
    $quality = $_POST['quality'] ?? 'standard';
    $crf = $qualityMap[$quality] ?? $qualityMap['standard'];
    $outputName = 'media_' . $token . '.mp4';
    $output = $mediaDir . $outputName;
    $command = escapeshellarg($timeoutBinary) . ' --signal=TERM --kill-after=5s 115s ' . escapeshellarg($ffmpegBinary) . ' -nostdin -hide_banner -loglevel error -y -i ' . escapeshellarg($source) . ' -c:v libx264 -threads 2 -preset medium -crf ' . $crf . ' -fpsmax 60 -pix_fmt yuv420p -c:a aac -b:a 128k -sn -dn -movflags +faststart -fs ' . MAX_OUTPUT_BYTES . ' ' . escapeshellarg($output) . ' 2>&1';
}

$lines = [];
$exitCode = 1;
exec($command, $lines, $exitCode);
@unlink($source);
if (in_array($exitCode, [124, 137], true)) {
    @unlink($output);
    respond(['ok' => false, 'error' => '处理超过 115 秒，已自动停止；请缩短视频或降低分辨率'], 408);
}
if ($exitCode !== 0 || !is_file($output) || filesize($output) === 0) {
    @unlink($output);
    error_log('media transcode failed [' . $token . ']: ' . implode(' | ', array_slice($lines, -3)));
    respond(['ok' => false, 'error' => '处理失败，请确认视频编码受支持'], 422);
}
$outputSize = filesize($output);
if ($outputSize >= MAX_OUTPUT_BYTES) {
    @unlink($output);
    respond(['ok' => false, 'error' => '生成文件超过 180 MB，已停止保存'], 413);
}

respond([
    'ok' => true,
    'name' => $mode === 'gif' ? 'office-tool.gif' : 'office-tool-compressed.mp4',
    'size' => $outputSize,
    'input_size' => $sourceSize,
    'saved_percent' => $sourceSize > 0 ? round(max(0, 1 - $outputSize / $sourceSize) * 100, 1) : 0,
    'download_url' => 'media.php?action=download&token=' . rawurlencode($outputName),
]);
