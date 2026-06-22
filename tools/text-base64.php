<?php
$title = 'Base64 编解码';
$desc = '文本与 Base64 字符串互转。支持中文 UTF-8。';
include '_header.php';
?>
            <div class="tool-panel">
                <label for="input1">原文本</label>
                <textarea id="input1" placeholder="请输入要编码的文本（支持中文 UTF-8）..."></textarea>
                <div class="btn-row">
                    <button class="btn" onclick="doEncode()">编码 → Base64</button>
                    <button class="btn success" onclick="doDecode()">解码 ← Base64</button>
                    <button class="btn secondary" onclick="clearAll()">清空</button>
                </div>
                <p class="tip">提示：点击"编码"将上方原文本转换为 Base64；点击"解码"将上方内容视为 Base64 转换为原文本。</p>
            </div>
            <div class="tool-panel">
                <label for="output1">Base64 / 解码结果</label>
                <textarea id="output1" readonly placeholder="结果将显示在此处..."></textarea>
                <div class="btn-row">
                    <button class="btn" onclick="copyOutput()">复制结果</button>
                </div>
            </div>
            <script>
            function $(id) { return document.getElementById(id); }
            function setOutput(v) { $('output1').value = v; }
            function doEncode() {
                const text = $('input1').value || '';
                try {
                    const bytes = new TextEncoder().encode(text);
                    let binary = '';
                    bytes.forEach(b => binary += String.fromCharCode(b));
                    setOutput(btoa(binary));
                } catch (e) {
                    setOutput('编码失败：' + e.message);
                }
            }
            function doDecode() {
                const text = ($('input1').value || '').trim();
                if (!text) return setOutput('');
                try {
                    const binary = atob(text);
                    const bytes = new Uint8Array(binary.length);
                    for (let i = 0; i < binary.length; i++) bytes[i] = binary.charCodeAt(i);
                    setOutput(new TextDecoder('utf-8').decode(bytes));
                } catch (e) {
                    setOutput('解码失败：输入内容不是有效的 Base64 字符串。');
                }
            }
            function clearAll() { $('input1').value = ''; $('output1').value = ''; }
            function copyOutput() {
                const out = $('output1').value;
                if (!out) return alert('没有可复制的内容');
                navigator.clipboard.writeText(out).then(() => alert('已复制到剪贴板'));
            }
            </script>
<?php include '_footer.php'; ?>
