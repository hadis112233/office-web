<?php
$title = '去除空行与重复';
$desc = '去除前后空白行、去重、去除多余空行。';
include '_header.php';
?>
            <div class="tool-panel">
                <label for="input1">输入文本</label>
                <textarea id="input1" placeholder="请输入文本..."></textarea>
                <div class="btn-row">
                    <button class="btn" onclick="removeEmpty()">去除空行</button>
                    <button class="btn success" onclick="removeDup()">去除重复行</button>
                    <button class="btn warning" onclick="trimLines()">去除首尾空格</button>
                    <button class="btn" onclick="mergeEmpty()">合并连续空行</button>
                    <button class="btn secondary" onclick="clearAll()">清空</button>
                </div>
            </div>
            <div class="tool-panel">
                <label for="output1">输出结果</label>
                <textarea id="output1" readonly placeholder="结果将显示在此处..."></textarea>
                <div class="btn-row">
                    <button class="btn" onclick="copyOutput()">复制结果</button>
                </div>
            </div>
            <script>
            function $(id) { return document.getElementById(id); }
            function getLines() {
                return ($('input1').value || '').split(/\r?\n/);
            }
            function setOutput(v) { $('output1').value = v; }
            function removeEmpty() {
                const lines = getLines().filter(l => l.trim() !== '');
                setOutput(lines.join('\n'));
            }
            function removeDup() {
                const seen = new Set();
                const out = [];
                getLines().forEach(l => {
                    if (!seen.has(l)) { seen.add(l); out.push(l); }
                });
                setOutput(out.join('\n'));
            }
            function trimLines() {
                setOutput(getLines().map(l => l.trim()).join('\n'));
            }
            function mergeEmpty() {
                const lines = getLines();
                const out = [];
                let lastEmpty = false;
                for (let i = 0; i < lines.length; i++) {
                    const isEmpty = lines[i].trim() === '';
                    if (isEmpty) {
                        if (!lastEmpty) { out.push(''); lastEmpty = true; }
                    } else {
                        out.push(lines[i]); lastEmpty = false;
                    }
                }
                setOutput(out.join('\n'));
            }
            function clearAll() { $('input1').value = ''; $('output1').value = ''; }
            function copyOutput() {
                const out = $('output1').value;
                if (!out) return alert('没有可复制的内容');
                navigator.clipboard.writeText(out).then(() => alert('已复制到剪贴板'));
            }
            </script>
<?php include '_footer.php'; ?>
