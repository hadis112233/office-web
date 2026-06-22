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
            const $ = id => document.getElementById(id);

            $('quality').addEventListener('input', function() {
                $('qVal').textContent = this.value;
            });

            $('file').addEventListener('change', function(e) {
                const f = e.target.files[0];
                if (!f) return;
                const reader = new FileReader();
                reader.onload = function(ev) {
                    const img = new Image();
                    img.onload = function() {
                        originalImage = img;
                        $('info').textContent = '原图：' + img.width + ' × ' + img.height + '，文件大小：' + (f.size / 1024).toFixed(2) + ' KB';
                    };
                    img.src = ev.target.result;
                };
                reader.readAsDataURL(f);
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
                if (fmt === 'image/jpeg') ctx.fillStyle = '#ffffff';
                ctx.drawImage(originalImage, 0, 0);
                canvas.toBlob(function(blob) {
                    if (!blob) return alert('压缩失败');
                    resultBlob = blob;
                    const url = URL.createObjectURL(blob);
                    const p = $('preview');
                    p.innerHTML = '';
                    const img = document.createElement('img');
                    img.src = url;
                    img.style.maxWidth = '100%';
                    p.appendChild(img);
                    $('info').textContent = '压缩后：' + (blob.size / 1024).toFixed(2) + ' KB（质量 ' + q + '）';
                }, fmt, q);
            }

            function downloadImage() {
                if (!resultBlob) return alert('请先生成预览');
                const a = document.createElement('a');
                a.href = URL.createObjectURL(resultBlob);
                a.download = resultName + '.' + resultExt;
                a.click();
            }
            </script>
<?php include '_footer.php'; ?>
