<?php
$title = 'PDF 压缩（降质）';
$desc = '通过重绘每页为图片（降低分辨率）实现简易压缩。';
include '_header.php';
?>
            <div class="tool-panel">
                <label>选择 PDF</label>
                <input type="file" id="file" accept="application/pdf">
                <label>分辨率（DPI）：<span id="dpiVal">100</span></label>
                <input type="range" id="dpi" min="50" max="200" step="10" value="100">
                <label>图片质量：<span id="qVal">0.6</span></label>
                <input type="range" id="quality" min="0.1" max="1" step="0.05" value="0.6">
                <div class="btn-row">
                    <button class="btn" onclick="doCompress()">开始压缩</button>
                    <button class="btn success" onclick="downloadResult()">下载压缩 PDF</button>
                </div>
                <p class="tip" id="info"></p>
            </div>
            <div class="tool-panel">
                <label>预览（第 <span id="curPage">0</span> / <span id="totalPage">0</span> 页）</label>
                <div id="preview" style="text-align:center;padding:10px;min-height:120px;border:1px dashed #ccc;border-radius:6px;">（进度将在此显示）</div>
            </div>
            <script src="https://unpkg.com/pdfjs-dist@3.11.174/build/pdf.min.js" onerror="onCdnFail()"></script>
            <script src="https://unpkg.com/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
            <script>
            const $ = id => document.getElementById(id);
            let resultBlob = null;
            let currentFile = null;
            let pdfjsLib = null;

            $('dpi').addEventListener('input', function() { $('dpiVal').textContent = this.value; });
            $('quality').addEventListener('input', function() { $('qVal').textContent = this.value; });

            $('file').addEventListener('change', function(e) {
                const f = e.target.files[0];
                if (!f) return;
                currentFile = f;
                $('info').textContent = '已选择：' + f.name + '（' + (f.size / 1024).toFixed(1) + ' KB）';
                $('preview').innerHTML = '';
                $('curPage').textContent = '0';
                $('totalPage').textContent = '0';
            });

            function onCdnFail() {
                $('info').textContent = '⚠ CDN 加载失败，当前为演示模式';
            }

            async function doCompress() {
                if (!currentFile) return alert('请先选择 PDF');
                if (!window['pdfjs-dist/build/pdf']) {
                    try { pdfjsLib = window['pdfjsLib']; } catch(e){}
                } else {
                    pdfjsLib = window['pdfjs-dist/build/pdf'] || window['pdfjsLib'];
                }
                pdfjsLib = pdfjsLib || window.pdfjsLib;
                if (!pdfjsLib) return alert('PDF.js 未加载，请检查网络');
                pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://unpkg.com/pdfjs-dist@3.11.174/build/pdf.worker.min.js';
                const jspdf = window.jspdf && window.jspdf.jsPDF;
                if (!jspdf) return alert('jsPDF 未加载');

                const dpi = parseFloat($('dpi').value);
                const quality = parseFloat($('quality').value);
                $('info').textContent = '正在读取 PDF…';
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
                        await page.render({ canvasContext: ctx, viewport: page.getViewport({ scale: scale }) }).promise;
                        const imgData = canvas.toDataURL('image/jpeg', quality);
                        const wPt = viewport.width;
                        const hPt = viewport.height;
                        if (!pdfDoc) pdfDoc = new jspdf({ unit: 'pt', format: [wPt, hPt] });
                        else pdfDoc.addPage([wPt, hPt], 'portrait');
                        pdfDoc.addImage(imgData, 'JPEG', 0, 0, wPt, hPt);
                    }
                    const out = pdfDoc.output('blob');
                    resultBlob = out;
                    $('info').textContent = '压缩完成：' + (currentFile.size / 1024).toFixed(1) + ' KB → ' + (out.size / 1024).toFixed(1) + ' KB';
                } catch (err) {
                    $('info').textContent = '压缩失败：' + err.message;
                }
            }

            function downloadResult() {
                if (!resultBlob) return alert('请先压缩 PDF');
                const a = document.createElement('a');
                a.href = URL.createObjectURL(resultBlob);
                a.download = 'compressed.pdf';
                a.click();
            }
            </script>
<?php include '_footer.php'; ?>
