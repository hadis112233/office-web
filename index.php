<?php
date_default_timezone_set('Asia/Shanghai');
$webp_images = glob('static/images/*.{webp,WEBP}', GLOB_BRACE) ?: [];
$fallback_images = glob('static/images/*.{jpg,jpeg,png,JPG,JPEG,PNG}', GLOB_BRACE) ?: [];
$bg_images = array_merge($webp_images, $fallback_images);
$bg_image = !empty($bg_images) ? $bg_images[0] : '';
$style_version = @filemtime(__DIR__ . '/css/style.css') ?: time();
$script_version = @filemtime(__DIR__ . '/js/main.js') ?: time();
$theme_version = @filemtime(__DIR__ . '/js/theme.js') ?: time();
$bg_url = $bg_image !== '' ? $bg_image . '?v=' . (@filemtime(__DIR__ . '/' . $bg_image) ?: time()) : '';
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
    <meta name="description" content="办公工具站提供 PDF、图片、文本处理与快捷文件传输等常用办公工具。">
    <meta name="theme-color" content="#4f46e5">
    <title>办公工具站 - 您的一站式办公助手</title>
    <script>(function(){try{var t=localStorage.getItem('office_theme');if(t==='dark'||(!t&&matchMedia('(prefers-color-scheme:dark)').matches))document.documentElement.classList.add('theme-dark');}catch(e){}})();</script>
    <link rel="stylesheet" href="css/style.css?v=<?php echo $style_version; ?>">
    <script defer src="js/theme.js?v=<?php echo $theme_version; ?>"></script>
