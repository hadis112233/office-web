<?php
date_default_timezone_set('Asia/Shanghai');

$dataDir = __DIR__ . '/../data';
if (!file_exists($dataDir)) {
    @mkdir($dataDir, 0777, true);
}
$authFile = $dataDir . '/auth.json';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

function readAuth($authFile) {
    if (!file_exists($authFile)) return null;
    $raw = @file_get_contents($authFile);
    if (!$raw) return null;
    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}

function writeAuth($authFile, $data) {
    @file_put_contents($authFile, json_encode($data, JSON_UNESCAPED_UNICODE));
}

function todayCode() {
    return date('Ymd');
}

// 计算试用期结束时间：首次访问后 2 小时
function trialEndTime($firstVisit) {
    return $firstVisit + 2 * 3600;
}

if ($action === 'check') {
    $auth = readAuth($authFile);
    $now = time();

    if (!$auth) {
        $auth = [
            'first_visit' => $now,
            'last_auth' => 0,
            'expires_at' => 0
        ];
        writeAuth($authFile, $auth);
    }

    $authorized = false;
    $daysLeft = 0;
    $expiredRed = false;
    $inTrial = false;
    $trialHoursLeft = 0;
    $permanent = false;

    $trialEnd = trialEndTime($auth['first_visit']);

    // 优先检查是否永久授权
    if (!empty($auth['permanent']) && $auth['permanent'] === true) {
        $authorized = true;
        $permanent = true;
    } elseif (!empty($auth['last_auth']) && $auth['last_auth'] > 0) {
        $expiresAt = $auth['last_auth'] + 30 * 86400;
        if ($now < $expiresAt) {
            $authorized = true;
            $daysLeft = ceil(($expiresAt - $now) / 86400);
            if ($daysLeft <= 1) {
                $expiredRed = true;
            }
        } else {
            $expiredRed = true;
        }
    }

    // 未授权时，检查是否在试用期（2 小时）
    if (!$authorized && !empty($auth['first_visit']) && $auth['first_visit'] > 0 && $now < $trialEnd) {
        $inTrial = true;
        $remaining = $trialEnd - $now;
        $trialHoursLeft = max(1, ceil($remaining / 3600));
    }

    echo json_encode([
        'ok' => true,
        'authorized' => $authorized,
        'permanent' => $permanent,
        'in_trial' => $inTrial,
        'trial_hours_left' => $trialHoursLeft,
        'days_left' => $daysLeft,
        'expired_red' => $expiredRed,
        'today_code' => todayCode()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'verify') {
    $code = trim($_POST['code'] ?? '');
    $expected = todayCode();
    $auth = readAuth($authFile);
    if (!$auth) {
        $auth = ['first_visit' => time(), 'last_auth' => 0, 'expires_at' => 0];
    }

    if ($code !== $expected) {
        echo json_encode([
            'ok' => false,
            'error' => '授权码错误，请重新输入'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $auth['last_auth'] = time();
    $auth['permanent'] = true;
    writeAuth($authFile, $auth);

    echo json_encode([
        'ok' => true,
        'permanent' => true,
        'message' => '✅ 授权成功！已获得永久授权'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'reset') {
    $auth = readAuth($authFile);
    if (!$auth) {
        $auth = ['first_visit' => 0, 'last_auth' => 0, 'expires_at' => 0, 'permanent' => false];
    } else {
        $auth['last_auth'] = 0;
        $auth['expires_at'] = 0;
        $auth['permanent'] = false;
        // 重置后立即锁定：first_visit 设为 0，表示无试用期，必须授权
        $auth['first_visit'] = 0;
    }
    writeAuth($authFile, $auth);

    echo json_encode([
        'ok' => true,
        'message' => '已重置授权，当前为未授权状态'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['ok' => false, 'error' => '未知操作']);
