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
                    <option value="jpeg" selected>JPG</option>
                </select>
                <label>分辨率（DPI）：<span id="dpiVal">150</span></label>
                <input type="range" id="dpi" min="72" max="300" step="10" value="150">
                <div class="btn-row">
                    <button class="btn" onclick="doConvert()">开始转换</button>
                    <button class="btn secondary" onclick="downloadCurrent()">下载当前页</button>
                    <button class="btn success" id="downloadAllButton" onclick="downloadAll()">打包下载全部 ZIP</button>
                </div>
                <p class="tip" id="info">选择 PDF 后即可开始转换。</p>
            </div>
            <div class="tool-panel">
                <div class="pdf-preview-heading"><label>双边预览（共 <span id="totalPage">0</span> 页）</label><div class="pdf-page-nav"><button class="btn secondary" id="prevPage" type="button">← 上一页</button><span id="pageIndicator">第 0 / 0 页</span><button class="btn secondary" id="nextPage" type="button">下一页 →</button></div></div>
                <div class="pdf-preview-grid" id="preview"><div class="pdf-preview-pane"><strong>📄 PDF 原页</strong><div id="sourcePreview" class="pdf-preview-content">选择文件后显示预览</div></div><div class="pdf-preview-pane"><strong>🖼️ 转换结果</strong><div id="imagePreview" class="pdf-preview-content">完成转换后显示 JPG / PNG</div></div></div>
            </div>
            <style>
            .pdf-preview-heading{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px}.pdf-page-nav{display:flex;align-items:center;gap:8px}.pdf-page-nav .btn{padding:7px 10px;font-size:12px}.pdf-page-nav span{min-width:74px;color:#64748b;font-size:12px;text-align:center}.pdf-preview-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.pdf-preview-pane{min-width:0;padding:12px;border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc}.pdf-preview-pane strong{display:block;margin-bottom:10px;color:#334155;font-size:13px}.pdf-preview-content{display:flex;align-items:center;justify-content:center;min-height:300px;overflow:auto;border:1px dashed #cbd5e1;border-radius:7px;color:#94a3b8;text-align:center}.pdf-preview-content canvas,.pdf-preview-content img{display:block;max-width:100%;height:auto}@media(max-width:700px){.pdf-preview-heading{align-items:flex-start;flex-direction:column}.pdf-preview-grid{grid-template-columns:1fr}.pdf-preview-content{min-height:200px}}
            </style>
            <script src="../static/vendor/pdf.min.js" onload="window.OFFICE_PDF_WORKER='../static/vendor/pdf.worker.min.js'" onerror="this.onerror=null;this.onload=function(){window.OFFICE_PDF_WORKER='https://unpkg.com/pdfjs-dist@3.11.174/build/pdf.worker.min.js'};this.src='https://unpkg.com/pdfjs-dist@3.11.174/build/pdf.min.js';document.getElementById('info').textContent='本地组件不可用，正在尝试网络备用组件…'"></script>
            <script src="../static/vendor/jszip.min.js" onerror="this.onerror=null;this.src='https://unpkg.com/jszip@3.10.1/dist/jszip.min.js'"></script>
            <script>
            const $ = id => document.getElementById(id);
            let images = [];
            let currentFile = null;
            let pdfDoc = null;
            let pdfLoading = null;
            let currentPage = 1;

            $('dpi').addEventListener('input', function() { $('dpiVal').textContent = this.value; });

            $('file').addEventListener('change', function(e) {
                const f = e.target.files[0];
                if (!f) return;
                currentFile = f;
                $('info').textContent = '已选择：' + f.name + '（' + formatSize(f.size) + '），正在读取 PDF…';
                images = [];
                $('totalPage').textContent = '0';
                pdfDoc = null;
                pdfLoading = null;
                currentPage = 1;
                loadPdf().catch(function(err) { $('info').textContent = '无法读取 PDF：' + err.message; });
            });

            function formatSize(bytes) { return bytes < 1024 * 1024 ? (bytes / 1024).toFixed(1) + ' KB' : (bytes / 1024 / 1024).toFixed(2) + ' MB'; }
            async function loadPdf() {
                if (pdfDoc) return pdfDoc;
                if (pdfLoading) return pdfLoading;
                if (!currentFile) throw new Error('请先选择 PDF');
                const pdfjsLib = window['pdfjsLib'] || window['pdfjs-dist/build/pdf'];
                if (!pdfjsLib) throw new Error('PDF 组件未加载，请刷新页面或联系管理员');
                pdfjsLib.GlobalWorkerOptions.workerSrc = window.OFFICE_PDF_WORKER || '../static/vendor/pdf.worker.min.js';
                pdfLoading = (async function() {
                    pdfDoc = await pdfjsLib.getDocument({ data: await currentFile.arrayBuffer() }).promise;
                    $('totalPage').textContent = pdfDoc.numPages;
                    $('info').textContent = '已选择：' + currentFile.name + '，共 ' + pdfDoc.numPages + ' 页。可开始转换。';
                    await showPage(1);
                    return pdfDoc;
                })();
                try { return await pdfLoading; } finally { pdfLoading = null; }
            }
            async function renderSource(pageNumber) {
                const source = $('sourcePreview'); source.textContent = '正在加载原页…';
                const page = await pdfDoc.getPage(pageNumber);
                const viewport = page.getViewport({ scale: 1.25 });
                const canvas = document.createElement('canvas'); canvas.width = viewport.width; canvas.height = viewport.height;
                await page.render({ canvasContext: canvas.getContext('2d'), viewport: viewport }).promise;
                source.innerHTML = ''; source.appendChild(canvas);
            }
            async function showPage(pageNumber) {
                if (!pdfDoc || pageNumber < 1 || pageNumber > pdfDoc.numPages) return;
                currentPage = pageNumber; $('pageIndicator').textContent = '第 ' + currentPage + ' / ' + pdfDoc.numPages + ' 页';
                $('prevPage').disabled = currentPage === 1; $('nextPage').disabled = currentPage === pdfDoc.numPages;
                await renderSource(currentPage);
                const output = $('imagePreview'); output.innerHTML = '';
                if (images[currentPage - 1]) {
                    const img = document.createElement('img');
                    const previewUrl = URL.createObjectURL(images[currentPage - 1].blob);
                    img.onload = img.onerror = function() { URL.revokeObjectURL(previewUrl); };
                    img.src = previewUrl;
                    output.appendChild(img);
                } else output.textContent = '完成转换后显示当前页结果';
            }

            async function doConvert() {
                if (!currentFile) return alert('请先选择 PDF');
                const format = $('format').value;
                const dpi = parseFloat($('dpi').value);
                images = [];
                $('info').textContent = '正在转换…';
                try {
                    const pdf = await loadPdf();
                    const total = pdf.numPages;
                    $('totalPage').textContent = total;
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
                        const blob = await new Promise(function(resolve, reject) {
                            canvas.toBlob(function(result) {
                                if (result) resolve(result);
                                else reject(new Error('第 ' + i + ' 页图片生成失败'));
                            }, 'image/' + format, 0.92);
                        });
                        images.push({ blob: blob, name: 'page-' + i + '.' + (format === 'jpeg' ? 'jpg' : format) });
                        $('info').textContent = '正在转换… ' + i + ' / ' + total;
                    }
                    $('info').textContent = '转换完成，共 ' + images.length + ' 张图片';
                    await showPage(currentPage);
                } catch (err) {
                    $('info').textContent = '转换失败：' + err.message;
                }
            }

            async function downloadAll() {
                if (!images.length) return alert('请先转换 PDF');
                if (!window.JSZip) return alert('ZIP 组件未加载，请刷新页面或联系管理员');
                const button = $('downloadAllButton');
                button.disabled = true;
                $('info').textContent = '正在打包 ZIP…';
                try {
                    const zip = new window.JSZip();
                    images.forEach(function(image) { zip.file(image.name, image.blob); });
                    const blob = await zip.generateAsync({
                        type: 'blob',
                        compression: 'STORE',
                        streamFiles: true
                    }, function(metadata) {
                        $('info').textContent = '正在打包 ZIP… ' + Math.round(metadata.percent) + '%';
                    });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'pdf-images.zip';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    setTimeout(function() { URL.revokeObjectURL(url); }, 1000);
                    $('info').textContent = 'ZIP 打包完成：' + images.length + ' 张图片，大小 ' + formatSize(blob.size);
                } catch (error) {
                    $('info').textContent = 'ZIP 打包失败：' + error.message;
                } finally {
                    button.disabled = false;
                }
            }
            function downloadCurrent() {
                const image = images[currentPage - 1];
                if (!image) return alert('请先完成转换');
                const url = URL.createObjectURL(image.blob);
                const a = document.createElement('a'); a.href = url; a.download = image.name;
                document.body.appendChild(a); a.click(); document.body.removeChild(a);
                setTimeout(function() { URL.revokeObjectURL(url); }, 1000);
            }
            $('prevPage').addEventListener('click', function() { showPage(currentPage - 1); });
            $('nextPage').addEventListener('click', function() { showPage(currentPage + 1); });
            </script>
<?php include '_footer.php'; ?>
