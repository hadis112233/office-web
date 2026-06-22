<?php
$title = 'PDF 加水印';
$desc = '为 PDF 每页添加自定义文字水印。';
include '_header.php';
?>
            <div class="tool-panel">
                <label>选择 PDF</label>
                <input type="file" id="file" accept="application/pdf">
                <label>水印文字</label>
                <input type="text" id="watermark" value="CONFIDENTIAL  机密文档" style="width:100%;">
                <label>字体大小（pt）：<span id="sizeVal">36</span></label>
                <input type="range" id="size" min="12" max="120" step="2" value="36">
                <label>透明度：<span id="opacityVal">0.25</span></label>
                <input type="range" id="opacity" min="0.05" max="1" step="0.05" value="0.25">
                <label>旋转角度（度）：<span id="rotateVal">-30</span></label>
                <input type="range" id="rotate" min="-90" max="90" step="5" value="-30">
                <label>颜色</label>
                <input type="color" id="color" value="#cc0000">
                <label>渲染 DPI：<span id="dpiVal">150</span></label>
                <input type="range" id="dpi" min="72" max="300" step="10" value="150">
                <div class="btn-row">
                    <button class="btn" onclick="doWatermark()">开始添加水印</button>
                    <button class="btn success" onclick="downloadResult()">下载带水印 PDF</button>
                </div>
                <p class="tip" id="info"></p>
            </div>
            <div class="tool-panel">
                <label>预览（第 <span id="curPage">0</span> / <span id="totalPage">0</span> 页）</label>
                <div id="preview" style="text-align:center;padding:10px;min-height:120px;border:1px dashed #ccc;border-radius:6px;">（进度将在此显示）</div>
            </div>
            <script src="https://unpkg.com/pdfjs-dist@3.11.174/build/pdf.min.js" onerror="document.getElementById('info').textContent='⚠ CDN 加载失败，当前为演示模式'"></script>
            <script src="https://unpkg.com/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
            <script>
            const $ = id => document.getElementById(id);
            let resultBlob = null;
            let currentFile = null;

            $('size').addEventListener('input', function() { $('sizeVal').textContent = this.value; });
            $('opacity').addEventListener('input', function() { $('opacityVal').textContent = this.value; });
            $('rotate').addEventListener('input', function() { $('rotateVal').textContent = this.value; });
            $('dpi').addEventListener('input', function() { $('dpiVal').textContent = this.value; });

            $('file').addEventListener('change', function(e) {
                const f = e.target.files[0];
                if (!f) return;
                currentFile = f;
                $('info').textContent = '已选择：' + f.name + '（' + (f.size / 1024).toFixed(1) + ' KB）';
                $('preview').innerHTML = '';
                $('curPage').textContent = '0';
                $('totalPage').textContent = '0';
            });

            async function doWatermark() {
                if (!currentFile) return alert('请先选择 PDF');
                const pdfjsLib = window['pdfjsLib'] || window['pdfjs-dist/build/pdf'];
                if (!pdfjsLib) return alert('PDF.js 未加载');
                const jspdf = window.jspdf && window.jspdf.jsPDF;
                if (!jspdf) return alert('jsPDF 未加载');
                pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://unpkg.com/pdfjs-dist@3.11.174/build/pdf.worker.min.js';

                const wm = $('watermark').value.trim() || 'WATERMARK';
                const fontSize = parseFloat($('size').value);
                const opacity = parseFloat($('opacity').value);
                const rotate = parseFloat($('rotate').value);
                const color = $('color').value;
                const dpi = parseFloat($('dpi').value);
                $('info').textContent = '正在处理…';
                try {
                    const bytes = await currentFile.arrayBuffer();
                    const pdf = await pdfjsLib.getDocument({ data: bytes }).promise;
                    const total = pdf.numPages;
                    $('totalPage').textContent = total;
                    let pdfDoc = null;
                    const preview = $('preview');
                    preview.innerHTML = '';
                    for (let i = 1; i <= total; i++) {
                        $('curPage').textContent = i;
                        const page = await pdf.getPage(i);
                        const viewport = page.getViewport({ scale: 1 });
                        const scale = dpi / 72;
                        const canvas = document.createElement('canvas');
                        canvas.width = viewport.width * scale;
                        canvas.height = viewport.height * scale;
                        const ctx = canvas.getContext('2d');
                        ctx.fillStyle = '#fff';
                        ctx.fillRect(0, 0, canvas.width, canvas.height);
                        await page.render({ canvasContext: ctx, viewport: page.getViewport({ scale: scale }) }).promise;

                        ctx.save();
                        ctx.font = (fontSize * scale) + 'pt Arial, sans-serif';
                        ctx.fillStyle = color;
                        ctx.globalAlpha = opacity;
                        ctx.translate(canvas.width / 2, canvas.height / 2);
                        ctx.rotate(rotate * Math.PI / 180);
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';
                        const metrics = ctx.measureText(wm);
                        const stepX = metrics.width + 200 * scale;
                        const stepY = 200 * scale;
                        for (let y = -canvas.height; y < canvas.height; y += stepY) {
                            for (let x = -canvas.width; x < canvas.width; x += stepX) {
                                ctx.fillText(wm, x, y);
                            }
                        }
                        ctx.restore();

                        const imgData = canvas.toDataURL('image/jpeg', 0.92);
                        const wPt = viewport.width;
                        const hPt = viewport.height;
                        if (!pdfDoc) pdfDoc = new jspdf({ unit: 'pt', format: [wPt, hPt] });
                        else pdfDoc.addPage([wPt, hPt], 'portrait');
                        pdfDoc.addImage(imgData, 'JPEG', 0, 0, wPt, hPt);
                        if (i === 1) {
                            const pv = document.createElement('img');
                            pv.src = imgData;
                            pv.style.maxWidth = '100%';
                            preview.appendChild(pv);
                        }
                    }
                    const out = pdfDoc.output('blob');
                    resultBlob = out;
                    $('info').textContent = '加水印完成，共 ' + total + ' 页。输出大小：' + (out.size / 1024).toFixed(1) + ' KB';
                } catch (err) {
                    $('info').textContent = '处理失败：' + err.message;
                }
            }

            function downloadResult() {
                if (!resultBlob) return alert('请先添加水印');
                const a = document.createElement('a');
                a.href = URL.createObjectURL(resultBlob);
                a.download = 'watermarked.pdf';
                a.click();
            }
            </script>
<?php include '_footer.php'; ?>
