<?php
$title = '图片裁剪';
$desc = '按像素范围 left/top/width/height 裁剪图片。';
include '_header.php';
?>
            <div class="tool-panel">
                <label>选择图片</label>
                <input type="file" id="file" accept="image/jpeg,image/png,image/webp">
                <div class="row" style="display:flex;gap:12px;flex-wrap:wrap;margin-top:8px;">
                    <div style="flex:1;min-width:120px;"><label>Left (左)</label><input type="number" id="left" min="0" value="0"></div>
                    <div style="flex:1;min-width:120px;"><label>Top (上)</label><input type="number" id="top" min="0" value="0"></div>
                    <div style="flex:1;min-width:120px;"><label>Width (宽)</label><input type="number" id="cw" min="1" value="0"></div>
                    <div style="flex:1;min-width:120px;"><label>Height (高)</label><input type="number" id="ch" min="1" value="0"></div>
                </div>
                <div class="btn-row">
                    <button class="btn" id="crop" type="button" disabled>生成预览</button>
                    <button class="btn success" id="download" type="button" disabled>下载图片</button>
                </div>
                <p class="tip">支持 JPG、PNG、WebP，单个文件不超过 40 MB，最多处理 5000 万像素。</p>
                <p class="tip">文件只在浏览器中处理，不会上传服务器。</p>
                <p class="tip" id="info" role="status"></p>
            </div>
            <div class="tool-panel">
                <label>原图预览</label>
                <div id="origPreview" style="text-align:center;padding:10px;min-height:120px;border:1px dashed #ccc;border-radius:6px;">（原图将在此显示）</div>
                <label style="margin-top:12px;display:block;">裁剪结果</label>
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
                $('crop').disabled = true;
                $('origPreview').textContent = '（原图将在此显示）';
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
                    $('left').value = 0;
                    $('top').value = 0;
                    $('cw').value = width;
                    $('ch').value = height;
                    $('left').max = Math.max(0, width - 1);
                    $('top').max = Math.max(0, height - 1);
                    $('cw').max = width;
                    $('ch').max = height;
                    $('crop').disabled = false;
                    const preview = new Image();
                    preview.src = imageUrl;
                    preview.alt = `原图，${width} × ${height} 像素`;
                    preview.style.maxWidth = '100%';
                    $('origPreview').replaceChildren(preview);
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

            for (const id of ['left', 'top', 'cw', 'ch']) $(id).addEventListener('input', resetResult);

            $('crop').addEventListener('click', async function() {
                if (!originalImage) return fail('请先选择图片');
                const left = Number.parseInt($('left').value, 10);
                const top = Number.parseInt($('top').value, 10);
                const width = Number.parseInt($('cw').value, 10);
                const height = Number.parseInt($('ch').value, 10);
                if (![left, top, width, height].every(Number.isInteger) || left < 0 || top < 0 || width < 1 || height < 1) {
                    return fail('请输入有效的裁剪位置和尺寸');
                }
                if (left + width > originalImage.naturalWidth || top + height > originalImage.naturalHeight) {
                    return fail('裁剪范围不能超出原图');
                }
                if (width > MAX_SIDE || height > MAX_SIDE || width * height > MAX_PIXELS) {
                    return fail('裁剪结果单边不能超过 16384 px，总像素不能超过 5000 万');
                }

                this.disabled = true;
                $('info').textContent = '正在生成预览…';
                try {
                    const canvas = document.createElement('canvas');
                    canvas.width = width;
                    canvas.height = height;
                    const context = canvas.getContext('2d');
                    if (!context) throw new Error('浏览器无法创建画布');
                    context.drawImage(originalImage, left, top, width, height, 0, 0, width, height);
                    const blob = await canvasToBlob(canvas);
                    resetResult();
                    resultBlob = blob;
                    resultUrl = URL.createObjectURL(blob);
                    const preview = new Image();
                    preview.src = resultUrl;
                    preview.alt = `裁剪结果，${width} × ${height} 像素`;
                    preview.style.maxWidth = '100%';
                    $('preview').replaceChildren(preview);
                    $('download').disabled = false;
                    $('info').textContent = `已裁剪：${width} × ${height} px（位置 ${left}, ${top}），${(blob.size / 1024 / 1024).toFixed(2)} MB`;
                } catch (error) {
                    fail(error.message || '裁剪图片失败');
                } finally {
                    this.disabled = !originalImage;
                }
            });

            $('download').addEventListener('click', function() {
                if (!resultBlob || !resultUrl) return fail('请先生成预览');
                const link = document.createElement('a');
                link.href = resultUrl;
                link.download = 'cropped.png';
                link.click();
            });
            window.addEventListener('beforeunload', () => {
                revoke(sourceUrl);
                revoke(resultUrl);
            });
            </script>
<?php include '_footer.php'; ?>
