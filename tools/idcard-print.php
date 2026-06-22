<?php
$title = '身份证打印';
$desc = '扫描二维码拍照上传身份证，自动对齐并按A4模板打印。';
include '_header.php';
?>
            <div class="tool-panel">
                <h3>📱 扫码上传身份证</h3>
                <div class="upload-hero">
                    <div class="qr-section">
                        <p class="upload-hero-text">使用手机扫描下方二维码，拍照或上传身份证照片</p>
                        <div class="qr-box">
                            <img id="qrImg" alt="扫码上传" />
                        </div>
                        <p class="qr-hint">扫码后在手机端拍照上传，照片会自动同步到此页面</p>
                    </div>
                    <div class="upload-section">
                        <p class="sub-hint">或直接在此处上传：</p>
                        <label class="upload-box" for="idcardUpload">
                            <div class="upload-box-icon">📷</div>
                            <div class="upload-box-title">点击上传身份证照片</div>
                            <div class="upload-box-hint">支持 JPG / PNG 格式，可同时上传正反面</div>
                            <input type="file" id="idcardUpload" accept="image/*" hidden multiple>
                        </label>
                        <div class="upload-thumb-list" id="uploadedImages"></div>
                    </div>
                </div>
            </div>

            <div class="tool-panel">
                <h3>⚙️ 水印设置</h3>
                <label class="watermark-row">
                    <input type="checkbox" id="addWatermark">
                    <span>添加水印</span>
                </label>
                <div class="watermark-settings" id="watermarkSettings" hidden>
                    <div class="wm-item">
                        <label>水印文字</label>
                        <input type="text" id="watermarkText" placeholder="请输入水印文字，如：仅供XX使用" value="仅供参考">
                    </div>
                    <div class="wm-item">
                        <label>水印透明度</label>
                        <div class="opacity-row">
                            <input type="range" id="watermarkOpacity" min="10" max="80" value="30">
                            <span id="opacityValue">30%</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tool-panel" id="previewPanel" hidden>
                <h3>📄 A4 预览</h3>
                <div class="a4-paper" id="a4Paper">
                    <div class="a4-section">
                        <div class="idcard-placeholder" id="frontPlaceholder">
                            <div class="ph-label">身份证正面</div>
                        </div>
                    </div>
                    <div class="a4-section">
                        <div class="idcard-placeholder" id="backPlaceholder">
                            <div class="ph-label">身份证反面</div>
                        </div>
                    </div>
                </div>
                <div class="btn-row" style="margin-top:20px;">
                    <button class="btn success" onclick="exportImage()">📥 导出图片</button>
                    <button class="btn" onclick="printPage()">🖨️ 打印</button>
                    <button class="btn secondary" onclick="clearImages()">🗑️ 清空</button>
                </div>
            </div>

<style>
.upload-hero {
    display: flex;
    gap: 25px;
    align-items: stretch;
    flex-wrap: wrap;
}
.qr-section {
    flex: 0 0 300px;
    text-align: center;
    padding: 20px;
    background: linear-gradient(135deg, #eef2ff, #f0f1fe);
    border-radius: 12px;
}
.upload-hero-text {
    color: #475569;
    margin-bottom: 15px;
    font-size: 14px;
}
.qr-box {
    width: 180px;
    height: 180px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 15px;
    margin: 0 auto 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}
.qr-box img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}
.qr-hint {
    color: #94a3b8;
    font-size: 12px;
    margin: 0;
}
.upload-section {
    flex: 1;
    min-width: 300px;
}
.sub-hint {
    color: #64748b;
    font-size: 13px;
    text-align: center;
    margin: 0 0 12px 0;
}
.upload-box {
    display: block;
    padding: 35px 20px;
    border: 2px dashed #cbd5e1;
    border-radius: 12px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
    background: #fff;
}
.upload-box:hover {
    border-color: #6366f1;
    background: #f8faff;
    transform: translateY(-2px);
}
.upload-box-icon {
    font-size: 42px;
    margin-bottom: 8px;
}
.upload-box-title {
    font-size: 15px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 6px;
}
.upload-box-hint {
    font-size: 12px;
    color: #94a3b8;
}
.upload-thumb-list {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 15px;
}
.upload-thumb {
    position: relative;
    width: 120px;
    height: 80px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.2s;
}
.upload-thumb.active {
    border-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
}
.upload-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.upload-thumb-label {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(0,0,0,0.6);
    color: #fff;
    font-size: 11px;
    text-align: center;
    padding: 3px 0;
}
.watermark-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    background: #f8fafc;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    color: #334155;
}
.watermark-row input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}
.watermark-settings {
    margin-top: 12px;
    padding: 16px;
    background: #f8fafc;
    border-radius: 8px;
}
.wm-item {
    margin-bottom: 14px;
}
.wm-item:last-child {
    margin-bottom: 0;
}
.wm-item label {
    display: block;
    font-size: 13px;
    color: #475569;
    font-weight: 500;
    margin-bottom: 8px;
}
.wm-item input[type="text"] {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 14px;
    box-sizing: border-box;
}
.wm-item input[type="text"]:focus {
    outline: none;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}
