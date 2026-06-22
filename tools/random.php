<?php
$title = '随机生成';
$desc = '随机数字、密码、颜色、字符串。';
include '_header.php';
?>
            <div class="tool-panel">
                <h3>① 随机整数</h3>
                <label>最小值</label>
                <input type="number" id="min" value="1" />
                <label>最大值</label>
                <input type="number" id="max" value="100" />
                <label>数量</label>
                <input type="number" id="count" value="10" min="1" max="1000" />
                <div class="btn-row">
                    <button class="btn success" onclick="randInt()">生成随机数</button>
                    <button class="btn" onclick="copyInt()">复制</button>
                </div>
                <textarea id="intOut" readonly placeholder="随机整数将显示在此处..."></textarea>
            </div>

            <div class="tool-panel">
                <h3>② 随机密码</h3>
                <label>密码长度</label>
                <input type="number" id="pwLen" value="12" min="4" max="128" />
                <div style="margin:10px 0;display:flex;flex-wrap:wrap;gap:15px;">
                    <label style="display:flex;align-items:center;gap:6px;"><input type="checkbox" id="pwUpper" checked /> 大写字母 (A-Z)</label>
                    <label style="display:flex;align-items:center;gap:6px;"><input type="checkbox" id="pwLower" checked /> 小写字母 (a-z)</label>
                    <label style="display:flex;align-items:center;gap:6px;"><input type="checkbox" id="pwNum" checked /> 数字 (0-9)</label>
                    <label style="display:flex;align-items:center;gap:6px;"><input type="checkbox" id="pwSym" checked /> 特殊字符 (!@#$...)</label>
                </div>
                <div class="btn-row">
                    <button class="btn success" onclick="randPwd()">生成密码</button>
                    <button class="btn" onclick="copyPwd()">复制</button>
                </div>
                <textarea id="pwdOut" readonly placeholder="随机密码将显示在此处..."></textarea>
            </div>

            <div class="tool-panel">
                <h3>③ 随机颜色</h3>
                <label>生成数量</label>
                <input type="number" id="colorCount" value="6" min="1" max="50" />
                <div class="btn-row">
                    <button class="btn success" onclick="randColor()">生成颜色</button>
                    <button class="btn" onclick="copyColor()">复制全部</button>
                </div>
                <div id="colorBox" style="display:flex;flex-wrap:wrap;gap:10px;margin-top:15px;"></div>
            </div>
            <script>
            function $(id) { return document.getElementById(id); }
            function randInt() {
                const min = parseInt($('min').value);
                const max = parseInt($('max').value);
                const count = parseInt($('count').value);
                if (isNaN(min) || isNaN(max) || isNaN(count)) return alert('请输入有效数值');
                if (min > max) return alert('最小值不能大于最大值');
                const result = [];
                for (let i = 0; i < count; i++) {
                    result.push(Math.floor(Math.random() * (max - min + 1)) + min);
                }
                $('intOut').value = result.join(', ');
            }
            function copyInt() { copy($('intOut').value); }
            function copyPwd() { copy($('pwdOut').value); }
            function copyColor() {
                const items = document.querySelectorAll('#colorBox .color-item');
                if (!items.length) return alert('暂无颜色');
                const arr = [];
                items.forEach(i => arr.push(i.dataset.hex));
                copy(arr.join('\n'));
            }
            function copy(v) {
                if (!v) return alert('暂无内容');
                navigator.clipboard.writeText(v).then(() => alert('已复制'));
            }
            function randPwd() {
                const len = parseInt($('pwLen').value);
                const useUpper = $('pwUpper').checked;
                const useLower = $('pwLower').checked;
                const useNum = $('pwNum').checked;
                const useSym = $('pwSym').checked;
                if (!useUpper && !useLower && !useNum && !useSym) return alert('请至少选择一种字符类型');
                let pool = '';
                if (useUpper) pool += 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                if (useLower) pool += 'abcdefghijklmnopqrstuvwxyz';
                if (useNum) pool += '0123456789';
                if (useSym) pool += '!@#$%^&*()_+-=[]{}|;:,.<>?';
                let pwd = '';
                for (let i = 0; i < len; i++) {
                    pwd += pool.charAt(Math.floor(Math.random() * pool.length));
                }
                $('pwdOut').value = pwd;
            }
            function randColor() {
                const n = parseInt($('colorCount').value) || 6;
                const box = $('colorBox');
                box.innerHTML = '';
                for (let i = 0; i < n; i++) {
                    const hex = '#' + Math.floor(Math.random() * 16777215).toString(16).padStart(6, '0').toUpperCase();
                    const item = document.createElement('div');
                    item.className = 'color-item';
                    item.dataset.hex = hex;
                    item.style.cssText = 'width:110px;text-align:center;cursor:pointer;';
                    item.innerHTML = `<div style="height:80px;background:${hex};border:1px solid #ddd;border-radius:6px;"></div><div style="font-family:monospace;font-size:13px;margin-top:5px;color:#333;">${hex}</div>`;
                    item.onclick = () => copy(hex);
                    box.appendChild(item);
                }
            }
            randColor();
            </script>
<?php include '_footer.php'; ?>
