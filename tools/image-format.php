<?php
$title = '图片格式转换';
$desc = 'JPG / PNG / WEBP 之间互转。';
include '_header.php';
?>
            <div class="tool-panel">
                <label>选择图片</label>
                <input type="file" id="file" accept="image/*">
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
            const $ = id => document.getElementById(id);

            $('file').addEventListener('change', function(e) {
                const f = e.target.files[0];
                if (!f) return;
                const reader = new FileReader();
                reader.onload = function(ev) {
                    const img = new Image();
                    img.onload = function() {
                        originalImage = img;
                        $('info').textContent = '原图：' + img.width + ' × ' + img.height + ' px';
                    };
                    img.src = ev.target.result;
                };
                reader.readAsDataURL(f);
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
                    const url = URL.createObjectURL(blob);
                    const p = $('preview');
                    p.innerHTML = '';
                    const img = document.createElement('img');
                    img.src = url;
                    img.style.maxWidth = '100%';
                    p.appendChild(img);
                    $('info').textContent = '已转换，文件大小：' + (blob.size / 1024).toFixed(2) + ' KB';
                }, fmt);
            }

            function downloadImage() {
                if (!resultBlob) return alert('请先生成预览');
                const a = document.createElement('a');
                a.href = URL.createObjectURL(resultBlob);
                a.download = 'converted.' + resultExt;
                a.click();
            }
            </script>
<?php include '_footer.php'; ?>