</head>
<body data-bg="<?php echo htmlspecialchars($bg_url, ENT_QUOTES, 'UTF-8'); ?>">
    <a class="skip-link" href="#main-content">跳到主要内容</a>
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
                <a href="#section-office" class="nav-item" data-target="section-office">
                    <span class="nav-icon">🧰</span><span>办公辅助</span>
                </a>
                <a href="#section-document" class="nav-item" data-target="section-document">
                    <span class="nav-icon">📊</span><span>表格文档</span>
                </a>
                <a href="#section-productivity" class="nav-item" data-target="section-productivity">
                    <span class="nav-icon">🎯</span><span>效率演示</span>
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
                <a href="#section-media" class="nav-item" data-target="section-media">
                    <span class="nav-icon">🎬</span><span>音/视频</span>
                </a>
            </nav>
            <div class="sidebar-footer">
                <span>© <?php echo date('Y'); ?> 办公工具站</span>
            </div>
        </aside>

        <main class="main" id="main-content" tabindex="-1">
            <section class="welcome-hero" id="top">
                <div class="welcome-inner">
                    <h1 class="welcome-title">欢迎使用办公工具站 👋</h1>
                    <div class="tool-search" role="search">
                        <label class="sr-only" for="tool-search-input">搜索工具</label>
                        <span class="tool-search-icon" aria-hidden="true">⌕</span>
                        <input id="tool-search-input" type="search" placeholder="搜索工具，例如：压缩、二维码、转换…" autocomplete="off">
                        <kbd title="按 Ctrl + K 快速搜索">Ctrl K</kbd>
                        <button class="tool-search-clear" id="tool-search-clear" type="button" aria-label="清空搜索" hidden>×</button>
                    </div>
                    <p class="tool-search-status" id="tool-search-status" role="status" aria-live="polite"></p>
                    <div class="recent-tools" id="recent-tools" hidden>
                        <span>最近使用</span>
                        <div id="recent-tools-list"></div>
                    </div>
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
                                    <button class="transfer-action-btn" data-modal="send-modal" aria-haspopup="dialog" aria-controls="send-modal" aria-expanded="false">
                                        <span class="tbtn-icon">📤</span>
                                        <div>
                                            <div class="tbtn-main">发送文件</div>
                                            <div class="tbtn-sub">拖拽上传 · 生成提取码</div>
                                        </div>
                                    </button>
                                    <button class="transfer-action-btn" data-modal="receive-modal" aria-haspopup="dialog" aria-controls="receive-modal" aria-expanded="false">
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
                        <div class="tool-name">二维码生成与识别</div>
                        <div class="tool-desc">生成二维码，识别图片或截图内容</div>
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
                    <a class="tool-card" href="tools/salary-calculator.php">
                        <div class="tool-icon">🧮</div>
                        <div class="tool-name">工资计算器</div>
                        <div class="tool-desc">估算个税、社保公积金和到手工资</div>
                    </a>
                    <a class="tool-card" href="tools/screen-test.php">
                        <div class="tool-icon">🖥️</div>
                        <div class="tool-name">在线屏幕测试</div>
                        <div class="tool-desc">全屏切换色卡，辅助检查显示异常</div>
                    </a>

                </div>
            </section>

            <section class="tool-section" id="section-office">
                <h2 class="section-title">🧰 办公辅助</h2>
                <div class="tool-grid">
                    <a class="tool-card" href="tools/date-calculator.php">
                        <div class="tool-icon">📆</div>
                        <div class="tool-name">日期计算器</div>
                        <div class="tool-desc">计算日期间隔、工作日和到期日期</div>
                    </a>
                    <a class="tool-card" href="tools/unit-converter.php">
                        <div class="tool-icon">📏</div>
                        <div class="tool-name">单位换算</div>
                        <div class="tool-desc">长度、重量、面积、温度和容量换算</div>
                    </a>
                    <a class="tool-card" href="tools/percentage-calculator.php">
                        <div class="tool-icon">％</div>
                        <div class="tool-name">百分比计算</div>
                        <div class="tool-desc">占比、增减比例和涨跌幅计算</div>
                    </a>
                    <a class="tool-card" href="tools/text-diff.php">
                        <div class="tool-icon">↔️</div>
                        <div class="tool-name">文本对比</div>
                        <div class="tool-desc">逐行找出两段文本的增删差异</div>
                    </a>
                    <a class="tool-card" href="tools/random.php">
                        <div class="tool-icon">🎲</div>
                        <div class="tool-name">安全随机生成</div>
                        <div class="tool-desc">安全随机数、强密码、颜色和 UUID</div>
                    </a>
                    <a class="tool-card" href="tools/hash-checker.php">
                        <div class="tool-icon">🧾</div>
                        <div class="tool-name">文件哈希校验</div>
                        <div class="tool-desc">本地计算并核对文件校验值</div>
                    </a>
                </div>
            </section>

            <section class="tool-section" id="section-document">
                <h2 class="section-title">📊 表格与文档</h2>
                <div class="tool-grid">
                    <a class="tool-card" href="tools/csv-helper.php">
                        <div class="tool-icon">📊</div>
                        <div class="tool-name">CSV / JSON 表格助手</div>
                        <div class="tool-desc">预览、清洗并双向转换表格数据</div>
                    </a>
                    <a class="tool-card" href="tools/rmb-uppercase.php">
                        <div class="tool-icon">💴</div>
                        <div class="tool-name">人民币大写</div>
                        <div class="tool-desc">金额转换为财务票据中文大写</div>
                    </a>
                    <a class="tool-card" href="tools/rich-text-editor.php">
                        <div class="tool-icon">📝</div>
                        <div class="tool-name">富文本编辑器</div>
                        <div class="tool-desc">本地排版、自动保存、打印和导出</div>
                    </a>
                </div>
            </section>

            <section class="tool-section" id="section-productivity">
                <h2 class="section-title">🎯 效率与演示</h2>
                <div class="tool-grid">
                    <a class="tool-card" href="tools/signature-pad.php">
                        <div class="tool-icon">✍️</div>
                        <div class="tool-name">电子签名板</div>
                        <div class="tool-desc">手写签名并导出透明 PNG 图片</div>
                    </a>
                    <a class="tool-card" href="tools/screen-recorder.php">
                        <div class="tool-icon">⏺️</div>
                        <div class="tool-name">屏幕录制</div>
                        <div class="tool-desc">录制屏幕或窗口并下载 WebM 视频</div>
                    </a>
                    <a class="tool-card" href="tools/pomodoro.php">
                        <div class="tool-icon">🍅</div>
                        <div class="tool-name">番茄专注钟</div>
                        <div class="tool-desc">专注与休息循环、提醒和次数统计</div>
                    </a>
                </div>
            </section>

            <section class="tool-section favorite-section" id="favorite-section" hidden>
                <h2 class="section-title">⭐ 我的收藏</h2>
                <div class="tool-grid" id="favorite-grid"></div>
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
                    <a class="tool-card" href="tools/pdf-organize.php">
                        <div class="tool-icon">🗂️</div>
                        <div class="tool-name">PDF 页面整理</div>
                        <div class="tool-desc">排序、旋转和删除 PDF 页面</div>
                    </a>
                    <a class="tool-card" href="tools/pdf-remove-blank.php">
                        <div class="tool-icon">🧹</div>
                        <div class="tool-name">PDF 空白页清理</div>
                        <div class="tool-desc">自动检测、确认并删除空白页面</div>
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
                    <a class="tool-card" href="tools/pdf-to-text.php">
                        <div class="tool-icon">📋</div>
                        <div class="tool-name">PDF 提取文字</div>
                        <div class="tool-desc">提取可选择文字并导出 TXT</div>
                    </a>
                    <a class="tool-card" href="tools/pdf-watermark.php">
                        <div class="tool-icon">💧</div>
                        <div class="tool-name">PDF 加水印</div>
                        <div class="tool-desc">为 PDF 添加文字水印</div>
                    </a>
                    <a class="tool-card" href="tools/pdf-page-numbers.php">
                        <div class="tool-icon">🔢</div>
                        <div class="tool-name">PDF 添加页码</div>
                        <div class="tool-desc">自定义范围、编号和页码位置</div>
                    </a>
                    <a class="tool-card" href="tools/pdf-metadata.php">
                        <div class="tool-icon">🏷️</div>
                        <div class="tool-name">PDF 文档属性</div>
                        <div class="tool-desc">查看、编辑或清空常见元数据</div>
                    </a>
                    <a class="tool-card" href="tools/images-to-pdf.php">
                        <div class="tool-icon">🖼️</div>
                        <div class="tool-name">图片转 PDF</div>
                        <div class="tool-desc">多张图片排序并合成为 PDF</div>
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
                        <div class="tool-desc">为图片添加右下角文字水印</div>
                    </a>
                    <a class="tool-card" href="tools/image-base64.php">
                        <div class="tool-icon">🔡</div>
                        <div class="tool-name">图片 Base64</div>
                        <div class="tool-desc">图片与 Base64 互转</div>
                    </a>
                    <a class="tool-card" href="tools/image-stitch.php">
                        <div class="tool-icon">🧩</div>
                        <div class="tool-name">图片拼接</div>
                        <div class="tool-desc">纵向、横向或网格拼接多张图片</div>
                    </a>
                    <a class="tool-card" href="tools/image-color-palette.php">
                        <div class="tool-icon">🎯</div>
                        <div class="tool-name">图片取色</div>
                        <div class="tool-desc">点击取色并提取主色调色板</div>
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
                    <a class="tool-card" href="tools/json-formatter.php">
                        <div class="tool-icon">🧱</div>
                        <div class="tool-name">JSON 格式化</div>
                        <div class="tool-desc">格式化、压缩、校验与错误定位</div>
                    </a>
                </div>
            </section>

            <section class="tool-section" id="section-media">
                <h2 class="section-title">🎬 音/视频工具</h2>
                <div class="tool-grid">
                    <a class="tool-card" href="tools/video-process.php">
                        <div class="tool-icon">🎞️</div>
                        <div class="tool-name">视频转 GIF</div>
                        <div class="tool-desc">截取视频片段，生成便于分享的 GIF</div>
                    </a>
                    <a class="tool-card" href="tools/video-process.php?mode=compress">
                        <div class="tool-icon">🗜️</div>
                        <div class="tool-name">视频压缩</div>
                        <div class="tool-desc">在清晰度与文件体积间灵活取舍</div>
                    </a>
                    <a class="tool-card" href="tools/screen-test.php">
                        <div class="tool-icon">🖥️</div>
                        <div class="tool-name">在线屏幕测试</div>
                        <div class="tool-desc">15 种纯色全屏切换测试</div>
                    </a>
                </div>
            </section>

            <footer class="page-footer">
                办公工具站 · 让工作更简单
            </footer>
            <div class="search-empty" id="search-empty" hidden>
                <div aria-hidden="true">🔎</div>
                <strong>没有找到匹配的工具</strong>
                <span>试试“PDF”“图片”“转换”等关键词</span>
            </div>
        </main>
    </div>

    <button class="floating-btn" id="chat-toggle" title="匿名聊天" aria-label="打开匿名聊天室" aria-controls="chat-modal" aria-expanded="false">
        <span>💬</span>
    </button>

    <div class="chat-modal" id="chat-modal" role="dialog" aria-modal="true" aria-labelledby="chat-dialog-title" hidden>
        <div class="chat-panel">
            <div class="chat-header">
                <div class="chat-title">
                    <span class="chat-avatar">💬</span>
                    <div>
                        <div class="chat-name" id="chat-dialog-title">匿名聊天室</div>
                        <div class="chat-sub">在线访客 · <span id="online-count">0</span></div>
                    </div>
                </div>
                <button class="chat-close" id="chat-close" title="关闭" aria-label="关闭匿名聊天室">✕</button>
            </div>
            <div class="chat-messages" id="chat-messages"></div>
            <div class="chat-footer">
                <input type="text" id="chat-input" placeholder="输入消息，匿名发送给在线访客..." maxlength="500">
                <button id="chat-send">发送</button>
            </div>
        </div>
    </div>

    <!-- 发送文件弹框 -->
    <div class="tfile-modal" id="send-modal" role="dialog" aria-modal="true" aria-labelledby="send-modal-title" hidden>
        <div class="tfile-panel">
            <div class="tfile-header">
                <div class="tfile-title" id="send-modal-title">📤 发送文件</div>
                <button class="tfile-close" data-close-modal aria-label="关闭发送文件窗口">✕</button>
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
                    <div class="result-text">上传成功！请记住您的 <strong>6 位提取码</strong>：</div>
                    <div class="result-code" id="send-result-code">------</div>
                    <div class="result-name" id="send-result-name"></div>
                    <div class="result-tip" id="send-result-expiry">该文件在被接收后将立即从服务器删除，最长保留 10 分钟。</div>
                    <button class="btn small" data-close-modal style="margin-top:10px;">关闭</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 接收文件弹框 -->
    <div class="tfile-modal" id="receive-modal" role="dialog" aria-modal="true" aria-labelledby="receive-modal-title" hidden>
        <div class="tfile-panel">
            <div class="tfile-header">
                <div class="tfile-title" id="receive-modal-title">📥 接收文件</div>
                <button class="tfile-close" data-close-modal aria-label="关闭接收文件窗口">✕</button>
            </div>
            <div class="tfile-body">
                <div class="tfile-hint">请输入对方分享的 6 位数字提取码</div>
                <div class="receive-box">
                    <div class="receive-row receive-row-head">
                        <div class="receive-icon">🔑</div>
                        <div class="receive-label">请输入 6 位提取码</div>
                    </div>
                    <div class="receive-row receive-row-input">
                        <input type="text" id="receive-code-input" maxlength="6" placeholder="例如 123456" inputmode="numeric" autocomplete="one-time-code">
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

    <script src="js/main.js?v=<?php echo $script_version; ?>"></script>
</body>
</html>
