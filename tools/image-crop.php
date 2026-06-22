<?php
$title = '图片裁剪';
$desc = '按像素范围 left/top/width/height 裁剪图片。';
include '_header.php';
?>
            <div class="tool-panel">
                <label>选择图片</label>
                <input type="file" id="file" accept="image/*">
                <div class="row" style="display:flex;gap:12px;flex-wrap:wrap;margin-top:8px;">
                    <div style="flex:1;min-width:120px;">
                        <label>Left (左)</label>
                        <input type="number" id="left" min="0" value="0">
                    </div>
                    <div style="flex:1;min-width:120px;">
                        <label>Top (上)</label>
                        <input type="number" id="top" min="0" value="0">
                    </div>
                    <div style="flex:1;min-width:120px;">
                        <label>Width (宽)</label>
                        <input type="number" id="cw" min="1" value="0">
                    </div>
                    <div style="flex:1;min-width:120px;">
                        <label>Height (高)</label>
                        <input type="number" id="ch" min="1" value="0">
                    </div>
                </div>
                <div class="btn-row">
                    <button class="btn" onclick="doCrop()">生成预览</button>
                    <button class="btn success" onclick="downloadImage()">下载图片</button>
                </div>
                <p class="tip" id="info"></p>
            </div>
            <div class="tool-panel">
                <label>原图预览</label>
                <div id="origPreview" style="text-align:center;padding:10px;min-height:120px;border:1px dashed #ccc;border-radius:6px;">（原图将在此显示）</div>
                <label style="margin-top:12px;display:block;">裁剪结果</label>
                <div id="preview" style="text-align:center;padding:10px;min-height:120px;border:1px dashed #ccc;border-radius:6px;">（预览将在此显示）</div>
            </div>
            <script>
            let originalImage = null;
            let resultDataURL = null;
            const $ = id => document.getElementById(id);

            $('file').addEventListener('change', function(e) {
                const f = e.target.files[0];
                if (!f) return;
                const reader = new FileReader();
                reader.onload = function(ev) {
                    const img = new Image();
                    img.onload = function() {
                        originalImage = img;
                        $('cw').value = img.width;
                        $('ch').value = img.height;
                        $('info').textContent = '原图：' + img.width + ' × ' + img.height + ' px';
                        const op = $('origPreview');
                        op.innerHTML = '';
                        const oimg = document.createElement('img');
                        oimg.src = img.src;
                        oimg.style.maxWidth = '100%';
                        op.appendChild(oimg);
                    };
                    img.src = ev.target.result;
                };
                reader.readAsDataURL(f);
            });

            function doCrop() {
                if (!originalImage) return alert('请先选择图片');
                const l = parseInt($('left').value) || 0;
                const t = parseInt($('top').value) || 0;
                const w = parseInt($('cw').value);
                const h = parseInt($('ch').value);
                if (!w || !h || w < 1 || h < 1) return alert('请输入有效的宽高值');
                if (l + w > originalImage.width || t + h > originalImage.height) {
                    if (!confirm('裁剪范围超出原图，是否继续？')) return;
                }
                const canvas = document.createElement('canvas');
                canvas.width = w;
                canvas.height = h;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(originalImage, l, t, w, h, 0, 0, w, h);
                resultDataURL = canvas.toDataURL('image/png');
                const p = $('preview');
                p.innerHTML = '';
                const img = document.createElement('img');
                img.src = resultDataURL;
                img.style.maxWidth = '100%';
                p.appendChild(img);
                $('info').textContent = '已裁剪：' + w + ' × ' + h + ' px（位置 ' + l + ',' + t + '）';
            }

            function downloadImage() {
                if (!resultDataURL) return alert('请先生成预览');
                const a = document.createElement('a');
                a.href = resultDataURL;
                a.download = 'cropped.png';
                a.click();
            }
            </script>
<?php include '_footer.php'; ?>