.opacity-row {
    display: flex;
    align-items: center;
    gap: 15px;
}
.opacity-row input[type="range"] {
    flex: 1;
    height: 6px;
    border-radius: 3px;
    background: #e2e8f0;
    cursor: pointer;
    -webkit-appearance: none;
    appearance: none;
}
.opacity-row input[type="range"]::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #6366f1;
    cursor: pointer;
}
.opacity-row span {
    font-size: 14px;
    color: #6366f1;
    font-weight: 600;
    min-width: 50px;
}
.a4-paper {
    width: 100%;
    max-width: 600px;
    margin: 0 auto;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    overflow: hidden;
}
.a4-section {
    padding: 18px 20px;
    border-bottom: 1px dashed #e2e8f0;
}
.a4-section:last-child {
    border-bottom: none;
}
.idcard-placeholder {
    width: 100%;
    max-width: 420px;
    height: 250px;
    margin: 0 auto;
    border: 2px dashed #cbd5e1;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #94a3b8;
    font-size: 14px;
    position: relative;
    overflow: hidden;
    background: #fafafa;
}
.idcard-placeholder.has-image {
    border-color: #10b981;
    background: #fff;
}
.idcard-placeholder img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}
.ph-label {
    font-weight: 600;
}
.watermark-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    pointer-events: none;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    font-weight: bold;
    color: rgba(0,0,0,0.3);
    transform: rotate(-15deg);
    white-space: nowrap;
}
</style>

<script>
let frontImage = null;
let backImage = null;

function $(id) { return document.getElementById(id); }

// 切换水印开关
$('addWatermark').addEventListener('change', function() {
    $('watermarkSettings').hidden = !this.checked;
    updateWatermarks();
});

// 水印透明度滑块
$('watermarkOpacity').addEventListener('input', function() {
    $('opacityValue').textContent = this.value + '%';
    updateWatermarks();
});

// 水印文字输入
$('watermarkText').addEventListener('input', updateWatermarks);

// 更新所有水印
function updateWatermarks() {
    const enabled = $('addWatermark').checked;
    const text = $('watermarkText').value || '仅供参考';
    const opacity = $('watermarkOpacity').value / 100;
    document.querySelectorAll('.idcard-placeholder').forEach(function(el) {
        // 移除旧水印
        const old = el.querySelector('.watermark-overlay');
        if (old) old.remove();
        if (enabled) {
            const wm = document.createElement('div');
            wm.className = 'watermark-overlay';
            wm.style.color = 'rgba(0,0,0,' + opacity + ')';
            wm.textContent = text;
            el.appendChild(wm);
        }
    });
}

// 文件上传处理
$('idcardUpload').addEventListener('change', function(e) {
    const files = e.target.files;
    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        if (!file.type.match(/image\/.*/)) continue;
        const reader = new FileReader();
        reader.onload = function(ev) {
            const src = ev.target.result;
            addThumb(src);
            // 自动分配：第一张放正面，第二张放反面
            if (!frontImage) {
                setFrontImage(src);
            } else if (!backImage) {
                setBackImage(src);
            }
        };
        reader.readAsDataURL(file);
    }
    e.target.value = '';
});

