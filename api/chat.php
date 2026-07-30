<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('X-Content-Type-Options: nosniff');

$dir = __DIR__ . '/../data';
if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => '聊天数据目录创建失败'], JSON_UNESCAPED_UNICODE);
    exit;
}
$msg_file = $dir . '/messages.json';
$online_file = $dir . '/online.json';
$rate_file = $dir . '/chat-rates.json';

$action = $_GET['action'] ?? '';

function respond($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$lockHandle = @fopen($dir . '/.chat.lock', 'c');
if (!$lockHandle || !@flock($lockHandle, LOCK_EX)) {
    respond(['ok' => false, 'error' => '聊天服务正忙，请稍后重试'], 503);
}
register_shutdown_function(function () use ($lockHandle) {
    @flock($lockHandle, LOCK_UN);
    @fclose($lockHandle);
});

// Helpers
function load_json($file, $default) {
    if (!file_exists($file)) return $default;
    $raw = @file_get_contents($file);
    if ($raw === false) return $default;
    $data = json_decode($raw, true);
    return is_array($data) ? $data : $default;
}
function save_json($file, $data) {
    $dir = dirname($file);
    if (!is_dir($dir) && !@mkdir($dir, 0775, true)) return false;
    try {
        $suffix = bin2hex(random_bytes(4));
    } catch (Throwable $e) {
        $suffix = str_replace('.', '', uniqid('', true));
    }
    $tmp = $file . '.tmp.' . $suffix;
    if (@file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX) === false) return false;
    if (@rename($tmp, $file)) return true;
    @unlink($tmp);
    return false;
}
function valid_uid($uid) {
    return is_string($uid) && preg_match('/^[a-zA-Z0-9_-]{4,64}$/', $uid);
}
function sanitize_nick($nick) {
    $nick = preg_replace('/[\x00-\x1F\x7F]/u', '', strip_tags((string)$nick));
    $nick = trim($nick);
    return $nick === '' ? '访客' : mb_substr($nick, 0, 16);
}
function enforce_rate_limit($file, $bucket, $limit, $window) {
    $now = time();
    $client = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $key = $client . ':' . $bucket;
    $rates = load_json($file, []);
    foreach ($rates as $rateKey => $item) {
        if (!is_array($item) || $now - (int)($item['start'] ?? 0) > $window) unset($rates[$rateKey]);
    }
    $item = $rates[$key] ?? ['start' => $now, 'count' => 0];
    if ($now - (int)$item['start'] > $window) $item = ['start' => $now, 'count' => 0];
    $item['count'] = (int)$item['count'] + 1;
    $rates[$key] = $item;
    if (!save_json($file, $rates)) respond(['ok' => false, 'error' => '聊天限速状态保存失败'], 503);
    if ($item['count'] > $limit) respond(['ok' => false, 'error' => '操作过于频繁，请稍后再试'], 429);
}

// Keep online list fresh
$now = time();
$online = load_json($online_file, []);
$onlineChanged = false;
foreach ($online as $uid => $t) {
    if (!valid_uid($uid) || !is_numeric($t) || $now - (int)$t > 60) {
        unset($online[$uid]);
        $onlineChanged = true;
    }
}
if ($onlineChanged && !save_json($online_file, $online)) respond(['ok' => false, 'error' => '在线状态保存失败'], 500);

