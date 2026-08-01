<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

$dataDir = __DIR__ . '/../data';
$mediaDir = $dataDir . '/media';
if (is_dir($mediaDir)) {
    $cutoff = time() - 60 * 60;
    foreach (glob($mediaDir . '/media_*') ?: [] as $path) {
        if (is_file($path) && filemtime($path) < $cutoff) {
            @unlink($path);
        }
    }
}

$checks = [
    'web' => true,
    'storage' => is_dir($dataDir) && is_writable($dataDir),
    'ffmpeg' => is_executable('/usr/bin/ffmpeg') || is_executable('/usr/local/bin/ffmpeg'),
    'ffprobe' => is_executable('/usr/bin/ffprobe') || is_executable('/usr/local/bin/ffprobe'),
    'timeout' => is_executable('/usr/bin/timeout') || is_executable('/usr/local/bin/timeout'),
];

$ok = !in_array(false, $checks, true);
http_response_code($ok ? 200 : 503);

echo json_encode([
    'ok' => $ok,
    'checks' => $checks,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
