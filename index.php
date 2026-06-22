<?php
date_default_timezone_set('Asia/Shanghai');
$bg_images = glob('static/images/*.{jpg,jpeg,png,JPG,JPEG,PNG}', GLOB_BRACE);
$bg_image = !empty($bg_images) ? $bg_images[0] : '';
$welcome_messages = [
    '今天也要元气满满哦！',
    '努力工作，成就未来！',
    '一步一步，终将抵达彼岸！',
    '保持专注，相信自己！',
    '每一次努力都不会白费！',
    '优秀是一种习惯！',
    '今天的汗水，是明天的光辉！',
    '相信自己，你比想象中更强大！',
    '坚持就是胜利！',
    '愿你的努力都被温柔以待！',
];
$quote = $welcome_messages[date('j') % count($welcome_messages)];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>办公工具站 - 您的一站式办公助手</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body data-bg="<?php echo $bg_image; ?>">
    <div class="app">
        <aside class="sidebar">
            <div class="logo">
                <span class="logo-icon">🛠</span>
                <span class="logo-text">办公工具站</span>
            </div>
            <nav class="nav">
                <a href="#section-common" class="nav-item active" data-target="section-common">
                    <span class="nav-icon">⭐</span><span>常用</span>
                </a>
                <a href="#section-pdf" class="nav-item" data-target="section-pdf">
                    <span class="nav-icon">📄</span><span>PDF</span>
                </a>
                <a href="#section-image" class="nav-item" data-target="section-image">
                    <span class="nav-icon">🖼️</span><span>图片</span>
                </a>
                <a href="#section-text" class="nav-item" data-target="section-text">
                    <span class="nav-icon">📝</span><span>文本工具</span>
                </a>
            </nav>
            <div class="auth-section">
                <button class="auth-btn" id="auth-btn" data-auth-state="unknown">
                    <span class="auth-icon">🔑</span>
                    <span class="auth-text">授权码</span>
                    <span class="auth-status" id="auth-status">检查中...</span>
                </button>
            </div>
            <div class="sidebar-footer">
                <span>© <?php echo date('Y'); ?> 办公工具站</span>
            </div>
        </aside>

        <main class="main">
            <section class="welcome-hero" id="top">
                <div class="welcome-inner">
                    <h1 class="welcome-title">欢迎使用办公工具站 👋</h1>
                    <div class="welcome-row">
                        <div class="welcome-left">
                            <div class="info-item time-item">
                                <span class="info-icon">🕐</span>
                                <div>
                                    <div class="info-label">当前时间</div>
                                    <div class="info-value" id="current-time"><?php echo date('Y年m月d日 H:i:s'); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="welcome-right">
                            <div class="transfer-buttons-card">
                                <div class="transfer-buttons-title">🚀 快捷文件传输</div>
                                <div class="transfer-buttons-row">
                                    <button class="transfer-action-btn" data-modal="send-modal">
                                        <span class="tbtn-icon">📤</span>
                                        <div>
                                            <div class="tbtn-main">发送文件</div>
                                            <div class="tbtn-sub">拖拽上传 · 生成提取码</div>
                                        </div>
                                    </button>
                                    <button class="transfer-action-btn" data-modal="receive-modal">
                                        <span class="tbtn-icon">📥</span>
                                        <div>
                                            <div class="tbtn-main">接收文件</div>
                                            <div class="tbtn-sub">输入提取码 · 下载文件</div>
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="tool-section" id="section-common">
                <h2 class="section-title">⭐ 常用工具</h2>
                <div class="tool-grid">
                    <a class="tool-card" href="tools/currency-convert.php">
                        <div class="tool-icon">💱</div>
                        <div class="tool-name">汇率换算</div>
                        <div class="tool-desc">世界主要货币汇率换算</div>
                    </a>
                    <a class="tool-card" href="tools/qrcode.php">
                        <div class="tool-icon">🔳</div>
                        <div class="tool-name">二维码生成</div>
                        <div class="tool-desc">文本、URL生成二维码</div>
                    </a>
                    <a class="tool-card" href="tools/idcard-print.php">
                        <div class="tool-icon">🪪</div>
                        <div class="tool-name">身份证打印</div>
                        <div class="tool-desc">扫描上传身份证，A4打印模板</div>
                    </a>
                    <a class="tool-card" href="tools/timer.php">
                        <div class="tool-icon">📅</div>
                        <div class="tool-name">工作计划</div>
                        <div class="tool-desc">每日工作计划与重要提醒</div>
                    </a>

                </div>
            </section>

            <section class="tool-section" id="section-pdf">
                <h2 class="section-title">📄 PDF 工具</h2>
                <div class="tool-grid">
                    <a class="tool-card" href="tools/pdf-merge.php">
                        <div class="tool-icon">🔗</div>
                        <div class="tool-name">PDF 合并</div>
                        <div class="tool-desc">将多个 PDF 合并为一个</div>
                    </a>
                    <a class="tool-card" href="tools/pdf-split.php">
                        <div class="tool-icon">✂️</div>
                        <div class="tool-name">PDF 分割</div>
                        <div class="tool-desc">从 PDF 中提取指定页</div>
                    </a>
                    <a class="tool-card" href="tools/pdf-compress.php">
                        <div class="tool-icon">🗜️</div>
                        <div class="tool-name">PDF 压缩</div>
                        <div class="tool-desc">减小 PDF 文件体积</div>
                    </a>
                    <a class="tool-card" href="tools/pdf-to-image.php">
                        <div class="tool-icon">📸</div>
                        <div class="tool-name">PDF 转图片</div>
                        <div class="tool-desc">将 PDF 每页导出为图片</div>
                    </a>
                    <a class="tool-card" href="tools/pdf-watermark.php">
                        <div class="tool-icon">💧</div>
                        <div class="tool-name">PDF 加水印</div>
                        <div class="tool-desc">为 PDF 添加文字水印</div>
                    </a>
                </div>
            </section>

            <section class="tool-section" id="section-image">
                <h2 class="section-title">🖼️ 图片工具</h2>
                <div class="tool-grid">
                    <a class="tool-card" href="tools/image-resize.php">
                        <div class="tool-icon">📐</div>
                        <div class="tool-name">图片尺寸调整</div>
                        <div class="tool-desc">改变图片宽度和高度</div>
                    </a>
                    <a class="tool-card" href="tools/image-compress.php">
                        <div class="tool-icon">🗜️</div>
                        <div class="tool-name">图片压缩</div>
                        <div class="tool-desc">压缩图片体积</div>
                    </a>
                    <a class="tool-card" href="tools/image-edit.php">
                        <div class="tool-icon">🎨</div>
                        <div class="tool-name">图片P图</div>
                        <div class="tool-desc">滤镜、裁剪、旋转、特效处理</div>
                    </a>
                    <a class="tool-card" href="tools/image-format.php">
                        <div class="tool-icon">🔄</div>
                        <div class="tool-name">格式转换</div>
                        <div class="tool-desc">JPG / PNG / WEBP / GIF 互转</div>
                    </a>
                    <a class="tool-card" href="tools/image-crop.php">
                        <div class="tool-icon">✂️</div>
                        <div class="tool-name">图片裁剪</div>
                        <div class="tool-desc">按区域裁剪图片</div>
                    </a>
                    <a class="tool-card" href="tools/image-watermark.php">
                        <div class="tool-icon">💧</div>
                        <div class="tool-name">图片加水印</div>
                        <div class="tool-desc">为图片添加文字或图片水印</div>
                    </a>
                    <a class="tool-card" href="tools/image-base64.php">
                        <div class="tool-icon">🔡</div>
                        <div class="tool-name">图片 Base64</div>
                        <div class="tool-desc">图片与 Base64 互转</div>
                    </a>
                </div>
            </section>

            <section class="tool-section" id="section-text">
                <h2 class="section-title">📝 文本工具</h2>
                <div class="tool-grid">
                    <a class="tool-card" href="tools/text-case.php">
                        <div class="tool-icon">🔡</div>
                        <div class="tool-name">大小写转换</div>
                        <div class="tool-desc">大写、小写、首字母大写</div>
                    </a>
                    <a class="tool-card" href="tools/text-count.php">
                        <div class="tool-icon">🔢</div>
                        <div class="tool-name">字数统计</div>
                        <div class="tool-desc">统计字符数、单词数、行数</div>
                    </a>
                    <a class="tool-card" href="tools/text-duplicate.php">
                        <div class="tool-icon">🗑️</div>
                        <div class="tool-name">去除空行重复</div>
                        <div class="tool-desc">去除空行、去重、去首尾空格</div>
                    </a>
                    <a class="tool-card" href="tools/text-base64.php">
                        <div class="tool-icon">🔣</div>
                        <div class="tool-name">Base64 编解码</div>
                        <div class="tool-desc">文本 Base64 编码与解码</div>
                    </a>
                    <a class="tool-card" href="tools/text-urlencode.php">
                        <div class="tool-icon">🔗</div>
                        <div class="tool-name">URL 编解码</div>
                        <div class="tool-desc">URL encode / decode</div>
                    </a>
                    <a class="tool-card" href="tools/text-markdown.php">
                        <div class="tool-icon">✨</div>
                        <div class="tool-name">Markdown 预览</div>
                        <div class="tool-desc">实时预览 Markdown 文本</div>
                    </a>
                </div>
            </section>

            <footer class="page-footer">
                办公工具站 · 让工作更简单
            </footer>
        </main>
    </div>

    <button class="floating-btn" id="chat-toggle" title="匿名聊天">
        <span>💬</span>
    </button>

    <div class="chat-modal" id="chat-modal" hidden>
        <div class="chat-panel">
            <div class="chat-header">
                <div class="chat-title">
                    <span class="chat-avatar">💬</span>
                    <div>
                        <div class="chat-name">匿名聊天室</div>
                        <div class="chat-sub">在线访客 · <span id="online-count">0</span></div>
                    </div>
                </div>
                <button class="chat-close" id="chat-close" title="关闭">✕</button>
            </div>
            <div class="chat-messages" id="chat-messages"></div>
            <div class="chat-footer">
                <input type="text" id="chat-input" placeholder="输入消息，匿名发送给在线访客..." maxlength="500">
                <button id="chat-send">发送</button>
            </div>
        </div>
    </div>

    <!-- 发送文件弹框 -->
    <div class="tfile-modal" id="send-modal" hidden>
        <div class="tfile-panel">
            <div class="tfile-header">
                <div class="tfile-title">📤 发送文件</div>
                <button class="tfile-close" data-close-modal>✕</button>
            </div>
            <div class="tfile-body">
                <div class="tfile-hint">拖拽文件到下方区域，或点击选择文件（最大 50 MB）</div>
                <label class="tfile-drop" id="send-transfer-drop">
                    <input type="file" id="send-transfer-file" hidden>
                    <div class="drop-icon">☁️</div>
                    <div class="drop-text">点击选择 <span class="drop-or">或</span> 拖拽文件到此处</div>
                    <div class="drop-hint">支持所有文件类型 · 单文件最大 50 MB</div>
                </label>
                <div class="transfer-progress" id="send-transfer-progress" hidden>
                    <div class="progress-bar"><div class="progress-fill" id="send-progress-fill"></div></div>
                    <div class="progress-text" id="send-progress-text">上传中 0%</div>
                </div>
                <div class="transfer-result" id="send-transfer-result" hidden>
                    <div class="result-icon">✅</div>
                    <div class="result-text">上传成功！请记住您的 <strong>4 位提取码</strong>：</div>
                    <div class="result-code" id="send-result-code">----</div>
                    <div class="result-name" id="send-result-name"></div>
                    <div class="result-tip">该文件在被接收后将立即从服务器删除，最长保留 1 分钟。</div>
                    <button class="btn small" data-close-modal style="margin-top:10px;">关闭</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 接收文件弹框 -->
    <div class="tfile-modal" id="receive-modal" hidden>
        <div class="tfile-panel">
            <div class="tfile-header">
                <div class="tfile-title">📥 接收文件</div>
                <button class="tfile-close" data-close-modal>✕</button>
            </div>
            <div class="tfile-body">
                <div class="tfile-hint">请输入对方分享的 4 位数字提取码</div>
                <div class="receive-box">
                    <div class="receive-row receive-row-head">
                        <div class="receive-icon">🔑</div>
                        <div class="receive-label">请输入 4 位提取码</div>
                    </div>
                    <div class="receive-row receive-row-input">
                        <input type="text" id="receive-code-input" maxlength="4" placeholder="例如 1234" inputmode="numeric">
                        <button class="btn" id="receive-file-btn">获取文件</button>
                    </div>
                    <div class="receive-msg" id="receive-file-msg"></div>
                    <div class="receive-row receive-row-download" id="receive-file-download" hidden>
                        <button type="button" class="btn success" id="receive-file-dl-btn" disabled>⬇ 下载文件</button>
                        <span class="receive-download-name" id="receive-file-dl-name"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 授权码弹框 -->
    <div class="auth-modal" id="auth-modal" hidden>
        <div class="auth-panel">
            <button class="auth-close" id="auth-close" title="关闭">✕</button>
            <div class="auth-header">
                <button class="auth-reset-btn" id="auth-reset-btn" title="重置授权" hidden>🔒</button>
                <div class="auth-title-icon">🔑</div>
                <h2 class="auth-title">授权码验证</h2>
                <div class="auth-sub" id="auth-modal-sub">授权码为当天日期</div>
            </div>
            <div class="auth-body">
                <input type="text" id="auth-code-input" maxlength="8" placeholder="请输入授权码" inputmode="numeric" autocomplete="off">
                <div class="auth-msg" id="auth-msg"></div>
                <button class="auth-submit" id="auth-submit">确认授权</button>
                <div class="auth-hint">授权后即可永久使用本系统</div>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
</body>
</html>