if ($action === 'list') {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') respond(['ok' => false, 'error' => '消息读取仅支持 GET 请求'], 405);
    $messages = load_json($msg_file, []);
    // 兼容旧版本没有消息 ID 的历史记录，使游标从首次升级后即可生效。
    $migrated = false;
    foreach ($messages as $index => &$message) {
        if (empty($message['id'])) {
            $message['id'] = 'legacy_' . substr(hash('sha256', ($message['time'] ?? 0) . '|' . ($message['uid'] ?? '') . '|' . ($message['text'] ?? '') . '|' . $index), 0, 16);
            $migrated = true;
        }
    }
    unset($message);
    if ($migrated && !save_json($msg_file, $messages)) respond(['ok' => false, 'error' => '历史消息升级失败'], 500);
    // Keep only last 100 messages and drop messages older than 48h
    $cutoff = $now - 48 * 3600;
    $filtered = [];
    foreach ($messages as $m) {
        if ($m['time'] >= $cutoff) $filtered[] = $m;
    }
    if (count($filtered) > 100) {
        $filtered = array_slice($filtered, -100);
    }
    if (count($filtered) !== count($messages)) {
        if (!save_json($msg_file, $filtered)) respond(['ok' => false, 'error' => '消息清理失败'], 500);
    }
    // cursor 是最后一条已看到的消息 ID；有游标时只返回新增消息，避免重复拉取。
    $cursor = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['cursor'] ?? '');
    $start = 0;
    $cursorFound = $cursor === '';
    if ($cursor !== '') {
        foreach ($filtered as $index => $message) {
            if (($message['id'] ?? '') === $cursor) { $start = $index + 1; $cursorFound = true; break; }
        }
    }
    $returned = $cursor !== '' && $cursorFound ? array_slice($filtered, $start) : $filtered;
    $last = end($filtered);
    echo json_encode([
        'messages' => $returned,
        'online' => count($online),
        'cursor' => $last['id'] ?? $cursor,
        'reset' => !$cursorFound,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'send') {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') respond(['ok' => false, 'error' => '发送仅支持 POST 请求'], 405);
    $text = trim($_POST['text'] ?? '');
    $uid = trim($_POST['uid'] ?? '');
    $nick = sanitize_nick($_POST['nick'] ?? '访客');
    $color = trim($_POST['color'] ?? '#6366f1');
    if ($text === '' || mb_strlen($text) > 500 || !valid_uid($uid)) {
        respond(['ok' => false, 'error' => '消息内容或用户标识无效'], 400);
    }
    enforce_rate_limit($rate_file, 'send', 20, 30);
    // 前端以 textContent 渲染；此处移除标签即可，避免保存后再次转义。
    $text = trim(strip_tags($text));
    if ($text === '') {
        respond(['ok' => false, 'error' => '消息不能为空'], 400);
    }
    if (!preg_match('/^#[a-fA-F0-9]{6}$/', $color)) $color = '#6366f1';
    $online = load_json($online_file, []);
    if (!isset($online[$uid]) && count($online) >= 200) {
        respond(['ok' => false, 'error' => '聊天室在线人数已达上限'], 503);
    }
    $messages = load_json($msg_file, []);
    // Rate limit: max 1 msg per 2s per uid
    $last = 0;
    for ($i = count($messages) - 1; $i >= 0; $i--) {
        if (($messages[$i]['uid'] ?? '') === $uid) { $last = $messages[$i]['time']; break; }
    }
    if ($now - $last < 2) {
        respond(['ok' => false, 'error' => '发送太快，请稍后再试'], 429);
    }
    $messages[] = [
        'id' => 'm_' . bin2hex(random_bytes(8)),
        'time' => $now,
        'uid' => $uid,
        'nick' => $nick,
        'color' => $color,
        'text' => $text,
    ];
    if (count($messages) > 200) {
        $messages = array_slice($messages, -200);
    }
    if (!save_json($msg_file, $messages)) respond(['ok' => false, 'error' => '消息保存失败'], 500);

    // Mark this user online
    $online[$uid] = $now;
    if (!save_json($online_file, $online)) respond(['ok' => false, 'error' => '在线状态保存失败'], 500);

    respond(['ok' => true]);
}

if ($action === 'heartbeat') {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') respond(['ok' => false, 'error' => '心跳仅支持 POST 请求'], 405);
    $uid = trim($_POST['uid'] ?? '');
    if (!valid_uid($uid)) {
        respond(['ok' => false, 'error' => '用户标识无效'], 400);
    }
    enforce_rate_limit($rate_file, 'heartbeat', 120, 60);
    $online = load_json($online_file, []);
    if (!isset($online[$uid]) && count($online) >= 200) {
        respond(['ok' => false, 'error' => '聊天室在线人数已达上限'], 503);
    }
    $online[$uid] = $now;
    if (!save_json($online_file, $online)) respond(['ok' => false, 'error' => '在线状态保存失败'], 500);
    respond(['ok' => true, 'online' => count($online)]);
}

respond(['ok' => false, 'error' => '未知操作'], 404);