// 添加缩略图
function addThumb(src) {
    const thumb = document.createElement('div');
    thumb.className = 'upload-thumb';
    const img = document.createElement('img');
    img.src = src;
    const label = document.createElement('div');
    label.className = 'upload-thumb-label';
    label.textContent = '点击设置';
    thumb.appendChild(img);
    thumb.appendChild(label);
    thumb.onclick = function() {
        const hasFront = document.querySelector('.upload-thumb[data-type="front"]');
        const hasBack = document.querySelector('.upload-thumb[data-type="back"]');
        if (thumb.dataset.type === 'front') {
            thumb.classList.remove('active');
            delete thumb.dataset.type;
            return;
        }
        if (!frontImage || thumb.dataset.type !== 'front') {
            if (hasFront) {
                hasFront.classList.remove('active');
                delete hasFront.dataset.type;
                const hf = hasFront.querySelector('.upload-thumb-label');
                if (hf) hf.textContent = '点击设置';
            }
            setFrontImage(src);
            thumb.classList.add('active');
            thumb.dataset.type = 'front';
            label.textContent = '正面';
            return;
        }
        if (!backImage || thumb.dataset.type !== 'back') {
            if (hasBack) {
                hasBack.classList.remove('active');
                delete hasBack.dataset.type;
                const hb = hasBack.querySelector('.upload-thumb-label');
                if (hb) hb.textContent = '点击设置';
            }
            setBackImage(src);
            thumb.classList.add('active');
            thumb.dataset.type = 'back';
            label.textContent = '反面';
        }
    };
    $('uploadedImages').appendChild(thumb);
}

// 添加带类型的缩略图（用于手机同步）
function addThumbWithType(src, type) {
    const thumb = document.createElement('div');
    thumb.className = 'upload-thumb active';
    thumb.dataset.type = type;
    const img = document.createElement('img');
    img.src = src;
    const label = document.createElement('div');
    label.className = 'upload-thumb-label';
    label.textContent = type === 'front' ? '正面' : '反面';
    thumb.appendChild(img);
    thumb.appendChild(label);
    $('uploadedImages').appendChild(thumb);
    return thumb;
}

function setFrontImage(src) {
    frontImage = src;
    const placeholder = $('frontPlaceholder');
    placeholder.classList.add('has-image');
    placeholder.innerHTML = '<img src="' + src + '" />';
    updateWatermarks();
    showPreview();
}

function setBackImage(src) {
    backImage = src;
    const placeholder = $('backPlaceholder');
    placeholder.classList.add('has-image');
    placeholder.innerHTML = '<img src="' + src + '" />';
    updateWatermarks();
    showPreview();
}

function showPreview() {
    $('previewPanel').hidden = false;
}

function clearImages() {
    frontImage = null;
    backImage = null;
    $('uploadedImages').innerHTML = '';
    const front = $('frontPlaceholder');
    const back = $('backPlaceholder');
    front.classList.remove('has-image');
    back.classList.remove('has-image');
    front.innerHTML = '<div class="ph-label">身份证正面</div>';
    back.innerHTML = '<div class="ph-label">身份证反面</div>';
    updateWatermarks();
}

// 导出为图片
function exportImage() {
    if (!frontImage && !backImage) {
        alert('请先上传身份证照片');
        return;
    }
    // 使用 canvas 绘制 A4 布局
    const canvas = document.createElement('canvas');
    const A4_WIDTH = 2480;
    const A4_HEIGHT = 3508;
    canvas.width = A4_WIDTH;
    canvas.height = A4_HEIGHT;
    const ctx = canvas.getContext('2d');

    // 白色背景
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, A4_WIDTH, A4_HEIGHT);

    const imgWidth = 1800;
    const imgHeight = 1100;
    const paddingTop = 200;
    const x = (A4_WIDTH - imgWidth) / 2;

    // 先绘制正面，完成后再绘制反面
    drawIdCardOnCanvas(ctx, frontImage, x, paddingTop, imgWidth, imgHeight, '身份证正面', function() {
        drawIdCardOnCanvas(ctx, backImage, x, paddingTop + imgHeight + 200, imgWidth, imgHeight, '身份证反面', function() {
            // 两张都绘制完成后导出
            const link = document.createElement('a');
            link.download = 'idcard-print.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
        });
    });
}

