<?php
$title = '二维码生成';
$desc = '将文本或 URL 生成二维码图片下载。';
include '_header.php';
?>
            <div class="tool-panel">
                <label>输入文本或 URL</label>
                <textarea id="text" placeholder="请输入要生成二维码的内容..."></textarea>
                <label>尺寸（像素）</label>
                <select id="size">
                    <option value="150x150">150 × 150</option>
                    <option value="200x200">200 × 200</option>
                    <option value="300x300" selected>300 × 300</option>
                    <option value="400x400">400 × 400</option>
                    <option value="500x500">500 × 500</option>
                </select>
                <div class="btn-row">
                    <button class="btn success" onclick="generate()">生成二维码</button>
                    <button class="btn" onclick="downloadQR()">下载图片</button>
                    <button class="btn secondary" onclick="clearAll()">清空</button>
                </div>
            </div>
            <div class="tool-panel">
                <label>二维码预览</label>
                <div id="qrBox" style="text-align:center;padding:20px;min-height:320px;display:flex;align-items:center;justify-content:center;">
                    <span style="color:#999;">请输入内容并点击生成</span>
                </div>
                <p class="tip">提示：可右键图片另存为，或点击"下载图片"按钮下载。</p>
            </div>
            <script>
            function $(id) { return document.getElementById(id); }
            function generate() {
                const text = $('text').value.trim();
                if (!text) return alert('请输入内容');
                const size = $('size').value;
                const url = 'https://api.qrserver.com/v1/create-qr-code/?size=' + size + '&data=' + encodeURIComponent(text);
                $('qrBox').innerHTML = '<img id="qrImg" src="' + url + '" alt="二维码" style="max-width:100%;" />';
            }
            function downloadQR() {
                const img = document.getElementById('qrImg');
                if (!img) return alert('请先生成二维码');
                const a = document.createElement('a');
                a.href = img.src;
                a.download = 'qrcode.png';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            }
            function clearAll() {
                $('text').value = '';
                $('qrBox').innerHTML = '<span style="color:#999;">请输入内容并点击生成</span>';
            }
            </script>
<?php include '_footer.php'; ?>
