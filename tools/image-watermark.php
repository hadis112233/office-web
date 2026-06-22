<?php
$title = '图片添加水印';
$desc = '为图片添加右下角文字水印。';
include '_header.php';
?>
            <div class="tool-panel">
                <label>选择图片</label>
                <input type="file" id="file" accept="image/*">
                <label>水印文字</label>
                <input type="text" id="text" value="© 示例水印" placeholder="请输入水印文字">
                <div class="row" style="display:flex;gap:12px;flex-wrap:wrap;margin-top:8px;">
                    <div style="flex:1;min-width:120px;">
                        <label>字体大小 (px)</label>
                        <input type="number" id="fontSize" min="1" value="32">
                    </div>
                    <div style="flex:1;min-width:120px;">
                        <label>颜色</label>
                        <input type="color" id="color" value="#ffffff" style="height:38px;width:100%;padding:2px;">
                    </div>
                    <div style="flex:1;min-width:120px;">
                        <label>透明度 (0-1)</label>
                        <input type="number" id="alpha" min="0" max="1" step="0.05" value="0.7">
                    </div>
                </div>
                <div class="btn-row">
                    <button class="btn" onclick="doWatermark()">生成预览</button>
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
                        $('info').textContent = '原图：' + img.width + ' × ' + img.height + ' px';
                    };
                    img.src = ev.target.result;
                };
                reader.readAsDataURL(f);
            });

            function doWatermark() {
                if (!originalImage) return alert('请先选择图片');
                const text = $('text').value || '';
                if (!text) return alert('请输入水印文字');
                const fontSize = parseInt($('fontSize').value) || 32;
                const color = $('color').value;
                const alpha = Math.max(0, Math.min(1, parseFloat($('alpha').value) || 0.7));
                const canvas = document.createElement('canvas');
                canvas.width = originalImage.width;
                canvas.height = originalImage.height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(originalImage, 0, 0);
                ctx.globalAlpha = alpha;
                ctx.fillStyle = color;
                ctx.font = fontSize + 'px sans-serif';
                ctx.textAlign = 'right';
                ctx.textBaseline = 'bottom';
                const padding = Math.max(fontSize, 20);
                ctx.fillText(text, canvas.width - padding, canvas.height - padding);
                resultDataURL = canvas.toDataURL('image/png');
                const p = $('preview');
                p.innerHTML = '';
                const img = document.createElement('img');
                img.src = resultDataURL;
                img.style.maxWidth = '100%';
                p.appendChild(img);
                $('info').textContent = '已添加水印';
            }

            function downloadImage() {
                if (!resultDataURL) return alert('请先生成预览');
                const a = document.createElement('a');
                a.href = resultDataURL;
                a.download = 'watermarked.png';
                a.click();
            }
            </script>
<?php include '_footer.php'; ?>
