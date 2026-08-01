<?php
$title = 'PDF 提取文字';
$desc = '从 PDF 中提取可选择的文字，复制或导出为 TXT。';
include '_header.php';
?>
            <div class="tool-panel">
                <label for="pdf-file">选择 PDF</label>
                <input type="file" id="pdf-file" accept="application/pdf,.pdf">
                <div class="pdf-text-settings">
                    <label>起始页面<input type="number" id="start-page" min="1" value="1"></label>
                    <label>结束页面<input type="number" id="end-page" min="1" value="1"></label>
                    <label>文字顺序<select id="text-order"><option value="visual">按视觉位置排列</option><option value="stream">按 PDF 内容流排列</option></select></label>
                    <label class="pdf-text-check"><input type="checkbox" id="page-markers" checked> 添加“第 N 页”分隔标记</label>
                </div>
                <div class="btn-row">
                    <button class="btn" id="extract-text" type="button" disabled>开始提取</button>
                    <button class="btn secondary" id="cancel-extract" type="button" disabled>取消</button>
                    <button class="btn secondary" id="copy-text" type="button" disabled>复制文字</button>
                    <button class="btn success" id="download-text" type="button" disabled>下载 TXT</button>
                </div>
                <p class="tip" id="extract-info" role="status" aria-live="polite">请选择 120 MB 以内、最多 500 页的 PDF。文件只在浏览器中处理，不会上传服务器。</p>
                <div class="pdf-text-warning">扫描件或纯图片 PDF 没有可直接提取的文字层，需要 OCR（光学文字识别）；本工具不会把图片自动识别成文字。</div>
            </div>
            <div class="tool-panel">
                <div class="pdf-text-heading"><label for="text-output">提取结果</label><span id="text-stats">尚未提取</span></div>
                <textarea id="text-output" class="pdf-text-output" readonly placeholder="提取出的文字会显示在这里"></textarea>
            </div>
            <style>
            .pdf-text-settings{display:grid;grid-template-columns:repeat(4,minmax(150px,1fr));align-items:end;gap:12px;margin:15px 0}.pdf-text-settings label{display:flex;min-width:0;flex-direction:column}.pdf-text-settings input,.pdf-text-settings select{width:100%;margin-top:5px}.pdf-text-settings .pdf-text-check{display:flex;min-height:40px;flex-direction:row;align-items:center;gap:8px;padding:8px 0}.pdf-text-settings .pdf-text-check input{width:auto;margin:0}.pdf-text-warning{margin-top:14px;padding:11px 13px;border:1px solid #bfdbfe;border-radius:9px;background:#eff6ff;color:#1e40af;font-size:12px;line-height:1.65}.pdf-text-heading{display:flex;align-items:center;justify-content:space-between;gap:12px}.pdf-text-heading span{color:#64748b;font-size:12px}.pdf-text-output{width:100%;min-height:430px;margin-top:8px;resize:vertical;white-space:pre-wrap;font-family:"SFMono-Regular",Consolas,"Liberation Mono",monospace;line-height:1.65}html.theme-dark .pdf-text-warning{border-color:#1e40af;background:#172554;color:#bfdbfe}html.theme-dark .pdf-text-heading span{color:#94a3b8}@media(max-width:760px){.pdf-text-settings{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:430px){.pdf-text-settings{grid-template-columns:1fr}}
            </style>
            <script src="../static/vendor/pdf.min.js" onload="window.OFFICE_PDF_WORKER='../static/vendor/pdf.worker.min.js'" onerror="this.onerror=null;this.onload=function(){window.OFFICE_PDF_WORKER='https://unpkg.com/pdfjs-dist@3.11.174/build/pdf.worker.min.js'};this.src='https://unpkg.com/pdfjs-dist@3.11.174/build/pdf.min.js';document.getElementById('extract-info').textContent='本地组件不可用，正在尝试网络备用组件…'"></script>
            <script>
            (function(){
                'use strict';
                const MAX_FILE_BYTES=120*1024*1024,MAX_PAGES=500,MAX_TEXT_CHARS=5000000,MAX_TEXT_ITEMS_PER_PAGE=200000;
                const $=id=>document.getElementById(id),fileInput=$('pdf-file'),extractButton=$('extract-text'),cancelButton=$('cancel-extract'),copyButton=$('copy-text'),downloadButton=$('download-text'),output=$('text-output'),info=$('extract-info');
                let sourceFile=null,pdfDocument=null,pageCount=0,cancelRequested=false,busy=false;
                const formatSize=bytes=>bytes<1024*1024?(bytes/1024).toFixed(1)+' KB':(bytes/1024/1024).toFixed(2)+' MB';
                function setBusy(value){busy=value;fileInput.disabled=value;extractButton.disabled=value||!pdfDocument;cancelButton.disabled=!value;['start-page','end-page','text-order','page-markers'].forEach(id=>$(id).disabled=value);copyButton.disabled=value||!output.value;downloadButton.disabled=value||!output.value}
                function clearResult(message){output.value='';copyButton.disabled=true;downloadButton.disabled=true;$('text-stats').textContent='尚未提取';if(message&&!busy)info.textContent=message}
                function contentStreamText(items){let text='';for(const item of items){if(!item.str)continue;if(text&&!/\s$/.test(text)&&/[A-Za-z0-9]$/.test(text)&&/^[A-Za-z0-9]/.test(item.str))text+=' ';text+=item.str;if(item.hasEOL)text+='\n'}return text.replace(/[ \t]+\n/g,'\n').trim()}
                function visualText(items){
                    const tokens=items.filter(item=>item.str&&item.transform&&Number.isFinite(item.transform[4])&&Number.isFinite(item.transform[5])).map(item=>({text:item.str,x:item.transform[4],y:item.transform[5],width:Number(item.width)||0,height:Math.max(1,Number(item.height)||Math.abs(item.transform[3])||10)})).sort((a,b)=>b.y-a.y||a.x-b.x);const lines=[];
                    for(const token of tokens){let line=lines[lines.length-1];if(!line||Math.abs(line.y-token.y)>Math.max(2,Math.min(line.height,token.height)*.4)){line={y:token.y,height:token.height,items:[]};lines.push(line)}line.items.push(token);line.height=Math.max(line.height,token.height)}
                    lines.sort((a,b)=>b.y-a.y);const rendered=[];for(let index=0;index<lines.length;index++){const line=lines[index];line.items.sort((a,b)=>a.x-b.x);let text='',previous=null;for(const token of line.items){if(previous){const gap=token.x-(previous.x+previous.width);if(gap>Math.max(1.5,Math.min(previous.height,token.height)*.12)&&!/[\s-]$/.test(text))text+=' '}text+=token.text;previous=token}if(index>0){const gap=lines[index-1].y-line.y;if(gap>Math.max(lines[index-1].height,line.height)*1.8)rendered.push('')}rendered.push(text.trimEnd())}return rendered.join('\n').trim();
                }
                async function destroyPdf(){if(pdfDocument){try{await pdfDocument.destroy()}catch(error){}pdfDocument=null}}
                fileInput.addEventListener('change',async event=>{
                    const file=event.target.files[0];await destroyPdf();sourceFile=null;pageCount=0;clearResult();if(!file)return;if(((file.type&&file.type!=='application/pdf')&&!/\.pdf$/i.test(file.name))||file.size>MAX_FILE_BYTES){fileInput.value='';info.textContent='请选择 120 MB 以内的 PDF 文件。';return}const pdfjsLib=window.pdfjsLib||window['pdfjs-dist/build/pdf'];if(!pdfjsLib){fileInput.value='';info.textContent='PDF 组件未加载，请刷新页面后重试。';return}
                    setBusy(true);cancelButton.disabled=true;info.textContent='正在读取 PDF…';
                    try{pdfjsLib.GlobalWorkerOptions.workerSrc=window.OFFICE_PDF_WORKER||'../static/vendor/pdf.worker.min.js';const bytes=await file.arrayBuffer();pdfDocument=await pdfjsLib.getDocument({data:new Uint8Array(bytes.slice(0))}).promise;pageCount=pdfDocument.numPages;if(pageCount>MAX_PAGES)throw new Error('PDF 共 '+pageCount+' 页，超过 500 页上限');sourceFile=file;$('start-page').value='1';$('end-page').value=String(pageCount);info.textContent='已加载 '+file.name+'，共 '+pageCount+' 页（'+formatSize(file.size)+'）。'}catch(error){await destroyPdf();fileInput.value='';info.textContent='读取失败：'+error.message}finally{setBusy(false)}
                });
                ['start-page','end-page','text-order','page-markers'].forEach(id=>$(id).addEventListener('input',()=>clearResult(sourceFile?'设置已修改，请重新提取。':'')));
                cancelButton.addEventListener('click',()=>{cancelRequested=true;cancelButton.disabled=true;info.textContent='正在取消…'});
                extractButton.addEventListener('click',async()=>{
                    if(busy||!pdfDocument)return;const start=Number.parseInt($('start-page').value,10),end=Number.parseInt($('end-page').value,10);if(!Number.isInteger(start)||!Number.isInteger(end)||start<1||end>pageCount||start>end)return alert('请输入有效的起止页面');cancelRequested=false;clearResult();setBusy(true);const pages=[],markers=$('page-markers').checked,visual=$('text-order').value==='visual';let extractedPages=0,totalChars=0;
                    try{for(let pageNumber=start;pageNumber<=end;pageNumber++){if(cancelRequested)throw new Error('EXTRACTION_CANCELLED');info.textContent='正在提取文字… '+(pageNumber-start+1)+' / '+(end-start+1);const page=await pdfDocument.getPage(pageNumber);const content=await page.getTextContent({disableNormalization:false});if(content.items.length>MAX_TEXT_ITEMS_PER_PAGE)throw new Error('第 '+pageNumber+' 页文字片段过多，已停止处理');const text=visual?visualText(content.items):contentStreamText(content.items);if(text)extractedPages++;const section=(markers?'===== 第 '+pageNumber+' 页 =====\n':'')+text;totalChars+=section.length;if(totalChars>MAX_TEXT_CHARS)throw new Error('提取结果超过 500 万字符，请缩小页面范围');pages.push(section)}output.value=pages.join('\n\n').trim();const visibleChars=output.value.replace(/\s/g,'').length;$('text-stats').textContent='共 '+output.value.length+' 字符 · 非空白 '+visibleChars+' · 有文字 '+extractedPages+' 页';info.textContent=extractedPages?'提取完成，共 '+extractedPages+' 页包含可选择文字。':'未提取到可选择文字，该 PDF 可能是扫描图片。';}catch(error){clearResult();info.textContent=error.message==='EXTRACTION_CANCELLED'?'已取消提取。':'提取失败：'+error.message}finally{setBusy(false)}
                });
                copyButton.addEventListener('click',async()=>{if(!output.value)return;try{await navigator.clipboard.writeText(output.value);info.textContent='文字已复制到剪贴板。'}catch(error){output.focus();output.select();const copied=document.execCommand('copy');output.setSelectionRange(0,0);info.textContent=copied?'文字已复制到剪贴板。':'复制失败，请手动选择文字复制。'}});
                downloadButton.addEventListener('click',()=>{if(!output.value||!sourceFile)return;const blob=new Blob(['\uFEFF',output.value],{type:'text/plain;charset=utf-8'}),url=URL.createObjectURL(blob),link=document.createElement('a');link.href=url;link.download=sourceFile.name.replace(/\.pdf$/i,'')+'-文字.txt';document.body.appendChild(link);link.click();link.remove();setTimeout(()=>URL.revokeObjectURL(url),1000)});
                window.addEventListener('beforeunload',()=>{destroyPdf()});
            })();
            </script>
<?php include '_footer.php'; ?>
