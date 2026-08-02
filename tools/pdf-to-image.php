<?php
$title = 'PDF 转图片';
$desc = '将 PDF 每页导出为 PNG/JPG；文件只在浏览器中处理，不会上传服务器。';
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
                    <button class="btn" id="convertButton" disabled>开始转换</button>
                    <button class="btn secondary" id="downloadCurrentButton" disabled>下载当前页</button>
                    <button class="btn success" id="downloadAllButton" disabled>打包下载全部 ZIP</button>
                </div>
                <p class="tip" id="info">选择 PDF 后即可开始转换。</p>
                <p class="tip">PNG 更清晰但体积较大；JPG 更适合扫描件和照片类 PDF。</p>
            </div>
            <div class="tool-panel">
                <div class="pdf-preview-heading"><label>双边预览（共 <span id="totalPage">0</span> 页）</label><div class="pdf-page-nav"><button class="btn secondary" id="prevPage" type="button" disabled>← 上一页</button><span id="pageIndicator">第 0 / 0 页</span><button class="btn secondary" id="nextPage" type="button" disabled>下一页 →</button></div></div>
                <div class="pdf-preview-grid" id="preview"><div class="pdf-preview-pane"><strong>📄 PDF 原页</strong><div id="sourcePreview" class="pdf-preview-content">选择文件后显示预览</div></div><div class="pdf-preview-pane"><strong>🖼️ 转换结果</strong><div id="imagePreview" class="pdf-preview-content">完成转换后显示 JPG / PNG</div></div></div>
            </div>
            <style>
            .pdf-preview-heading{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px}.pdf-page-nav{display:flex;align-items:center;gap:8px}.pdf-page-nav .btn{padding:7px 10px;font-size:12px}.pdf-page-nav span{min-width:74px;color:#64748b;font-size:12px;text-align:center}.pdf-preview-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.pdf-preview-pane{min-width:0;padding:12px;border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc}.pdf-preview-pane strong{display:block;margin-bottom:10px;color:#334155;font-size:13px}.pdf-preview-content{display:flex;align-items:center;justify-content:center;min-height:300px;overflow:auto;border:1px dashed #cbd5e1;border-radius:7px;color:#94a3b8;text-align:center}.pdf-preview-content canvas,.pdf-preview-content img{display:block;max-width:100%;height:auto}@media(max-width:700px){.pdf-preview-heading{align-items:flex-start;flex-direction:column}.pdf-preview-grid{grid-template-columns:1fr}.pdf-preview-content{min-height:200px}}
            </style>
            <script src="../static/vendor/pdf.min.js" onload="window.OFFICE_PDF_WORKER='../static/vendor/pdf.worker.min.js'" onerror="this.onerror=null;this.onload=function(){window.OFFICE_PDF_WORKER='https://unpkg.com/pdfjs-dist@3.11.174/build/pdf.worker.min.js'};this.src='https://unpkg.com/pdfjs-dist@3.11.174/build/pdf.min.js';document.getElementById('info').textContent='本地组件不可用，正在尝试网络备用组件…'"></script>
            <script src="../static/vendor/jszip.min.js" onerror="this.onerror=null;this.src='https://unpkg.com/jszip@3.10.1/dist/jszip.min.js'"></script>
            <script>
            const $ = id => document.getElementById(id);
            const MAX_FILE_BYTES = 120 * 1024 * 1024;
            const MAX_PAGES = 200;
            const MAX_PAGE_PIXELS = 20000000;
            const MAX_CANVAS_SIDE = 8192;
            const MAX_IMAGES_BYTES = 256 * 1024 * 1024;
            const PREVIEW_PIXELS = 4000000;
            let images = [];
            let imagesBytes = 0;
            let currentFile = null;
            let pdfDoc = null;
            let pdfLoadingTask = null;
            let currentPage = 1;
            let documentVersion = 0;
            let previewVersion = 0;
            let previewRenderTask = null;
            let converting = false;
            let zipping = false;

            $('dpi').addEventListener('input', function() { $('dpiVal').textContent = this.value; });

            function formatSize(bytes) {
                return bytes < 1024 * 1024 ? (bytes / 1024).toFixed(1) + ' KB' : (bytes / 1024 / 1024).toFixed(2) + ' MB';
            }

            function releaseImages() {
                for (const image of images) image.blob = null;
                images = [];
                imagesBytes = 0;
                updateActionButtons();
            }

            function invalidateResults() {
                if (!images.length) return;
                releaseImages();
                $('imagePreview').textContent = '参数已修改，请重新转换';
                $('info').textContent = '输出参数已修改，请重新转换 PDF。';
            }

            async function releasePdf() {
                previewVersion += 1;
                if (previewRenderTask) {
                    try { previewRenderTask.cancel(); } catch (error) {}
                    previewRenderTask = null;
                }
                const task = pdfLoadingTask;
                pdfLoadingTask = null;
                pdfDoc = null;
                if (task) {
                    try { await task.destroy(); } catch (error) {}
                }
            }

            function updateActionButtons() {
                const locked = converting || zipping;
                $('file').disabled = locked;
                $('format').disabled = locked;
                $('dpi').disabled = locked;
                $('convertButton').disabled = locked || !pdfDoc;
                $('downloadCurrentButton').disabled = locked || !images[currentPage - 1];
                $('downloadAllButton').disabled = locked || images.length === 0;
                $('convertButton').textContent = converting ? '正在转换…' : '开始转换';
            }

            function safeScale(viewport, requested, maxPixels) {
                return Math.min(
                    requested,
                    MAX_CANVAS_SIDE / viewport.width,
                    MAX_CANVAS_SIDE / viewport.height,
                    Math.sqrt(maxPixels / (viewport.width * viewport.height))
                );
            }

            function ensureCurrent(version, file) {
                if (version !== documentVersion || file !== currentFile) throw new Error('已取消旧的处理任务');
            }

            $('file').addEventListener('change', async function(e) {
                const version = ++documentVersion;
                const file = e.target.files[0] || null;
                currentFile = null;
                releaseImages();
                await releasePdf();
                if (version !== documentVersion) return;
                currentPage = 1;
                $('totalPage').textContent = '0';
                $('pageIndicator').textContent = '第 0 / 0 页';
                $('sourcePreview').textContent = '选择文件后显示预览';
                $('imagePreview').textContent = '完成转换后显示 JPG / PNG';
                if (!file) { updateActionButtons(); return; }
                if ((file.type && file.type !== 'application/pdf') || file.size > MAX_FILE_BYTES) {
                    e.target.value = '';
                    $('info').textContent = '请选择 120 MB 以内的 PDF 文件。';
                    updateActionButtons();
                    return;
                }
                currentFile = file;
                $('info').textContent = '已选择：' + file.name + '（' + formatSize(file.size) + '），正在读取 PDF…';
                try {
                    await loadPdf(version, file);
                } catch (error) {
                    if (version === documentVersion) {
                        await releasePdf();
                        currentFile = null;
                        e.target.value = '';
                        $('info').textContent = '无法读取 PDF：' + (error.message || '未知错误');
                    }
                } finally {
                    updateActionButtons();
                }
            });

            async function loadPdf(version, file) {
                const pdfjsLib = window.pdfjsLib || window['pdfjs-dist/build/pdf'];
                if (!pdfjsLib) throw new Error('PDF 组件未加载，请刷新页面或联系管理员');
                pdfjsLib.GlobalWorkerOptions.workerSrc = window.OFFICE_PDF_WORKER || '../static/vendor/pdf.worker.min.js';
                const data = await file.arrayBuffer();
                ensureCurrent(version, file);
                const task = pdfjsLib.getDocument({ data });
                pdfLoadingTask = task;
                const doc = await task.promise;
                ensureCurrent(version, file);
                if (doc.numPages < 1 || doc.numPages > MAX_PAGES) throw new Error('仅支持 1–' + MAX_PAGES + ' 页的 PDF');
                pdfDoc = doc;
                $('totalPage').textContent = doc.numPages;
                $('info').textContent = '已选择：' + file.name + '，共 ' + doc.numPages + ' 页。可开始转换。';
                await showPage(1);
                return doc;
            }

            async function renderSource(pageNumber, version) {
                const source = $('sourcePreview');
                source.textContent = '正在加载原页…';
                const page = await pdfDoc.getPage(pageNumber);
                let canvas = null;
                try {
                    const base = page.getViewport({ scale: 1 });
                    const scale = safeScale(base, 1.25, PREVIEW_PIXELS);
                    const viewport = page.getViewport({ scale });
                    canvas = document.createElement('canvas');
                    canvas.width = Math.max(1, Math.round(viewport.width));
                    canvas.height = Math.max(1, Math.round(viewport.height));
                    const ctx = canvas.getContext('2d', { alpha: false });
                    if (!ctx) throw new Error('浏览器无法创建预览画布');
                    ctx.fillStyle = '#fff';
                    ctx.fillRect(0, 0, canvas.width, canvas.height);
                    const renderTask = page.render({ canvasContext: ctx, viewport });
                    previewRenderTask = renderTask;
                    await renderTask.promise;
                    if (version !== previewVersion) return;
                    source.replaceChildren(canvas);
                    canvas = null;
                } catch (error) {
                    if (error && error.name !== 'RenderingCancelledException') throw error;
                } finally {
                    if (version === previewVersion) previewRenderTask = null;
                    page.cleanup();
                    if (canvas) { canvas.width = 1; canvas.height = 1; }
                }
            }

            async function showPage(pageNumber) {
                if (!pdfDoc || pageNumber < 1 || pageNumber > pdfDoc.numPages) return;
                const version = ++previewVersion;
                if (previewRenderTask) {
                    try { previewRenderTask.cancel(); } catch (error) {}
                    previewRenderTask = null;
                }
                currentPage = pageNumber;
                $('pageIndicator').textContent = '第 ' + currentPage + ' / ' + pdfDoc.numPages + ' 页';
                $('prevPage').disabled = true;
                $('nextPage').disabled = true;
                try {
                    await renderSource(currentPage, version);
                    if (version !== previewVersion) return;
                    const output = $('imagePreview');
                    output.replaceChildren();
                    const result = images[currentPage - 1];
                    if (result && result.blob) {
                        const image = document.createElement('img');
                        const previewUrl = URL.createObjectURL(result.blob);
                        image.onload = image.onerror = () => URL.revokeObjectURL(previewUrl);
                        image.src = previewUrl;
                        output.appendChild(image);
                    } else {
                        output.textContent = '完成转换后显示当前页结果';
                    }
                } catch (error) {
                    if (version === previewVersion) $('sourcePreview').textContent = '预览失败：' + (error.message || '未知错误');
                } finally {
                    if (version === previewVersion && pdfDoc) {
                        $('prevPage').disabled = converting || zipping || currentPage === 1;
                        $('nextPage').disabled = converting || zipping || currentPage === pdfDoc.numPages;
                        updateActionButtons();
                    }
                }
            }

            async function doConvert() {
                if (converting || zipping) return;
                if (!currentFile || !pdfDoc) return alert('请先选择 PDF');
                const version = documentVersion;
                const file = currentFile;
                const doc = pdfDoc;
                const format = $('format').value;
                const dpi = Number.parseFloat($('dpi').value);
                converting = true;
                releaseImages();
                updateActionButtons();
                $('prevPage').disabled = true;
                $('nextPage').disabled = true;
                $('info').textContent = '正在转换…';
                let limitedPages = 0;
                try {
                    for (let i = 1; i <= doc.numPages; i++) {
                        ensureCurrent(version, file);
                        const page = await doc.getPage(i);
                        let canvas = null;
                        try {
                            const base = page.getViewport({ scale: 1 });
                            const requested = dpi / 72;
                            const scale = safeScale(base, requested, MAX_PAGE_PIXELS);
                            if (scale < requested) limitedPages += 1;
                            const viewport = page.getViewport({ scale });
                            canvas = document.createElement('canvas');
                            canvas.width = Math.max(1, Math.round(viewport.width));
                            canvas.height = Math.max(1, Math.round(viewport.height));
                            const ctx = canvas.getContext('2d', { alpha: false });
                            if (!ctx) throw new Error('浏览器无法创建页面画布');
                            ctx.fillStyle = '#fff';
                            ctx.fillRect(0, 0, canvas.width, canvas.height);
                            await page.render({ canvasContext: ctx, viewport }).promise;
                            ensureCurrent(version, file);
                            const blob = await new Promise((resolve, reject) => canvas.toBlob(
                                result => result ? resolve(result) : reject(new Error('第 ' + i + ' 页图片生成失败')),
                                'image/' + format,
                                format === 'jpeg' ? 0.92 : undefined
                            ));
                            imagesBytes += blob.size;
                            if (imagesBytes > MAX_IMAGES_BYTES) throw new Error('转换结果累计超过 256 MB，请选择 JPG 或降低 DPI');
                            images.push({ blob, name: 'page-' + String(i).padStart(3, '0') + '.' + (format === 'jpeg' ? 'jpg' : format) });
                            $('info').textContent = '正在转换… ' + i + ' / ' + doc.numPages + '，已生成 ' + formatSize(imagesBytes);
                        } finally {
                            page.cleanup();
                            if (canvas) { canvas.width = 1; canvas.height = 1; }
                        }
                    }
                    $('info').textContent = '转换完成，共 ' + images.length + ' 张图片，总计 ' + formatSize(imagesBytes) + (limitedPages ? '；' + limitedPages + ' 页已自动限制像素' : '');
                    await showPage(Math.min(currentPage, images.length));
                } catch (error) {
                    releaseImages();
                    if (version === documentVersion) $('info').textContent = '转换失败：' + (error.message || '未知错误');
                } finally {
                    converting = false;
                    updateActionButtons();
                    if (pdfDoc) {
                        $('prevPage').disabled = currentPage === 1;
                        $('nextPage').disabled = currentPage === pdfDoc.numPages;
                    }
                }
            }

            async function downloadAll() {
                if (converting || zipping) return;
                if (!images.length) return alert('请先转换 PDF');
                if (!window.JSZip) return alert('ZIP 组件未加载，请刷新页面或联系管理员');
                zipping = true;
                updateActionButtons();
                $('info').textContent = '正在打包 ZIP…';
                try {
                    const zip = new window.JSZip();
                    images.forEach(image => zip.file(image.name, image.blob));
                    const blob = await zip.generateAsync({ type: 'blob', compression: 'STORE', streamFiles: true }, metadata => {
                        $('info').textContent = '正在打包 ZIP… ' + Math.round(metadata.percent) + '%';
                    });
                    const url = URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = (currentFile ? currentFile.name.replace(/\.pdf$/i, '') : 'pdf') + '-images.zip';
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                    setTimeout(() => URL.revokeObjectURL(url), 1000);
                    $('info').textContent = 'ZIP 打包完成：' + images.length + ' 张图片，大小 ' + formatSize(blob.size);
                } catch (error) {
                    $('info').textContent = 'ZIP 打包失败：' + (error.message || '未知错误');
                } finally {
                    zipping = false;
                    updateActionButtons();
                }
            }

            function downloadCurrent() {
                const image = images[currentPage - 1];
                if (!image || !image.blob) return alert('请先完成转换');
                const url = URL.createObjectURL(image.blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = image.name;
                document.body.appendChild(link);
                link.click();
                link.remove();
                setTimeout(() => URL.revokeObjectURL(url), 1000);
            }

            $('convertButton').addEventListener('click', doConvert);
            $('downloadCurrentButton').addEventListener('click', downloadCurrent);
            $('downloadAllButton').addEventListener('click', downloadAll);
            $('format').addEventListener('change', invalidateResults);
            $('dpi').addEventListener('change', invalidateResults);
            $('prevPage').addEventListener('click', () => showPage(currentPage - 1));
            $('nextPage').addEventListener('click', () => showPage(currentPage + 1));
            window.addEventListener('beforeunload', () => {
                documentVersion += 1;
                releaseImages();
                if (previewRenderTask) {
                    try { previewRenderTask.cancel(); } catch (error) {}
                }
                if (pdfLoadingTask) {
                    try { pdfLoadingTask.destroy(); } catch (error) {}
                }
            });
            </script>
<?php include '_footer.php'; ?>
