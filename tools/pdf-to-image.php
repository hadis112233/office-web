<?php
$title = 'PDF 转图片';
$desc = '将 PDF 每页导出为 PNG/JPG。';
include '_header.php';
?>
            <div class="tool-panel">
                <label>选择 PDF</label>
                <input type="file" id="file" accept="application/pdf">
                <label>输出格式</label>
                <select id="format">
                    <option value="png">PNG</option>
                    <option value="jpeg">JPG</option>
                </select>
                <label>分辨率（DPI）：<span id="dpiVal">150</span></label>
                <input type="range" id="dpi" min="72" max="300" step="10" value="150">
                <div class="btn-row">
                    <button class="btn" onclick="doConvert()">开始转换</button>
                    <button class="btn success" onclick="downloadAll()">打包下载全部</button>
                </div>
                <p class="tip" id="info"></p>
            </div>
            <div class="tool-panel">
                <label>预览（共 <span id="totalPage">0</span> 页）</label>
                <div id="preview" style="text-align:center;padding:10px;min-height:120px;border:1px dashed #ccc;border-radius:6px;">（图片将在此显示）</div>
            </div>
            <script src="https://unpkg.com/pdfjs-dist@3.11.174/build/pdf.min.js" onerror="document.getElementById('info').textContent='⚠ CDN 加载失败，当前为演示模式'"></script>
            <script src="https://unpkg.com/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
            <script>
            const $ = id => document.getElementById(id);
            let images = [];
            let currentFile = null;

            $('dpi').addEventListener('input', function() { $('dpiVal').textContent = this.value; });

            $('file').addEventListener('change', function(e) {
                const f = e.target.files[0];
                if (!f) return;
                currentFile = f;
                $('info').textContent = '已选择：' + f.name;
                $('preview').innerHTML = '';
                images = [];
                $('totalPage').textContent = '0';
            });

            async function doConvert() {
                if (!currentFile) return alert('请先选择 PDF');
                const pdfjsLib = window['pdfjsLib'] || window['pdfjs-dist/build/pdf'];
                if (!pdfjsLib) return alert('PDF.js 未加载');
                pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://unpkg.com/pdfjs-dist@3.11.174/build/pdf.worker.min.js';
                const format = $('format').value;
                const dpi = parseFloat($('dpi').value);
                images = [];
                $('info').textContent = '正在转换…';
                try {
                    const bytes = await currentFile.arrayBuffer();
                    const pdf = await pdfjsLib.getDocument({ data: bytes }).promise;
                    const total = pdf.numPages;
                    $('totalPage').textContent = total;
                    const preview = $('preview');
                    preview.innerHTML = '';
                    for (let i = 1; i <= total; i++) {
                        const page = await pdf.getPage(i);
                        const viewport = page.getViewport({ scale: 1 });
                        const scale = dpi / 72;
                        const canvas = document.createElement('canvas');
                        canvas.width = viewport.width * scale;
                        canvas.height = viewport.height * scale;
                        const ctx = canvas.getContext('2d');
                        if (format === 'jpeg') { ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, canvas.width, canvas.height); }
                        await page.render({ canvasContext: ctx, viewport: page.getViewport({ scale: scale }) }).promise;
                        const blob = await new Promise(res => canvas.toBlob(res, 'image/' + format, 0.92));
                        images.push({ blob: blob, name: 'page-' + i + '.' + (format === 'jpeg' ? 'jpg' : format) });
                        const previewImg = document.createElement('img');
                        previewImg.src = URL.createObjectURL(blob);
                        previewImg.style.maxWidth = '100%';
                        previewImg.style.border = '1px solid #ddd';
                        previewImg.style.margin = '4px';
                        preview.appendChild(previewImg);
                    }
                    $('info').textContent = '转换完成，共 ' + images.length + ' 张图片';
                } catch (err) {
                    $('info').textContent = '转换失败：' + err.message;
                }
            }

            async function downloadAll() {
                if (!images.length) return alert('请先转换 PDF');
                for (const img of images) {
                    const a = document.createElement('a');
                    a.href = URL.createObjectURL(img.blob);
                    a.download = img.name;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    await new Promise(r => setTimeout(r, 300));
                }
            }
            </script>
<?php include '_footer.php'; ?>
