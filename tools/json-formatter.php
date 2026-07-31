<?php
$title = 'JSON 格式化';
$desc = '格式化、压缩、校验 JSON 数据，支持键名排序、错误定位和文件导出。内容只在浏览器中处理。';
include '_header.php';
?>
            <div class="json-layout">
                <section class="tool-panel">
                    <div class="panel-heading">
                        <h3>原始 JSON</h3>
                        <label class="file-button" for="jsonFile">选择文件</label>
                        <input id="jsonFile" type="file" accept=".json,application/json" hidden>
                    </div>
                    <label class="sr-only" for="jsonInput">原始 JSON 内容</label>
                    <textarea id="jsonInput" class="json-editor" spellcheck="false" placeholder='粘贴 JSON，例如：&#10;{"name":"办公工具站","tools":38,"online":true}'></textarea>
                    <p class="json-meta" id="inputMeta">最多处理 5 MB，内容不会上传服务器。</p>
                </section>

                <section class="tool-panel">
                    <div class="panel-heading">
                        <h3>处理结果</h3>
                        <span class="json-validity" id="validity">等待处理</span>
                    </div>
                    <label class="sr-only" for="jsonOutput">处理后的 JSON 内容</label>
                    <textarea id="jsonOutput" class="json-editor" readonly spellcheck="false" placeholder="处理结果会显示在这里"></textarea>
                    <p class="json-meta" id="stats">尚无统计信息。</p>
                </section>
            </div>

            <div class="tool-panel action-panel">
                <div class="json-options">
                    <label for="indent">缩进</label>
                    <select id="indent">
                        <option value="2">2 个空格</option>
                        <option value="4">4 个空格</option>
                        <option value="tab">制表符</option>
                    </select>
                    <label class="check-option"><input id="sortKeys" type="checkbox"> 按键名排序</label>
                </div>
                <div class="btn-row">
                    <button class="btn success" id="format" type="button">格式化</button>
                    <button class="btn" id="minify" type="button">压缩</button>
                    <button class="btn" id="validate" type="button">仅校验</button>
                    <button class="btn secondary" id="copy" type="button" disabled>复制结果</button>
                    <button class="btn secondary" id="download" type="button" disabled>下载 JSON</button>
                    <button class="btn warning" id="clear" type="button">清空</button>
                </div>
                <p class="json-status" id="status" role="status" aria-live="polite">粘贴内容或选择 JSON 文件后开始处理，也可按 Ctrl + Enter 快速格式化。</p>
            </div>

            <style>
            .json-layout{display:grid;grid-template-columns:1fr 1fr;gap:18px}.json-layout .tool-panel{margin-bottom:0}.panel-heading{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:10px}.panel-heading h3{margin:0}.file-button{padding:7px 12px;border:1px solid #c7d2fe;border-radius:8px;color:#4338ca;background:#eef2ff;cursor:pointer;font-size:13px}.json-editor{min-height:420px!important;margin:0!important;resize:vertical;font:13px/1.65 Consolas,"SFMono-Regular",Menlo,monospace!important;tab-size:2}.json-meta{min-height:18px;margin:9px 0 0;color:#64748b;font-size:12px}.json-validity{padding:5px 9px;border-radius:999px;color:#64748b;background:#f1f5f9;font-size:12px}.json-validity.valid{color:#047857;background:#d1fae5}.json-validity.invalid{color:#b91c1c;background:#fee2e2}.action-panel{margin-top:18px}.json-options{display:flex;align-items:center;flex-wrap:wrap;gap:10px 14px;margin-bottom:12px}.json-options select{width:auto;min-width:130px}.check-option{display:flex!important;align-items:center;gap:7px;margin:0!important}.json-status{min-height:20px;margin:12px 0 0;color:#475569;font-size:13px;white-space:pre-wrap}.json-status.error{color:#b91c1c}.json-status.success{color:#047857}@media(max-width:800px){.json-layout{grid-template-columns:1fr}.json-editor{min-height:300px!important}}html.theme-dark .file-button{border-color:#4f46e5;color:#c7d2fe;background:#312e81}html.theme-dark .json-validity{color:#cbd5e1;background:#334155}html.theme-dark .json-validity.valid{color:#6ee7b7;background:#064e3b}html.theme-dark .json-validity.invalid{color:#fca5a5;background:#7f1d1d}html.theme-dark .json-status{color:#cbd5e1}html.theme-dark .json-status.error{color:#fca5a5}html.theme-dark .json-status.success{color:#6ee7b7}
            </style>

            <script>
            (function(){
                const $=id=>document.getElementById(id);
                const maxBytes=5*1024*1024,maxNodes=100000,maxDepth=100;
                let inputProcessed=false,hasResult=false;

                function byteSize(text){return new Blob([text]).size;}
                function setStatus(message,type=''){
                    $('status').textContent=message;
                    $('status').className='json-status'+(type?' '+type:'');
                }
                function setValidity(text,type=''){
                    $('validity').textContent=text;
                    $('validity').className='json-validity'+(type?' '+type:'');
                }
                function setResult(text){
                    $('jsonOutput').value=text;
                    hasResult=Boolean(text);
                    $('copy').disabled=!hasResult;
                    $('download').disabled=!hasResult;
                }
                function errorLocation(error,text){
                    const match=String(error.message||'').match(/position\s+(\d+)/i);
                    if(!match)return '';
                    const position=Math.min(Number(match[1]),text.length);
                    const before=text.slice(0,position),lines=before.split('\n');
                    return `（第 ${lines.length} 行，第 ${lines.at(-1).length+1} 列）`;
                }
                function inspect(value){
                    const counts={objects:0,arrays:0,values:0,maxDepth:0};
                    const stack=[{value,depth:0}];
                    while(stack.length){
                        const current=stack.pop();
                        counts.values++;
                        counts.maxDepth=Math.max(counts.maxDepth,current.depth);
                        if(counts.values>maxNodes)throw new Error(`节点数量超过 ${maxNodes.toLocaleString()}，请拆分后处理`);
                        if(current.depth>maxDepth)throw new Error(`嵌套层级超过 ${maxDepth}，请简化后处理`);
                        if(Array.isArray(current.value)){
                            counts.arrays++;
                            current.value.forEach(item=>stack.push({value:item,depth:current.depth+1}));
                        }else if(current.value!==null&&typeof current.value==='object'){
                            counts.objects++;
                            Object.values(current.value).forEach(item=>stack.push({value:item,depth:current.depth+1}));
                        }
                    }
                    return counts;
                }
                function sortValue(value,depth=0,state={count:0}){
                    state.count++;
                    if(state.count>maxNodes||depth>maxDepth)throw new Error('数据过大或嵌套过深，无法安全排序');
                    if(Array.isArray(value))return value.map(item=>sortValue(item,depth+1,state));
                    if(value!==null&&typeof value==='object'){
                        return Object.keys(value).sort((a,b)=>a.localeCompare(b,'zh-CN')).reduce((result,key)=>{
                            result[key]=sortValue(value[key],depth+1,state);
                            return result;
                        },Object.create(null));
                    }
                    return value;
                }
                function parseInput(){
                    const text=$('jsonInput').value;
                    if(!text.trim())throw new Error('请先粘贴 JSON 内容或选择文件');
                    const size=byteSize(text);
                    if(size>maxBytes)throw new Error('内容超过 5 MB，请拆分后再处理');
                    try{
                        const value=JSON.parse(text),counts=inspect(value);
                        inputProcessed=true;
                        $('inputMeta').textContent=`输入 ${(size/1024).toFixed(1)} KB`;
                        $('stats').textContent=`${counts.values.toLocaleString()} 个节点 · ${counts.objects.toLocaleString()} 个对象 · ${counts.arrays.toLocaleString()} 个数组 · 最大 ${counts.maxDepth} 层`;
                        setValidity('JSON 有效','valid');
                        return value;
                    }catch(error){
                        inputProcessed=false;
                        setResult('');
                        $('stats').textContent='无法统计无效 JSON。';
                        setValidity('JSON 无效','invalid');
                        if(error instanceof SyntaxError)throw new Error('JSON 语法错误'+errorLocation(error,text)+'：'+error.message);
                        throw error;
                    }
                }
                function process(mode){
                    try{
                        let value=parseInput();
                        if($('sortKeys').checked)value=sortValue(value);
                        if(mode==='validate'){
                            setStatus('校验通过：这是有效的 JSON。','success');
                            return;
                        }
                        const indent=mode==='minify'?0:($('indent').value==='tab'?'\t':Number($('indent').value));
                        const output=JSON.stringify(value,null,indent);
                        setResult(output);
                        setStatus(`${mode==='minify'?'压缩':'格式化'}完成：${(byteSize(output)/1024).toFixed(1)} KB。`,'success');
                    }catch(error){
                        setStatus(error.message,'error');
                    }
                }
                async function copyResult(){
                    if(!hasResult)return;
                    try{
                        await navigator.clipboard.writeText($('jsonOutput').value);
                        setStatus('结果已复制到剪贴板。','success');
                    }catch(error){
                        $('jsonOutput').focus();$('jsonOutput').select();
                        setStatus('浏览器未允许自动复制，已为你选中结果，请按 Ctrl + C。','error');
                    }
                }
                function downloadResult(){
                    if(!hasResult)return;
                    const blob=new Blob([$('jsonOutput').value],{type:'application/json;charset=utf-8'});
                    const url=URL.createObjectURL(blob),link=document.createElement('a');
                    link.href=url;link.download='formatted.json';link.click();
                    setTimeout(()=>URL.revokeObjectURL(url),1000);
                    setStatus('JSON 文件已下载。','success');
                }
                function clearAll(){
                    $('jsonFile').value='';$('jsonInput').value='';inputProcessed=false;
                    setResult('');$('inputMeta').textContent='最多处理 5 MB，内容不会上传服务器。';
                    $('stats').textContent='尚无统计信息。';setValidity('等待处理');
                    setStatus('已清空。粘贴内容或选择 JSON 文件后可重新处理。');
                    $('jsonInput').focus();
                }

                $('format').addEventListener('click',()=>process('format'));
                $('minify').addEventListener('click',()=>process('minify'));
                $('validate').addEventListener('click',()=>process('validate'));
                $('copy').addEventListener('click',copyResult);
                $('download').addEventListener('click',downloadResult);
                $('clear').addEventListener('click',clearAll);
                $('jsonInput').addEventListener('input',()=>{if(inputProcessed||hasResult){inputProcessed=false;setResult('');setValidity('内容已修改');$('stats').textContent='请重新处理以更新统计。';}});
                $('jsonInput').addEventListener('keydown',event=>{if((event.ctrlKey||event.metaKey)&&event.key==='Enter'){event.preventDefault();process('format');}});
                $('jsonFile').addEventListener('change',async event=>{
                    const file=event.target.files[0];
                    if(!file)return;
                    if(file.size>maxBytes){setStatus('文件超过 5 MB，请选择较小的 JSON 文件。','error');event.target.value='';return;}
                    try{$('jsonInput').value=await file.text();process('format');}
                    catch(error){setStatus('文件读取失败：'+error.message,'error');}
                });
            })();
            </script>
<?php include '_footer.php'; ?>
