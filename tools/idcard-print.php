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
                        <div class="mobile-address-setting">
                            <label for="mobileBaseUrl">手机访问地址</label>
                            <div class="mobile-address-row">
                                <input id="mobileBaseUrl" type="url" inputmode="url" placeholder="例如：http://192.168.1.20:8080">
                                <button id="refreshQr" type="button">刷新二维码</button>
                            </div>
                            <p id="mobileAddressHint" class="mobile-address-hint"></p>
                        </div>
                    </div>
                    <div class="upload-section">
                        <p class="sub-hint">或直接在此处上传：</p>
                        <label class="upload-box" for="idcardUpload">
                            <div class="upload-box-icon">📷</div>
                            <div class="upload-box-title">点击上传身份证照片</div>
                            <div class="upload-box-hint">支持 JPG / PNG 格式，可同时上传正反面</div>
                            <input type="file" id="idcardUpload" accept="image/jpeg,image/png" hidden multiple>
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
                        <input type="text" id="watermarkText" maxlength="120" placeholder="请输入水印文字，如：仅供XX使用" value="仅供参考">
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
                    <button class="btn success" id="exportImage" type="button">📥 导出图片</button>
                    <button class="btn" id="printPage" type="button">🖨️ 打印</button>
                    <button class="btn secondary" id="clearImages" type="button">🗑️ 清空</button>
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
.mobile-address-setting {
    margin-top: 14px;
    text-align: left;
}
.mobile-address-setting label {
    display: block;
    margin-bottom: 6px;
    color: #475569;
    font-size: 12px;
    font-weight: 600;
}
.mobile-address-row {
    display: flex;
    gap: 6px;
}
.mobile-address-row input {
    min-width: 0;
    flex: 1;
    padding: 7px 8px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 11px;
}
.mobile-address-row button {
    flex: 0 0 auto;
    padding: 7px 8px;
    border: 0;
    border-radius: 6px;
    background: #4f46e5;
    color: #fff;
    cursor: pointer;
    font-size: 11px;
}
.mobile-address-hint {
    min-height: 32px;
    margin: 6px 0 0;
    color: #64748b;
    font-size: 11px;
    line-height: 1.45;
}
.mobile-address-hint.warning { color: #c2410c; }
.mobile-address-hint.success { color: #047857; }
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

<script src="../static/vendor/qrcode.js" onload="window.dispatchEvent(new Event('office-qrcode-ready'))" onerror="this.onerror=null;this.src='https://unpkg.com/qrcode@1.5.4/build/qrcode.js'"></script>
<script>
let frontImage = null;
let backImage = null;
const allowedImageTypes = new Set(['image/jpeg', 'image/png']);
const MAX_IMAGE_BYTES = 12 * 1024 * 1024;
const MAX_IMAGE_PIXELS = 40000000;
const MAX_IMAGE_SIDE = 16384;
const managedImageUrls = new Set();
const uploadSessionBytes = new Uint8Array(16);
window.crypto.getRandomValues(uploadSessionBytes);
const uploadSession = Array.from(uploadSessionBytes, function(byte) {
    return byte.toString(16).padStart(2, '0');
}).join('');
const mobileAddressStorageKey = 'office-tools-idcard-mobile-origin';

function $(id) { return document.getElementById(id); }

function validatedImageUrl(blob) {
    return new Promise((resolve, reject) => {
        if (!allowedImageTypes.has(blob.type)) return reject(new Error('仅支持 JPG 或 PNG 图片'));
        if (blob.size <= 0 || blob.size > MAX_IMAGE_BYTES) return reject(new Error('单张图片不能超过 12 MB'));
        const url = URL.createObjectURL(blob);
        const image = new Image();
        image.onload = function() {
            const width = image.naturalWidth;
            const height = image.naturalHeight;
            if (width < 1 || height < 1 || width > MAX_IMAGE_SIDE || height > MAX_IMAGE_SIDE || width * height > MAX_IMAGE_PIXELS) {
                URL.revokeObjectURL(url);
                reject(new Error('图片尺寸过大，单边最多 16384 像素且总计不超过 4000 万像素'));
                return;
            }
            managedImageUrls.add(url);
            resolve(url);
        };
        image.onerror = function() {
            URL.revokeObjectURL(url);
            reject(new Error('图片已损坏或浏览器无法读取'));
        };
        image.src = url;
    });
}

function releaseManagedImages() {
    for (const url of managedImageUrls) URL.revokeObjectURL(url);
    managedImageUrls.clear();
}

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
    const text = ($('watermarkText').value || '仅供参考').slice(0, 120);
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
$('idcardUpload').addEventListener('change', async function(event) {
    const files = Array.from(event.target.files || []);
    event.target.value = '';
    if (files.length > 2) {
        alert('一次最多选择正反面 2 张图片');
        return;
    }
    for (const file of files) {
        try {
            const src = await validatedImageUrl(file);
            const assignedType = !frontImage ? 'front' : (!backImage ? 'back' : '');
            const thumb = addThumb(src);
            if (assignedType) assignThumb(thumb, src, assignedType);
        } catch (error) {
            alert(error.message);
        }
    }
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
        const current = thumb.dataset.type || '';
        const next = current === '' ? 'front' : (current === 'front' ? 'back' : '');
        if (current) clearSide(current, src);
        if (next) assignThumb(thumb, src, next);
        else {
            thumb.classList.remove('active');
            delete thumb.dataset.type;
            label.textContent = '点击设置';
        }
    };
    $('uploadedImages').appendChild(thumb);
    return thumb;
}

function assignThumb(thumb, src, type) {
    const existing = document.querySelector('.upload-thumb[data-type="' + type + '"]');
    if (existing && existing !== thumb) {
        existing.classList.remove('active');
        delete existing.dataset.type;
        const existingLabel = existing.querySelector('.upload-thumb-label');
        if (existingLabel) existingLabel.textContent = '点击设置';
    }
    if (type === 'front') setFrontImage(src);
    else setBackImage(src);
    thumb.classList.add('active');
    thumb.dataset.type = type;
    const label = thumb.querySelector('.upload-thumb-label');
    if (label) label.textContent = type === 'front' ? '正面' : '反面';
}

function clearSide(type, expectedSrc) {
    if (type === 'front' && (!expectedSrc || frontImage === expectedSrc)) {
        frontImage = null;
        renderPlaceholder('frontPlaceholder', '身份证正面');
    }
    if (type === 'back' && (!expectedSrc || backImage === expectedSrc)) {
        backImage = null;
        renderPlaceholder('backPlaceholder', '身份证反面');
    }
}

// 添加带类型的缩略图（用于手机同步）
function addThumbWithType(src, type) {
    const thumb = addThumb(src);
    assignThumb(thumb, src, type);
    return thumb;
}

function renderPlaceholder(id, label) {
    const placeholder = $(id);
    placeholder.classList.remove('has-image');
    const text = document.createElement('div');
    text.className = 'ph-label';
    text.textContent = label;
    placeholder.replaceChildren(text);
    updateWatermarks();
}

function setFrontImage(src) {
    frontImage = src;
    const placeholder = $('frontPlaceholder');
    placeholder.classList.add('has-image');
    const image = new Image();
    image.src = src;
    image.alt = '身份证正面';
    placeholder.replaceChildren(image);
    updateWatermarks();
    showPreview();
}

function setBackImage(src) {
    backImage = src;
    const placeholder = $('backPlaceholder');
    placeholder.classList.add('has-image');
    const image = new Image();
    image.src = src;
    image.alt = '身份证反面';
    placeholder.replaceChildren(image);
    updateWatermarks();
    showPreview();
}

function showPreview() {
    $('previewPanel').hidden = false;
}

function clearImages() {
    frontImage = null;
    backImage = null;
    $('uploadedImages').replaceChildren();
    renderPlaceholder('frontPlaceholder', '身份证正面');
    renderPlaceholder('backPlaceholder', '身份证反面');
    releaseManagedImages();
    $('previewPanel').hidden = true;
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
            // 两张都绘制完成后，以 Blob 导出，避免创建超长 Base64 字符串。
            canvas.toBlob(function(blob) {
                if (!blob) {
                    alert('浏览器无法生成打印图片');
                    return;
                }
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.download = 'idcard-print.png';
                link.href = url;
                link.click();
                setTimeout(function() { URL.revokeObjectURL(url); }, 1000);
            }, 'image/png');
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
            const text = ($('watermarkText').value || '仅供参考').slice(0, 120);
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
    if (!printWindow) {
        alert('浏览器阻止了打印窗口，请允许弹窗后重试');
        return;
    }
    printWindow.opener = null;
    const watermarkEnabled = $('addWatermark').checked;
    const watermarkText = ($('watermarkText').value || '仅供参考').slice(0, 120);
    const watermarkOpacity = $('watermarkOpacity').value / 100;
    printWindow.document.open();
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
                .print-watermark {
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%) rotate(-15deg);
                    font-size: 60px;
                    font-weight: bold;
                    color: #000;
                    pointer-events: none;
                    white-space: nowrap;
                }
                .wm-card { position: relative; }
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
        </head>
        <body>
            <div class="page">
                <div class="card-area wm-card" id="printFront"></div>
                <div class="card-area wm-card" id="printBack"></div>
            </div>
        </body>
        </html>
    `);
    printWindow.document.close();

    const imageLoads = [];
    function fillPrintCard(id, src, emptyText) {
        const card = printWindow.document.getElementById(id);
        if (src) {
            const image = printWindow.document.createElement('img');
            image.alt = emptyText;
            imageLoads.push(new Promise(resolve => {
                image.onload = resolve;
                image.onerror = resolve;
            }));
            image.src = src;
            card.appendChild(image);
        } else {
            const empty = printWindow.document.createElement('div');
            empty.className = 'empty-hint';
            empty.textContent = emptyText + '（未上传）';
            card.appendChild(empty);
        }
        if (watermarkEnabled) {
            const watermark = printWindow.document.createElement('div');
            watermark.className = 'print-watermark';
            watermark.style.opacity = String(watermarkOpacity);
            watermark.textContent = watermarkText;
            card.appendChild(watermark);
        }
    }
    fillPrintCard('printFront', frontImage, '身份证正面');
    fillPrintCard('printBack', backImage, '身份证反面');
    Promise.all(imageLoads).then(function() {
        setTimeout(function() { printWindow.print(); }, 300);
    });
}

$('exportImage').addEventListener('click', exportImage);
$('printPage').addEventListener('click', printPage);
$('clearImages').addEventListener('click', clearImages);

// 生成二维码（指向手机上传页面）
function isLoopbackHost(hostname) {
    return hostname === 'localhost' || hostname === '127.0.0.1' || hostname === '0.0.0.0' || hostname === '::1' || hostname === '[::1]';
}

function updateMobileAddressHint(origin) {
    const hint = $('mobileAddressHint');
    let parsed;
    try { parsed = new URL(origin); } catch (error) { parsed = null; }
    if (!parsed || !['http:', 'https:'].includes(parsed.protocol)) {
        hint.className = 'mobile-address-hint warning';
        hint.textContent = '请输入以 http:// 或 https:// 开头的完整地址';
        return false;
    }
    if (isLoopbackHost(parsed.hostname)) {
        hint.className = 'mobile-address-hint warning';
        hint.textContent = 'localhost 只能本机访问，请改成电脑局域网 IP 后刷新二维码';
    } else {
        hint.className = 'mobile-address-hint success';
        hint.textContent = '请确认手机和电脑连接同一网络';
    }
    return true;
}

async function generateQRCode() {
    const configuredOrigin = $('mobileBaseUrl').value.trim();
    if (!updateMobileAddressHint(configuredOrigin)) return;
    const baseUrl = new URL(configuredOrigin);
    localStorage.setItem(mobileAddressStorageKey, baseUrl.origin);
    const mobileUrl = new URL(window.location.href);
    mobileUrl.protocol = baseUrl.protocol;
    mobileUrl.host = baseUrl.host;
    mobileUrl.pathname = mobileUrl.pathname.replace('idcard-print', 'idcard-mobile');
    mobileUrl.search = '';
    mobileUrl.hash = '';
    mobileUrl.searchParams.set('session', uploadSession);
    const url = mobileUrl.toString();
    const qrEl = $('qrImg');
    function showError() {
        qrEl.style.display = 'none';
        const parent = qrEl.parentNode;
        let fallback = document.getElementById('qrLoadError');
        if (fallback) return;
        fallback = document.createElement('div');
        fallback.id = 'qrLoadError';
        fallback.style.cssText = 'font-size:12px;color:#94a3b8;text-align:center;';
        fallback.textContent = '二维码生成失败，请刷新页面';
        parent.appendChild(fallback);
    }
    if (!window.QRCode) return showError();
    try {
        qrEl.src = await window.QRCode.toDataURL(url, {
            width: 200,
            margin: 1,
            errorCorrectionLevel: 'M'
        });
        qrEl.style.display = '';
        const loadError = document.getElementById('qrLoadError');
        if (loadError) loadError.remove();
    } catch (error) {
        showError();
    }
}

const savedMobileOrigin = localStorage.getItem(mobileAddressStorageKey);
const currentOriginIsLoopback = isLoopbackHost(window.location.hostname);
$('mobileBaseUrl').value = currentOriginIsLoopback && savedMobileOrigin ? savedMobileOrigin : window.location.origin;
$('mobileBaseUrl').addEventListener('input', function() {
    updateMobileAddressHint(this.value.trim());
});
$('refreshQr').addEventListener('click', generateQRCode);
window.addEventListener('office-qrcode-ready', generateQRCode);

// 轮询同步手机上传的照片
let syncInterval = null;
let syncStatus = null;
let syncInFlight = false;
const syncedFiles = new Set();

async function fetchWithTimeout(resource, options = {}, timeoutMs = 15000) {
    const controller = new AbortController();
    const timeout = setTimeout(function() { controller.abort(); }, timeoutMs);
    try {
        return await fetch(resource, Object.assign({}, options, { signal: controller.signal }));
    } finally {
        clearTimeout(timeout);
    }
}

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
    if (syncInFlight) return;
    syncInFlight = true;
    try {
        // 添加时间戳参数防止缓存
        const ts = Date.now();
        const response = await fetchWithTimeout('../api/upload.php?action=list&session=' + encodeURIComponent(uploadSession) + '&_=' + ts);
        if (!response.ok) {
            throw new Error('HTTP ' + response.status);
        }
        const result = await response.json();
        if (!result.ok) {
            throw new Error(result.error || 'API错误');
        }
        
        // 接口按时间倒序返回；这里从旧到新处理，确保同一面的最新照片最终生效。
        const newFiles = result.files.slice().reverse();
        if (newFiles.length > 0) {
            for (let i = 0; i < newFiles.length; i++) {
                const file = newFiles[i];
                if (syncedFiles.has(file.filename)) continue;
                try {
                    const imgResponse = await fetchWithTimeout('../api/upload.php?action=get&session=' + encodeURIComponent(uploadSession) + '&filename=' + encodeURIComponent(file.filename) + '&_=' + ts);
                    if (!imgResponse.ok) continue;
                    const blob = await imgResponse.blob();
                    const src = await validatedImageUrl(blob);
                    const type = file.type === 'back' ? 'back' : 'front';
                    addThumbWithType(src, type);
                    syncedFiles.add(file.filename);
                    showSyncStatus(type === 'front' ? '✅ 已收到正面照片' : '✅ 已收到反面照片', 'success');

                    // 删除服务器上的文件（已同步）；删除操作使用 POST，避免链接预加载误删。
                    const deleteData = new FormData();
                    deleteData.append('filename', file.filename);
                    deleteData.append('session', uploadSession);
                    fetchWithTimeout('../api/upload.php?action=delete', {
                        method: 'POST',
                        body: deleteData,
                        cache: 'no-store'
                    }).catch(function(){});
                } catch (err) {
                    console.log('下载照片失败:', err);
                }
            }
        }
    } catch (err) {
        console.log('同步检查失败:', err);
    } finally {
        syncInFlight = false;
    }
}

generateQRCode();
startSync();

// 页面关闭时停止轮询
window.addEventListener('beforeunload', function() {
    stopSync();
    releaseManagedImages();
});
</script>

<?php include '_footer.php'; ?>
