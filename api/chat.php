<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$dir = __DIR__ . '/../data';
if (!is_dir($dir)) {
    @mkdir($dir, 0777, true);
}
$msg_file = $dir . '/messages.json';
$online_file = $dir . '/online.json';

$action = $_GET['action'] ?? '';

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
    if (!is_dir($dir)) @mkdir($dir, 0777, true);
    $tmp = $file . '.tmp.' . bin2hex(random_bytes(4));
    file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_UNICODE));
    @rename($tmp, $file);
}

// Keep online list fresh
$now = time();
$online = load_json($online_file, []);
foreach ($online as $uid => $t) {
    if ($now - $t > 60) unset($online[$uid]);
}
save_json($online_file, $online);

if ($action === 'list') {
    $messages = load_json($msg_file, []);
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
        save_json($msg_file, $filtered);
    }
    echo json_encode([
        'messages' => $filtered,
        'online' => count($online),
        'last_time' => $now,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'send') {
    $text = trim($_POST['text'] ?? '');
    $uid = trim($_POST['uid'] ?? '');
    $nick = trim($_POST['nick'] ?? '访客');
    $color = trim($_POST['color'] ?? '#6366f1');
    if ($text === '' || mb_strlen($text) > 500 || $uid === '') {
        echo json_encode(['ok' => false, 'error' => 'invalid input'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    // Basic sanitize - strip tags but keep text
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    $messages = load_json($msg_file, []);
    // Rate limit: max 1 msg per 2s per uid
    $last = 0;
    for ($i = count($messages) - 1; $i >= 0; $i--) {
        if (($messages[$i]['uid'] ?? '') === $uid) { $last = $messages[$i]['time']; break; }
    }
    if ($now - $last < 2) {
        echo json_encode(['ok' => false, 'error' => 'too fast'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $messages[] = [
        'time' => $now,
        'uid' => $uid,
        'nick' => htmlspecialchars($nick, ENT_QUOTES, 'UTF-8'),
        'color' => htmlspecialchars($color, ENT_QUOTES, 'UTF-8'),
        'text' => $text,
    ];
    if (count($messages) > 200) {
        $messages = array_slice($messages, -200);
    }
    save_json($msg_file, $messages);

    // Mark this user online
    $online = load_json($online_file, []);
    $online[$uid] = $now;
    save_json($online_file, $online);

    echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'heartbeat') {
    $uid = trim($_POST['uid'] ?? '') ?: ('anon_' . substr(md5(($_SERVER['REMOTE_ADDR'] ?? '') . ($_SERVER['HTTP_USER_AGENT'] ?? '')), 0, 8));
    $online = load_json($online_file, []);
    $online[$uid] = $now;
    save_json($online_file, $online);
    echo json_encode(['ok' => true, 'online' => count($online)], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'unknown action'], JSON_UNESCAPED_UNICODE);
