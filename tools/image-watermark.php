<?php
$title = '图片添加水印';
$desc = '为图片添加右下角文字水印。';
include '_header.php';
?>
            <div class="tool-panel">
                <label>选择图片</label>
                <input type="file" id="file" accept="image/jpeg,image/png,image/webp">
                <label>水印文字</label>
                <input type="text" id="text" value="© 示例水印" maxlength="200" placeholder="请输入水印文字">
                <div class="row" style="display:flex;gap:12px;flex-wrap:wrap;margin-top:8px;">
                    <div style="flex:1;min-width:120px;"><label>字体大小 (px)</label><input type="number" id="fontSize" min="8" max="500" value="32"></div>
                    <div style="flex:1;min-width:120px;"><label>颜色</label><input type="color" id="color" value="#ffffff" style="height:38px;width:100%;padding:2px;"></div>
                    <div style="flex:1;min-width:120px;"><label>透明度 (0.05-1)</label><input type="number" id="alpha" min="0.05" max="1" step="0.05" value="0.7"></div>
                </div>
                <div class="btn-row">
                    <button class="btn" id="watermark" type="button" disabled>生成预览</button>
                    <button class="btn success" id="download" type="button" disabled>下载图片</button>
                </div>
                <p class="tip">支持 JPG、PNG、WebP，单个文件不超过 40 MB，最多处理 5000 万像素；过长文字会自动缩小。</p>
                <p class="tip">文件只在浏览器中处理，不会上传服务器。</p>
                <p class="tip" id="info" role="status"></p>
            </div>
            <div class="tool-panel">
                <label>预览</label>
                <div id="preview" style="text-align:center;padding:10px;min-height:120px;border:1px dashed #ccc;border-radius:6px;">（预览将在此显示）</div>
            </div>
            <script>
            const $ = id => document.getElementById(id);
            const allowedTypes = new Set(['image/jpeg', 'image/png', 'image/webp']);
            const MAX_FILE_BYTES = 40 * 1024 * 1024;
            const MAX_PIXELS = 50000000;
            const MAX_SIDE = 16384;
            let originalImage = null;
            let sourceUrl = '';
            let resultUrl = '';
            let resultBlob = null;

            function revoke(url) { if (url) URL.revokeObjectURL(url); }
            function resetResult() {
                revoke(resultUrl);
                resultUrl = '';
                resultBlob = null;
                $('download').disabled = true;
                $('preview').textContent = '（预览将在此显示）';
            }
            function fail(message) { $('info').textContent = message; alert(message); }
            function canvasToBlob(canvas) {
                return new Promise((resolve, reject) => canvas.toBlob(
                    blob => blob ? resolve(blob) : reject(new Error('浏览器无法生成图片')),
                    'image/png'
                ));
            }

            $('file').addEventListener('change', function(event) {
                const file = event.target.files[0];
                resetResult();
                originalImage = null;
                $('watermark').disabled = true;
                revoke(sourceUrl);
                sourceUrl = '';
                if (!file) return;
                if (!allowedTypes.has(file.type)) return fail('请选择 JPG、PNG 或 WebP 图片');
                if (file.size > MAX_FILE_BYTES) return fail('图片不能超过 40 MB');

                sourceUrl = URL.createObjectURL(file);
                const imageUrl = sourceUrl;
                const image = new Image();
                image.onload = function() {
                    if (imageUrl !== sourceUrl) return;
                    const width = image.naturalWidth;
                    const height = image.naturalHeight;
                    if (width > MAX_SIDE || height > MAX_SIDE || width * height > MAX_PIXELS) {
                        revoke(sourceUrl);
                        sourceUrl = '';
                        return fail('原图尺寸过大：单边不能超过 16384 px，总像素不能超过 5000 万');
                    }
                    originalImage = image;
                    $('watermark').disabled = false;
                    $('info').textContent = `原图：${width} × ${height} px，${(file.size / 1024 / 1024).toFixed(2)} MB`;
                };
                image.onerror = function() {
                    if (imageUrl !== sourceUrl) return;
                    revoke(sourceUrl);
                    sourceUrl = '';
                    fail('图片已损坏或浏览器无法读取');
                };
                image.src = imageUrl;
            });

            for (const id of ['text', 'fontSize', 'color', 'alpha']) $(id).addEventListener('input', resetResult);

            $('watermark').addEventListener('click', async function() {
                if (!originalImage) return fail('请先选择图片');
                const text = $('text').value.trim();
                const requestedFontSize = Number.parseInt($('fontSize').value, 10);
                const alpha = Number.parseFloat($('alpha').value);
                if (!text) return fail('请输入水印文字');
                if (!Number.isInteger(requestedFontSize) || requestedFontSize < 8 || requestedFontSize > 500) return fail('字体大小应为 8-500 px');
                if (!Number.isFinite(alpha) || alpha < 0.05 || alpha > 1) return fail('透明度应为 0.05-1');

                this.disabled = true;
                $('info').textContent = '正在生成预览…';
                try {
                    const canvas = document.createElement('canvas');
                    canvas.width = originalImage.naturalWidth;
                    canvas.height = originalImage.naturalHeight;
                    const context = canvas.getContext('2d');
                    if (!context) throw new Error('浏览器无法创建画布');
                    context.drawImage(originalImage, 0, 0);

                    const padding = Math.max(12, Math.round(Math.min(canvas.width, canvas.height) * 0.025));
                    const maxWidth = Math.max(1, canvas.width - padding * 2);
                    let safeFontSize = Math.min(requestedFontSize, Math.max(8, Math.floor(canvas.height / 3)));
                    context.font = `600 ${safeFontSize}px sans-serif`;
                    const measuredWidth = context.measureText(text).width;
                    if (measuredWidth > maxWidth) safeFontSize = Math.max(6, Math.floor(safeFontSize * maxWidth / measuredWidth));
                    context.font = `600 ${safeFontSize}px sans-serif`;
                    context.globalAlpha = alpha;
                    context.fillStyle = $('color').value;
                    context.textAlign = 'right';
                    context.textBaseline = 'bottom';
                    context.shadowColor = 'rgba(0,0,0,0.45)';
                    context.shadowBlur = Math.max(1, Math.round(safeFontSize / 10));
                    context.fillText(text, canvas.width - padding, canvas.height - padding, maxWidth);

                    const blob = await canvasToBlob(canvas);
                    resetResult();
                    resultBlob = blob;
                    resultUrl = URL.createObjectURL(blob);
                    const preview = new Image();
                    preview.src = resultUrl;
                    preview.alt = '已添加文字水印的图片';
                    preview.style.maxWidth = '100%';
                    $('preview').replaceChildren(preview);
                    $('download').disabled = false;
                    $('info').textContent = `已添加水印，实际字号 ${safeFontSize} px，输出 ${(blob.size / 1024 / 1024).toFixed(2)} MB`;
                } catch (error) {
                    fail(error.message || '添加水印失败');
                } finally {
                    this.disabled = !originalImage;
                }
            });

            $('download').addEventListener('click', function() {
                if (!resultBlob || !resultUrl) return fail('请先生成预览');
                const link = document.createElement('a');
                link.href = resultUrl;
                link.download = 'watermarked.png';
                link.click();
            });
            window.addEventListener('beforeunload', () => {
                revoke(sourceUrl);
                revoke(resultUrl);
            });
            </script>
<?php include '_footer.php'; ?>
