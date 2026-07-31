<?php
$title = '图片格式转换';
$desc = 'JPG / PNG / WEBP 之间互转。';
include '_header.php';
?>
            <div class="tool-panel">
                <label>选择图片</label>
                <input type="file" id="file" accept="image/jpeg,image/png,image/webp">
                <label>目标格式</label>
                <select id="format">
                    <option value="image/jpeg">JPEG (.jpg)</option>
                    <option value="image/png">PNG (.png)</option>
                    <option value="image/webp">WEBP (.webp)</option>
                </select>
                <div class="btn-row">
                    <button class="btn" onclick="doConvert()">生成预览</button>
                    <button class="btn success" onclick="downloadImage()">下载图片</button>
                </div>
                <p class="tip">提示：转 JPEG 时透明背景将变为白色。</p>
                <p class="tip" id="info"></p>
            </div>
            <div class="tool-panel">
                <label>预览</label>
                <div id="preview" style="text-align:center;padding:10px;min-height:120px;border:1px dashed #ccc;border-radius:6px;">（预览将在此显示）</div>
            </div>
            <script>
            let originalImage = null;
            let resultBlob = null;
            let resultExt = 'png';
            let sourceUrl = '';
            let previewUrl = '';
            const $ = id => document.getElementById(id);
            const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            function revoke(url) { if (url) URL.revokeObjectURL(url); }

            $('file').addEventListener('change', function(e) {
                const f = e.target.files[0];
                if (!f) return;
                if (!allowedTypes.includes(f.type) || f.size > 40 * 1024 * 1024) {
                    $('info').textContent = '仅支持 40 MB 以内的 JPG、PNG 或 WebP 图片。';
                    e.target.value = '';
                    return;
                }
                revoke(sourceUrl);
                revoke(previewUrl);
                previewUrl = '';
                resultBlob = null;
                $('preview').textContent = '（预览将在此显示）';
                sourceUrl = URL.createObjectURL(f);
                const img = new Image();
                img.onload = function() {
                    if (img.width * img.height > 50000000) {
                        originalImage = null;
                        $('info').textContent = '图片超过 5000 万像素，为防止浏览器卡死已停止读取。';
                        return;
                    }
                    originalImage = img;
                    $('info').textContent = '原图：' + img.width + ' × ' + img.height + ' px';
                };
                img.onerror = function() { originalImage = null; $('info').textContent = '图片读取失败或文件已损坏。'; };
                img.src = sourceUrl;
            });

            function doConvert() {
                if (!originalImage) return alert('请先选择图片');
                const fmt = $('format').value;
                resultExt = fmt === 'image/jpeg' ? 'jpg' : (fmt === 'image/webp' ? 'webp' : 'png');
                const canvas = document.createElement('canvas');
                canvas.width = originalImage.width;
                canvas.height = originalImage.height;
                const ctx = canvas.getContext('2d');
                if (fmt === 'image/jpeg') {
                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, canvas.width, canvas.height);
                }
                ctx.drawImage(originalImage, 0, 0);
                canvas.toBlob(function(blob) {
                    if (!blob) return alert('转换失败');
                    resultBlob = blob;
                    revoke(previewUrl);
                    previewUrl = URL.createObjectURL(blob);
                    const p = $('preview');
                    p.replaceChildren();
                    const img = document.createElement('img');
                    img.src = previewUrl;
                    img.style.maxWidth = '100%';
                    p.appendChild(img);
                    $('info').textContent = '已转换，文件大小：' + (blob.size / 1024).toFixed(2) + ' KB';
                }, fmt);
            }

            function downloadImage() {
                if (!resultBlob) return alert('请先生成预览');
                const a = document.createElement('a');
                a.href = previewUrl;
                a.download = 'converted.' + resultExt;
                a.click();
            }
            window.addEventListener('beforeunload', function() { revoke(sourceUrl); revoke(previewUrl); });
            </script>
<?php include '_footer.php'; ?>
