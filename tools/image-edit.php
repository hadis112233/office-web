<?php
$title = '图片P图';
$desc = '在线图片编辑工具：滤镜、色彩调节、裁剪、旋转翻转、特效处理、边框、多格式导出';
include '_header.php';
?>

<style>
.image-editor {
    display: flex;
    flex-direction: column;
    gap: 12px;
    max-width: 100%;
}

.editor-header {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    border-radius: 12px;
    padding: 14px 18px;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.editor-btn {
    padding: 8px 14px;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.editor-btn-primary {
    background: #ef4444;
    color: white;
}
.editor-btn-primary:hover { background: #dc2626; }

.editor-btn-secondary {
    background: #3b82f6;
    color: white;
}
.editor-btn-secondary:hover { background: #2563eb; }

.editor-btn-success {
    background: #22c55e;
    color: white;
}
.editor-btn-success:hover { background: #16a34a; }

.editor-btn-warning {
    background: #f59e0b;
    color: white;
}
.editor-btn-warning:hover { background: #d97706; }

.editor-btn-default {
    background: #475569;
    color: white;
}
.editor-btn-default:hover { background: #64748b; }

.editor-btn-group {
    display: flex;
    gap: 6px;
    align-items: center;
}

.editor-divider {
    width: 1px;
    height: 24px;
    background: rgba(255,255,255,0.2);
    margin: 0 4px;
}

.editor-section {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 10px;
    background: rgba(255,255,255,0.05);
    border-radius: 8px;
}

.editor-section-title {
    font-size: 12px;
    color: #94a3b8;
    min-width: 40px;
}

.editor-range {
    width: 120px;
    height: 6px;
    border-radius: 3px;
    background: rgba(255,255,255,0.2);
    outline: none;
    cursor: pointer;
}

.editor-range::-webkit-slider-thumb {
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: #6366f1;
    cursor: pointer;
    border: 2px solid white;
}

.editor-range-value {
    font-size: 12px;
    color: #cbd5e1;
    min-width: 45px;
    text-align: right;
}

.filter-btn {
    padding: 6px 10px;
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 6px;
    color: #e2e8f0;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s;
}

.filter-btn:hover {
    background: rgba(99,102,241,0.3);
    border-color: rgba(99,102,241,0.5);
}

.filter-btn.active {
    background: rgba(99,102,241,0.5);
    border-color: #6366f1;
}

.editor-canvas-area {
    background: #0f172a;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 500px;
    position: relative;
    overflow: hidden;
    flex: 1;
}

.editor-canvas-area canvas {
    max-width: 100%;
    max-height: 600px;
    border-radius: 8px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.4);
}

.editor-canvas-area .placeholder {
    color: #64748b;
    text-align: center;
}

.editor-canvas-area .placeholder-icon {
    font-size: 64px;
    margin-bottom: 12px;
}

.editor-content {
    display: flex;
    flex-direction: row;
    gap: 16px;
    padding: 16px;
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    border-radius: 12px;
    margin-top: 0;
}

.editor-sidebar {
    display: flex;
    flex-direction: column;
    gap: 12px;
    min-width: 280px;
    width: 280px;
    flex-shrink: 0;
}

.editor-sidebar .info-section {
    background: rgba(15, 23, 42, 0.6);
    border: 1px solid rgba(99, 102, 241, 0.15);
    border-radius: 10px;
    padding: 12px 14px;
}

.editor-sidebar .info-section:last-child {
    margin-bottom: 0;
}

.info-section {
    margin-bottom: 16px;
}

.info-section:last-child {
    margin-bottom: 0;
}

.info-title {
    font-size: 14px;
    font-weight: 600;
    color: #6366f1;
    margin-bottom: 8px;
    padding-bottom: 4px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.info-row {
    display: flex;
    justify-content: space-between;
    padding: 4px 0;
    font-size: 13px;
}

.info-label {
    color: #94a3b8;
}

.info-value {
    color: #fff;
    font-weight: 500;
}

.history-list {
    max-height: 120px;
    overflow-y: auto;
    font-size: 12px;
    color: #94a3b8;
}

.history-item {
    padding: 3px 0;
    border-bottom: 1px dashed rgba(255,255,255,0.05);
}

.download-panel {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

.download-panel select {
    padding: 6px 10px;
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 6px;
    color: #e2e8f0;
    font-size: 13px;
}

.download-panel option {
    background: #1e293b;
    color: #e2e8f0;
}

@media (max-width: 768px) {
    .editor-header {
        padding: 10px 12px;
        gap: 6px;
    }
    .editor-btn {
        padding: 6px 10px;
        font-size: 12px;
    }
    .editor-section {
        padding: 4px 6px;
        gap: 6px;
    }
    .editor-range {
        width: 80px;
    }
    .editor-content {
        flex-direction: column;
        padding: 10px;
        gap: 10px;
    }
    .editor-canvas-area {
        padding: 10px;
        min-height: 260px;
    }
    .editor-sidebar {
        min-width: 100%;
        flex-direction: row;
        flex-wrap: wrap;
    }
    .editor-sidebar .info-section {
        min-width: calc(50% - 5px);
    }
}
</style>

<div class="image-editor">
    <div class="editor-header">
        <div class="editor-btn-group">
            <button class="editor-btn editor-btn-primary" id="open-btn">📂 打开</button>
            <input type="file" id="file-input" accept="image/*" hidden>
            <button class="editor-btn editor-btn-default" id="undo-btn" disabled>↩ 撤销</button>
            <button class="editor-btn editor-btn-default" id="redo-btn" disabled>↪ 重做</button>
        </div>

        <div class="editor-divider"></div>

        <div class="download-panel">
            <button class="editor-btn editor-btn-success" id="download-btn" disabled>⬇ 下载</button>
            <select id="export-format">
                <option value="image/jpeg">JPEG</option>
                <option value="image/png">PNG</option>
                <option value="image/webp">WEBP</option>
            </select>
            <button class="editor-btn editor-btn-warning" id="reset-btn" disabled>🔄 重置</button>
        </div>

        <div class="editor-divider"></div>

        <div class="editor-section">
            <span class="editor-section-title">翻转</span>
            <button class="editor-btn editor-btn-default" id="flip-h-btn">⇔ 水平</button>
            <button class="editor-btn editor-btn-default" id="flip-v-btn">⇕ 垂直</button>
        </div>

        <div class="editor-section">
            <span class="editor-section-title">旋转</span>
            <button class="editor-btn editor-btn-default" id="rotate-l-btn">↻ 90°</button>
            <button class="editor-btn editor-btn-default" id="rotate-r-btn">↺ 90°</button>
        </div>

        <div class="editor-divider"></div>

        <div class="editor-section">
            <span class="editor-section-title">裁剪缩放</span>
            <button class="editor-btn editor-btn-default" id="crop-btn">✂ 裁剪</button>
            <button class="editor-btn editor-btn-default" id="size-btn">📐 尺寸</button>
            <button class="editor-btn editor-btn-default" id="zoom-in-btn">➕</button>
            <button class="editor-btn editor-btn-default" id="zoom-out-btn">➖</button>
        </div>

        <div class="editor-divider"></div>

        <div class="editor-section">
            <span class="editor-section-title">特效</span>
            <button class="filter-btn" data-effect="blur">模糊</button>
            <button class="filter-btn" data-effect="sharpen">锐化</button>
            <button class="filter-btn" data-effect="round">圆角</button>
        </div>

        <div class="editor-divider"></div>

        <div class="editor-section">
            <span class="editor-section-title">滤镜</span>
            <button class="filter-btn" data-filter="none">原图</button>
            <button class="filter-btn" data-filter="grayscale">黑白</button>
            <button class="filter-btn" data-filter="sepia">复古</button>
            <button class="filter-btn" data-filter="sketch">素描</button>
            <button class="filter-btn" data-filter="japanese">日系</button>
            <button class="filter-btn" data-filter="lomo">LOMO</button>
            <button class="filter-btn" data-filter="nostalgia">怀旧</button>
        </div>

        <div class="editor-divider"></div>

        <div class="editor-section">
            <span class="editor-section-title">亮度</span>
            <input type="range" class="editor-range" id="brightness" min="0" max="200" value="100">
            <span class="editor-range-value" id="brightness-val">100%</span>
        </div>

        <div class="editor-section">
            <span class="editor-section-title">对比度</span>
            <input type="range" class="editor-range" id="contrast" min="0" max="200" value="100">
            <span class="editor-range-value" id="contrast-val">100%</span>
        </div>

        <div class="editor-section">
            <span class="editor-section-title">饱和度</span>
            <input type="range" class="editor-range" id="saturation" min="0" max="200" value="100">
            <span class="editor-range-value" id="saturation-val">100%</span>
        </div>

        <div class="editor-section">
            <span class="editor-section-title">色调</span>
            <input type="range" class="editor-range" id="hue" min="-180" max="180" value="0">
            <span class="editor-range-value" id="hue-val">0°</span>
        </div>

        <div class="editor-divider"></div>

        <div class="editor-section">
            <button class="editor-btn editor-btn-default" id="border-btn">🖼️ 边框</button>
            <input type="range" class="editor-range" id="border-width" min="0" max="50" value="0">
            <span class="editor-range-value" id="border-val">0px</span>
        </div>
    </div>

    <div class="editor-content">
        <div class="editor-canvas-area" id="canvas-area">
            <div class="placeholder">
                <div class="placeholder-icon">🖼️</div>
                <div>点击「打开」选择图片开始编辑</div>
            </div>
            <canvas id="editor-canvas" hidden></canvas>
        </div>

        <div class="editor-sidebar">
            <div class="info-section">
                <div class="info-title">📋 图片信息</div>
                <div class="info-row">
                    <span class="info-label">文件名</span>
                    <span class="info-value" id="info-name">-</span>
                </div>
                <div class="info-row">
                    <span class="info-label">原始尺寸</span>
                    <span class="info-value" id="info-original-size">-</span>
                </div>
                <div class="info-row">
                    <span class="info-label">当前尺寸</span>
                    <span class="info-value" id="info-current-size">-</span>
                </div>
                <div class="info-row">
                    <span class="info-label">文件大小</span>
                    <span class="info-value" id="info-size">-</span>
                </div>
                <div class="info-row">
                    <span class="info-label">类型</span>
                    <span class="info-value" id="info-type">-</span>
                </div>
            </div>

            <div class="info-section">
                <div class="info-title">📜 操作历史</div>
                <div class="history-list" id="history-list">暂无操作记录</div>
            </div>
        </div>
    </div>
</div>

<script>
const $ = id => document.getElementById(id);

let originalImage = null;
let canvas = null;
let ctx = null;
let history = [];
let historyIndex = -1;
let currentFilter = 'none';
let currentEffect = '';
let effects = {
    brightness: 100,
    contrast: 100,
    saturation: 100,
    hue: 0,
    borderWidth: 0
};

const filterFunctions = {
    grayscale: (imgData) => {
        for (let i = 0; i < imgData.data.length; i += 4) {
            const avg = (imgData.data[i] + imgData.data[i+1] + imgData.data[i+2]) / 3;
            imgData.data[i] = imgData.data[i+1] = imgData.data[i+2] = avg;
        }
    },
    sepia: (imgData) => {
        for (let i = 0; i < imgData.data.length; i += 4) {
            const r = imgData.data[i], g = imgData.data[i+1], b = imgData.data[i+2];
            imgData.data[i] = Math.min(255, 0.393*r + 0.769*g + 0.189*b);
            imgData.data[i+1] = Math.min(255, 0.349*r + 0.686*g + 0.168*b);
            imgData.data[i+2] = Math.min(255, 0.272*r + 0.534*g + 0.131*b);
        }
    },
    sketch: (imgData) => {
        for (let i = 0; i < imgData.data.length; i += 4) {
            const avg = (imgData.data[i] + imgData.data[i+1] + imgData.data[i+2]) / 3;
            const sketch = 255 - avg;
            imgData.data[i] = imgData.data[i+1] = imgData.data[i+2] = sketch;
        }
    },
    japanese: (imgData) => {
        for (let i = 0; i < imgData.data.length; i += 4) {
            imgData.data[i] = Math.min(255, imgData.data[i] * 1.1);
            imgData.data[i+1] = Math.min(255, imgData.data[i+1] * 1.05);
            imgData.data[i+2] = Math.min(255, imgData.data[i+2] * 0.95);
        }
    },
    lomo: (imgData) => {
        for (let i = 0; i < imgData.data.length; i += 4) {
            imgData.data[i] = Math.min(255, imgData.data[i] * 1.3);
            imgData.data[i+1] = Math.min(255, imgData.data[i+1] * 1.1);
            imgData.data[i+2] = Math.min(255, imgData.data[i+2] * 0.8);
        }
    },
    nostalgia: (imgData) => {
        for (let i = 0; i < imgData.data.length; i += 4) {
            const r = imgData.data[i], g = imgData.data[i+1], b = imgData.data[i+2];
            imgData.data[i] = Math.min(255, r * 0.9 + 50);
            imgData.data[i+1] = Math.min(255, g * 0.85 + 30);
            imgData.data[i+2] = Math.min(255, b * 0.75);
        }
    },
    negative: (imgData) => {
        for (let i = 0; i < imgData.data.length; i += 4) {
            imgData.data[i] = 255 - imgData.data[i];
            imgData.data[i+1] = 255 - imgData.data[i+1];
            imgData.data[i+2] = 255 - imgData.data[i+2];
        }
    },
    cool: (imgData) => {
        for (let i = 0; i < imgData.data.length; i += 4) {
            imgData.data[i] = Math.max(0, imgData.data[i] - 20);
            imgData.data[i+1] = Math.max(0, imgData.data[i+1] - 10);
            imgData.data[i+2] = Math.min(255, imgData.data[i+2] + 20);
        }
    },
    warm: (imgData) => {
        for (let i = 0; i < imgData.data.length; i += 4) {
            imgData.data[i] = Math.min(255, imgData.data[i] + 20);
            imgData.data[i+1] = Math.min(255, imgData.data[i+1] + 15);
            imgData.data[i+2] = Math.max(0, imgData.data[i+2] - 15);
        }
    },
    emboss: (imgData) => {
        const width = imgData.width, height = imgData.height;
        const data = imgData.data;
        const output = new Uint8ClampedArray(data.length);
        
        for (let y = 0; y < height; y++) {
            for (let x = 0; x < width; x++) {
                const i = (y * width + x) * 4;
                const ni = ((y + 1) * width + (x + 1)) * 4;
                for (let c = 0; c < 3; c++) {
                    output[i + c] = Math.abs(data[i + c] - (ni < data.length ? data[ni + c] : 0)) + 128;
                }
                output[i + 3] = data[i + 3];
            }
        }
        imgData.data.set(output);
    },
    oldphoto: (imgData) => {
        for (let i = 0; i < imgData.data.length; i += 4) {
            const r = imgData.data[i], g = imgData.data[i+1], b = imgData.data[i+2];
            imgData.data[i] = Math.min(255, 0.65*r + 0.77*g + 0.5*b);
            imgData.data[i+1] = Math.min(255, 0.5*r + 0.58*g + 0.38*b);
            imgData.data[i+2] = Math.min(255, 0.39*r + 0.45*g + 0.3*b);
        }
    },
    dream: (imgData) => {
        for (let i = 0; i < imgData.data.length; i += 4) {
            imgData.data[i] = Math.min(255, imgData.data[i] * 1.15 + 10);
            imgData.data[i+1] = Math.min(255, imgData.data[i+1] * 1.1 + 15);
            imgData.data[i+2] = Math.min(255, imgData.data[i+2] * 1.05 + 20);
        }
    },
    vignette: (imgData) => {
        const width = imgData.width, height = imgData.height;
        const cx = width / 2, cy = height / 2;
        const maxDist = Math.sqrt(cx * cx + cy * cy);
        
        for (let y = 0; y < height; y++) {
            for (let x = 0; x < width; x++) {
                const i = (y * width + x) * 4;
                const dist = Math.sqrt((x - cx) ** 2 + (y - cy) ** 2);
                const factor = 1 - (dist / maxDist) * 0.4;
                for (let c = 0; c < 3; c++) {
                    imgData.data[i + c] = Math.max(0, imgData.data[i + c] * factor);
                }
            }
        }
    },
    noise: (imgData) => {
        for (let i = 0; i < imgData.data.length; i += 4) {
            const noise = (Math.random() - 0.5) * 50;
            imgData.data[i] = Math.max(0, Math.min(255, imgData.data[i] + noise));
            imgData.data[i+1] = Math.max(0, Math.min(255, imgData.data[i+1] + noise));
            imgData.data[i+2] = Math.max(0, Math.min(255, imgData.data[i+2] + noise));
        }
    }
};

function initCanvas(image) {
    canvas = $('editor-canvas');
    ctx = canvas.getContext('2d');
    
    canvas.width = image.width;
    canvas.height = image.height;
    ctx.drawImage(image, 0, 0);
    
    $('canvas-area').querySelector('.placeholder').hidden = true;
    canvas.hidden = false;
    
    saveHistory('打开图片');
    updateInfo(image);
    
    $('download-btn').disabled = false;
    $('reset-btn').disabled = false;
}

function saveHistory(action) {
    if (!canvas) return;
    
    if (historyIndex < history.length - 1) {
        history = history.slice(0, historyIndex + 1);
    }
    
    history.push({
        data: canvas.toDataURL(),
        action: action,
        time: new Date().toLocaleTimeString()
    });
    
    historyIndex = history.length - 1;
    updateHistoryUI();
    updateUndoRedoButtons();
}

function updateHistoryUI() {
    const list = $('history-list');
    if (history.length === 0) {
        list.innerHTML = '暂无操作记录';
        return;
    }
    
    list.innerHTML = history.map((item, idx) => {
        const active = idx === historyIndex ? ' style="color:#6366f1;font-weight:bold;"' : '';
        return `<div class="history-item"${active}>${item.time} - ${item.action}</div>`;
    }).join('');
    
    list.scrollTop = list.scrollHeight;
}

function updateUndoRedoButtons() {
    $('undo-btn').disabled = historyIndex <= 0;
    $('redo-btn').disabled = historyIndex >= history.length - 1;
}

function applyFilters() {
    if (!canvas || !originalImage) return;
    
    ctx.save();
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    
    ctx.filter = `brightness(${effects.brightness}%) contrast(${effects.contrast}%) saturate(${effects.saturation}%) hue-rotate(${effects.hue}deg)`;
    ctx.drawImage(originalImage, 0, 0);
    ctx.filter = 'none';
    
    if (effects.borderWidth > 0) {
        ctx.strokeStyle = '#ffffff';
        ctx.lineWidth = effects.borderWidth;
        ctx.strokeRect(effects.borderWidth / 2, effects.borderWidth / 2, canvas.width - effects.borderWidth, canvas.height - effects.borderWidth);
    }
    
    if (currentFilter !== 'none' && filterFunctions[currentFilter]) {
        const imgData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        filterFunctions[currentFilter](imgData);
        ctx.putImageData(imgData, 0, 0);
    }
}

function applyEffect(effect) {
    if (!canvas || !ctx) return;
    
    const imgData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    
    switch (effect) {
        case 'blur':
            ctx.save();
            ctx.filter = 'blur(3px)';
            ctx.drawImage(canvas, 0, 0);
            ctx.filter = 'none';
            ctx.restore();
            break;
        case 'sharpen':
            const weights = [0, -1, 0, -1, 5, -1, 0, -1, 0];
            const side = Math.round(Math.sqrt(weights.length));
            const halfSide = Math.floor(side / 2);
            const output = ctx.createImageData(imgData);
            const dst = output.data;
            
            for (let y = 0; y < imgData.height; y++) {
                for (let x = 0; x < imgData.width; x++) {
                    const dstOff = (y * imgData.width + x) * 4;
                    let r = 0, g = 0, b = 0;
                    
                    for (let cy = 0; cy < side; cy++) {
                        for (let cx = 0; cx < side; cx++) {
                            const scy = y + cy - halfSide;
                            const scx = x + cx - halfSide;
                            if (scy >= 0 && scy < imgData.height && scx >= 0 && scx < imgData.width) {
                                const srcOff = (scy * imgData.width + scx) * 4;
                                const wt = weights[cy * side + cx];
                                r += imgData.data[srcOff] * wt;
                                g += imgData.data[srcOff + 1] * wt;
                                b += imgData.data[srcOff + 2] * wt;
                            }
                        }
                    }
                    
                    dst[dstOff] = r;
                    dst[dstOff + 1] = g;
                    dst[dstOff + 2] = b;
                    dst[dstOff + 3] = imgData.data[dstOff + 3];
                }
            }
            ctx.putImageData(output, 0, 0);
            break;
        case 'round':
            const roundedCanvas = document.createElement('canvas');
            roundedCanvas.width = canvas.width;
            roundedCanvas.height = canvas.height;
            const rctx = roundedCanvas.getContext('2d');
            
            rctx.beginPath();
            rctx.roundRect(0, 0, canvas.width, canvas.height, 20);
            rctx.closePath();
            rctx.clip();
            rctx.drawImage(canvas, 0, 0);
            
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(roundedCanvas, 0, 0);
            break;
    }
}

function updateInfo(image) {
    const file = $('file-input').files[0];
    $('info-name').textContent = file ? file.name : '-';
    $('info-original-size').textContent = image ? image.width + ' × ' + image.height : '-';
    $('info-current-size').textContent = canvas ? canvas.width + ' × ' + canvas.height : '-';
    $('info-size').textContent = file ? (file.size / 1024).toFixed(2) + ' KB' : '-';
    $('info-type').textContent = file ? file.type : '-';
}

$('open-btn').addEventListener('click', () => {
    $('file-input').click();
});

$('file-input').addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (!file) return;
    
    const reader = new FileReader();
    reader.onload = (ev) => {
        const img = new Image();
        img.onload = () => {
            originalImage = img;
            initCanvas(img);
        };
        img.src = ev.target.result;
    };
    reader.readAsDataURL(file);
});

$('undo-btn').addEventListener('click', () => {
    if (historyIndex <= 0) return;
    
    historyIndex--;
    const item = history[historyIndex];
    const img = new Image();
    img.onload = () => {
        canvas.width = img.width;
        canvas.height = img.height;
        ctx.drawImage(img, 0, 0);
        updateUndoRedoButtons();
        updateHistoryUI();
    };
    img.src = item.data;
});

$('redo-btn').addEventListener('click', () => {
    if (historyIndex >= history.length - 1) return;
    
    historyIndex++;
    const item = history[historyIndex];
    const img = new Image();
    img.onload = () => {
        canvas.width = img.width;
        canvas.height = img.height;
        ctx.drawImage(img, 0, 0);
        updateUndoRedoButtons();
        updateHistoryUI();
    };
    img.src = item.data;
});

$('download-btn').addEventListener('click', () => {
    if (!canvas) return;
    
    const format = $('export-format').value;
    const ext = format.split('/')[1].toUpperCase();
    const quality = format === 'image/jpeg' ? 0.92 : 0.95;
    
    canvas.toBlob((blob) => {
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'edited_' + Date.now() + '.' + ext.toLowerCase();
        a.click();
        URL.revokeObjectURL(url);
    }, format, quality);
});

$('reset-btn').addEventListener('click', () => {
    if (!originalImage) return;
    
    canvas.width = originalImage.width;
    canvas.height = originalImage.height;
    ctx.drawImage(originalImage, 0, 0);
    
    currentFilter = 'none';
    effects = { brightness: 100, contrast: 100, saturation: 100, hue: 0, borderWidth: 0 };
    
    $('brightness').value = 100;
    $('brightness-val').textContent = '100%';
    $('contrast').value = 100;
    $('contrast-val').textContent = '100%';
    $('saturation').value = 100;
    $('saturation-val').textContent = '100%';
    $('hue').value = 0;
    $('hue-val').textContent = '0°';
    $('border-width').value = 0;
    $('border-val').textContent = '0px';
    
    document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
    
    saveHistory('重置图片');
});

$('flip-h-btn').addEventListener('click', () => {
    if (!canvas || !ctx) return;
    
    ctx.save();
    ctx.translate(canvas.width, 0);
    ctx.scale(-1, 1);
    ctx.drawImage(canvas, 0, 0);
    ctx.restore();
    
    saveHistory('水平翻转');
});

$('flip-v-btn').addEventListener('click', () => {
    if (!canvas || !ctx) return;
    
    ctx.save();
    ctx.translate(0, canvas.height);
    ctx.scale(1, -1);
    ctx.drawImage(canvas, 0, 0);
    ctx.restore();
    
    saveHistory('垂直翻转');
});

$('rotate-l-btn').addEventListener('click', () => {
    if (!canvas || !ctx) return;
    
    const tempCanvas = document.createElement('canvas');
    tempCanvas.width = canvas.height;
    tempCanvas.height = canvas.width;
    const tempCtx = tempCanvas.getContext('2d');
    
    tempCtx.translate(tempCanvas.width, 0);
    tempCtx.rotate(Math.PI / 2);
    tempCtx.drawImage(canvas, 0, 0);
    
    canvas.width = tempCanvas.width;
    canvas.height = tempCanvas.height;
    ctx.drawImage(tempCanvas, 0, 0);
    
    saveHistory('旋转90°');
});

$('rotate-r-btn').addEventListener('click', () => {
    if (!canvas || !ctx) return;
    
    const tempCanvas = document.createElement('canvas');
    tempCanvas.width = canvas.height;
    tempCanvas.height = canvas.width;
    const tempCtx = tempCanvas.getContext('2d');
    
    tempCtx.translate(0, tempCanvas.height);
    tempCtx.rotate(-Math.PI / 2);
    tempCtx.drawImage(canvas, 0, 0);
    
    canvas.width = tempCanvas.width;
    canvas.height = tempCanvas.height;
    ctx.drawImage(tempCanvas, 0, 0);
    
    saveHistory('旋转-90°');
});

$('crop-btn').addEventListener('click', () => {
    if (!canvas || !ctx) return;
    
    const size = Math.min(canvas.width, canvas.height);
    const x = (canvas.width - size) / 2;
    const y = (canvas.height - size) / 2;
    
    const tempCanvas = document.createElement('canvas');
    tempCanvas.width = size;
    tempCanvas.height = size;
    const tempCtx = tempCanvas.getContext('2d');
    
    tempCtx.drawImage(canvas, x, y, size, size, 0, 0, size, size);
    
    canvas.width = size;
    canvas.height = size;
    ctx.drawImage(tempCanvas, 0, 0);
    
    updateInfo({ width: size, height: size });
    saveHistory('裁剪为正方形');
});

$('size-btn').addEventListener('click', () => {
    if (!canvas || !ctx) return;
    
    const width = parseInt(prompt('请输入宽度（像素）：', canvas.width));
    const height = parseInt(prompt('请输入高度（像素）：', canvas.height));
    
    if (isNaN(width) || isNaN(height) || width <= 0 || height <= 0) {
        alert('无效的尺寸');
        return;
    }
    
    const tempCanvas = document.createElement('canvas');
    tempCanvas.width = width;
    tempCanvas.height = height;
    const tempCtx = tempCanvas.getContext('2d');
    
    tempCtx.drawImage(canvas, 0, 0, width, height);
    
    canvas.width = width;
    canvas.height = height;
    ctx.drawImage(tempCanvas, 0, 0);
    
    updateInfo({ width, height });
    saveHistory(`调整尺寸 ${width}×${height}`);
});

$('zoom-in-btn').addEventListener('click', () => {
    if (!canvas || !ctx) return;
    
    const tempCanvas = document.createElement('canvas');
    tempCanvas.width = canvas.width * 1.2;
    tempCanvas.height = canvas.height * 1.2;
    const tempCtx = tempCanvas.getContext('2d');
    
    tempCtx.drawImage(canvas, 0, 0, tempCanvas.width, tempCanvas.height);
    
    canvas.width = tempCanvas.width;
    canvas.height = tempCanvas.height;
    ctx.drawImage(tempCanvas, 0, 0);
    
    saveHistory('放大1.2倍');
});

$('zoom-out-btn').addEventListener('click', () => {
    if (!canvas || !ctx) return;
    
    const tempCanvas = document.createElement('canvas');
    tempCanvas.width = canvas.width * 0.8;
    tempCanvas.height = canvas.height * 0.8;
    const tempCtx = tempCanvas.getContext('2d');
    
    tempCtx.drawImage(canvas, 0, 0, tempCanvas.width, tempCanvas.height);
    
    canvas.width = tempCanvas.width;
    canvas.height = tempCanvas.height;
    ctx.drawImage(tempCanvas, 0, 0);
    
    saveHistory('缩小0.8倍');
});

document.querySelectorAll('[data-effect]').forEach(btn => {
    btn.addEventListener('click', () => {
        const effect = btn.dataset.effect;
        applyEffect(effect);
        saveHistory(`特效：${btn.textContent}`);
    });
});

document.querySelectorAll('[data-filter]').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('[data-filter]').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentFilter = btn.dataset.filter;
        applyFilters();
        saveHistory(`滤镜：${btn.textContent}`);
    });
});

$('brightness').addEventListener('input', (e) => {
    effects.brightness = e.target.value;
    $('brightness-val').textContent = e.target.value + '%';
    applyFilters();
});

$('contrast').addEventListener('input', (e) => {
    effects.contrast = e.target.value;
    $('contrast-val').textContent = e.target.value + '%';
    applyFilters();
});

$('saturation').addEventListener('input', (e) => {
    effects.saturation = e.target.value;
    $('saturation-val').textContent = e.target.value + '%';
    applyFilters();
});

$('hue').addEventListener('input', (e) => {
    effects.hue = e.target.value;
    $('hue-val').textContent = e.target.value + '°';
    applyFilters();
});

$('border-width').addEventListener('input', (e) => {
    effects.borderWidth = parseInt(e.target.value);
    $('border-val').textContent = e.target.value + 'px';
    applyFilters();
});

$('border-btn').addEventListener('click', () => {
    if (effects.borderWidth === 0) {
        effects.borderWidth = 10;
        $('border-width').value = 10;
        $('border-val').textContent = '10px';
    } else {
        effects.borderWidth = 0;
        $('border-width').value = 0;
        $('border-val').textContent = '0px';
    }
    applyFilters();
    saveHistory(effects.borderWidth > 0 ? '添加边框' : '移除边框');
});
</script>

<?php include '_footer.php'; ?>