function drawIdCardOnCanvas(ctx, imgSrc, x, y, width, height, label, callback) {
    // 画边框
    ctx.strokeStyle = '#ddd';
    ctx.lineWidth = 3;
    ctx.setLineDash([15, 10]);
    ctx.strokeRect(x, y, width, height);
    ctx.setLineDash([]);

    function drawWatermark() {
        if ($('addWatermark').checked) {
            const text = $('watermarkText').value || '仅供参考';
            const opacity = $('watermarkOpacity').value / 100;
            ctx.save();
            ctx.globalAlpha = opacity;
            ctx.font = 'bold 100px Arial';
            ctx.fillStyle = '#000000';
            ctx.translate(x + width / 2, y + height / 2);
            ctx.rotate(-Math.PI / 12);
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(text, 0, 0);
            ctx.restore();
        }
    }

    if (imgSrc) {
        const img = new Image();
        img.onload = function() {
            // 等比填充
            const scale = Math.min(width / img.width, height / img.height);
            const drawW = img.width * scale;
            const drawH = img.height * scale;
            const drawX = x + (width - drawW) / 2;
            const drawY = y + (height - drawH) / 2;
            ctx.drawImage(img, drawX, drawY, drawW, drawH);
            drawWatermark();
            if (callback) callback();
        };
        img.onerror = function() {
            drawWatermark();
            if (callback) callback();
        };
        img.src = imgSrc;
    } else {
        // 没有图片时显示提示
        ctx.fillStyle = '#cbd5e1';
        ctx.font = '48px Arial';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(label, x + width / 2, y + height / 2);
        drawWatermark();
        if (callback) callback();
    }
}

