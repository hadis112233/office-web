<?php
$title = '二维码生成与识别';
$desc = '在浏览器本地生成二维码，或从图片和剪贴板截图中识别二维码内容。数据不会上传服务器。';
include '_header.php';
?>
            <div class="qr-layout">
                <section class="tool-panel qr-panel">
                    <h3>生成二维码</h3>
                    <label for="text">输入文本或 URL</label>
                    <textarea id="text" placeholder="请输入要生成二维码的内容..."></textarea>
                    <label for="size">尺寸（像素）</label>
                    <select id="size">
                        <option value="150">150 × 150</option>
                        <option value="200">200 × 200</option>
                        <option value="300" selected>300 × 300</option>
                        <option value="400">400 × 400</option>
                        <option value="500">500 × 500</option>
                    </select>
                    <div class="btn-row">
                        <button class="btn success" id="generateButton" type="button">生成二维码</button>
                        <button class="btn" id="downloadButton" type="button" disabled>下载图片</button>
                        <button class="btn secondary" id="clearGenerateButton" type="button">清空</button>
                    </div>
                    <div id="qrBox" class="qr-preview" aria-live="polite"><span>请输入内容并点击生成</span></div>
                </section>

                <section class="tool-panel qr-panel">
                    <h3>识别二维码图片</h3>
                    <label class="qr-drop" id="scanDrop" for="scanFile">
                        <input id="scanFile" type="file" accept="image/png,image/jpeg,image/webp,image/gif,image/bmp">
                        <span>📷</span>
                        <strong>点击选择图片，或拖到这里</strong>
                        <small>也可以直接粘贴截图，单张不超过 20 MB</small>
                    </label>
                    <div class="scan-preview" id="scanPreview" hidden><canvas id="scanCanvas" aria-label="待识别二维码图片"></canvas></div>
                    <div class="btn-row">
                        <button class="btn success" id="scanButton" type="button" disabled>重新识别</button>
                        <button class="btn" id="copyScanButton" type="button" disabled>复制内容</button>
                        <button class="btn secondary" id="clearScanButton" type="button">清空</button>
                    </div>
                    <p class="scan-status" id="scanStatus" role="status" aria-live="polite">请选择或粘贴一张包含二维码的图片。</p>
                    <label for="scanResult">识别结果</label>
                    <textarea id="scanResult" class="scan-result" rows="5" readonly placeholder="识别出的文本或网址会显示在这里"></textarea>
                    <a class="btn scan-link" id="scanLink" target="_blank" rel="noopener noreferrer" hidden>确认后打开识别到的网址</a>
                </section>
            </div>
            <p class="tip qr-tip">🔒 生成和识别都在当前浏览器本地完成，文本与图片不会上传服务器或第三方网站。</p>
            <style>
            .qr-layout{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;align-items:start}.qr-panel{margin:0}.qr-panel h3{margin-bottom:15px}.qr-preview{display:flex;min-height:330px;margin-top:18px;padding:15px;align-items:center;justify-content:center;border:1px solid #e2e8f0;border-radius:12px;color:#94a3b8;background:#f8fafc}.qr-preview img{display:block;max-width:100%;height:auto}.qr-drop{display:flex!important;min-height:160px;margin:10px 0 15px!important;padding:20px;flex-direction:column;align-items:center;justify-content:center;gap:7px;border:2px dashed #cbd5e1;border-radius:13px;background:#f8fafc;text-align:center;cursor:pointer;transition:.2s}.qr-drop:hover,.qr-drop.drag-over{border-color:#6366f1;background:#eef2ff}.qr-drop input{position:absolute;width:1px;height:1px;overflow:hidden;opacity:0}.qr-drop>span{font-size:35px}.qr-drop small{color:#64748b;font-size:12px}.scan-preview{overflow:hidden;margin:12px 0;border:1px solid #e2e8f0;border-radius:12px;background:#f8fafc}.scan-preview canvas{display:block;width:100%;height:auto;max-height:420px;object-fit:contain}.scan-status{margin:12px 0;padding:10px 12px;border-radius:9px;color:#475569;background:#f1f5f9;font-size:13px}.scan-status.success{color:#047857;background:#ecfdf5}.scan-status.error{color:#b91c1c;background:#fef2f2}.scan-result{font-family:Consolas,Monaco,monospace}.scan-link{display:inline-block;margin-top:10px;text-decoration:none}.scan-link[hidden]{display:none}.qr-tip{margin-top:18px}@media(max-width:900px){.qr-layout{grid-template-columns:1fr}}html.theme-dark .qr-preview,html.theme-dark .qr-drop,html.theme-dark .scan-preview{border-color:#475569;background:#1e293b}html.theme-dark .qr-drop:hover,html.theme-dark .qr-drop.drag-over{border-color:#818cf8;background:#312e81}html.theme-dark .scan-status{color:#cbd5e1;background:#1e293b}html.theme-dark .scan-status.success{color:#6ee7b7;background:#064e3b}html.theme-dark .scan-status.error{color:#fca5a5;background:#450a0a}
            </style>
            <script src="../static/vendor/qrcode.js"></script>
            <script src="../static/vendor/jsqr.min.js"></script>
            <script>
            (function(){
                const $=id=>document.getElementById(id),maxFileBytes=20*1024*1024,maxPixels=16*1024*1024,maxSide=4096;
                const scanFile=$('scanFile'),scanDrop=$('scanDrop'),scanCanvas=$('scanCanvas'),scanContext=scanCanvas.getContext('2d',{willReadFrequently:true}),scanStatus=$('scanStatus'),scanResult=$('scanResult'),scanLink=$('scanLink'),scanButton=$('scanButton'),copyScanButton=$('copyScanButton');
                let sourceBitmap=null;
                function placeholder(text){const span=document.createElement('span');span.textContent=text;$('qrBox').replaceChildren(span);}
                function setScanStatus(message,type=''){scanStatus.textContent=message;scanStatus.className='scan-status'+(type?' '+type:'');}
                function clearScanResult(){scanResult.value='';copyScanButton.disabled=true;scanLink.hidden=true;scanLink.removeAttribute('href');}
                async function generate(){
                    const text=$('text').value.trim();if(!text){placeholder('请输入内容后再生成');return;}if(!window.QRCode){placeholder('二维码生成组件未加载，请刷新页面重试');return;}
                    $('generateButton').disabled=true;placeholder('正在生成二维码…');
                    try{const dataUrl=await window.QRCode.toDataURL(text,{width:Number($('size').value),margin:2,errorCorrectionLevel:'M'}),img=document.createElement('img');img.id='qrImg';img.src=dataUrl;img.alt='根据输入内容生成的二维码';$('qrBox').replaceChildren(img);$('downloadButton').disabled=false;}
                    catch(error){placeholder('生成失败，请缩短内容后重试');$('downloadButton').disabled=true;}
                    finally{$('generateButton').disabled=false;}
                }
                function downloadQR(){const img=$('qrImg');if(!img)return;const link=document.createElement('a');link.href=img.src;link.download='qrcode.png';document.body.appendChild(link);link.click();link.remove();}
                function clearGenerate(){$('text').value='';$('downloadButton').disabled=true;placeholder('请输入内容并点击生成');}
                function releaseBitmap(){if(sourceBitmap&&typeof sourceBitmap.close==='function')sourceBitmap.close();sourceBitmap=null;}
                async function loadBitmap(file){
                    if(typeof createImageBitmap==='function')return createImageBitmap(file);
                    const url=URL.createObjectURL(file),image=new Image();try{await new Promise((resolve,reject)=>{image.onload=resolve;image.onerror=()=>reject(new Error('图片解码失败'));image.src=url;});return image;}finally{URL.revokeObjectURL(url);}
                }
                function drawLocation(location,scale){if(!location)return;const points=[location.topLeftCorner,location.topRightCorner,location.bottomRightCorner,location.bottomLeftCorner];scanContext.save();scanContext.strokeStyle='#10b981';scanContext.lineWidth=Math.max(3,Math.round(4*scale));scanContext.lineJoin='round';scanContext.beginPath();points.forEach((point,index)=>{if(index)scanContext.lineTo(point.x,point.y);else scanContext.moveTo(point.x,point.y);});scanContext.closePath();scanContext.stroke();scanContext.restore();}
                function safeWebUrl(value){try{const url=new URL(value);return url.protocol==='http:'||url.protocol==='https:'?url.href:'';}catch{return '';}}
                async function scanCurrent(){
                    if(!sourceBitmap||!window.jsQR)return;scanButton.disabled=true;clearScanResult();setScanStatus('正在本地识别二维码…');
                    try{const width=sourceBitmap.width||sourceBitmap.naturalWidth,height=sourceBitmap.height||sourceBitmap.naturalHeight;if(!width||!height)throw new Error('无法读取图片尺寸');const scale=Math.min(1,maxSide/Math.max(width,height),Math.sqrt(maxPixels/(width*height))),targetWidth=Math.max(1,Math.round(width*scale)),targetHeight=Math.max(1,Math.round(height*scale));scanCanvas.width=targetWidth;scanCanvas.height=targetHeight;scanContext.clearRect(0,0,targetWidth,targetHeight);scanContext.drawImage(sourceBitmap,0,0,targetWidth,targetHeight);$('scanPreview').hidden=false;await new Promise(resolve=>requestAnimationFrame(resolve));const imageData=scanContext.getImageData(0,0,targetWidth,targetHeight),result=window.jsQR(imageData.data,targetWidth,targetHeight,{inversionAttempts:'attemptBoth'});if(!result){setScanStatus('未识别到二维码。请尝试更清晰、完整、正对镜头的图片。','error');return;}scanResult.value=result.data;copyScanButton.disabled=false;drawLocation(result.location,scale);const url=safeWebUrl(result.data);if(url){scanLink.href=url;scanLink.hidden=false;}setScanStatus(`识别成功 · QR 版本 ${result.version} · 图片 ${targetWidth} × ${targetHeight}`,'success');}
                    catch(error){setScanStatus(error.message||'识别失败，请更换图片后重试。','error');}
                    finally{scanButton.disabled=false;}
                }
                async function selectImage(file){
                    if(!file)return;if(!file.type.startsWith('image/')){setScanStatus('请选择 PNG、JPG、WEBP、GIF 或 BMP 图片。','error');return;}if(file.size>maxFileBytes){setScanStatus('图片超过 20 MB，请压缩后重试。','error');return;}
                    releaseBitmap();clearScanResult();scanButton.disabled=true;setScanStatus('正在读取图片…');
                    try{sourceBitmap=await loadBitmap(file);scanDrop.querySelector('strong').textContent=file.name||'已粘贴截图';scanDrop.querySelector('small').textContent=`${sourceBitmap.width||sourceBitmap.naturalWidth} × ${sourceBitmap.height||sourceBitmap.naturalHeight} · ${(file.size/1024).toFixed(1)} KB`;scanButton.disabled=false;await scanCurrent();}
                    catch(error){setScanStatus(error.message||'图片读取失败，请更换文件。','error');}
                }
                function clearScan(){releaseBitmap();scanFile.value='';scanCanvas.width=1;scanCanvas.height=1;$('scanPreview').hidden=true;scanButton.disabled=true;clearScanResult();scanDrop.querySelector('strong').textContent='点击选择图片，或拖到这里';scanDrop.querySelector('small').textContent='也可以直接粘贴截图，单张不超过 20 MB';setScanStatus('请选择或粘贴一张包含二维码的图片。');}
                async function copyResult(){const value=scanResult.value;if(!value)return;try{if(!navigator.clipboard||!window.isSecureContext)throw new Error();await navigator.clipboard.writeText(value);copyScanButton.textContent='已复制';}catch{scanResult.focus();scanResult.select();copyScanButton.textContent=document.execCommand('copy')?'已复制':'请手动复制';}setTimeout(()=>copyScanButton.textContent='复制内容',1200);}
                $('generateButton').addEventListener('click',generate);$('downloadButton').addEventListener('click',downloadQR);$('clearGenerateButton').addEventListener('click',clearGenerate);$('text').addEventListener('keydown',event=>{if((event.ctrlKey||event.metaKey)&&event.key==='Enter')generate();});
                scanFile.addEventListener('change',event=>selectImage(event.target.files&&event.target.files[0]));scanButton.addEventListener('click',scanCurrent);copyScanButton.addEventListener('click',copyResult);$('clearScanButton').addEventListener('click',clearScan);
                ['dragenter','dragover'].forEach(name=>scanDrop.addEventListener(name,event=>{event.preventDefault();scanDrop.classList.add('drag-over');}));['dragleave','drop'].forEach(name=>scanDrop.addEventListener(name,event=>{event.preventDefault();scanDrop.classList.remove('drag-over');}));scanDrop.addEventListener('drop',event=>{const file=event.dataTransfer&&event.dataTransfer.files&&event.dataTransfer.files[0];if(file)selectImage(file);});
                document.addEventListener('paste',event=>{const item=Array.from(event.clipboardData&&event.clipboardData.items||[]).find(entry=>entry.type.startsWith('image/'));if(item){event.preventDefault();const file=item.getAsFile();if(file)selectImage(file);}});
                window.addEventListener('beforeunload',releaseBitmap);
                if(!window.QRCode)$('generateButton').disabled=true;if(!window.jsQR){scanButton.disabled=true;setScanStatus('二维码识别组件未加载，请刷新页面或联系管理员。','error');}
            })();
            </script>
<?php include '_footer.php'; ?>
