<?php
$title = '图片尺寸调整';
$desc = '上传图片，自定义宽高后导出。';
include '_header.php';
?>
            <div class="tool-panel">
                <label>选择图片</label>
                <input type="file" id="file" accept="image/jpeg,image/png,image/webp">
                <div class="row" style="display:flex;gap:12px;flex-wrap:wrap;margin-top:8px;">
                    <div style="flex:1;min-width:140px;">
                        <label>宽度 (px)</label>
                        <input type="number" id="width" min="1" max="16384" placeholder="宽度">
                    </div>
                    <div style="flex:1;min-width:140px;">
                        <label>高度 (px)</label>
                        <input type="number" id="height" min="1" max="16384" placeholder="高度">
                    </div>
                    <div style="display:flex;align-items:flex-end;">
                        <label><input type="checkbox" id="keepRatio" checked> 保持比例</label>
                    </div>
                </div>
                <div class="btn-row">
                    <button class="btn" id="resize" type="button" disabled>生成预览</button>
                    <button class="btn success" id="download" type="button" disabled>下载图片</button>
                </div>
                <p class="tip">支持 JPG、PNG、WebP，单个文件不超过 40 MB，最多处理 5000 万像素；默认导出 PNG。</p>
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
            function fail(message) {
                $('info').textContent = message;
                alert(message);
            }
            function canvasToBlob(canvas) {
                return new Promise((resolve, reject) => canvas.toBlob(
                    blob => blob ? resolve(blob) : reject(new Error('浏览器无法生成图片')),
                    'image/png'
                ));
            }
            function validDimensions(width, height) {
                return Number.isInteger(width) && Number.isInteger(height) && width > 0 && height > 0
                    && width <= MAX_SIDE && height <= MAX_SIDE && width * height <= MAX_PIXELS;
            }

            $('file').addEventListener('change', function(event) {
                const file = event.target.files[0];
                resetResult();
                originalImage = null;
                $('resize').disabled = true;
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
                    if (!validDimensions(width, height)) {
                        revoke(sourceUrl);
                        sourceUrl = '';
                        return fail('原图尺寸过大：单边不能超过 16384 px，总像素不能超过 5000 万');
                    }
                    originalImage = image;
                    $('width').value = width;
                    $('height').value = height;
                    $('resize').disabled = false;
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

            $('width').addEventListener('input', function() {
                resetResult();
                if (!$('keepRatio').checked || !originalImage) return;
                const width = Number.parseInt(this.value, 10);
                if (width > 0) $('height').value = Math.round(width * originalImage.naturalHeight / originalImage.naturalWidth);
            });
            $('height').addEventListener('input', function() {
                resetResult();
                if (!$('keepRatio').checked || !originalImage) return;
                const height = Number.parseInt(this.value, 10);
                if (height > 0) $('width').value = Math.round(height * originalImage.naturalWidth / originalImage.naturalHeight);
            });
            $('keepRatio').addEventListener('change', resetResult);

            $('resize').addEventListener('click', async function() {
                if (!originalImage) return fail('请先选择图片');
                const width = Number.parseInt($('width').value, 10);
                const height = Number.parseInt($('height').value, 10);
                if (!validDimensions(width, height)) return fail('输出单边不能超过 16384 px，总像素不能超过 5000 万');

                this.disabled = true;
                $('info').textContent = '正在生成预览…';
                try {
                    const canvas = document.createElement('canvas');
                    canvas.width = width;
                    canvas.height = height;
                    const context = canvas.getContext('2d');
                    if (!context) throw new Error('浏览器无法创建画布');
                    context.imageSmoothingEnabled = true;
                    context.imageSmoothingQuality = 'high';
                    context.drawImage(originalImage, 0, 0, width, height);
                    const blob = await canvasToBlob(canvas);
                    resetResult();
                    resultBlob = blob;
                    resultUrl = URL.createObjectURL(blob);
                    const image = new Image();
                    image.src = resultUrl;
                    image.alt = `调整后的图片，${width} × ${height} 像素`;
                    image.style.maxWidth = '100%';
                    $('preview').replaceChildren(image);
                    $('download').disabled = false;
                    $('info').textContent = `已生成：${width} × ${height} px，${(blob.size / 1024 / 1024).toFixed(2)} MB`;
                } catch (error) {
                    fail(error.message || '生成图片失败');
                } finally {
                    this.disabled = !originalImage;
                }
            });

            $('download').addEventListener('click', function() {
                if (!resultBlob || !resultUrl) return fail('请先生成预览');
                const link = document.createElement('a');
                link.href = resultUrl;
                link.download = 'resized.png';
                link.click();
            });
            window.addEventListener('beforeunload', () => {
                revoke(sourceUrl);
                revoke(resultUrl);
            });
            </script>
<?php include '_footer.php'; ?>
