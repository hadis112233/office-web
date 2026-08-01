<?php
$title = 'PDF 加水印';
$desc = '为 PDF 页面添加平铺文字水印，保留原有文字和矢量内容。';
include '_header.php';
?>
            <div class="tool-panel">
                <label for="pdf-file">选择 PDF</label>
                <input type="file" id="pdf-file" accept="application/pdf,.pdf">
                <label for="watermark">水印文字</label>
                <input type="text" id="watermark" value="CONFIDENTIAL  机密文档" maxlength="120" style="width:100%;">
                <div class="watermark-grid">
                    <label>起始页面<input type="number" id="start-page" min="1" value="1"></label>
                    <label>结束页面<input type="number" id="end-page" min="1" value="1"></label>
                    <label>字体大小：<span id="size-value">36 pt</span><input type="range" id="size" min="12" max="120" step="2" value="36"></label>
                    <label>透明度：<span id="opacity-value">25%</span><input type="range" id="opacity" min="0.05" max="1" step="0.05" value="0.25"></label>
                    <label>旋转角度：<span id="rotate-value">-30°</span><input type="range" id="rotate" min="-90" max="90" step="5" value="-30"></label>
                    <label>水印间距：<span id="spacing-value">180 pt</span><input type="range" id="spacing" min="80" max="360" step="20" value="180"></label>
                    <label>颜色<input type="color" id="color" value="#cc0000"></label>
                </div>
                <div class="btn-row">
                    <button class="btn" id="create-watermark" type="button" disabled>开始添加水印</button>
                    <button class="btn success" id="download-pdf" type="button" disabled>下载带水印 PDF</button>
                </div>
                <p class="tip" id="watermark-info" role="status" aria-live="polite">请选择 120 MB 以内、最多 300 页的 PDF。原页面不会转成图片，文件只在浏览器中处理。</p>
            </div>
            <div class="tool-panel" id="preview-panel" hidden>
                <label>效果预览（首个加水印页面）</label>
                <div class="watermark-preview" id="watermark-preview">处理完成后显示预览</div>
            </div>
            <style>
            .watermark-grid{display:grid;grid-template-columns:repeat(3,minmax(170px,1fr));gap:12px 18px;margin:15px 0}.watermark-grid label{display:flex;min-width:0;flex-direction:column}.watermark-grid input{width:100%;margin-top:5px}.watermark-grid input[type=color]{min-height:40px;padding:3px}.watermark-preview{display:flex;align-items:center;justify-content:center;min-height:300px;overflow:auto;border:1px dashed #cbd5e1;border-radius:9px;background:#f8fafc;color:#94a3b8}.watermark-preview canvas{display:block;max-width:100%;height:auto}html.theme-dark .watermark-preview{border-color:#475569;background:#0f172a}@media(max-width:720px){.watermark-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:440px){.watermark-grid{grid-template-columns:1fr}}
            </style>
            <script src="../static/vendor/pdf.min.js" onload="window.OFFICE_PDF_WORKER='../static/vendor/pdf.worker.min.js'" onerror="this.onerror=null;this.onload=function(){window.OFFICE_PDF_WORKER='https://unpkg.com/pdfjs-dist@3.11.174/build/pdf.worker.min.js'};this.src='https://unpkg.com/pdfjs-dist@3.11.174/build/pdf.min.js';document.getElementById('watermark-info').textContent='本地预览组件不可用，正在尝试网络备用组件…'"></script>
            <script src="../static/vendor/pdf-lib.min.js" onerror="this.onerror=null;this.src='https://unpkg.com/pdf-lib@1.17.1/dist/pdf-lib.min.js';document.getElementById('watermark-info').textContent='本地编辑组件不可用，正在尝试网络备用组件…'"></script>
            <script>
            (function(){
                'use strict';
                const MAX_FILE_BYTES=120*1024*1024,MAX_OUTPUT_BYTES=180*1024*1024,MAX_PAGES=300,MAX_WATERMARK_DRAWS=20000,settingIds=['watermark','start-page','end-page','size','opacity','rotate','spacing','color'];
                const $=id=>document.getElementById(id),fileInput=$('pdf-file'),createButton=$('create-watermark'),downloadButton=$('download-pdf'),info=$('watermark-info');
                let sourceFile=null,sourceBytes=null,pageCount=0,resultBlob=null,busy=false;
                const formatSize=bytes=>bytes<1024*1024?(bytes/1024).toFixed(1)+' KB':(bytes/1024/1024).toFixed(2)+' MB';
                function setBusy(value){busy=value;fileInput.disabled=value;createButton.disabled=value||!sourceBytes;downloadButton.disabled=value||!resultBlob;settingIds.forEach(id=>$(id).disabled=value)}
                function invalidateResult(){resultBlob=null;downloadButton.disabled=true;$('preview-panel').hidden=true;if(sourceFile&&!busy)info.textContent='设置已修改，请重新添加水印。'}
                function updateLabels(){$('size-value').textContent=$('size').value+' pt';$('opacity-value').textContent=Math.round(Number($('opacity').value)*100)+'%';$('rotate-value').textContent=$('rotate').value+'°';$('spacing-value').textContent=$('spacing').value+' pt'}
                async function makeWatermarkPng(text,fontSize,color){
                    const density=2,padding=16*density,probe=document.createElement('canvas').getContext('2d');probe.font='600 '+(fontSize*density)+'px "Microsoft YaHei","PingFang SC",sans-serif';const measured=probe.measureText(text).width,rawWidth=Math.max(1,measured+padding*2),rawHeight=fontSize*density*1.7,fit=Math.min(1,2048/rawWidth,512/rawHeight),canvas=document.createElement('canvas');canvas.width=Math.max(1,Math.ceil(rawWidth*fit));canvas.height=Math.max(1,Math.ceil(rawHeight*fit));const context=canvas.getContext('2d');context.scale(fit,fit);context.font=probe.font;context.fillStyle=color;context.textAlign='center';context.textBaseline='middle';context.fillText(text,rawWidth/2,rawHeight/2);const blob=await new Promise((resolve,reject)=>canvas.toBlob(value=>value?resolve(value):reject(new Error('水印图层生成失败')),'image/png'));return{bytes:await blob.arrayBuffer(),aspect:rawWidth/rawHeight};
                }
                function drawPattern(page,image,aspect,fontSize,spacing,angle,opacity,counter){
                    const pageWidth=page.getWidth(),pageHeight=page.getHeight(),maxWidth=Math.max(40,pageWidth*.72),baseHeight=fontSize*1.7,tileWidth=Math.min(baseHeight*aspect,maxWidth),tileHeight=tileWidth/aspect,stepX=tileWidth+spacing,stepY=tileHeight+spacing,theta=angle*Math.PI/180,centerOffsetX=tileWidth/2*Math.cos(theta)-tileHeight/2*Math.sin(theta),centerOffsetY=tileWidth/2*Math.sin(theta)+tileHeight/2*Math.cos(theta);
                    for(let centerY=-pageHeight;centerY<=pageHeight*2;centerY+=stepY){for(let centerX=-pageWidth;centerX<=pageWidth*2;centerX+=stepX){counter.count++;if(counter.count>MAX_WATERMARK_DRAWS)throw new Error('水印数量超过 20000 个，请增大间距或缩小页面范围');page.drawImage(image,{x:centerX-centerOffsetX,y:centerY-centerOffsetY,width:tileWidth,height:tileHeight,rotate:window.PDFLib.degrees(angle),opacity:opacity})}}
                }
                async function renderPreview(bytes,pageNumber){const pdfjsLib=window.pdfjsLib||window['pdfjs-dist/build/pdf'];if(!pdfjsLib)throw new Error('PDF 预览组件未加载');pdfjsLib.GlobalWorkerOptions.workerSrc=window.OFFICE_PDF_WORKER||'../static/vendor/pdf.worker.min.js';const pdf=await pdfjsLib.getDocument({data:new Uint8Array(bytes.slice(0))}).promise;try{const page=await pdf.getPage(pageNumber),base=page.getViewport({scale:1}),viewport=page.getViewport({scale:Math.min(1.3,900/base.width)}),canvas=document.createElement('canvas');canvas.width=Math.ceil(viewport.width);canvas.height=Math.ceil(viewport.height);await page.render({canvasContext:canvas.getContext('2d'),viewport:viewport}).promise;$('watermark-preview').replaceChildren(canvas);$('preview-panel').hidden=false}finally{await pdf.destroy()}}
                fileInput.addEventListener('change',async event=>{
                    const file=event.target.files[0];sourceFile=null;sourceBytes=null;pageCount=0;resultBlob=null;createButton.disabled=true;downloadButton.disabled=true;$('preview-panel').hidden=true;if(!file)return;if(((file.type&&file.type!=='application/pdf')&&!/\.pdf$/i.test(file.name))||file.size>MAX_FILE_BYTES){fileInput.value='';info.textContent='请选择 120 MB 以内的 PDF 文件。';return}if(!window.PDFLib){fileInput.value='';info.textContent='PDF 编辑组件未加载，请刷新页面后重试。';return}
                    setBusy(true);info.textContent='正在读取 PDF…';try{const bytes=await file.arrayBuffer(),pdf=await window.PDFLib.PDFDocument.load(bytes.slice(0),{updateMetadata:false});pageCount=pdf.getPageCount();if(pageCount>MAX_PAGES)throw new Error('PDF 共 '+pageCount+' 页，超过 300 页上限');sourceFile=file;sourceBytes=bytes;$('start-page').value='1';$('end-page').value=String(pageCount);info.textContent='已加载 '+file.name+'，共 '+pageCount+' 页（'+formatSize(file.size)+'）。'}catch(error){fileInput.value='';info.textContent='读取失败：'+error.message}finally{setBusy(false)}
                });
                settingIds.forEach(id=>$(id).addEventListener('input',()=>{updateLabels();invalidateResult()}));updateLabels();
                createButton.addEventListener('click',async()=>{
                    if(busy||!sourceBytes)return;const start=Number.parseInt($('start-page').value,10),end=Number.parseInt($('end-page').value,10),text=$('watermark').value.trim().slice(0,120),fontSize=Number($('size').value),opacity=Number($('opacity').value),angle=Number($('rotate').value),spacing=Number($('spacing').value);if(!text)return alert('请输入水印文字');if(!Number.isInteger(start)||!Number.isInteger(end)||start<1||end>pageCount||start>end)return alert('请输入有效的起止页面');if(!Number.isFinite(fontSize)||fontSize<12||fontSize>120||!Number.isFinite(opacity)||opacity<.05||opacity>1||!Number.isFinite(angle)||angle<-90||angle>90||!Number.isFinite(spacing)||spacing<80||spacing>360)return alert('水印设置超出允许范围');
                    setBusy(true);resultBlob=null;info.textContent='正在添加水印…';try{const pdf=await window.PDFLib.PDFDocument.load(sourceBytes.slice(0),{updateMetadata:false}),tile=await makeWatermarkPng(text,fontSize,$('color').value),image=await pdf.embedPng(tile.bytes),counter={count:0};for(let index=start-1;index<end;index++){info.textContent='正在添加水印… '+(index-start+2)+' / '+(end-start+1);drawPattern(pdf.getPage(index),image,tile.aspect,fontSize,spacing,angle,opacity,counter)}const output=await pdf.save();if(output.byteLength>MAX_OUTPUT_BYTES)throw new Error('生成文件超过 180 MB，请缩小页面范围或增大水印间距');resultBlob=new Blob([output],{type:'application/pdf'});info.textContent='水印添加完成：处理 '+(end-start+1)+' 页，共放置 '+counter.count+' 个水印，文件大小 '+formatSize(resultBlob.size)+'。原有页面内容未栅格化。';try{await renderPreview(output,start)}catch(previewError){$('preview-panel').hidden=true;info.textContent+=' 预览暂不可用，但可正常下载。'}}catch(error){resultBlob=null;info.textContent='处理失败：'+error.message}finally{setBusy(false)}
                });
                downloadButton.addEventListener('click',()=>{if(!resultBlob||!sourceFile)return;const url=URL.createObjectURL(resultBlob),link=document.createElement('a');link.href=url;link.download=sourceFile.name.replace(/\.pdf$/i,'')+'-水印.pdf';document.body.appendChild(link);link.click();link.remove();setTimeout(()=>URL.revokeObjectURL(url),1000)});
            })();
            </script>
<?php include '_footer.php'; ?>
