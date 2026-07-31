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
                <label>组件状态</label>
                <div style="padding:10px;border:1px dashed #ccc;border-radius:6px;background:#fafafa;min-height:60px;">
                    PDF 处理组件已随系统本地部署，局域网断网环境也可使用。
                </div>
            </div>
            <script src="../static/vendor/pdf-lib.min.js" onerror="this.onerror=null;this.src='https://unpkg.com/pdf-lib@1.17.1/dist/pdf-lib.min.js';document.getElementById('info').textContent='本地组件不可用，正在尝试网络备用组件…'"></script>
            <script>
            const $ = id => document.getElementById(id);
            let resultBlob = null;
            let currentFile = null;
            let totalPages = 0;

            $('file').addEventListener('change', async function(e) {
                const f = e.target.files[0];
                if (!f) return;
                resultBlob = null;
                if ((f.type && f.type !== 'application/pdf') || f.size > 120 * 1024 * 1024) {
                    currentFile = null;
                    e.target.value = '';
                    $('info').textContent = '请选择 120 MB 以内的 PDF 文件。';
                    return;
                }
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
                    $('info').textContent = '已选择：' + f.name + '（PDF 组件暂未加载）';
                }
            });

            async function doSplit() {
                if (!currentFile) return alert('请先选择 PDF');
                if (!window.PDFLib) return alert('PDF 组件未加载，请刷新页面或联系管理员');
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
                const url = URL.createObjectURL(resultBlob);
                a.href = url;
                a.download = 'split.pdf';
                a.click();
                setTimeout(() => URL.revokeObjectURL(url), 1000);
            }
            </script>
<?php include '_footer.php'; ?>
