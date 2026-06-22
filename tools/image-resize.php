<?php
$title = '图片尺寸调整';
$desc = '上传图片，自定义宽高后导出。';
include '_header.php';
?>
            <div class="tool-panel">
                <label>选择图片</label>
                <input type="file" id="file" accept="image/*">
                <div class="row" style="display:flex;gap:12px;flex-wrap:wrap;margin-top:8px;">
                    <div style="flex:1;min-width:140px;">
                        <label>宽度 (px)</label>
                        <input type="number" id="width" min="1" placeholder="宽度">
                    </div>
                    <div style="flex:1;min-width:140px;">
                        <label>高度 (px)</label>
                        <input type="number" id="height" min="1" placeholder="高度">
                    </div>
                    <div style="display:flex;align-items:flex-end;">
                        <label><input type="checkbox" id="keepRatio" checked> 保持比例</label>
                    </div>
                </div>
                <div class="btn-row">
                    <button class="btn" onclick="doResize()">生成预览</button>
                    <button class="btn success" onclick="downloadImage()">下载图片</button>
                </div>
                <p class="tip">提示：默认导出为 PNG，如需其他格式请使用"格式转换"工具。</p>
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
                        $('width').value = img.width;
                        $('height').value = img.height;
                        $('info').textContent = '原图：' + img.width + ' × ' + img.height + ' px';
                    };
                    img.src = ev.target.result;
                };
                reader.readAsDataURL(f);
            });

            $('width').addEventListener('input', function() {
                if (!$('keepRatio').checked || !originalImage) return;
                const w = parseInt(this.value);
                if (!w) return;
                $('height').value = Math.round(w * originalImage.height / originalImage.width);
            });
            $('height').addEventListener('input', function() {
                if (!$('keepRatio').checked || !originalImage) return;
                const h = parseInt(this.value);
                if (!h) return;
                $('width').value = Math.round(h * originalImage.width / originalImage.height);
            });

            function doResize() {
                if (!originalImage) return alert('请先选择图片');
                const w = parseInt($('width').value);
                const h = parseInt($('height').value);
                if (!w || !h || w < 1 || h < 1) return alert('请输入有效的宽高值');
                const canvas = document.createElement('canvas');
                canvas.width = w;
                canvas.height = h;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(originalImage, 0, 0, w, h);
                resultDataURL = canvas.toDataURL('image/png');
                const p = $('preview');
                p.innerHTML = '';
                const img = document.createElement('img');
                img.src = resultDataURL;
                img.style.maxWidth = '100%';
                p.appendChild(img);
                $('info').textContent = '已生成：' + w + ' × ' + h + ' px';
            }

            function downloadImage() {
                if (!resultDataURL) return alert('请先生成预览');
                const a = document.createElement('a');
                a.href = resultDataURL;
                a.download = 'resized.png';
                a.click();
            }
            </script>
<?php include '_footer.php'; ?>
