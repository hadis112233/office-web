<?php
$title = '文件哈希校验';
$desc = '计算文件或文本的 MD5、SHA-1、SHA-256、SHA-512 校验值，并快速核对两份结果。所有内容仅在浏览器本地处理。';
include '_header.php';
?>
            <div class="hash-notice" id="hashNotice" role="status" aria-live="polite">
                <span>🔒</span>
                <div><strong>本地分块计算</strong><p>文件不会上传服务器；大文件会分块读取，避免一次性占满浏览器内存。MD5 和 SHA-1 仅用于兼容旧校验值，不适合安全用途。</p></div>
            </div>

            <section class="tool-panel">
                <h3>文件校验</h3>
                <div class="hash-settings">
                    <label for="fileAlgorithm">算法</label>
                    <select id="fileAlgorithm">
                        <option value="SHA256" selected>SHA-256（推荐）</option>
                        <option value="SHA512">SHA-512</option>
                        <option value="SHA1">SHA-1（兼容）</option>
                        <option value="MD5">MD5（兼容）</option>
                    </select>
                </div>
                <label class="hash-drop" id="hashDrop" for="hashFiles">
                    <input type="file" id="hashFiles" multiple>
                    <span class="hash-drop-icon">📄</span>
                    <strong>点击选择文件，或拖到这里</strong>
                    <small>一次最多处理 20 个文件</small>
                </label>
                <div class="btn-row">
                    <button class="btn success" id="hashFilesButton" type="button">开始计算</button>
                    <button class="btn" id="copyAllButton" type="button" disabled>复制全部</button>
                    <button class="btn" id="clearFilesButton" type="button">清空</button>
                </div>
                <div class="hash-progress" id="hashProgress" hidden>
                    <progress id="hashProgressBar" max="100" value="0"></progress>
                    <span id="hashProgressText">准备计算…</span>
                </div>
                <div class="hash-results" id="hashResults" aria-live="polite"></div>
            </section>

            <section class="tool-panel">
                <h3>文本校验</h3>
                <label for="hashText">输入文本</label>
                <textarea id="hashText" rows="6" placeholder="输入要计算校验值的文本；按 UTF-8 编码处理"></textarea>
                <div class="btn-row">
                    <button class="btn success" id="hashTextButton" type="button">计算文本校验值</button>
                    <button class="btn" id="copyTextHashButton" type="button" disabled>复制结果</button>
                </div>
                <label for="textHashResult">计算结果</label>
                <textarea id="textHashResult" class="hash-output" rows="3" readonly placeholder="校验值将显示在这里"></textarea>
            </section>

            <section class="tool-panel">
                <h3>校验值对比</h3>
                <p class="hash-helper">粘贴发布方提供的校验值和本页计算结果。空格、换行、大小写以及常见算法前缀会自动忽略。</p>
                <div class="compare-grid">
                    <label for="expectedHash">预期校验值<input id="expectedHash" type="text" autocomplete="off" spellcheck="false" placeholder="例如：SHA256: abc123…"></label>
                    <label for="actualHash">实际校验值<input id="actualHash" type="text" autocomplete="off" spellcheck="false" placeholder="粘贴计算结果"></label>
                </div>
                <div class="compare-result" id="compareResult" role="status" aria-live="polite">输入两份校验值后自动比较。</div>
            </section>

            <style>
            .hash-notice{display:flex;align-items:flex-start;gap:12px;margin-bottom:18px;padding:14px 16px;border:1px solid #a7f3d0;border-radius:12px;color:#065f46;background:#ecfdf5}.hash-notice>span{font-size:24px}.hash-notice strong{display:block;margin-bottom:3px}.hash-notice p{margin:0;font-size:12px;line-height:1.6}.hash-notice.error{border-color:#fecaca;color:#991b1b;background:#fef2f2}.hash-settings{display:flex;align-items:center;gap:10px;margin:12px 0}.hash-settings label{margin:0}.hash-settings select{width:min(100%,240px)}.hash-drop{display:flex!important;min-height:150px;margin:14px 0!important;padding:24px;flex-direction:column;align-items:center;justify-content:center;gap:7px;border:2px dashed #cbd5e1;border-radius:14px;background:#f8fafc;text-align:center;cursor:pointer;transition:.2s}.hash-drop:hover,.hash-drop.drag-over{border-color:#6366f1;background:#eef2ff}.hash-drop input{position:absolute;width:1px;height:1px;overflow:hidden;opacity:0}.hash-drop-icon{font-size:36px}.hash-drop small,.hash-helper{color:#64748b;font-size:12px}.hash-progress{display:grid;grid-template-columns:minmax(120px,1fr) auto;align-items:center;gap:12px;margin:14px 0;color:#64748b;font-size:12px}.hash-progress progress{width:100%;height:12px}.hash-results{display:grid;gap:10px;margin-top:14px}.hash-result{display:grid;grid-template-columns:minmax(120px,1fr) auto;gap:8px 14px;padding:13px 14px;border:1px solid #dbe3f0;border-radius:11px;background:#f8fafc}.hash-result-name{min-width:0;overflow:hidden;color:#334155;font-weight:600;text-overflow:ellipsis;white-space:nowrap}.hash-result-meta{color:#64748b;font-size:12px}.hash-result-value{grid-column:1/-1;display:flex;align-items:flex-start;gap:8px}.hash-result code{flex:1;min-width:0;padding:8px;border-radius:7px;color:#334155;background:#fff;font:12px/1.55 Consolas,Monaco,monospace;overflow-wrap:anywhere}.hash-copy{flex:0 0 auto;padding:7px 10px;border:1px solid #c7d2fe;border-radius:7px;color:#4338ca;background:#eef2ff;cursor:pointer}.hash-output,.compare-grid input{font-family:Consolas,Monaco,monospace}.compare-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;margin:14px 0}.compare-grid label{margin:0}.compare-grid input{margin-top:7px}.compare-result{padding:12px 14px;border-radius:10px;color:#475569;background:#f1f5f9}.compare-result.match{color:#047857;background:#ecfdf5}.compare-result.mismatch,.compare-result.error{color:#b91c1c;background:#fef2f2}@media(max-width:650px){.compare-grid{grid-template-columns:1fr}.hash-progress{grid-template-columns:1fr}.hash-result{grid-template-columns:1fr}.hash-result-value{flex-direction:column}.hash-copy{width:100%}}html.theme-dark .hash-notice{border-color:#047857;color:#a7f3d0;background:#064e3b}html.theme-dark .hash-notice.error{border-color:#b91c1c;color:#fecaca;background:#450a0a}html.theme-dark .hash-drop,html.theme-dark .hash-result{border-color:#475569;background:#1e293b}html.theme-dark .hash-drop:hover,html.theme-dark .hash-drop.drag-over{border-color:#818cf8;background:#312e81}html.theme-dark .hash-result-name,html.theme-dark .hash-result code{color:#e2e8f0}html.theme-dark .hash-result code{background:#0f172a}html.theme-dark .compare-result{color:#cbd5e1;background:#1e293b}html.theme-dark .compare-result.match{color:#6ee7b7;background:#064e3b}html.theme-dark .compare-result.mismatch,html.theme-dark .compare-result.error{color:#fca5a5;background:#450a0a}
            </style>
            <script src="../static/vendor/hash-wasm.umd.min.js"></script>
            <script>
            (function(){
                const $=id=>document.getElementById(id),chunkSize=4*1024*1024,maxFiles=20;
                const fileInput=$('hashFiles'),drop=$('hashDrop'),fileButton=$('hashFilesButton'),copyAllButton=$('copyAllButton'),clearButton=$('clearFilesButton'),resultsBox=$('hashResults'),progress=$('hashProgress'),progressBar=$('hashProgressBar'),progressText=$('hashProgressText');
                const algorithmNames={MD5:'MD5',SHA1:'SHA-1',SHA256:'SHA-256',SHA512:'SHA-512'};
                const factories={MD5:'createMD5',SHA1:'createSHA1',SHA256:'createSHA256',SHA512:'createSHA512'};
                let selectedFiles=[],results=[],runToken=0,running=false;
                function formatSize(bytes){if(bytes<1024)return bytes+' B';if(bytes<1048576)return(bytes/1024).toFixed(1)+' KB';if(bytes<1073741824)return(bytes/1048576).toFixed(2)+' MB';return(bytes/1073741824).toFixed(2)+' GB';}
                function setNotice(message,error){const notice=$('hashNotice');notice.classList.toggle('error',Boolean(error));notice.querySelector('strong').textContent=error?'当前无法计算':'本地分块计算';notice.querySelector('p').textContent=message;}
                function setBusy(value){running=value;fileButton.disabled=value;fileInput.disabled=value;$('fileAlgorithm').disabled=value;clearButton.textContent=value?'取消并清空':'清空';}
                function copyText(value){
                    if(!value)return Promise.resolve(false);
                    if(navigator.clipboard&&window.isSecureContext)return navigator.clipboard.writeText(value).then(()=>true).catch(()=>fallbackCopy(value));
                    return Promise.resolve(fallbackCopy(value));
                }
                function fallbackCopy(value){const helper=document.createElement('textarea');helper.value=value;helper.style.position='fixed';helper.style.opacity='0';document.body.appendChild(helper);helper.select();const ok=document.execCommand('copy');helper.remove();return ok;}
                function showFiles(files){
                    selectedFiles=Array.from(files||[]).slice(0,maxFiles);results=[];resultsBox.replaceChildren();copyAllButton.disabled=true;
                    if(!selectedFiles.length){drop.querySelector('strong').textContent='点击选择文件，或拖到这里';drop.querySelector('small').textContent='一次最多处理 20 个文件';return;}
                    drop.querySelector('strong').textContent=`已选择 ${selectedFiles.length} 个文件`;
                    drop.querySelector('small').textContent=selectedFiles.map(file=>file.name).join('、');
                    if((files||[]).length>maxFiles)setNotice(`一次最多处理 ${maxFiles} 个文件，已保留前 ${maxFiles} 个。`,true);
                }
                function resultRow(item){
                    const row=document.createElement('div'),name=document.createElement('div'),meta=document.createElement('div'),valueWrap=document.createElement('div'),code=document.createElement('code'),button=document.createElement('button');
                    row.className='hash-result';name.className='hash-result-name';meta.className='hash-result-meta';valueWrap.className='hash-result-value';button.className='hash-copy';button.type='button';
                    name.textContent=item.name;name.title=item.name;meta.textContent=formatSize(item.size)+' · '+algorithmNames[item.algorithm];code.textContent=item.hash;button.textContent='复制';button.addEventListener('click',async()=>{button.textContent=await copyText(item.hash)?'已复制':'复制失败';setTimeout(()=>button.textContent='复制',1200);});
                    valueWrap.append(code,button);row.append(name,meta,valueWrap);return row;
                }
                async function hashFile(file,algorithm,token,fileIndex){
                    const factory=window.hashwasm&&window.hashwasm[factories[algorithm]];if(typeof factory!=='function')throw new Error('哈希组件加载失败，请刷新页面重试');
                    const hasher=await factory();hasher.init();let offset=0;
                    while(offset<file.size){if(token!==runToken)throw new Error('已取消');const end=Math.min(offset+chunkSize,file.size),buffer=await file.slice(offset,end).arrayBuffer();hasher.update(new Uint8Array(buffer));offset=end;const current=file.size?offset/file.size:1;progressBar.value=Math.round(((fileIndex+current)/selectedFiles.length)*100);progressText.textContent=`正在计算 ${fileIndex+1}/${selectedFiles.length}：${file.name}（${Math.round(current*100)}%）`;await new Promise(resolve=>setTimeout(resolve,0));}
                    if(file.size===0)hasher.update(new Uint8Array(0));return hasher.digest('hex');
                }
                async function calculateFiles(){
                    if(running||!selectedFiles.length){if(!selectedFiles.length)setNotice('请先选择要校验的文件。',true);return;}
                    const algorithm=$('fileAlgorithm').value,token=++runToken;results=[];resultsBox.replaceChildren();copyAllButton.disabled=true;progress.hidden=false;progressBar.value=0;setBusy(true);
                    try{for(let i=0;i<selectedFiles.length;i++){const file=selectedFiles[i],hash=await hashFile(file,algorithm,token,i);const item={name:file.name,size:file.size,algorithm,hash};results.push(item);resultsBox.appendChild(resultRow(item));}progressBar.value=100;progressText.textContent=`计算完成：${results.length} 个文件`;copyAllButton.disabled=false;setNotice(`已完成 ${algorithmNames[algorithm]} 计算。文件内容始终留在当前浏览器中。`,false);}
                    catch(error){if(error.message!=='已取消')setNotice(error.message||'计算失败，请重试。',true);}
                    finally{if(token===runToken)setBusy(false);}
                }
                function clearFiles(){runToken++;setBusy(false);selectedFiles=[];results=[];fileInput.value='';resultsBox.replaceChildren();progress.hidden=true;progressBar.value=0;copyAllButton.disabled=true;showFiles([]);setNotice('文件不会上传服务器；大文件会分块读取，避免一次性占满浏览器内存。MD5 和 SHA-1 仅用于兼容旧校验值，不适合安全用途。',false);}
                fileInput.addEventListener('change',event=>showFiles(event.target.files));['dragenter','dragover'].forEach(name=>drop.addEventListener(name,event=>{event.preventDefault();drop.classList.add('drag-over');}));['dragleave','drop'].forEach(name=>drop.addEventListener(name,event=>{event.preventDefault();drop.classList.remove('drag-over');}));drop.addEventListener('drop',event=>{if(!running&&event.dataTransfer&&event.dataTransfer.files)showFiles(event.dataTransfer.files);});fileButton.addEventListener('click',calculateFiles);clearButton.addEventListener('click',clearFiles);
                copyAllButton.addEventListener('click',async()=>{const text=results.map(item=>`${algorithmNames[item.algorithm]}  ${item.hash}  ${item.name}`).join('\n');copyAllButton.textContent=await copyText(text)?'已复制':'复制失败';setTimeout(()=>copyAllButton.textContent='复制全部',1200);});
                $('hashTextButton').addEventListener('click',async()=>{const value=$('hashText').value,algorithm=$('fileAlgorithm').value,fn=window.hashwasm&&window.hashwasm[algorithm.toLowerCase()];if(typeof fn!=='function'){setNotice('哈希组件加载失败，请刷新页面重试。',true);return;}try{const hash=await fn(new TextEncoder().encode(value));$('textHashResult').value=hash;$('actualHash').value=hash;$('copyTextHashButton').disabled=false;compare();}catch(error){setNotice(error.message||'文本计算失败。',true);}});
                $('copyTextHashButton').addEventListener('click',async()=>{const button=$('copyTextHashButton');button.textContent=await copyText($('textHashResult').value)?'已复制':'复制失败';setTimeout(()=>button.textContent='复制结果',1200);});
                function normalizeHash(value){return String(value||'').trim().replace(/^(?:md5|sha-?1|sha-?256|sha-?512)\s*[:=]\s*/i,'').replace(/[\s-]/g,'').toLowerCase();}
                function compare(){const expected=normalizeHash($('expectedHash').value),actual=normalizeHash($('actualHash').value),box=$('compareResult');box.className='compare-result';if(!expected||!actual){box.textContent='输入两份校验值后自动比较。';return;}const lengths=[32,40,64,128];if(!/^[0-9a-f]+$/.test(expected)||!lengths.includes(expected.length)||!/^[0-9a-f]+$/.test(actual)||!lengths.includes(actual.length)){box.textContent='校验值格式不正确，请粘贴完整的十六进制结果。';box.classList.add('error');return;}if(expected.length!==actual.length){box.textContent='❌ 不一致：两份校验值使用的算法或长度不同。';box.classList.add('mismatch');return;}const match=expected===actual;box.textContent=match?'✅ 完全一致：文件或文本与预期相符。':'❌ 不一致：请确认文件来源，或重新下载后再校验。';box.classList.add(match?'match':'mismatch');}
                ['expectedHash','actualHash'].forEach(id=>$(id).addEventListener('input',compare));
                if(!window.WebAssembly||!window.hashwasm){setNotice('当前浏览器不支持所需的本地计算组件，请升级浏览器后重试。',true);document.querySelectorAll('.tool-panel button').forEach(button=>button.disabled=true);}
            })();
            </script>
<?php include '_footer.php'; ?>