// 打印
function printPage() {
    if (!frontImage && !backImage) {
        alert('请先上传身份证照片');
        return;
    }
    const printWindow = window.open('', '_blank');
    const frontSrc = frontImage || '';
    const backSrc = backImage || '';
    const watermarkEnabled = $('addWatermark').checked;
    const watermarkText = $('watermarkText').value || '仅供参考';
    const watermarkOpacity = $('watermarkOpacity').value / 100;

    let watermarkStyle = '';
    if (watermarkEnabled) {
        watermarkStyle = `
            <style>
            .wm-card { position: relative; }
            .wm-card::after {
                content: "${watermarkText}";
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%) rotate(-15deg);
                font-size: 60px;
                font-weight: bold;
                color: rgba(0, 0, 0, ${watermarkOpacity});
                pointer-events: none;
                white-space: nowrap;
            }
            </style>
        `;
    }

    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>身份证打印</title>
            <style>
                body { margin: 0; padding: 20px; background: #f1f5f9; font-family: Arial, sans-serif; }
                .page {
                    width: 210mm;
                    min-height: 297mm;
                    padding: 20mm;
                    margin: 0 auto;
                    background: white;
                    box-sizing: border-box;
                    box-shadow: 0 0 5px rgba(0,0,0,0.1);
                }
                .card-area {
                    border: 1px dashed #ccc;
                    padding: 20px;
                    margin-bottom: 30px;
                }
                .card-area:last-child { margin-bottom: 0; }
                .card-area img {
                    width: 100%;
                    height: auto;
                    display: block;
                }
                .empty-hint {
                    padding: 60px 20px;
                    text-align: center;
                    color: #aaa;
                    border: 2px dashed #ddd;
                    font-size: 18px;
                }
                @media print {
                    body { background: white; padding: 0; }
                    .page { box-shadow: none; margin: 0; }
                    @page { size: A4; margin: 0; }
                }
            </style>
            ${watermarkStyle}
        </head>
        <body>
            <div class="page">
                <div class="card-area wm-card">
                    ${frontSrc ? '<img src="' + frontSrc + '">' : '<div class="empty-hint">身份证正面（未上传）</div>'}
                </div>
                <div class="card-area wm-card">
                    ${backSrc ? '<img src="' + backSrc + '">' : '<div class="empty-hint">身份证反面（未上传）</div>'}
                </div>
            </div>
            <script>
                window.onload = function() {
                    setTimeout(function() {
                        window.print();
                    }, 500);
                };
            <\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
}

// 生成二维码（指向手机上传页面）
function generateQRCode() {
    const url = window.location.href.replace('idcard-print', 'idcard-mobile');
    const qrEl = $('qrImg');
    qrEl.src = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&margin=10&data=' + encodeURIComponent(url);
    qrEl.onerror = function() {
        qrEl.style.display = 'none';
        const parent = qrEl.parentNode;
        const fallback = document.createElement('div');
        fallback.style.cssText = 'font-size:12px;color:#94a3b8;text-align:center;';
        fallback.textContent = '二维码生成中...';
        parent.appendChild(fallback);
    };
}

// 轮询同步手机上传的照片
let lastSyncTime = Math.floor(Date.now() / 1000);
let syncInterval = null;
let syncStatus = null;

function startSync() {
    if (syncInterval) clearInterval(syncInterval);
    // 首次立即检查一次
    checkNewFiles();
    syncInterval = setInterval(checkNewFiles, 3000);
}

function stopSync() {
    if (syncInterval) {
        clearInterval(syncInterval);
        syncInterval = null;
    }
}

function showSyncStatus(message, type) {
    if (!syncStatus) {
        syncStatus = document.createElement('div');
        syncStatus.style.cssText = 'position:fixed;top:10px;right:10px;padding:8px 14px;background:#fff;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1);font-size:13px;z-index:1000;';
        document.body.appendChild(syncStatus);
        setTimeout(function() {
            if (syncStatus) {
                syncStatus.remove();
                syncStatus = null;
            }
        }, 3000);
    }
    syncStatus.textContent = message;
    syncStatus.style.color = type === 'error' ? '#dc2626' : (type === 'success' ? '#059669' : '#2563eb');
}

async function checkNewFiles() {
    try {
        // 添加时间戳参数防止缓存
        const ts = Date.now();
        const response = await fetch('../api/upload.php?action=list&_=' + ts);
        if (!response.ok) {
            throw new Error('HTTP ' + response.status);
        }
        const result = await response.json();
        if (!result.ok) {
            throw new Error(result.error || 'API错误');
        }
        
        const newFiles = result.files.filter(function(f) { return f.timestamp > lastSyncTime; });
        if (newFiles.length > 0) {
            lastSyncTime = Math.max.apply(null, newFiles.map(function(f) { return f.timestamp; }));
            
            for (let i = 0; i < newFiles.length; i++) {
                const file = newFiles[i];
                try {
                    const imgResponse = await fetch('../api/upload.php?action=get&filename=' + encodeURIComponent(file.filename) + '&_=' + ts);
                    if (!imgResponse.ok) continue;
                    const imgResult = await imgResponse.json();
                    if (imgResult.ok && imgResult.data) {
                        const ext = file.filename.split('.').pop().toLowerCase();
                        const mimeType = ext === 'png' ? 'image/png' : 'image/jpeg';
                        const src = 'data:' + mimeType + ';base64,' + imgResult.data;
                        
                        // 标记是正面还是反面
                        let type = 'front';
                        if (frontImage && !backImage) {
                            type = 'back';
                        }
                        
                        // 添加缩略图并立即标记类型
                        const thumb = addThumbWithType(src, type);
                        
                        if (type === 'front') {
                            setFrontImage(src);
                            showSyncStatus('✅ 已收到正面照片', 'success');
                        } else {
                            setBackImage(src);
                            showSyncStatus('✅ 已收到反面照片', 'success');
                        }
                        
                        // 删除服务器上的文件（已同步）
                        fetch('../api/upload.php?action=delete&filename=' + encodeURIComponent(file.filename)).catch(function(){});
                    }
                } catch (err) {
                    console.log('下载照片失败:', err);
                }
            }
        }
    } catch (err) {
        console.log('同步检查失败:', err);
    }
}

generateQRCode();
startSync();

// 页面关闭时停止轮询
window.addEventListener('beforeunload', stopSync);
</script>

<?php include '_footer.php'; ?>
