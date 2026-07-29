<?php
$title = 'PDF 合并';
$desc = '上传多个 PDF，合并为一个 PDF 下载。';
include '_header.php';
?>
            <div class="tool-panel">
                <label>选择多个 PDF（按文件顺序合并）</label>
                <input type="file" id="files" accept="application/pdf" multiple>
                <div id="fileList" style="margin-top:8px;font-size:13px;color:#555;"></div>
                <div class="btn-row" style="margin-top:10px;">
                    <button class="btn" onclick="doMerge()">开始合并</button>
                    <button class="btn success" onclick="downloadResult()">下载合并后的 PDF</button>
                </div>
                <p class="tip" id="info"></p>
            </div>
            <div class="tool-panel">
                <label>组件状态</label>
                <div id="demo" style="padding:10px;border:1px dashed #ccc;border-radius:6px;background:#fafafa;min-height:60px;">
                    PDF 处理组件已随系统本地部署，局域网断网环境也可使用。
                </div>
            </div>
            <script src="../static/vendor/pdf-lib.min.js" onerror="this.onerror=null;this.src='https://unpkg.com/pdf-lib@1.17.1/dist/pdf-lib.min.js';document.getElementById('info').textContent='本地组件不可用，正在尝试网络备用组件…'"></script>
            <script>
            const $ = id => document.getElementById(id);
            let resultBlob = null;
            let selectedFiles = [];

            $('files').addEventListener('change', function(e) {
                selectedFiles = Array.from(e.target.files);
                const list = $('fileList');
                list.replaceChildren();
                if (!selectedFiles.length) {
                    list.textContent = '（未选择文件）';
                    return;
                }
                selectedFiles.forEach(function(file, index) {
                    const row = document.createElement('div');
                    row.textContent = (index + 1) + '. ' + file.name + '（' + (file.size / 1024).toFixed(1) + ' KB）';
                    list.appendChild(row);
                });
            });

            async function doMerge() {
                if (!selectedFiles.length) return alert('请先选择 PDF 文件');
                if (!window.PDFLib) return alert('PDF 组件未加载，请刷新页面或联系管理员');
                $('info').textContent = '正在合并…';
                try {
                    const PDFLib = window.PDFLib;
                    const mergedPdf = await PDFLib.PDFDocument.create();
                    for (const f of selectedFiles) {
                        const bytes = await f.arrayBuffer();
                        const pdf = await PDFLib.PDFDocument.load(bytes);
                        const pages = await mergedPdf.copyPages(pdf, pdf.getPageIndices());
                        pages.forEach(p => mergedPdf.addPage(p));
                    }
                    const mergedBytes = await mergedPdf.save();
                    resultBlob = new Blob([mergedBytes], { type: 'application/pdf' });
                    $('info').textContent = '合并完成，共 ' + mergedPdf.getPageCount() + ' 页，大小：' + (resultBlob.size / 1024).toFixed(1) + ' KB';
                } catch (err) {
                    $('info').textContent = '合并失败：' + err.message;
                }
            }

            function downloadResult() {
                if (!resultBlob) return alert('请先合并 PDF');
                const a = document.createElement('a');
                a.href = URL.createObjectURL(resultBlob);
                a.download = 'merged.pdf';
                a.click();
            }

            (function checkLib(){
                const check = setInterval(() => {
                    if (window.PDFLib) {
                        clearInterval(check);
                        $('info').textContent = '✓ PDF 组件已加载，可上传 PDF 合并';
                    }
                }, 300);
                setTimeout(() => clearInterval(check), 5000);
            })();
            </script>
<?php include '_footer.php'; ?>
