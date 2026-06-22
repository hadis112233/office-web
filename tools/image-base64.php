<?php
$title = '图片 Base64 互转';
$desc = '图片文件 ↔ Base64 数据URI字符串。';
include '_header.php';
?>
            <div class="tool-panel">
                <label>① 上传图片 → 输出 Base64</label>
                <input type="file" id="file" accept="image/*">
                <div class="btn-row">
                    <button class="btn" onclick="encodeImage()">生成 Base64</button>
                    <button class="btn secondary" onclick="copyB64()">复制结果</button>
                </div>
                <label style="margin-top:12px;display:block;">Base64 输出（data:image/...;base64,...）</label>
                <textarea id="b64Out" readonly placeholder="Base64 字符串将显示在此处..."></textarea>
                <p class="tip" id="info"></p>
            </div>
            <div class="tool-panel">
                <label>② 粘贴 Base64 → 预览图片</label>
                <textarea id="b64In" placeholder="在此粘贴 data:image/...;base64,... 字符串..."></textarea>
                <div class="btn-row">
                    <button class="btn" onclick="decodeB64()">预览图片</button>
                    <button class="btn success" onclick="downloadFromB64()">下载图片</button>
                </div>
                <label style="margin-top:12px;display:block;">图片预览</label>
                <div id="preview" style="text-align:center;padding:10px;min-height:120px;border:1px dashed #ccc;border-radius:6px;">（预览将在此显示）</div>
            </div>
            <script>
            let lastDataURL = null;
            const $ = id => document.getElementById(id);

            function encodeImage() {
                const f = $('file').files[0];
                if (!f) return alert('请先选择图片');
                const reader = new FileReader();
                reader.onload = function(ev) {
                    lastDataURL = ev.target.result;
                    $('b64Out').value = lastDataURL;
                    $('info').textContent = '文件：' + f.name + '，大小：' + (f.size / 1024).toFixed(2) + ' KB，Base64 长度：' + lastDataURL.length;
                };
                reader.readAsDataURL(f);
            }

            function copyB64() {
                const v = $('b64Out').value;
                if (!v) return alert('请先生成 Base64');
                navigator.clipboard.writeText(v).then(() => alert('已复制到剪贴板'));
            }

            function decodeB64() {
                const s = ($('b64In').value || '').trim();
                if (!s) return alert('请粘贴 Base64 字符串');
                if (!s.startsWith('data:image/')) {
                    alert('内容不是有效的 data:image/...;base64,... URI');
                    return;
                }
                lastDataURL = s;
                const p = $('preview');
                p.innerHTML = '';
                const img = document.createElement('img');
                img.src = s;
                img.style.maxWidth = '100%';
                img.onload = function() {
                    $('info').textContent = '已解析：' + img.width + ' × ' + img.height + ' px';
                };
                img.onerror = function() {
                    alert('图片加载失败，内容可能不是有效的 Base64 图片数据');
                };
                p.appendChild(img);
            }

            function downloadFromB64() {
                const s = lastDataURL || ($('b64In').value || '').trim();
                if (!s || !s.startsWith('data:image/')) return alert('请先预览有效图片');
                const m = s.match(/^data:image\/(\w+);/);
                const ext = m ? (m[1] === 'jpeg' ? 'jpg' : m[1]) : 'png';
                const a = document.createElement('a');
                a.href = s;
                a.download = 'image.' + ext;
                a.click();
            }
            </script>
<?php include '_footer.php'; ?>
