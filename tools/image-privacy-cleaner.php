<?php
$title = '图片隐私清理';
$desc = '通过像素重新编码移除 JPG、PNG、WebP 中常见的 EXIF、GPS、XMP/IPTC 元数据；图片只在浏览器中处理，不会上传服务器。';
include '_header.php';
?>
            <style>
            .privacy-layout{display:grid;grid-template-columns:minmax(300px,.8fr) minmax(0,1.2fr);gap:18px}.drop-zone{display:flex;min-height:145px;align-items:center;justify-content:center;text-align:center;padding:22px;border:2px dashed #cbd5e1;border-radius:12px;background:rgba(248,250,252,.75);cursor:pointer;transition:.2s}.drop-zone.dragging{border-color:#6366f1;background:rgba(99,102,241,.08)}.drop-zone strong{display:block;margin-bottom:8px;color:#334155}.drop-zone small{color:#64748b}.settings{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:15px}.setting label{display:block;margin-bottom:6px}.quality-row{display:flex;align-items:center;gap:8px}.quality-row input{min-width:0;flex:1}.notice{margin-top:14px;padding:12px;border-radius:10px;background:#fff7ed;color:#9a3412;font-size:13px;line-height:1.6}.preview{min-height:260px;display:flex;align-items:center;justify-content:center;border:1px dashed #cbd5e1;border-radius:12px;background:repeating-conic-gradient(#eef2f7 0 25%,#fff 0 50%) 50%/20px 20px;overflow:hidden}.preview img{display:block;max-width:100%;max-height:520px;object-fit:contain}.preview span{color:#64748b}.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:9px;margin-top:14px}.stat{padding:11px;border:1px solid #e2e8f0;border-radius:10px;text-align:center;background:rgba(255,255,255,.75)}.stat small{display:block;color:#64748b;margin-bottom:4px}.stat strong{overflow-wrap:anywhere}.status-line{min-height:22px;margin-top:12px;color:#64748b}.status-line.success{color:#047857}.status-line.error{color:#b91c1c}
            html.theme-dark .drop-zone,html.theme-dark .stat{background:rgba(30,41,59,.72);border-color:#475569}html.theme-dark .drop-zone strong{color:#e2e8f0}html.theme-dark .preview{border-color:#475569}html.theme-dark .notice{background:#431407;color:#fed7aa}
            @media(max-width:780px){.privacy-layout{grid-template-columns:1fr}.settings{grid-template-columns:1fr}.stats{grid-template-columns:1fr}.preview{min-height:190px}}
            </style>
            <div class="privacy-layout">
                <div class="tool-panel">
                    <label class="drop-zone" id="dropZone" for="imageFile"><span><strong>点击或拖放静态图片</strong><small>支持 JPG、PNG、WebP，最大 40 MB</small></span></label>
                    <input id="imageFile" type="file" accept="image/jpeg,image/png,image/webp" hidden>
                    <div class="settings">
                        <div class="setting"><label for="format">输出格式</label><select id="format"><option value="image/jpeg">JPG（照片推荐）</option><option value="image/png">PNG（透明图推荐）</option><option value="image/webp">WebP（体积较小）</option></select></div>
                        <div class="setting"><label for="quality">质量：<output id="qualityValue">92%</output></label><div class="quality-row"><input id="quality" type="range" min="50" max="100" value="92"></div></div>
                    </div>
                    <div class="btn-row"><button class="btn" id="cleanButton" type="button" disabled>清理并生成</button><button class="btn success" id="downloadButton" type="button" disabled>下载已清理图片</button><button class="btn secondary" id="resetButton" type="button" disabled>重置</button></div>
                    <div class="notice">清理采用重新编码，会移除常见定位、相机型号、拍摄时间等元数据，同时可能改变文件体积和色彩配置。动画 GIF 不适用本工具。</div>
                    <p class="status-line" id="status" role="status">请先选择图片。</p>
                </div>
                <div class="tool-panel">
                    <div class="preview" id="preview"><span>清理后的图片将在这里预览</span></div>
                    <div class="stats"><div class="stat"><small>图片尺寸</small><strong id="dimensions">—</strong></div><div class="stat"><small>原文件</small><strong id="originalSize">—</strong></div><div class="stat"><small>清理后</small><strong id="cleanedSize">—</strong></div></div>
                </div>
            </div>
            <script>
            (()=>{
                'use strict';
                const MAX_FILE_BYTES=40*1024*1024,MAX_SOURCE_PIXELS=50000000,MAX_CANVAS_SIDE=16384;
                const allowedTypes=new Set(['image/jpeg','image/png','image/webp']);
                const $=id=>document.getElementById(id);let sourceImage=null,sourceUrl='',resultUrl='',resultBlob=null,resultName='cleaned-image';
                function revoke(url){if(url)URL.revokeObjectURL(url);}
                function readable(bytes){if(!Number.isFinite(bytes))return '—';return bytes>=1024*1024?(bytes/1024/1024).toFixed(2)+' MB':(bytes/1024).toFixed(1)+' KB';}
                function setStatus(text,type=''){$('status').textContent=text;$('status').className='status-line '+type;}
                function resetResult(){revoke(resultUrl);resultUrl='';resultBlob=null;$('downloadButton').disabled=true;$('cleanedSize').textContent='—';$('preview').replaceChildren(Object.assign(document.createElement('span'),{textContent:'清理后的图片将在这里预览'}));}
                function reset(){revoke(sourceUrl);sourceUrl='';sourceImage=null;$('imageFile').value='';$('cleanButton').disabled=true;$('resetButton').disabled=true;$('dimensions').textContent='—';$('originalSize').textContent='—';resetResult();setStatus('请先选择图片。');}
                function loadFile(file){
                    if(!file)return;if(!allowedTypes.has(file.type)||file.size>MAX_FILE_BYTES){setStatus('请选择 40 MB 以内的 JPG、PNG 或 WebP 图片。','error');return;}revoke(sourceUrl);sourceUrl=URL.createObjectURL(file);resetResult();const image=new Image();
                    image.onload=()=>{const pixels=image.naturalWidth*image.naturalHeight;if(!image.naturalWidth||pixels>MAX_SOURCE_PIXELS||image.naturalWidth>MAX_CANVAS_SIDE||image.naturalHeight>MAX_CANVAS_SIDE){revoke(sourceUrl);sourceUrl='';sourceImage=null;setStatus('图片超过 5000 万像素或单边超过 16384px，为保护浏览器已停止读取。','error');return;}sourceImage=image;resultName=(file.name.replace(/\.[^.]+$/,'')||'image')+'-privacy-clean';$('dimensions').textContent=image.naturalWidth+' × '+image.naturalHeight;$('originalSize').textContent=readable(file.size);$('cleanButton').disabled=false;$('resetButton').disabled=false;$('format').value=file.type;setStatus('图片已载入，点击“清理并生成”。');};image.onerror=()=>{revoke(sourceUrl);sourceUrl='';sourceImage=null;setStatus('图片读取失败或文件已损坏。','error');};image.src=sourceUrl;
                }
                function cleanImage(){
                    if(!sourceImage)return;const canvas=document.createElement('canvas');canvas.width=sourceImage.naturalWidth;canvas.height=sourceImage.naturalHeight;const context=canvas.getContext('2d');const format=$('format').value;if(format==='image/jpeg'){context.fillStyle='#fff';context.fillRect(0,0,canvas.width,canvas.height);}context.drawImage(sourceImage,0,0);$('cleanButton').disabled=true;setStatus('正在重新编码，请稍候…');canvas.toBlob(blob=>{if(!blob){$('cleanButton').disabled=false;setStatus('浏览器不支持所选格式，请换一种输出格式。','error');return;}resultBlob=blob;revoke(resultUrl);resultUrl=URL.createObjectURL(blob);const image=new Image();image.alt='已清理图片预览';image.src=resultUrl;$('preview').replaceChildren(image);$('cleanedSize').textContent=readable(blob.size);$('downloadButton').disabled=false;$('cleanButton').disabled=false;setStatus('清理完成：已通过像素重新编码移除常见元数据。','success');},format,Number($('quality').value)/100);
                }
                $('imageFile').addEventListener('change',event=>loadFile(event.target.files[0]));$('quality').addEventListener('input',event=>$('qualityValue').textContent=event.target.value+'%');$('format').addEventListener('change',()=>{const lossy=$('format').value!=='image/png';$('quality').disabled=!lossy;$('qualityValue').textContent=lossy?$('quality').value+'%':'无损';resetResult();});
                ['dragenter','dragover'].forEach(name=>$('dropZone').addEventListener(name,event=>{event.preventDefault();$('dropZone').classList.add('dragging');}));['dragleave','drop'].forEach(name=>$('dropZone').addEventListener(name,event=>{event.preventDefault();$('dropZone').classList.remove('dragging');}));$('dropZone').addEventListener('drop',event=>loadFile([...event.dataTransfer.files].find(file=>file.type.startsWith('image/'))));
                $('cleanButton').addEventListener('click',cleanImage);$('downloadButton').addEventListener('click',()=>{if(!resultBlob)return;const extension={'image/jpeg':'jpg','image/png':'png','image/webp':'webp'}[$('format').value];const link=document.createElement('a');link.href=resultUrl;link.download=resultName+'.'+extension;link.click();});$('resetButton').addEventListener('click',reset);window.addEventListener('beforeunload',()=>{revoke(sourceUrl);revoke(resultUrl);});
            })();
            </script>
<?php include '_footer.php'; ?>
