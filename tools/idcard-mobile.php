<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>身份证上传</title>
    <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        padding: 20px;
    }
    .container {
        max-width: 400px;
        margin: 0 auto;
    }
    .header {
        text-align: center;
        color: #fff;
        margin-bottom: 25px;
    }
    .header h1 {
        font-size: 24px;
        font-weight: 600;
        margin-bottom: 8px;
    }
    .header p {
        font-size: 14px;
        opacity: 0.9;
    }
    .card {
        background: #fff;
        border-radius: 16px;
        padding: 25px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        margin-bottom: 20px;
    }
    .card-title {
        font-size: 16px;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .upload-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 30px 20px;
        border: 2px dashed #cbd5e1;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.2s;
        background: #fafafa;
    }
    .upload-btn:hover {
        border-color: #6366f1;
        background: #f0f1fe;
    }
    .upload-btn:active {
        transform: scale(0.98);
    }
    .upload-icon {
        font-size: 48px;
        margin-bottom: 12px;
    }
    .upload-text {
        font-size: 15px;
        font-weight: 600;
        color: #475569;
        margin-bottom: 5px;
    }
    .upload-hint {
        font-size: 12px;
        color: #94a3b8;
    }
    .upload-btn input {
        display: none;
    }
    .preview-list {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin-top: 15px;
    }
    .preview-item {
        aspect-ratio: 1.6;
        border-radius: 10px;
        overflow: hidden;
        position: relative;
        background: #f1f5f9;
        border: 2px solid #e2e8f0;
    }
    .preview-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .preview-item.empty {
        display: flex;
        align-items: center;
        justify-content: center;
        color: #94a3b8;
        font-size: 12px;
    }
    .preview-label {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(0,0,0,0.6);
        color: #fff;
        font-size: 11px;
        text-align: center;
        padding: 4px 0;
    }
    .btn {
        width: 100%;
        padding: 14px 20px;
        border: none;
        border-radius: 12px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }
    .btn-primary {
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: #fff;
    }
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(99, 102, 241, 0.4);
    }
    .btn-primary:active {
        transform: translateY(0);
    }
    .btn-secondary {
        background: #f1f5f9;
        color: #475569;
        margin-top: 10px;
    }
    .btn-secondary:hover {
        background: #e2e8f0;
    }
    .btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none !important;
    }
    .status {
        text-align: center;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 15px;
        font-size: 14px;
    }
    .status.success {
        background: #dcfce7;
        color: #059669;
    }
    .status.error {
        background: #fee2e2;
        color: #dc2626;
    }
    .status.info {
        background: #dbeafe;
        color: #2563eb;
    }
    .progress-bar {
        height: 6px;
        background: #e2e8f0;
        border-radius: 3px;
        overflow: hidden;
        margin-bottom: 10px;
    }
    .progress-fill {
        height: 100%;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        border-radius: 3px;
        transition: width 0.3s;
    }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📷 身份证上传</h1>
            <p>拍照或上传身份证正反面照片</p>
        </div>

        <div class="card">
            <div class="card-title">📄 身份证正面</div>
            <label class="upload-btn" for="frontInput">
                <div class="upload-icon">🪪</div>
                <div class="upload-text">点击拍摄正面</div>
                <div class="upload-hint">支持拍照或从相册选择</div>
                <input type="file" id="frontInput" accept="image/*" capture="environment">
            </label>
        </div>

        <div class="card">
            <div class="card-title">🔄 身份证反面</div>
            <label class="upload-btn" for="backInput">
                <div class="upload-icon">📋</div>
                <div class="upload-text">点击拍摄反面</div>
                <div class="upload-hint">支持拍照或从相册选择</div>
                <input type="file" id="backInput" accept="image/*" capture="environment">
            </label>
        </div>

        <div class="card">
            <div class="card-title">👁️ 预览</div>
            <div class="preview-list">
                <div class="preview-item empty" id="frontPreview">
                    <span>正面</span>
                </div>
                <div class="preview-item empty" id="backPreview">
                    <span>反面</span>
                </div>
            </div>
        </div>

        <div class="card">
            <button class="btn btn-primary" id="uploadBtn" disabled>🚀 上传照片到电脑</button>
            <button class="btn btn-secondary" id="clearBtn">🗑️ 清空照片</button>
            <div class="progress-bar" hidden>
                <div class="progress-fill"></div>
            </div>
            <div class="status" id="status" hidden></div>
        </div>

        <div class="card">
            <div style="text-align:center;color:#64748b;font-size:13px;">
                <p>📱 照片上传后会自动同步到电脑端页面</p>
                <p style="margin-top:5px;">💾 文件会在30分钟后自动删除</p>
            </div>
        </div>
    </div>

    <script>
    let frontFile = null;
    let backFile = null;

    function $(id) { return document.getElementById(id); }

    $('frontInput').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            frontFile = file;
            showPreview(file, 'frontPreview', '正面');
            checkReady();
        }
    });

    $('backInput').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            backFile = file;
            showPreview(file, 'backPreview', '反面');
            checkReady();
        }
    });

    function showPreview(file, previewId, label) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = $(previewId);
            preview.className = 'preview-item';
            preview.innerHTML = `<img src="${e.target.result}" />
                                <div class="preview-label">${label}</div>`;
        };
        reader.readAsDataURL(file);
    }

    function checkReady() {
        $('uploadBtn').disabled = !frontFile;
    }

    $('clearBtn').addEventListener('click', function() {
        frontFile = null;
        backFile = null;
        $('frontPreview').className = 'preview-item empty';
        $('frontPreview').innerHTML = '<span>正面</span>';
        $('backPreview').className = 'preview-item empty';
        $('backPreview').innerHTML = '<span>反面</span>';
        $('frontInput').value = '';
        $('backInput').value = '';
        checkReady();
        $('status').hidden = true;
    });

    $('uploadBtn').addEventListener('click', async function() {
        const files = [];
        if (frontFile) files.push({ file: frontFile, type: 'front' });
        if (backFile) files.push({ file: backFile, type: 'back' });

        if (files.length === 0) return;

        const progressBar = document.querySelector('.progress-bar');
        const progressFill = document.querySelector('.progress-fill');
        const status = $('status');

        progressBar.hidden = false;
        status.hidden = false;
        status.className = 'status info';
        status.textContent = '正在上传...';
        $('uploadBtn').disabled = true;

        let successCount = 0;
        for (let i = 0; i < files.length; i++) {
            const item = files[i];
            progressFill.style.width = ((i) / files.length * 100) + '%';
            try {
                await uploadFile(item.file, item.type);
                successCount++;
                progressFill.style.width = ((i + 1) / files.length * 100) + '%';
            } catch (err) {
                status.className = 'status error';
                status.textContent = '上传失败: ' + err.message;
                progressBar.hidden = true;
                $('uploadBtn').disabled = false;
                return;
            }
        }

        status.className = 'status success';
        status.textContent = '✅ 成功上传 ' + successCount + ' 张照片！3秒后可继续上传';
        progressBar.hidden = true;
        
        setTimeout(function() {
            frontFile = null;
            backFile = null;
            const fp = $('frontPreview');
            const bp = $('backPreview');
            fp.className = 'preview-item empty';
            fp.innerHTML = '<span>正面</span>';
            bp.className = 'preview-item empty';
            bp.innerHTML = '<span>反面</span>';
            $('frontInput').value = '';
            $('backInput').value = '';
            checkReady();
            status.textContent = '📤 等待新照片...';
            status.className = 'status info';
        }, 3000);
    });

    async function uploadFile(file, type) {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('type', type);
        // 加时间戳防止微信浏览器缓存
        formData.append('_ts', Date.now());

        const response = await fetch('../api/upload.php?action=upload&_=' + Date.now(), {
            method: 'POST',
            body: formData,
            cache: 'no-store'
        });

        if (!response.ok) {
            throw new Error('网络错误 ' + response.status);
        }

        const result = await response.json();
        if (!result.ok) {
            throw new Error(result.error || '上传失败');
        }
        return result;
    }
    </script>
</body>
</html>