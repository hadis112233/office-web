<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

const RATE_CACHE_TTL = 6 * 60 * 60;

function respond($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    respond(['ok' => false, 'error' => '汇率查询仅支持 GET 请求'], 405);
}

$dataDir = __DIR__ . '/../data/';
if (!is_dir($dataDir) && !@mkdir($dataDir, 0775, true)) {
    respond(['ok' => false, 'error' => '汇率缓存目录创建失败'], 500);
}

$cacheFile = $dataDir . 'exchange-rates.json';
$lockHandle = @fopen($dataDir . 'exchange-rates.lock', 'c');
if (!$lockHandle || !@flock($lockHandle, LOCK_EX)) {
    respond(['ok' => false, 'error' => '汇率服务正忙，请稍后重试'], 503);
}
register_shutdown_function(function () use ($lockHandle) {
    @flock($lockHandle, LOCK_UN);
    @fclose($lockHandle);
});

function readCache($cacheFile) {
    if (!is_file($cacheFile)) return null;
    $raw = @file_get_contents($cacheFile);
    $cache = $raw === false ? null : json_decode($raw, true);
    if (!is_array($cache)
        || !isset($cache['fetched_at'], $cache['date'], $cache['rates'])
        || !is_array($cache['rates'])) {
        return null;
    }
    return $cache;
}

function cacheResponse($cache, $stale = false) {
    return [
        'ok' => true,
        'base' => 'CNY',
        'date' => $cache['date'],
        'rates' => $cache['rates'],
        'source' => 'Frankfurter 参考汇率',
        'cached' => true,
        'stale' => $stale,
    ];
}

$cache = readCache($cacheFile);
if ($cache && time() - (int)$cache['fetched_at'] < RATE_CACHE_TTL) {
    respond(cacheResponse($cache));
}

$currencies = ['USD', 'EUR', 'GBP', 'JPY', 'KRW', 'AUD', 'CAD', 'CHF', 'HKD', 'SGD', 'THB', 'MYR', 'INR', 'RUB', 'BRL', 'MXN', 'ZAR', 'AED', 'TRY'];
$url = 'https://api.frankfurter.dev/v2/rates?base=CNY&quotes=' . rawurlencode(implode(',', $currencies));
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 8,
        'ignore_errors' => true,
        'header' => "Accept: application/json\r\nUser-Agent: office-tools/1.0\r\n",
    ],
    'ssl' => [
        'verify_peer' => true,
        'verify_peer_name' => true,
    ],
]);
$raw = @file_get_contents($url, false, $context);
$rows = $raw === false ? null : json_decode($raw, true);

$rates = ['CNY' => 1.0];
$date = null;
if (is_array($rows)) {
    foreach ($rows as $row) {
        $quote = $row['quote'] ?? '';
        $rate = $row['rate'] ?? null;
        if (($row['base'] ?? '') !== 'CNY'
            || !in_array($quote, $currencies, true)
            || !is_numeric($rate)
            || (float)$rate <= 0) {
            continue;
        }
        $rates[$quote] = (float)$rate;
        if (!$date && preg_match('/^\d{4}-\d{2}-\d{2}$/', $row['date'] ?? '')) {
            $date = $row['date'];
        }
    }
}

if (count($rates) >= 10 && $date) {
    $cache = ['fetched_at' => time(), 'date' => $date, 'rates' => $rates];
    $tempFile = $cacheFile . '.tmp-' . bin2hex(random_bytes(6));
    $encoded = json_encode($cache, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (@file_put_contents($tempFile, $encoded, LOCK_EX) !== false) {
        if (!@rename($tempFile, $cacheFile)) @unlink($tempFile);
    } else {
        @unlink($tempFile);
    }
    respond([
        'ok' => true,
        'base' => 'CNY',
        'date' => $date,
        'rates' => $rates,
        'source' => 'Frankfurter 参考汇率',
        'cached' => false,
        'stale' => false,
    ]);
}

if ($cache) {
    respond(cacheResponse($cache, true));
}

respond(['ok' => false, 'error' => '暂时无法获取汇率，且本机还没有缓存'], 503);
