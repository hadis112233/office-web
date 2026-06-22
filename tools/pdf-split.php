<?php
$title = 'PDF 分割';
$desc = '从 PDF 中提取指定页范围。';
include '_header.php';
?>
            <div class="tool-panel">
                <label>选择 PDF</label>
                <input type="file" id="file" accept="application/pdf">
                <label>起始页码（从 1 开始）</label>
                <input type="number" id="startPage" min="1" value="1">
                <label>结束页码</label>
                <input type="number" id="endPage" min="1" value="1">
                <div class="btn-row">
                    <button class="btn" onclick="doSplit()">开始分割</button>
                    <button class="btn success" onclick="downloadResult()">下载新 PDF</button>
                </div>
                <p class="tip" id="info"></p>
            </div>
            <div class="tool-panel">
                <label>功能演示模式（无 CDN 时）</label>
                <div style="padding:10px;border:1px dashed #ccc;border-radius:6px;background:#fafafa;min-height:60px;">
                    分割功能需要联网加载 <code>pdf-lib</code>。若未加载，界面可预览。
                </div>
            </div>
            <script src="https://unpkg.com/pdf-lib@1.17.1/dist/pdf-lib.min.js" onerror="document.getElementById('info').textContent='⚠ CDN 加载失败，当前为演示模式'"></script>
            <script>
            const $ = id => document.getElementById(id);
            let resultBlob = null;
            let currentFile = null;
            let totalPages = 0;

            $('file').addEventListener('change', async function(e) {
                const f = e.target.files[0];
                if (!f) return;
                currentFile = f;
                if (window.PDFLib) {
                    try {
                        const bytes = await f.arrayBuffer();
                        const pdf = await window.PDFLib.PDFDocument.load(bytes);
                        totalPages = pdf.getPageCount();
                        $('endPage').value = totalPages;
                        $('info').textContent = '已加载：' + f.name + '，共 ' + totalPages + ' 页';
                    } catch (err) {
                        $('info').textContent = '解析失败：' + err.message;
                    }
                } else {
                    $('info').textContent = '已选择：' + f.name + '（库未加载，演示模式）';
                }
            });

            async function doSplit() {
                if (!currentFile) return alert('请先选择 PDF');
                if (!window.PDFLib) return alert('pdf-lib 未加载，请检查网络后刷新');
                const start = parseInt($('startPage').value);
                const end = parseInt($('endPage').value);
                if (!start || !end || start > end || start < 1) return alert('页码输入错误');
                $('info').textContent = '正在分割…';
                try {
                    const bytes = await currentFile.arrayBuffer();
                    const srcPdf = await window.PDFLib.PDFDocument.load(bytes);
                    totalPages = srcPdf.getPageCount();
                    if (end > totalPages) return alert('结束页码超过总页数 ' + totalPages);
                    const newPdf = await window.PDFLib.PDFDocument.create();
                    const indices = [];
                    for (let i = start - 1; i < end; i++) indices.push(i);
                    const pages = await newPdf.copyPages(srcPdf, indices);
                    pages.forEach(p => newPdf.addPage(p));
                    const outBytes = await newPdf.save();
                    resultBlob = new Blob([outBytes], { type: 'application/pdf' });
                    $('info').textContent = '分割完成：第 ' + start + '-' + end + ' 页，共 ' + pages.length + ' 页，大小：' + (resultBlob.size / 1024).toFixed(1) + ' KB';
                } catch (err) {
                    $('info').textContent = '分割失败：' + err.message;
                }
            }

            function downloadResult() {
                if (!resultBlob) return alert('请先分割 PDF');
                const a = document.createElement('a');
                a.href = URL.createObjectURL(resultBlob);
                a.download = 'split.pdf';
                a.click();
            }
            </script>
<?php include '_footer.php'; ?>
