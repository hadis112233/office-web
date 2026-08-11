<?php
$title = 'PDF 空白页清理';
$desc = '自动检测扫描件或文档中的空白页，确认后导出新 PDF。文件只在浏览器中处理，不会上传服务器。';
include '_header.php';
?>
            <section class="tool-panel">
                <label for="blank-pdf-file">选择 PDF</label>
                <input type="file" id="blank-pdf-file" accept="application/pdf,.pdf">
                <div class="blank-settings">
                    <label for="contrast-threshold">墨迹识别差值：<strong id="contrast-value">18</strong><input id="contrast-threshold" type="range" min="8" max="48" step="1" value="18"></label>
                    <label for="ink-threshold">允许墨迹比例：<strong><span id="ink-value">0.10</span>%</strong><input id="ink-threshold" type="range" min="0.01" max="1" step="0.01" value="0.10"></label>
                </div>
                <p class="blank-help">差值越大、允许比例越高，页面越容易判定为空白。检测会忽略最外侧约 2% 的扫描阴影；导出前仍请检查预览。</p>
                <div class="btn-row">
                    <button class="btn" id="redetect-blank" type="button" disabled>按当前阈值重新判断</button>
                    <button class="btn secondary" id="keep-all-pages" type="button" disabled>全部保留</button>
                    <button class="btn success" id="export-clean-pdf" type="button" disabled>导出已清理 PDF</button>
                </div>
                <p class="tip" id="blank-info" role="status" aria-live="polite">请选择 120 MB 以内、最多 120 页的 PDF。</p>
            </section>

            <section class="tool-panel" id="blank-results-panel" hidden>
                <div class="blank-heading"><h3>页面检测结果</h3><span id="blank-summary">尚未检测</span></div>
                <div class="blank-pages" id="blank-pages" aria-label="PDF 空白页检测结果"></div>
            </section>

            <style>
            .blank-settings{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;margin:18px 0 8px}.blank-settings label{margin:0}.blank-settings input{width:100%;margin-top:9px}.blank-help{margin:8px 0 14px;color:#64748b;font-size:12px;line-height:1.7}.blank-heading{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px}.blank-heading h3{margin:0}.blank-heading span{color:#64748b;font-size:13px}.blank-pages{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:14px}.blank-page{overflow:hidden;padding:10px;border:1px solid #dbe3ef;border-radius:12px;background:#f8fafc;transition:.2s}.blank-page.remove{border-color:#fca5a5;background:#fff7f7;box-shadow:0 0 0 2px rgba(239,68,68,.08)}.blank-thumb{display:flex;height:210px;align-items:center;justify-content:center;overflow:hidden;border:1px solid #e2e8f0;border-radius:8px;background:#fff}.blank-thumb img{display:block;max-width:100%;max-height:100%;width:auto;height:auto}.blank-page-meta{display:flex;align-items:center;justify-content:space-between;gap:8px;margin:9px 1px 6px;color:#334155;font-size:12px}.blank-page-meta strong{font-size:13px}.blank-ink{color:#64748b;font-size:11px}.blank-choice{display:flex!important;align-items:center;gap:7px;margin:8px 1px 1px!important;padding:7px 8px;border-radius:7px;background:#fff;cursor:pointer}.blank-choice input{width:auto;margin:0}.blank-page.remove .blank-choice{color:#b91c1c;background:#fef2f2}@media(max-width:650px){.blank-settings{grid-template-columns:1fr}.blank-heading{align-items:flex-start;flex-direction:column}.blank-pages{grid-template-columns:repeat(2,minmax(0,1fr));gap:9px}.blank-page{padding:7px}.blank-thumb{height:165px}}html.theme-dark .blank-page{border-color:#334155;background:#111827}html.theme-dark .blank-page.remove{border-color:#991b1b;background:#2a1111}html.theme-dark .blank-thumb,html.theme-dark .blank-choice{border-color:#334155;background:#0f172a}html.theme-dark .blank-page-meta{color:#cbd5e1}html.theme-dark .blank-page.remove .blank-choice{color:#fca5a5;background:#450a0a}
            </style>
            <script src="../static/vendor/pdf.min.js" onload="window.OFFICE_PDF_WORKER='../static/vendor/pdf.worker.min.js'"></script>
            <script src="../static/vendor/pdf-lib.min.js"></script>
            <script>
            (function(){
                'use strict';
                const MAX_FILE_BYTES=120*1024*1024,MAX_PAGES=120,MAX_ANALYSIS_PIXELS=500000,MAX_PREVIEW_BYTES=64*1024*1024;
                const $=id=>document.getElementById(id),fileInput=$('blank-pdf-file'),info=$('blank-info'),pagesBox=$('blank-pages'),resultsPanel=$('blank-results-panel'),redetectButton=$('redetect-blank'),keepAllButton=$('keep-all-pages'),exportButton=$('export-clean-pdf');
                let sourceFile=null,sourceBytes=null,pages=[],previewUrls=[],previewBytes=0,loadingTask=null,operationVersion=0,busy=false;
                const formatSize=bytes=>bytes<1024*1024?(bytes/1024).toFixed(1)+' KB':(bytes/1024/1024).toFixed(2)+' MB';
                function setBusy(value){busy=value;fileInput.disabled=value;redetectButton.disabled=value||!pages.length;keepAllButton.disabled=value||!pages.length;updateSummary();}
                async function destroyLoadingTask(){const task=loadingTask;loadingTask=null;if(task){try{await task.destroy();}catch(error){}}}
                function releasePreviews(){previewUrls.forEach(url=>URL.revokeObjectURL(url));previewUrls=[];previewBytes=0;}
                async function resetState(){operationVersion++;await destroyLoadingTask();releasePreviews();sourceFile=null;sourceBytes=null;pages=[];pagesBox.replaceChildren();resultsPanel.hidden=true;redetectButton.disabled=true;keepAllButton.disabled=true;exportButton.disabled=true;}
                function histogramStats(imageData){
                    const histogram=new Uint32Array(256),width=imageData.width,height=imageData.height,data=imageData.data,marginX=Math.floor(width*.02),marginY=Math.floor(height*.02);let samples=0;
                    for(let y=marginY;y<height-marginY;y++){for(let x=marginX;x<width-marginX;x++){const offset=(y*width+x)*4,luminance=Math.max(0,Math.min(255,Math.round(.2126*data[offset]+.7152*data[offset+1]+.0722*data[offset+2])));histogram[luminance]++;samples++;}}
                    let cumulative=0,background=255,target=samples*.9;for(let value=0;value<256;value++){cumulative+=histogram[value];if(cumulative>=target){background=value;break;}}
                    return{histogram,samples,background};
                }
                function calculateInk(item){const contrast=Number($('contrast-threshold').value),cutoff=Math.max(0,item.background-contrast);let ink=0;for(let value=0;value<=cutoff;value++)ink+=item.histogram[value];return item.samples?ink/item.samples:0;}
                function applyDetection(){const allowed=Number($('ink-threshold').value)/100;pages.forEach(item=>{item.inkRatio=calculateInk(item);item.remove=item.inkRatio<=allowed;refreshCard(item);});updateSummary();}
                function refreshCard(item){const card=pagesBox.querySelector('[data-page="'+item.pageNumber+'"]');if(!card)return;card.classList.toggle('remove',item.remove);const checkbox=card.querySelector('input');checkbox.checked=item.remove;card.querySelector('.blank-ink').textContent='墨迹 '+(item.inkRatio*100).toFixed(item.inkRatio<.001?3:2)+'%';card.querySelector('.choice-text').textContent=item.remove?'将删除':'保留此页';}
                function updateSummary(){const removed=pages.filter(item=>item.remove).length,kept=pages.length-removed;$('blank-summary').textContent=pages.length?`检测 ${pages.length} 页 · 计划删除 ${removed} 页 · 保留 ${kept} 页`:'尚未检测';exportButton.disabled=busy||!sourceBytes||removed===0||kept===0;}
                function previewBlob(canvas){return new Promise(resolve=>canvas.toBlob(resolve,'image/jpeg',.78));}
                function createCard(item,previewUrl){
                    const card=document.createElement('article');card.className='blank-page';card.dataset.page=String(item.pageNumber);
                    const thumb=document.createElement('div'),image=document.createElement('img'),meta=document.createElement('div'),number=document.createElement('strong'),ink=document.createElement('span'),choice=document.createElement('label'),checkbox=document.createElement('input'),choiceText=document.createElement('span');
                    thumb.className='blank-thumb';image.src=previewUrl;image.alt='PDF 第 '+item.pageNumber+' 页预览';image.loading='lazy';thumb.appendChild(image);meta.className='blank-page-meta';number.textContent='第 '+item.pageNumber+' 页';ink.className='blank-ink';meta.append(number,ink);choice.className='blank-choice';checkbox.type='checkbox';checkbox.setAttribute('aria-label','删除第 '+item.pageNumber+' 页');choiceText.className='choice-text';choice.append(checkbox,choiceText);checkbox.addEventListener('change',()=>{item.remove=checkbox.checked;refreshCard(item);updateSummary();});card.append(thumb,meta,choice);return card;
                }
                async function analyzePage(pdf,pageNumber,version){
                    const page=await pdf.getPage(pageNumber),base=page.getViewport({scale:1}),scale=Math.min(.8,Math.sqrt(MAX_ANALYSIS_PIXELS/(base.width*base.height)),900/Math.max(base.width,base.height)),viewport=page.getViewport({scale:Math.max(.1,scale)}),canvas=document.createElement('canvas'),context=canvas.getContext('2d',{alpha:false,willReadFrequently:true});canvas.width=Math.max(1,Math.ceil(viewport.width));canvas.height=Math.max(1,Math.ceil(viewport.height));context.fillStyle='#fff';context.fillRect(0,0,canvas.width,canvas.height);await page.render({canvasContext:context,viewport}).promise;if(version!==operationVersion)throw new Error('已取消');const stats=histogramStats(context.getImageData(0,0,canvas.width,canvas.height)),blob=await previewBlob(canvas);page.cleanup();canvas.width=1;canvas.height=1;if(!blob)throw new Error('页面预览生成失败');if(previewBytes+blob.size>MAX_PREVIEW_BYTES)throw new Error('页面预览总量超过 64 MB，请拆分 PDF 后重试');previewBytes+=blob.size;const url=URL.createObjectURL(blob);previewUrls.push(url);return{pageNumber,histogram:stats.histogram,samples:stats.samples,background:stats.background,inkRatio:0,remove:false,previewUrl:url};
                }
                async function loadFile(file){
                    await resetState();const version=operationVersion;if((file.type&&file.type!=='application/pdf')&&!/\.pdf$/i.test(file.name))throw new Error('请选择 PDF 文件');if(file.size>MAX_FILE_BYTES)throw new Error('PDF 不能超过 120 MB');const pdfjsLib=window.pdfjsLib||window['pdfjs-dist/build/pdf'];if(!pdfjsLib||!window.PDFLib)throw new Error('PDF 组件未加载，请刷新页面后重试');
                    setBusy(true);sourceFile=file;sourceBytes=await file.arrayBuffer();pdfjsLib.GlobalWorkerOptions.workerSrc=window.OFFICE_PDF_WORKER||'../static/vendor/pdf.worker.min.js';loadingTask=pdfjsLib.getDocument({data:new Uint8Array(sourceBytes.slice(0)),isEvalSupported:false});const pdf=await loadingTask.promise;if(pdf.numPages>MAX_PAGES)throw new Error('PDF 共 '+pdf.numPages+' 页，超过 120 页上限');resultsPanel.hidden=false;
                    for(let pageNumber=1;pageNumber<=pdf.numPages;pageNumber++){info.textContent='正在分析页面… '+pageNumber+' / '+pdf.numPages;const item=await analyzePage(pdf,pageNumber,version);pages.push(item);pagesBox.appendChild(createCard(item,item.previewUrl));}
                    await destroyLoadingTask();applyDetection();const removed=pages.filter(item=>item.remove).length;info.textContent=removed?`检测完成：发现 ${removed} 个疑似空白页。请检查预览和勾选状态后导出。`:'检测完成：当前阈值下未发现空白页，可调高阈值后重新判断。';
                }
                fileInput.addEventListener('change',async event=>{const file=event.target.files&&event.target.files[0];if(!file)return;try{await loadFile(file);}catch(error){await resetState();fileInput.value='';info.textContent='读取失败：'+(error.message||'未知错误');}finally{setBusy(false);}});
                $('contrast-threshold').addEventListener('input',event=>$('contrast-value').textContent=event.target.value);$('ink-threshold').addEventListener('input',event=>$('ink-value').textContent=Number(event.target.value).toFixed(2));redetectButton.addEventListener('click',()=>{if(!busy&&pages.length){applyDetection();const removed=pages.filter(item=>item.remove).length;info.textContent=`已按新阈值重新判断，发现 ${removed} 个疑似空白页。`;}});keepAllButton.addEventListener('click',()=>{pages.forEach(item=>{item.remove=false;refreshCard(item);});updateSummary();info.textContent='已取消所有删除标记。';});
                exportButton.addEventListener('click',async()=>{if(busy||!sourceBytes||!sourceFile)return;const removeIndexes=pages.filter(item=>item.remove).map(item=>item.pageNumber-1).sort((a,b)=>b-a),kept=pages.length-removeIndexes.length;if(!removeIndexes.length||kept<1)return;setBusy(true);info.textContent='正在生成清理后的 PDF…';try{const pdfDocument=await window.PDFLib.PDFDocument.load(sourceBytes.slice(0));removeIndexes.forEach(index=>pdfDocument.removePage(index));const bytes=await pdfDocument.save();const blob=new Blob([bytes],{type:'application/pdf'}),url=URL.createObjectURL(blob),link=document.createElement('a');link.href=url;link.download=sourceFile.name.replace(/\.pdf$/i,'')+'-空白页清理.pdf';document.body.appendChild(link);link.click();link.remove();setTimeout(()=>URL.revokeObjectURL(url),1000);info.textContent=`导出完成：删除 ${removeIndexes.length} 页，保留 ${kept} 页，文件大小 ${formatSize(blob.size)}。`;}catch(error){info.textContent='导出失败：'+(error.message||'未知错误');}finally{setBusy(false);}});
                window.addEventListener('beforeunload',()=>{operationVersion++;releasePreviews();if(loadingTask){try{loadingTask.destroy();}catch(error){}}});
            })();
            </script>
<?php include '_footer.php'; ?>
