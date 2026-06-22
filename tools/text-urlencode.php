<?php
$title = 'URL 编解码';
$desc = 'URL encode / decode 转换。';
include '_header.php';
?>
            <div class="tool-panel">
                <label for="input1">输入文本 / URL</label>
                <textarea id="input1" placeholder="请输入文本或 URL..."></textarea>
                <div class="btn-row">
                    <button class="btn" onclick="doEncode()">编码 Encode</button>
                    <button class="btn success" onclick="doDecode()">解码 Decode</button>
                    <button class="btn warning" onclick="doEncodeComp()">完整编码 (encodeURIComponent)</button>
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
            function setOutput(v) { $('output1').value = v; }
            function doEncode() {
                setOutput(encodeURI($('input1').value || ''));
            }
            function doDecode() {
                try {
                    setOutput(decodeURIComponent($('input1').value || ''));
                } catch (e) {
                    setOutput('解码失败：' + e.message);
                }
            }
            function doEncodeComp() {
                setOutput(encodeURIComponent($('input1').value || ''));
            }
            function clearAll() { $('input1').value = ''; $('output1').value = ''; }
            function copyOutput() {
                const out = $('output1').value;
                if (!out) return alert('没有可复制的内容');
                navigator.clipboard.writeText(out).then(() => alert('已复制到剪贴板'));
            }
            </script>
<?php include '_footer.php'; ?>
