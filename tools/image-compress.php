<?php
$title = '图片压缩';
$desc = '通过降低 quality 压缩 JPG / WEBP，减小体积。';
include '_header.php';
?>
            <div class="tool-panel">
                <label>选择图片</label>
                <input type="file" id="file" accept="image/jpeg,image/webp,image/png">
                <label>输出格式</label>
                <select id="format">
                    <option value="image/jpeg">JPEG</option>
                    <option value="image/webp">WEBP</option>
                </select>
                <label>质量：<span id="qVal">0.75</span></label>
                <input type="range" id="quality" min="0" max="1" step="0.01" value="0.75">
                <div class="btn-row">
                    <button class="btn" onclick="doCompress()">生成预览</button>
                    <button class="btn success" onclick="downloadImage()">下载图片</button>
                </div>
                <p class="tip" id="info"></p>
            </div>
            <div class="tool-panel">
                <label>预览</label>
                <div id="preview" style="text-align:center;padding:10px;min-height:120px;border:1px dashed #ccc;border-radius:6px;">（预览将在此显示）</div>
            </div>
            <script>
            let originalImage = null;
            let resultBlob = null;
            let resultName = 'compressed';
            let resultExt = 'jpg';
            let sourceUrl = '';
            let previewUrl = '';
            const $ = id => document.getElementById(id);
            const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            function revoke(url) { if (url) URL.revokeObjectURL(url); }

            $('quality').addEventListener('input', function() {
                $('qVal').textContent = this.value;
            });

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
                    resultName = (f.name.replace(/\.[^.]+$/, '') || 'compressed') + '-compressed';
                    $('info').textContent = '原图：' + img.width + ' × ' + img.height + '，文件大小：' + (f.size / 1024).toFixed(2) + ' KB';
                };
                img.onerror = function() { originalImage = null; $('info').textContent = '图片读取失败或文件已损坏。'; };
                img.src = sourceUrl;
            });

            function doCompress() {
                if (!originalImage) return alert('请先选择图片');
                const q = parseFloat($('quality').value);
                const fmt = $('format').value;
                resultExt = fmt === 'image/jpeg' ? 'jpg' : 'webp';
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
                    if (!blob) return alert('压缩失败');
                    resultBlob = blob;
                    revoke(previewUrl);
                    previewUrl = URL.createObjectURL(blob);
                    const p = $('preview');
                    p.replaceChildren();
                    const img = document.createElement('img');
                    img.src = previewUrl;
                    img.style.maxWidth = '100%';
                    p.appendChild(img);
                    $('info').textContent = '压缩后：' + (blob.size / 1024).toFixed(2) + ' KB（质量 ' + q + '）';
                }, fmt, q);
            }

            function downloadImage() {
                if (!resultBlob) return alert('请先生成预览');
                const a = document.createElement('a');
                a.href = previewUrl;
                a.download = resultName + '.' + resultExt;
                a.click();
            }
            window.addEventListener('beforeunload', function() { revoke(sourceUrl); revoke(previewUrl); });
            </script>
<?php include '_footer.php'; ?>
