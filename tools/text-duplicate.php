<?php
$title = '清单整理与去重';
$desc = '整理名单、编号和文件清单：去重、排序、统计重复次数并下载结果。文本不会上传服务器。';
include '_header.php';
?>
            <div class="tool-panel">
                <label for="input1">输入文本</label>
                <textarea id="input1" placeholder="请输入文本..."></textarea>
                <p><label><input type="checkbox" id="ignoreSpace"> 去重时忽略首尾空格</label>　<label><input type="checkbox" id="ignoreCase"> 去重时忽略英文大小写</label></p>
                <div class="btn-row">
                    <button class="btn" onclick="removeEmpty()">去除空行</button>
                    <button class="btn success" onclick="removeDup()">去除重复行</button>
                    <button class="btn warning" onclick="trimLines()">去除首尾空格</button>
                    <button class="btn" onclick="mergeEmpty()">合并连续空行</button>
                    <button class="btn" onclick="sortLines(false)">自然升序</button>
                    <button class="btn" onclick="sortLines(true)">自然降序</button>
                    <button class="btn" onclick="countDuplicates()">重复次数统计</button>
                    <button class="btn secondary" onclick="clearAll()">清空</button>
                </div>
                <p class="tip">自然排序会把编号 2 排在编号 10 前面。忽略选项只影响去重和统计，保留首次出现的原始文本。</p>
                <p id="status" role="status">每次操作使用输入框内容；可将结果用作下一步输入。</p>
            </div>
            <div class="tool-panel">
                <label for="output1">输出结果</label>
                <textarea id="output1" readonly placeholder="结果将显示在此处..."></textarea>
                <div class="btn-row">
                    <button class="btn" onclick="copyOutput()">复制结果</button>
                    <button class="btn secondary" onclick="useOutput()">结果用作输入</button>
                    <button class="btn success" onclick="downloadOutput()">下载 TXT</button>
                </div>
            </div>
            <script>
            function $(id) { return document.getElementById(id); }
            function getLines() {
                const text = $('input1').value;
                if (text.length > 2000000) throw new Error('文本过长，请分批处理（最多 200 万字符）。');
                const lines = text ? text.split(/\r\n|\r|\n/) : [];
                if (lines.length > 50000) throw new Error('行数过多，请分批处理（最多 5 万行）。');
                return lines;
            }
            function setOutput(v, message) { $('output1').value = v; $('status').textContent = message || '处理完成，可复制或下载结果。'; }
            function lineKey(line) {
                if ($('ignoreSpace').checked) line = line.trim();
                return $('ignoreCase').checked ? line.toLowerCase() : line;
            }
            function removeEmpty() {
                const lines = getLines().filter(l => l.trim() !== '');
                setOutput(lines.join('\n'));
            }
            function removeDup() {
                const seen = new Set();
                const out = [];
                getLines().forEach(l => {
                    const key = lineKey(l);
                    if (!seen.has(key)) { seen.add(key); out.push(l); }
                });
                setOutput(out.join('\n'), '保留 ' + out.length + ' 行，移除 ' + (getLines().length - out.length) + ' 个重复行。');
            }
            function sortLines(descending) {
                const collator = new Intl.Collator('zh-CN', {numeric:true, sensitivity:'variant'});
                const lines = getLines().sort((a,b) => (descending ? -1 : 1) * collator.compare(a,b));
                setOutput(lines.join('\n'), '已按自然' + (descending ? '降序' : '升序') + '整理 ' + lines.length + ' 行。');
            }
            function countDuplicates() {
                const counts = new Map();
                for (const line of getLines()) {
                    const key = lineKey(line);
                    if (!counts.has(key)) counts.set(key, {line, count:0});
                    counts.get(key).count++;
                }
                const rows = Array.from(counts.values()).sort((a,b) => b.count-a.count);
                setOutput(rows.map(row => row.count + '\t' + row.line).join('\n'), '共 ' + rows.length + ' 种内容，左侧为出现次数；空白行也计入统计。');
            }
            function useOutput() { $('input1').value = $('output1').value; $('status').textContent = '结果已用作输入，可以继续整理。'; }
            function downloadOutput() {
                if (!$('output1').value) { $('status').textContent = '请先生成非空结果。'; return; }
                const url = URL.createObjectURL(new Blob(['\uFEFF', $('output1').value], {type:'text/plain;charset=utf-8'}));
                const link = document.createElement('a'); link.href=url; link.download='整理清单.txt'; link.click();
                setTimeout(() => URL.revokeObjectURL(url), 1000);
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
            function clearAll() { $('input1').value = ''; $('output1').value = ''; $('status').textContent = '已清空文本。'; }
            function copyOutput() {
                const out = $('output1').value;
                if (!out) return alert('没有可复制的内容');
                if (!navigator.clipboard) { $('status').textContent = '请选中输出框内容后手动复制。'; return; }
                navigator.clipboard.writeText(out).then(() => { $('status').textContent = '已复制到剪贴板。'; }).catch(() => { $('status').textContent = '复制未成功，请选中输出框内容后手动复制。'; });
            }
            ['removeEmpty','removeDup','trimLines','mergeEmpty','sortLines','countDuplicates'].forEach(name => {
                const action = window[name];
                window[name] = function(...args) {
                    try { action(...args); } catch (error) { $('status').textContent = error.message; }
                };
            });
            </script>
<?php include '_footer.php'; ?>
