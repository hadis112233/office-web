<?php
$title = '字数统计';
$desc = '统计字符数(含/不含空格)、单词数、行数、中文字数。';
include '_header.php';
?>
            <div class="tool-panel">
                <label for="input1">输入文本</label>
                <textarea id="input1" placeholder="在此输入文本，下方将实时显示统计结果..."></textarea>
                <div class="stats" id="stats">
                    <div class="stat-box"><div class="num" id="st-total">0</div><div class="label">总字符（含空格）</div></div>
                    <div class="stat-box"><div class="num" id="st-nospace">0</div><div class="label">字符（不含空格）</div></div>
                    <div class="stat-box"><div class="num" id="st-cn">0</div><div class="label">中文字数</div></div>
                    <div class="stat-box"><div class="num" id="st-word">0</div><div class="label">单词数</div></div>
                    <div class="stat-box"><div class="num" id="st-line">0</div><div class="label">行数</div></div>
                    <div class="stat-box"><div class="num" id="st-space">0</div><div class="label">空白字符</div></div>
                </div>
            </div>
            <script>
            (function() {
                const ta = document.getElementById('input1');
                function update() {
                    const text = ta.value || '';
                    const total = text.length;
                    const nospace = text.replace(/\s/g, '').length;
                    const cn = (text.match(/[\u4e00-\u9fa5]/g) || []).length;
                    const word = (text.trim() ? text.trim().split(/\s+/).length : 0);
                    const line = text ? text.split(/\r?\n/).length : 0;
                    const space = total - nospace;
                    document.getElementById('st-total').innerText = total;
                    document.getElementById('st-nospace').innerText = nospace;
                    document.getElementById('st-cn').innerText = cn;
                    document.getElementById('st-word').innerText = word;
                    document.getElementById('st-line').innerText = line;
                    document.getElementById('st-space').innerText = space;
                }
                ta.addEventListener('input', update);
                update();
            })();
            </script>
<?php include '_footer.php'; ?>
