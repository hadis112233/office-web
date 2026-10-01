<?php
$title = '文本大小写与全半角转换';
$desc = '一键转换大/小写、全角/半角字符和倒转文本；内容仅在浏览器中处理。';
include '_header.php';
?>
            <div class="tool-panel">
                <label for="input1">输入文本</label>
                <textarea id="input1" placeholder="请输入要转换的文本..."></textarea>
                <div class="btn-row">
                    <button class="btn" onclick="doUpper()">大写 UPPER</button>
                    <button class="btn" onclick="doLower()">小写 lower</button>
                    <button class="btn success" onclick="doTitle()">首字母大写</button>
                    <button class="btn warning" onclick="doReverse()">倒转文本</button>
                    <button class="btn secondary" onclick="toHalfWidth()">全角转半角</button>
                    <button class="btn secondary" onclick="toFullWidth()">半角转全角</button>
                    <button class="btn secondary" onclick="clearAll()">清空</button>
                </div>
                <p class="tip" id="tip">提示：全半角转换适用于英文字母、数字、常用符号和空格；中文内容不会被改动。</p>
            </div>
            <div class="tool-panel">
                <label for="output1">输出结果</label>
                <textarea id="output1" readonly placeholder="结果将显示在此处..."></textarea>
                <div class="btn-row">
                    <button class="btn" onclick="copyOutput()">复制结果</button>
                    <button class="btn secondary" onclick="swapIO()">使用输出作为新输入</button>
                </div>
            </div>
            <script>
            function $(id) { return document.getElementById(id); }
            function setOutput(v) { $('output1').value = v; }
            function doUpper() { setOutput(($('input1').value || '').toUpperCase()); }
            function doLower() { setOutput(($('input1').value || '').toLowerCase()); }
            function doTitle() {
                const text = $('input1').value || '';
                setOutput(text.replace(/\w\S*/g, function(w) {
                    return w.charAt(0).toUpperCase() + w.substr(1).toLowerCase();
                }));
            }
            function doReverse() {
                const text = $('input1').value || '';
                setOutput(text.split('').reverse().join(''));
            }
            function toHalfWidth() {
                const text = $('input1').value || '';
                setOutput(text.replace(/\u3000/g, ' ').replace(/[\uFF01-\uFF5E]/g, function(char) {
                    return String.fromCharCode(char.charCodeAt(0) - 0xFEE0);
                }));
                $('tip').textContent = '已转换可识别的全角英文、数字、符号和空格；中文内容保持不变。';
            }
            function toFullWidth() {
                const text = $('input1').value || '';
                setOutput(text.replace(/ /g, '\u3000').replace(/[!-~]/g, function(char) {
                    return String.fromCharCode(char.charCodeAt(0) + 0xFEE0);
                }));
                $('tip').textContent = '已转换可识别的半角英文、数字、符号和空格；中文内容保持不变。';
            }
            function clearAll() { $('input1').value = ''; $('output1').value = ''; }
            function copyOutput() {
                const out = $('output1').value;
                if (!out) return alert('没有可复制的内容');
                navigator.clipboard.writeText(out).then(() => alert('已复制到剪贴板'));
            }
            function swapIO() {
                const out = $('output1').value;
                if (!out) return alert('输出为空，无法交换');
                $('input1').value = out;
            }
            </script>
<?php include '_footer.php'; ?>
