<?php
$title = 'PDF 文档属性';
$desc = '查看、编辑或清空 PDF 的标题、作者、主题等常见元数据。';
include '_header.php';
?>
            <div class="tool-panel">
                <label for="pdf-file">选择 PDF</label>
                <input type="file" id="pdf-file" accept="application/pdf,.pdf">
                <div class="metadata-grid" id="metadata-fields" hidden>
                    <label>标题<input type="text" id="meta-title" maxlength="500" autocomplete="off"></label>
                    <label>作者<input type="text" id="meta-author" maxlength="500" autocomplete="off"></label>
                    <label>主题<input type="text" id="meta-subject" maxlength="500" autocomplete="off"></label>
                    <label>关键词（逗号分隔）<input type="text" id="meta-keywords" maxlength="1000" autocomplete="off"></label>
                    <label>创建程序<input type="text" id="meta-creator" maxlength="500" autocomplete="off"></label>
                    <label>生成程序<input type="text" id="meta-producer" maxlength="500" autocomplete="off"></label>
                </div>
                <div class="btn-row">
                    <button class="btn" id="save-metadata" type="button" disabled>应用属性修改</button>
                    <button class="btn secondary" id="clear-metadata" type="button" disabled>清空常见属性并生成</button>
                    <button class="btn success" id="download-pdf" type="button" disabled>下载新 PDF</button>
                </div>
                <p class="tip" id="metadata-info" role="status" aria-live="polite">请选择 120 MB 以内、最多 1000 页的 PDF。文件只在浏览器中处理，不会上传服务器。</p>
                <div class="metadata-warning">注意：本工具处理阅读器中常见的文档属性，不会删除 PDF 附件、批注、隐藏图层、历史对象或页面中的个人信息，不能替代专业取证级清理工具。</div>
            </div>
            <div class="tool-panel" id="metadata-summary-panel" hidden>
                <label>文档信息</label>
                <div class="metadata-summary" id="metadata-summary"></div>
            </div>
            <style>
            .metadata-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin:15px 0}.metadata-grid label{display:flex;min-width:0;flex-direction:column}.metadata-grid input{width:100%;margin-top:5px}.metadata-warning{margin-top:14px;padding:11px 13px;border:1px solid #fed7aa;border-radius:9px;background:#fff7ed;color:#9a3412;font-size:12px;line-height:1.65}.metadata-summary{display:grid;grid-template-columns:150px minmax(0,1fr);border:1px solid #e2e8f0;border-radius:9px;overflow:hidden}.metadata-summary dt,.metadata-summary dd{min-width:0;padding:9px 12px;border-bottom:1px solid #e2e8f0}.metadata-summary dt{background:#f8fafc;color:#475569;font-weight:600}.metadata-summary dd{overflow-wrap:anywhere;color:#334155}.metadata-summary dt:last-of-type,.metadata-summary dd:last-of-type{border-bottom:0}html.theme-dark .metadata-warning{border-color:#9a3412;background:#431407;color:#fed7aa}html.theme-dark .metadata-summary{border-color:#334155}html.theme-dark .metadata-summary dt,html.theme-dark .metadata-summary dd{border-color:#334155}html.theme-dark .metadata-summary dt{background:#0f172a;color:#94a3b8}html.theme-dark .metadata-summary dd{color:#cbd5e1}@media(max-width:650px){.metadata-grid{grid-template-columns:1fr}.metadata-summary{grid-template-columns:110px minmax(0,1fr)}}
            </style>
            <script src="../static/vendor/pdf-lib.min.js" onerror="this.onerror=null;this.src='https://unpkg.com/pdf-lib@1.17.1/dist/pdf-lib.min.js';document.getElementById('metadata-info').textContent='本地组件不可用，正在尝试网络备用组件…'"></script>
            <script>
            (function(){
                'use strict';
                const MAX_FILE_BYTES=120*1024*1024,MAX_PAGES=1000,fieldIds=['meta-title','meta-author','meta-subject','meta-keywords','meta-creator','meta-producer'];
                const $=id=>document.getElementById(id),fileInput=$('pdf-file'),saveButton=$('save-metadata'),clearButton=$('clear-metadata'),downloadButton=$('download-pdf'),info=$('metadata-info');
                let sourceFile=null,sourceBytes=null,resultBlob=null,busy=false;
                const safeGet=getter=>{try{return getter()}catch(error){return undefined}};
                const valueOrEmpty=value=>value===undefined||value===null?'':String(value);
                const formatSize=bytes=>bytes<1024*1024?(bytes/1024).toFixed(1)+' KB':(bytes/1024/1024).toFixed(2)+' MB';
                const formatDate=value=>value instanceof Date&&!Number.isNaN(value.getTime())?value.toLocaleString('zh-CN',{hour12:false}):'未设置或无法读取';
                function setBusy(value){busy=value;fileInput.disabled=value;saveButton.disabled=value||!sourceBytes;clearButton.disabled=value||!sourceBytes;downloadButton.disabled=value||!resultBlob;fieldIds.forEach(id=>$(id).disabled=value)}
                function readMetadata(pdf){const keywords=safeGet(()=>pdf.getKeywords());return{title:valueOrEmpty(safeGet(()=>pdf.getTitle())),author:valueOrEmpty(safeGet(()=>pdf.getAuthor())),subject:valueOrEmpty(safeGet(()=>pdf.getSubject())),keywords:Array.isArray(keywords)?keywords.join(', '):valueOrEmpty(keywords),creator:valueOrEmpty(safeGet(()=>pdf.getCreator())),producer:valueOrEmpty(safeGet(()=>pdf.getProducer())),creationDate:formatDate(safeGet(()=>pdf.getCreationDate())),modificationDate:formatDate(safeGet(()=>pdf.getModificationDate())),pages:pdf.getPageCount()}}
                function fillFields(metadata){$('meta-title').value=metadata.title;$('meta-author').value=metadata.author;$('meta-subject').value=metadata.subject;$('meta-keywords').value=metadata.keywords;$('meta-creator').value=metadata.creator;$('meta-producer').value=metadata.producer}
                function showSummary(metadata,label){
                    const names=[['状态',label],['页数',String(metadata.pages)],['标题',metadata.title||'（空）'],['作者',metadata.author||'（空）'],['主题',metadata.subject||'（空）'],['关键词',metadata.keywords||'（空）'],['创建程序',metadata.creator||'（空）'],['生成程序',metadata.producer||'（空）'],['创建日期',metadata.creationDate],['修改日期',metadata.modificationDate]];const list=document.createElement('dl');list.className='metadata-summary';names.forEach(entry=>{const term=document.createElement('dt');term.textContent=entry[0];const detail=document.createElement('dd');detail.textContent=entry[1];list.append(term,detail)});$('metadata-summary').replaceChildren(...list.childNodes);$('metadata-summary-panel').hidden=false;
                }
                function invalidateResult(){resultBlob=null;downloadButton.disabled=true;if(sourceFile&&!busy)info.textContent='属性已修改，请点击“应用属性修改”生成新文件。'}
                fileInput.addEventListener('change',async event=>{
                    const file=event.target.files[0];sourceFile=null;sourceBytes=null;resultBlob=null;saveButton.disabled=true;clearButton.disabled=true;downloadButton.disabled=true;$('metadata-fields').hidden=true;$('metadata-summary-panel').hidden=true;if(!file)return;
                    if(((file.type&&file.type!=='application/pdf')&&!/\.pdf$/i.test(file.name))||file.size>MAX_FILE_BYTES){fileInput.value='';info.textContent='请选择 120 MB 以内的 PDF 文件。';return}if(!window.PDFLib){fileInput.value='';info.textContent='PDF 组件未加载，请刷新页面后重试。';return}
                    setBusy(true);info.textContent='正在读取文档属性…';
                    try{const bytes=await file.arrayBuffer();const pdf=await window.PDFLib.PDFDocument.load(bytes.slice(0),{updateMetadata:false});const metadata=readMetadata(pdf);if(metadata.pages>MAX_PAGES)throw new Error('PDF 共 '+metadata.pages+' 页，超过 1000 页上限');sourceFile=file;sourceBytes=bytes;fillFields(metadata);showSummary(metadata,'原始属性');$('metadata-fields').hidden=false;info.textContent='已读取 '+file.name+'（'+formatSize(file.size)+'）。可编辑后生成新文件。'}catch(error){fileInput.value='';info.textContent='读取失败：'+error.message}finally{setBusy(false)}
                });
                fieldIds.forEach(id=>$(id).addEventListener('input',invalidateResult));
                async function createResult(clear){
                    if(busy||!sourceBytes)return;setBusy(true);resultBlob=null;info.textContent=clear?'正在清空常见属性…':'正在写入文档属性…';
                    try{const pdf=await window.PDFLib.PDFDocument.load(sourceBytes.slice(0),{updateMetadata:false});const text=id=>clear?'':$(id).value.trim().slice(0,500);const keywords=clear?[]:$('meta-keywords').value.split(/[,，;；\n]+/).map(value=>value.trim()).filter(Boolean).slice(0,50).map(value=>value.slice(0,100));pdf.setTitle(text('meta-title'));pdf.setAuthor(text('meta-author'));pdf.setSubject(text('meta-subject'));pdf.setKeywords(keywords);pdf.setCreator(text('meta-creator'));pdf.setProducer(text('meta-producer'));pdf.setModificationDate(new Date());const output=await pdf.save();resultBlob=new Blob([output],{type:'application/pdf'});const updated=readMetadata(pdf);fillFields(updated);showSummary(updated,clear?'已清空常见属性':'修改后属性');info.textContent=(clear?'常见属性已清空':'文档属性已更新')+'，新文件大小 '+formatSize(resultBlob.size)+'。';}catch(error){resultBlob=null;info.textContent='处理失败：'+error.message}finally{setBusy(false)}
                }
                saveButton.addEventListener('click',()=>createResult(false));clearButton.addEventListener('click',()=>createResult(true));downloadButton.addEventListener('click',()=>{if(!resultBlob||!sourceFile)return;const url=URL.createObjectURL(resultBlob);const link=document.createElement('a');link.href=url;link.download=sourceFile.name.replace(/\.pdf$/i,'')+'-属性更新.pdf';document.body.appendChild(link);link.click();link.remove();setTimeout(()=>URL.revokeObjectURL(url),1000)});
            })();
            </script>
<?php include '_footer.php'; ?>
