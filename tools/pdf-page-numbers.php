<?php
$title = 'PDF 添加页码';
$desc = '为 PDF 指定页面添加可自定义位置和样式的页码。';
include '_header.php';
?>
            <div class="tool-panel">
                <label for="pdf-file">选择 PDF</label>
                <input type="file" id="pdf-file" accept="application/pdf,.pdf">
                <div class="page-number-grid">
                    <label>起始页面<input type="number" id="start-page" min="1" value="1"></label>
                    <label>结束页面<input type="number" id="end-page" min="1" value="1"></label>
                    <label>起始编号<input type="number" id="start-number" min="1" max="999999" value="1"></label>
                    <label>编号样式<select id="number-format"><option value="number">1</option><option value="total">1 / 总页数</option><option value="dash">- 1 -</option><option value="page">Page 1</option></select></label>
                    <label>页码位置<select id="number-position"><option value="bottom-center">页脚居中</option><option value="bottom-left">页脚左侧</option><option value="bottom-right">页脚右侧</option><option value="top-center">页眉居中</option><option value="top-left">页眉左侧</option><option value="top-right">页眉右侧</option></select></label>
                    <label>字号（pt）<input type="number" id="font-size" min="8" max="36" value="12"></label>
                    <label>边距（pt）<input type="number" id="page-margin" min="12" max="72" value="28"></label>
                    <label>页码颜色<input type="color" id="number-color" value="#334155"></label>
                </div>
                <div class="btn-row">
                    <button class="btn" id="create-pdf" type="button" disabled>开始添加页码</button>
                    <button class="btn success" id="download-pdf" type="button" disabled>下载新 PDF</button>
                </div>
                <p class="tip" id="number-info" role="status" aria-live="polite">请选择 120 MB 以内、最多 500 页的 PDF。文件只在浏览器中处理，不会上传服务器。</p>
            </div>
            <div class="tool-panel" id="preview-panel" hidden>
                <label>效果预览（首个编号页面）</label>
                <div class="page-number-preview" id="number-preview">完成处理后显示预览</div>
            </div>
            <style>
            .page-number-grid{display:grid;grid-template-columns:repeat(4,minmax(145px,1fr));gap:12px;margin:15px 0}.page-number-grid label{display:flex;min-width:0;flex-direction:column}.page-number-grid input,.page-number-grid select{width:100%;margin-top:5px}.page-number-grid input[type=color]{min-height:40px;padding:3px}.page-number-preview{display:flex;align-items:center;justify-content:center;min-height:280px;overflow:auto;border:1px dashed #cbd5e1;border-radius:9px;background:#f8fafc;color:#94a3b8}.page-number-preview canvas{display:block;max-width:100%;height:auto}html.theme-dark .page-number-preview{border-color:#475569;background:#0f172a}@media(max-width:760px){.page-number-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:440px){.page-number-grid{grid-template-columns:1fr}}
            </style>
            <script src="../static/vendor/pdf.min.js" onload="window.OFFICE_PDF_WORKER='../static/vendor/pdf.worker.min.js'" onerror="this.onerror=null;this.onload=function(){window.OFFICE_PDF_WORKER='https://unpkg.com/pdfjs-dist@3.11.174/build/pdf.worker.min.js'};this.src='https://unpkg.com/pdfjs-dist@3.11.174/build/pdf.min.js';document.getElementById('number-info').textContent='本地预览组件不可用，正在尝试网络备用组件…'"></script>
            <script src="../static/vendor/pdf-lib.min.js" onerror="this.onerror=null;this.src='https://unpkg.com/pdf-lib@1.17.1/dist/pdf-lib.min.js';document.getElementById('number-info').textContent='本地编辑组件不可用，正在尝试网络备用组件…'"></script>
            <script>
            (function(){
                'use strict';
                const MAX_FILE_BYTES=120*1024*1024,MAX_PAGES=500;
                const $=id=>document.getElementById(id),fileInput=$('pdf-file'),createButton=$('create-pdf'),downloadButton=$('download-pdf'),info=$('number-info');
                const settingIds=['start-page','end-page','start-number','number-format','number-position','font-size','page-margin','number-color'];
                let sourceFile=null,sourceBytes=null,pageCount=0,resultBlob=null,busy=false;
                const clampInteger=(id,min,max)=>{const value=Number.parseInt($(id).value,10);return Number.isInteger(value)&&value>=min&&value<=max?value:null};
                const normalizeRotation=value=>((Math.round(value/90)*90%360)+360)%360;
                const formatSize=bytes=>bytes<1024*1024?(bytes/1024).toFixed(1)+' KB':(bytes/1024/1024).toFixed(2)+' MB';
                function setBusy(value){busy=value;fileInput.disabled=value;createButton.disabled=value||!sourceBytes;downloadButton.disabled=value||!resultBlob;settingIds.forEach(id=>$(id).disabled=value)}
                function invalidateResult(){resultBlob=null;downloadButton.disabled=true;$('preview-panel').hidden=true;if(sourceFile&&!busy)info.textContent='设置已修改，请重新添加页码。'}
                function hexToRgb(hex){const value=Number.parseInt(hex.slice(1),16);return {r:((value>>16)&255)/255,g:((value>>8)&255)/255,b:(value&255)/255}}
                function pageLabel(number,total,format){if(format==='total')return number+' / '+total;if(format==='dash')return '- '+number+' -';if(format==='page')return 'Page '+number;return String(number)}
                function visualToPdf(u,v,width,height,rotation){if(rotation===90)return{x:width-v,y:u};if(rotation===180)return{x:width-u,y:height-v};if(rotation===270)return{x:v,y:height-u};return{x:u,y:v}}
                function drawPageNumber(page,text,font,fontSize,margin,position,color){
                    const width=page.getWidth(),height=page.getHeight(),rotation=normalizeRotation(page.getRotation().angle||0),vertical=rotation===90||rotation===270,visualWidth=vertical?height:width,visualHeight=vertical?width:height,measuredWidth=font.widthOfTextAtSize(text,fontSize),safeFontSize=measuredWidth>visualWidth-8?Math.max(6,fontSize*(visualWidth-8)/measuredWidth):fontSize,textWidth=font.widthOfTextAtSize(text,safeFontSize),safeMargin=Math.min(margin,visualWidth/4,visualHeight/4);
                    const horizontal=position.endsWith('left')?'left':position.endsWith('right')?'right':'center',top=position.startsWith('top');
                    const u=horizontal==='left'?safeMargin:horizontal==='right'?visualWidth-safeMargin-textWidth:(visualWidth-textWidth)/2;
                    const v=top?visualHeight-safeMargin-safeFontSize:safeMargin;
                    const point=visualToPdf(u,v,width,height,rotation);
                    page.drawText(text,{x:point.x,y:point.y,size:safeFontSize,font:font,color:window.PDFLib.rgb(color.r,color.g,color.b),rotate:window.PDFLib.degrees(rotation)});
                }
                async function renderPreview(bytes,pageNumber){
                    const pdfjsLib=window.pdfjsLib||window['pdfjs-dist/build/pdf'];if(!pdfjsLib)throw new Error('PDF 预览组件未加载');pdfjsLib.GlobalWorkerOptions.workerSrc=window.OFFICE_PDF_WORKER||'../static/vendor/pdf.worker.min.js';
                    const documentTask=pdfjsLib.getDocument({data:new Uint8Array(bytes.slice(0))});const pdf=await documentTask.promise;const page=await pdf.getPage(pageNumber);const base=page.getViewport({scale:1});const scale=Math.min(1.35,900/base.width);const viewport=page.getViewport({scale:scale});const canvas=document.createElement('canvas');canvas.width=Math.ceil(viewport.width);canvas.height=Math.ceil(viewport.height);await page.render({canvasContext:canvas.getContext('2d'),viewport:viewport}).promise;$('number-preview').replaceChildren(canvas);$('preview-panel').hidden=false;await pdf.destroy();
                }
                fileInput.addEventListener('change',async event=>{
                    const file=event.target.files[0];sourceFile=null;sourceBytes=null;pageCount=0;resultBlob=null;createButton.disabled=true;downloadButton.disabled=true;$('preview-panel').hidden=true;if(!file)return;
                    if(((file.type&&file.type!=='application/pdf')&&!/\.pdf$/i.test(file.name))||file.size>MAX_FILE_BYTES){fileInput.value='';info.textContent='请选择 120 MB 以内的 PDF 文件。';return}
                    if(!window.PDFLib){fileInput.value='';info.textContent='PDF 组件未加载，请刷新页面后重试。';return}
                    setBusy(true);info.textContent='正在读取 PDF…';
                    try{const bytes=await file.arrayBuffer();const pdf=await window.PDFLib.PDFDocument.load(bytes.slice(0));pageCount=pdf.getPageCount();if(pageCount>MAX_PAGES)throw new Error('PDF 共 '+pageCount+' 页，超过 500 页上限');sourceFile=file;sourceBytes=bytes;$('start-page').value='1';$('end-page').value=String(pageCount);info.textContent='已加载 '+file.name+'，共 '+pageCount+' 页（'+formatSize(file.size)+'）。'}catch(error){fileInput.value='';info.textContent='读取失败：'+error.message}finally{setBusy(false)}
                });
                settingIds.forEach(id=>$(id).addEventListener('input',invalidateResult));
                createButton.addEventListener('click',async()=>{
                    if(busy||!sourceBytes)return;const start=clampInteger('start-page',1,pageCount),end=clampInteger('end-page',1,pageCount),startNumber=clampInteger('start-number',1,999999),fontSize=clampInteger('font-size',8,36),margin=clampInteger('page-margin',12,72);if(!start||!end||start>end)return alert('请输入有效的起止页面');if(!startNumber||!fontSize||!margin)return alert('起始编号、字号或边距超出允许范围');
                    setBusy(true);info.textContent='正在添加页码…';resultBlob=null;
                    try{const pdf=await window.PDFLib.PDFDocument.load(sourceBytes.slice(0));const font=await pdf.embedFont(window.PDFLib.StandardFonts.Helvetica);const format=$('number-format').value,position=$('number-position').value,color=hexToRgb($('number-color').value);for(let pageIndex=start-1;pageIndex<end;pageIndex++){const number=startNumber+pageIndex-(start-1);const text=pageLabel(number,pageCount,format);drawPageNumber(pdf.getPage(pageIndex),text,font,fontSize,margin,position,color)}const outputBytes=await pdf.save();resultBlob=new Blob([outputBytes],{type:'application/pdf'});info.textContent='页码添加完成：第 '+start+' 至 '+end+' 页，共处理 '+(end-start+1)+' 页，大小 '+formatSize(resultBlob.size)+'。';try{await renderPreview(outputBytes,start)}catch(previewError){$('preview-panel').hidden=true;info.textContent+=' 预览暂不可用，但可正常下载。'}}catch(error){resultBlob=null;info.textContent='处理失败：'+error.message}finally{setBusy(false)}
                });
                downloadButton.addEventListener('click',()=>{if(!resultBlob||!sourceFile)return;const url=URL.createObjectURL(resultBlob);const link=document.createElement('a');link.href=url;link.download=sourceFile.name.replace(/\.pdf$/i,'')+'-页码.pdf';document.body.appendChild(link);link.click();link.remove();setTimeout(()=>URL.revokeObjectURL(url),1000)});
            })();
            </script>
<?php include '_footer.php'; ?>
