<?php
$title = 'PDF 页面整理';
$desc = '拖拽调整页面顺序，旋转或删除页面，并导出新的 PDF。';
include '_header.php';
?>
            <div class="tool-panel">
                <label for="pdf-file">选择 PDF</label>
                <input type="file" id="pdf-file" accept="application/pdf,.pdf">
                <div class="btn-row">
                    <button class="btn" id="save-pdf" type="button" disabled>导出整理后的 PDF</button>
                    <button class="btn secondary" id="reset-order" type="button" disabled>恢复原始顺序</button>
                </div>
                <p class="tip" id="organize-info" role="status" aria-live="polite">请选择 120 MB 以内、最多 150 页的 PDF。文件只在浏览器中处理，不会上传服务器。</p>
            </div>
            <div class="tool-panel" id="organize-panel" hidden>
                <div class="organize-heading">
                    <strong>页面顺序</strong>
                    <span>电脑可拖拽卡片；手机可使用前移、后移按钮</span>
                </div>
                <div class="pdf-organizer" id="pdf-organizer" aria-label="PDF 页面列表"></div>
            </div>
            <style>
            .organize-heading{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px}.organize-heading span{color:#64748b;font-size:12px}.pdf-organizer{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:14px}.pdf-page-card{position:relative;min-width:0;padding:10px;border:1px solid #dbe3ef;border-radius:12px;background:#f8fafc;box-shadow:0 2px 8px rgba(15,23,42,.04);cursor:grab}.pdf-page-card:active{cursor:grabbing}.pdf-page-card.dragging{opacity:.45}.pdf-page-card.drag-target{border-color:#6366f1;box-shadow:0 0 0 2px rgba(99,102,241,.14)}.pdf-thumb{display:flex;align-items:center;justify-content:center;height:210px;overflow:hidden;border:1px solid #e2e8f0;border-radius:8px;background:#fff;color:#94a3b8}.pdf-thumb canvas{display:block;max-width:100%;max-height:100%;width:auto;height:auto}.pdf-page-meta{display:flex;align-items:center;justify-content:space-between;gap:8px;margin:9px 2px 7px;color:#334155;font-size:12px}.pdf-page-meta strong{font-size:13px}.pdf-page-actions{display:grid;grid-template-columns:repeat(5,1fr);gap:5px}.pdf-page-actions button{min-height:34px;padding:4px;border:1px solid #dbe3ef;border-radius:7px;background:#fff;color:#334155;cursor:pointer}.pdf-page-actions button:hover:not(:disabled){border-color:#6366f1;color:#4f46e5}.pdf-page-actions button:disabled{cursor:not-allowed;opacity:.35}.pdf-page-actions .delete-page:hover{border-color:#ef4444;color:#dc2626}html.theme-dark .organize-heading span{color:#94a3b8}html.theme-dark .pdf-page-card{border-color:#334155;background:#111827}html.theme-dark .pdf-thumb,html.theme-dark .pdf-page-actions button{border-color:#334155;background:#0f172a;color:#cbd5e1}html.theme-dark .pdf-page-meta{color:#cbd5e1}@media(max-width:620px){.organize-heading{align-items:flex-start;flex-direction:column}.pdf-organizer{grid-template-columns:repeat(2,minmax(0,1fr));gap:9px}.pdf-page-card{padding:7px}.pdf-thumb{height:170px}.pdf-page-actions{grid-template-columns:repeat(5,minmax(0,1fr))}}
            </style>
            <script src="../static/vendor/pdf.min.js" onload="window.OFFICE_PDF_WORKER='../static/vendor/pdf.worker.min.js'" onerror="this.onerror=null;this.onload=function(){window.OFFICE_PDF_WORKER='https://unpkg.com/pdfjs-dist@3.11.174/build/pdf.worker.min.js'};this.src='https://unpkg.com/pdfjs-dist@3.11.174/build/pdf.min.js';document.getElementById('organize-info').textContent='本地预览组件不可用，正在尝试网络备用组件…'"></script>
            <script src="../static/vendor/pdf-lib.min.js" onerror="this.onerror=null;this.src='https://unpkg.com/pdf-lib@1.17.1/dist/pdf-lib.min.js';document.getElementById('organize-info').textContent='本地编辑组件不可用，正在尝试网络备用组件…'"></script>
            <script>
            (function(){
                'use strict';
                const MAX_FILE_BYTES=120*1024*1024,MAX_PAGES=150;
                const $=id=>document.getElementById(id);
                const fileInput=$('pdf-file'),organizer=$('pdf-organizer'),info=$('organize-info'),saveButton=$('save-pdf'),resetButton=$('reset-order');
                let sourceFile=null,sourceBytes=null,previewPdf=null,pages=[],draggedId=null,busy=false;
                const normalizeRotation=value=>((value%360)+360)%360;
                const formatSize=bytes=>bytes<1024*1024?(bytes/1024).toFixed(1)+' KB':(bytes/1024/1024).toFixed(2)+' MB';

                function setBusy(value){busy=value;fileInput.disabled=value;saveButton.disabled=value||!pages.length;resetButton.disabled=value||!pages.length;organizer.querySelectorAll('button').forEach(button=>button.disabled=value||button.dataset.edge==='true')}
                function resetState(){sourceFile=null;sourceBytes=null;previewPdf=null;pages=[];draggedId=null;organizer.replaceChildren();$('organize-panel').hidden=true;saveButton.disabled=true;resetButton.disabled=true}
                function button(label,title,action,className=''){
                    const element=document.createElement('button');element.type='button';element.textContent=label;element.title=title;element.setAttribute('aria-label',title);if(className)element.className=className;element.addEventListener('click',action);return element;
                }
                function cardFor(item){
                    const card=document.createElement('article');card.className='pdf-page-card';card.draggable=true;card.dataset.id=String(item.id);
                    const thumb=document.createElement('div');thumb.className='pdf-thumb';thumb.textContent='正在生成预览…';
                    const meta=document.createElement('div');meta.className='pdf-page-meta';
                    const order=document.createElement('strong');order.className='order-number';
                    const original=document.createElement('span');original.textContent='原第 '+(item.originalIndex+1)+' 页';meta.append(order,original);
                    const actions=document.createElement('div');actions.className='pdf-page-actions';
                    actions.append(
                        button('←','前移一页',()=>moveItem(item.id,-1),'move-back'),
                        button('→','后移一页',()=>moveItem(item.id,1),'move-forward'),
                        button('↶','向左旋转 90°',()=>rotateItem(item.id,-90)),
                        button('↷','向右旋转 90°',()=>rotateItem(item.id,90)),
                        button('删','删除此页',()=>removeItem(item.id),'delete-page')
                    );
                    card.append(thumb,meta,actions);
                    card.addEventListener('dragstart',event=>{if(busy){event.preventDefault();return}draggedId=item.id;card.classList.add('dragging');event.dataTransfer.effectAllowed='move'});
                    card.addEventListener('dragover',event=>{event.preventDefault();if(draggedId!==null&&draggedId!==item.id)card.classList.add('drag-target')});
                    card.addEventListener('dragleave',()=>card.classList.remove('drag-target'));
                    card.addEventListener('drop',event=>{event.preventDefault();card.classList.remove('drag-target');const bounds=card.getBoundingClientRect();moveRelative(draggedId,item.id,event.clientY>bounds.top+bounds.height/2)});
                    card.addEventListener('dragend',()=>{draggedId=null;organizer.querySelectorAll('.pdf-page-card').forEach(node=>node.classList.remove('dragging','drag-target'))});
                    return card;
                }
                function refreshOrder(){
                    pages.forEach((item,index)=>{
                        const card=organizer.querySelector('[data-id="'+item.id+'"]');if(!card)return;organizer.appendChild(card);card.querySelector('.order-number').textContent='第 '+(index+1)+' 页';
                        const back=card.querySelector('.move-back'),forward=card.querySelector('.move-forward');back.dataset.edge=String(index===0);forward.dataset.edge=String(index===pages.length-1);back.disabled=busy||index===0;forward.disabled=busy||index===pages.length-1;
                    });
                    saveButton.disabled=busy||!pages.length;resetButton.disabled=busy||!pages.length;
                }
                function moveItem(id,offset){const from=pages.findIndex(item=>item.id===id),to=from+offset;if(from<0||to<0||to>=pages.length)return;const [item]=pages.splice(from,1);pages.splice(to,0,item);refreshOrder()}
                function moveRelative(sourceId,targetId,after){if(sourceId===null||sourceId===targetId)return;const from=pages.findIndex(item=>item.id===sourceId);if(from<0)return;const [item]=pages.splice(from,1);const target=pages.findIndex(entry=>entry.id===targetId);if(target<0)return;pages.splice(target+(after?1:0),0,item);refreshOrder()}
                async function rotateItem(id,amount){const item=pages.find(entry=>entry.id===id);if(!item||busy)return;item.rotation=normalizeRotation(item.rotation+amount);await renderThumbnail(item)}
                function removeItem(id){if(busy)return;if(pages.length===1)return alert('PDF 至少需要保留一页');pages=pages.filter(item=>item.id!==id);const card=organizer.querySelector('[data-id="'+id+'"]');if(card)card.remove();refreshOrder();info.textContent='已保留 '+pages.length+' 页，可继续整理或导出。'}
                async function renderThumbnail(item){
                    const card=organizer.querySelector('[data-id="'+item.id+'"]');if(!card||!previewPdf)return;const thumb=card.querySelector('.pdf-thumb');thumb.textContent='正在生成预览…';
                    try{const page=await previewPdf.getPage(item.originalIndex+1);const base=page.getViewport({scale:1,rotation:normalizeRotation((page.rotate||0)+item.rotation)});const scale=Math.min(.34,160/base.width,190/base.height);const viewport=page.getViewport({scale:scale,rotation:normalizeRotation((page.rotate||0)+item.rotation)});const canvas=document.createElement('canvas');canvas.width=Math.max(1,Math.ceil(viewport.width));canvas.height=Math.max(1,Math.ceil(viewport.height));await page.render({canvasContext:canvas.getContext('2d'),viewport:viewport}).promise;if(card.isConnected){thumb.replaceChildren(canvas)}}catch(error){thumb.textContent='预览失败';}
                }
                async function loadFile(file){
                    resetState();
                    if((file.type&&file.type!=='application/pdf')&&!/\.pdf$/i.test(file.name))throw new Error('请选择 PDF 文件');
                    if(file.size>MAX_FILE_BYTES)throw new Error('PDF 不能超过 120 MB');
                    const pdfjsLib=window.pdfjsLib||window['pdfjs-dist/build/pdf'];if(!pdfjsLib||!window.PDFLib)throw new Error('PDF 组件未加载，请刷新页面后重试');
                    setBusy(true);info.textContent='正在读取 PDF…';sourceFile=file;sourceBytes=await file.arrayBuffer();pdfjsLib.GlobalWorkerOptions.workerSrc=window.OFFICE_PDF_WORKER||'../static/vendor/pdf.worker.min.js';previewPdf=await pdfjsLib.getDocument({data:new Uint8Array(sourceBytes.slice(0))}).promise;
                    if(previewPdf.numPages>MAX_PAGES)throw new Error('PDF 共 '+previewPdf.numPages+' 页，超过 150 页上限');
                    pages=Array.from({length:previewPdf.numPages},(_,index)=>({id:index+1,originalIndex:index,rotation:0}));organizer.replaceChildren(...pages.map(cardFor));$('organize-panel').hidden=false;refreshOrder();
                    for(let index=0;index<pages.length;index++){await renderThumbnail(pages[index]);info.textContent='正在生成页面预览… '+(index+1)+' / '+pages.length;}
                    info.textContent='已加载 '+file.name+'，共 '+pages.length+' 页。拖拽或使用按钮即可整理。';
                }
                fileInput.addEventListener('change',async event=>{const file=event.target.files[0];if(!file)return;try{await loadFile(file)}catch(error){resetState();fileInput.value='';info.textContent='读取失败：'+error.message}finally{setBusy(false)}});
                resetButton.addEventListener('click',async()=>{if(busy||!sourceFile)return;setBusy(true);pages=Array.from({length:previewPdf.numPages},(_,index)=>({id:index+1,originalIndex:index,rotation:0}));organizer.replaceChildren(...pages.map(cardFor));refreshOrder();for(const item of pages)await renderThumbnail(item);info.textContent='已恢复原始顺序和方向。';setBusy(false)});
                saveButton.addEventListener('click',async()=>{
                    if(busy||!sourceBytes||!pages.length)return;setBusy(true);info.textContent='正在生成新的 PDF…';
                    try{const source=await window.PDFLib.PDFDocument.load(sourceBytes.slice(0));const output=await window.PDFLib.PDFDocument.create();const copied=await output.copyPages(source,pages.map(item=>item.originalIndex));copied.forEach((page,index)=>{const originalRotation=page.getRotation().angle||0;page.setRotation(window.PDFLib.degrees(normalizeRotation(originalRotation+pages[index].rotation)));output.addPage(page)});const bytes=await output.save();const blob=new Blob([bytes],{type:'application/pdf'});const url=URL.createObjectURL(blob);const link=document.createElement('a');link.href=url;link.download=sourceFile.name.replace(/\.pdf$/i,'')+'-整理.pdf';document.body.appendChild(link);link.click();link.remove();setTimeout(()=>URL.revokeObjectURL(url),1000);info.textContent='整理完成：'+pages.length+' 页，文件大小 '+formatSize(blob.size)+'。'}catch(error){info.textContent='导出失败：'+error.message}finally{setBusy(false)}
                });
            })();
            </script>
<?php include '_footer.php'; ?>
