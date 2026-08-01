<?php
$title = 'PDF 压缩（降质）';
$desc = '通过降低页面分辨率和图片质量压缩 PDF；文件只在浏览器中处理，不会上传服务器。';
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
                    <button class="btn" id="compressBtn">开始压缩</button>
                    <button class="btn success" id="downloadBtn" disabled>下载压缩 PDF</button>
                </div>
                <p class="tip" id="info"></p>
                <p class="tip">注意：压缩会将每页转为 JPEG 图片，文字将不再能选中或搜索。</p>
            </div>
            <div class="tool-panel">
                <label>预览（第 <span id="curPage">0</span> / <span id="totalPage">0</span> 页）</label>
                <div id="preview" style="text-align:center;padding:10px;min-height:120px;border:1px dashed #ccc;border-radius:6px;">（进度将在此显示）</div>
            </div>
            <script src="../static/vendor/pdf.min.js" onload="window.OFFICE_PDF_WORKER='../static/vendor/pdf.worker.min.js'" onerror="this.onerror=null;this.onload=function(){window.OFFICE_PDF_WORKER='https://unpkg.com/pdfjs-dist@3.11.174/build/pdf.worker.min.js'};this.src='https://unpkg.com/pdfjs-dist@3.11.174/build/pdf.min.js';document.getElementById('info').textContent='本地组件不可用，正在尝试网络备用组件…'"></script>
            <script src="../static/vendor/pdf-lib.min.js" onerror="this.onerror=null;this.src='https://unpkg.com/pdf-lib@1.17.1/dist/pdf-lib.min.js'"></script>
            <script>
            const $ = id => document.getElementById(id);
            const MAX_FILE_BYTES = 120 * 1024 * 1024;
            const MAX_PAGES = 300;
            const MAX_PAGE_PIXELS = 16000000;
            const MAX_CANVAS_SIDE = 8192;
            const MAX_IMAGE_BYTES = 256 * 1024 * 1024;
            let resultBlob = null;
            let currentFile = null;
            let pdfjsLib = null;
            let operationVersion = 0;
            let busy = false;

            $('dpi').addEventListener('input', function() { $('dpiVal').textContent = this.value; });
            $('quality').addEventListener('input', function() { $('qVal').textContent = this.value; });

            $('file').addEventListener('change', function(e) {
                operationVersion += 1;
                const f = e.target.files[0];
                if (!f) return;
                resultBlob = null;
                $('downloadBtn').disabled = true;
                if ((f.type && f.type !== 'application/pdf') || f.size > MAX_FILE_BYTES) {
                    currentFile = null;
                    e.target.value = '';
                    $('info').textContent = '请选择 120 MB 以内的 PDF 文件。';
                    return;
                }
                currentFile = f;
                $('info').textContent = '已选择：' + f.name + '（' + (f.size / 1024).toFixed(1) + ' KB）';
                $('preview').textContent = '（进度将在此显示）';
                $('curPage').textContent = '0';
                $('totalPage').textContent = '0';
            });

            function setBusy(value) {
                busy = value;
                for (const id of ['file', 'dpi', 'quality', 'compressBtn']) $(id).disabled = value;
                $('downloadBtn').disabled = value || !resultBlob;
                $('compressBtn').textContent = value ? '正在压缩…' : '开始压缩';
            }

            function canvasJpeg(canvas, quality) {
                return new Promise((resolve, reject) => canvas.toBlob(
                    blob => blob ? resolve(blob) : reject(new Error('浏览器无法生成 JPEG 页面')),
                    'image/jpeg',
                    quality
                ));
            }

            function safeScale(viewport, dpi) {
                const requested = dpi / 72;
                return Math.min(
                    requested,
                    MAX_CANVAS_SIDE / viewport.width,
                    MAX_CANVAS_SIDE / viewport.height,
                    Math.sqrt(MAX_PAGE_PIXELS / (viewport.width * viewport.height))
                );
            }

            function ensureCurrent(version, file) {
                if (version !== operationVersion || file !== currentFile) throw new Error('已取消旧的压缩任务');
            }

            async function doCompress() {
                if (busy) return;
                if (!currentFile) return alert('请先选择 PDF');
                if (!window['pdfjs-dist/build/pdf']) {
                    try { pdfjsLib = window['pdfjsLib']; } catch(e){}
                } else {
                    pdfjsLib = window['pdfjs-dist/build/pdf'] || window['pdfjsLib'];
                }
                pdfjsLib = pdfjsLib || window.pdfjsLib;
                if (!pdfjsLib) return alert('PDF 组件未加载，请刷新页面或联系管理员');
                pdfjsLib.GlobalWorkerOptions.workerSrc = window.OFFICE_PDF_WORKER || '../static/vendor/pdf.worker.min.js';
                if (!window.PDFLib) return alert('PDF 导出组件未加载，请刷新页面或联系管理员');

                const dpi = Number.parseFloat($('dpi').value);
                const quality = Number.parseFloat($('quality').value);
                const file = currentFile;
                const version = ++operationVersion;
                let loadingTask = null;
                let pdf = null;
                resultBlob = null;
                setBusy(true);
                $('info').textContent = '正在读取 PDF…';
                try {
                    const bytes = await file.arrayBuffer();
                    ensureCurrent(version, file);
                    loadingTask = pdfjsLib.getDocument({ data: bytes });
                    pdf = await loadingTask.promise;
                    const total = pdf.numPages;
                    if (total < 1 || total > MAX_PAGES) throw new Error('仅支持 1–' + MAX_PAGES + ' 页的 PDF');
                    $('totalPage').textContent = total;
                    const outputPdf = await window.PDFLib.PDFDocument.create();
                    const preview = $('preview');
                    preview.textContent = '';
                    let imageBytesTotal = 0;
                    let limitedPages = 0;
                    for (let i = 1; i <= total; i++) {
                        ensureCurrent(version, file);
                        $('curPage').textContent = i;
                        const page = await pdf.getPage(i);
                        let canvas = null;
                        try {
                            const viewport = page.getViewport({ scale: 1 });
                            const scale = safeScale(viewport, dpi);
                            if (scale < dpi / 72) limitedPages += 1;
                            const renderViewport = page.getViewport({ scale });
                            canvas = document.createElement('canvas');
                            canvas.width = Math.max(1, Math.round(renderViewport.width));
                            canvas.height = Math.max(1, Math.round(renderViewport.height));
                            const ctx = canvas.getContext('2d', { alpha: false });
                            if (!ctx) throw new Error('浏览器无法创建页面画布');
                            ctx.fillStyle = '#fff';
                            ctx.fillRect(0, 0, canvas.width, canvas.height);
                            await page.render({ canvasContext: ctx, viewport: renderViewport }).promise;
                            ensureCurrent(version, file);
                            const jpegBlob = await canvasJpeg(canvas, quality);
                            imageBytesTotal += jpegBlob.size;
                            if (imageBytesTotal > MAX_IMAGE_BYTES) throw new Error('压缩页面累计超过 256 MB，请降低 DPI 或图片质量');
                            const jpeg = await outputPdf.embedJpg(await jpegBlob.arrayBuffer());
                            const outputPage = outputPdf.addPage([viewport.width, viewport.height]);
                            outputPage.drawImage(jpeg, { x: 0, y: 0, width: viewport.width, height: viewport.height });
                            preview.textContent = '已处理 ' + i + ' / ' + total + ' 页，临时图片 ' + (imageBytesTotal / 1024 / 1024).toFixed(1) + ' MB';
                        } finally {
                            page.cleanup();
                            if (canvas) { canvas.width = 1; canvas.height = 1; }
                        }
                    }
                    ensureCurrent(version, file);
                    const outputBytes = await outputPdf.save({ useObjectStreams: true });
                    resultBlob = new Blob([outputBytes], { type: 'application/pdf' });
                    const change = resultBlob.size < file.size ? '节省 ' + ((1 - resultBlob.size / file.size) * 100).toFixed(1) + '%' : '输出变大，建议降低 DPI 或质量';
                    $('info').textContent = '压缩完成：' + (file.size / 1024).toFixed(1) + ' KB → ' + (resultBlob.size / 1024).toFixed(1) + ' KB（' + change + '）' + (limitedPages ? '；' + limitedPages + ' 页已自动限制像素' : '');
                } catch (err) {
                    if (version === operationVersion) $('info').textContent = '压缩失败：' + (err.message || '未知错误');
                    resultBlob = null;
                } finally {
                    if (pdf) {
                        try { await pdf.cleanup(); } catch (error) {}
                    }
                    if (loadingTask) {
                        try { await loadingTask.destroy(); } catch (error) {}
                    }
                    if (version === operationVersion) setBusy(false);
                }
            }

            function downloadResult() {
                if (!resultBlob) return alert('请先压缩 PDF');
                const a = document.createElement('a');
                const url = URL.createObjectURL(resultBlob);
                a.href = url;
                a.download = 'compressed.pdf';
                a.click();
                setTimeout(() => URL.revokeObjectURL(url), 1000);
            }

            $('compressBtn').addEventListener('click', doCompress);
            $('downloadBtn').addEventListener('click', downloadResult);
            window.addEventListener('beforeunload', () => { operationVersion += 1; resultBlob = null; });
            </script>
<?php include '_footer.php'; ?>
