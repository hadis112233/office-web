<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

$dataDir = __DIR__ . '/../data';
$checks = [
    'web' => true,
    'storage' => is_dir($dataDir) && is_writable($dataDir),
    'ffmpeg' => is_executable('/usr/bin/ffmpeg') || is_executable('/usr/local/bin/ffmpeg'),
];

$ok = !in_array(false, $checks, true);
http_response_code($ok ? 200 : 503);

echo json_encode([
    'ok' => $ok,
    'checks' => $checks,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
