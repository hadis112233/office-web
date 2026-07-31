<?php
$title = 'CSV / JSON 表格助手';
$desc = '预览、清洗并双向转换 CSV、TSV 与 JSON 表格，导出时可降低 Excel 公式注入风险。文件只在浏览器中处理。';
include '_header.php';
?>
            <div class="tool-panel">
                <div class="source-row">
                    <div>
                        <label for="dataFile">选择 CSV、TSV 或 JSON 文件（最大 5 MB）</label>
                        <input id="dataFile" type="file" accept=".csv,.tsv,.json,text/csv,text/tab-separated-values,application/json">
                    </div>
                    <div>
                        <label for="inputMode">输入格式</label>
                        <select id="inputMode">
                            <option value="auto">自动识别</option>
                            <option value="csv">CSV（逗号）</option>
                            <option value="tsv">TSV（制表符）</option>
                            <option value="semicolon">CSV（分号）</option>
                            <option value="json">JSON</option>
                        </select>
                    </div>
                </div>
                <label for="dataText">或粘贴表格内容</label>
                <textarea id="dataText" placeholder='姓名,部门,电话&#10;张三,销售部,13800000000&#10;&#10;也支持 JSON：&#10;[{"姓名":"张三","部门":"销售部"}]'></textarea>
                <div class="options">
                    <label><input id="hasHeader" type="checkbox" checked> 第一行是表头</label>
                    <label title="为危险开头的单元格添加文本前缀；不同表格软件行为可能不同"><input id="protectFormula" type="checkbox" checked> 降低 Excel 公式注入风险</label>
                </div>
                <div class="btn-row">
                    <button class="btn success" id="parse" type="button">解析预览</button>
                    <button class="btn" id="transpose" type="button">行列转置</button>
                    <button class="btn" id="dedupe" type="button">整行去重</button>
                    <button class="btn secondary" id="clear" type="button">清空</button>
                </div>
                <p class="helper" id="status" role="status" aria-live="polite">尚未解析表格。最多 20,000 行、200 列、250,000 个单元格。</p>
            </div>
            <div class="tool-panel" id="previewPanel" hidden>
                <div class="preview-head">
                    <h3>表格预览（最多显示前 100 行）</h3>
                    <div class="export-actions">
                        <button class="btn small success" id="downloadCsv" type="button">下载 CSV</button>
                        <button class="btn small" id="downloadTsv" type="button">下载 TSV</button>
                        <button class="btn small" id="downloadJson" type="button">下载 JSON</button>
                    </div>
                </div>
                <div class="table-scroll"><table id="preview"></table></div>
            </div>
            <style>
            #dataText{min-height:210px;margin-top:8px}.source-row{display:grid;grid-template-columns:minmax(0,1fr) minmax(180px,260px);gap:16px;align-items:end;margin-bottom:12px}.source-row input[type=file]{margin-top:7px}.source-row select{width:100%}.options{display:flex;align-items:center;flex-wrap:wrap;gap:10px 20px;margin:14px 0}.options label{display:flex;align-items:center;gap:7px;margin:0}.helper{min-height:18px;color:#64748b;font-size:12px}.helper.error{color:#b91c1c}.helper.success{color:#047857}.preview-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px}.preview-head h3{margin:0}.export-actions{display:flex;flex-wrap:wrap;gap:7px}.table-scroll{overflow:auto;max-height:520px;border:1px solid #e2e8f0;border-radius:10px}table{width:100%;border-collapse:collapse;font-size:13px}th,td{padding:8px 10px;border:1px solid #e2e8f0;text-align:left;white-space:pre-wrap;min-width:90px;max-width:360px;overflow-wrap:anywhere}th{position:sticky;top:0;background:#eef2ff;color:#3730a3;z-index:1}@media(max-width:700px){.source-row{grid-template-columns:1fr}.preview-head{align-items:flex-start;flex-direction:column}}html.theme-dark .helper.error{color:#fca5a5}html.theme-dark .helper.success{color:#6ee7b7}html.theme-dark th{color:#c7d2fe;background:#312e81}html.theme-dark th,html.theme-dark td,html.theme-dark .table-scroll{border-color:#475569}
            </style>
            <script>
            (function(){
                const $=id=>document.getElementById(id);
                const maxBytes=5*1024*1024,maxRows=20000,maxColumns=200,maxCells=250000;
                let rows=[];

                function setStatus(message,type=''){
                    $('status').textContent=message;
                    $('status').className='helper'+(type?' '+type:'');
                }
                function displayValue(value){
                    if(value===null)return 'null';
                    if(value!==null&&typeof value==='object')return JSON.stringify(value);
                    return String(value??'');
                }
                function detectSeparator(text){
                    const first=(text.split(/\r?\n/,1)[0]||'');
                    return [',','\t',';'].sort((a,b)=>first.split(b).length-first.split(a).length)[0];
                }
                function parseCsv(text,separator){
                    const data=[];let row=[],cell='',quoted=false;
                    for(let i=0;i<text.length;i++){
                        const char=text[i];
                        if(quoted){
                            if(char==='"'&&text[i+1]==='"'){cell+='"';i++;}
                            else if(char==='"')quoted=false;
                            else cell+=char;
                        }else if(char==='"'&&cell==='')quoted=true;
                        else if(char===separator){row.push(cell);cell='';}
                        else if(char==='\n'){row.push(cell.replace(/\r$/,''));data.push(row);row=[];cell='';}
                        else cell+=char;
                    }
                    if(quoted)throw new Error('CSV 中存在未闭合的双引号，请检查原始内容');
                    row.push(cell.replace(/\r$/,''));
                    if(row.some(value=>value!==''))data.push(row);
                    return data;
                }
                function jsonCell(value){
                    if(value===null||['string','number','boolean'].includes(typeof value))return value;
                    return JSON.stringify(value);
                }
                function parseJson(text){
                    const value=JSON.parse(text);
                    const list=Array.isArray(value)?value:[value];
                    if(!list.length)throw new Error('JSON 数组为空，没有可转换的数据');
                    if(list.every(item=>Array.isArray(item))){
                        return list.map(row=>row.map(jsonCell));
                    }
                    if(list.every(item=>item!==null&&typeof item==='object'&&!Array.isArray(item))){
                        const headers=[...new Set(list.flatMap(item=>Object.keys(item)))];
                        if(!headers.length)throw new Error('JSON 对象没有可转换的字段');
                        $('hasHeader').checked=true;
                        return [headers,...list.map(item=>headers.map(header=>jsonCell(Object.hasOwn(item,header)?item[header]:'')))];
                    }
                    throw new Error('JSON 顶层应为对象、对象数组或二维数组，不能混合多种结构');
                }
                function enforceLimits(data){
                    const width=data.reduce((max,row)=>Math.max(max,row.length),0);
                    if(data.length>maxRows)throw new Error(`行数超过 ${maxRows.toLocaleString()}，请拆分后处理`);
                    if(width>maxColumns)throw new Error(`列数超过 ${maxColumns}，请删减字段后处理`);
                    if(data.length*width>maxCells)throw new Error(`单元格超过 ${maxCells.toLocaleString()}，请拆分后处理`);
                    return width;
                }
                function normalise(){
                    const width=enforceLimits(rows);
                    rows=rows.map(row=>Array.from({length:width},(_,index)=>index in row?row[index]:''));
                    return width;
                }
                function render(message){
                    const width=normalise(),table=$('preview');
                    table.replaceChildren();
                    rows.slice(0,100).forEach((row,index)=>{
                        const tr=document.createElement('tr');
                        row.forEach(value=>{
                            const cell=document.createElement(index===0&&$('hasHeader').checked?'th':'td');
                            cell.textContent=displayValue(value);
                            tr.appendChild(cell);
                        });
                        table.appendChild(tr);
                    });
                    $('previewPanel').hidden=!rows.length;
                    const suffix=rows.length>100?'，当前仅预览前 100 行':'';
                    setStatus(message||`已解析 ${rows.length.toLocaleString()} 行、${width.toLocaleString()} 列${suffix}。`,'success');
                }
                function inputKind(text){
                    const selected=$('inputMode').value;
                    if(selected!=='auto')return selected;
                    const trimmed=text.trimStart();
                    if(trimmed.startsWith('[')||trimmed.startsWith('{'))return 'json';
                    const separator=detectSeparator(text);
                    return separator==='\t'?'tsv':separator===';'?'semicolon':'csv';
                }
                function parse(){
                    const text=$('dataText').value;
                    try{
                        if(!text.trim())throw new Error('请先选择文件或粘贴 CSV、TSV 或 JSON 内容');
                        if(new Blob([text]).size>maxBytes)throw new Error('内容超过 5 MB，请拆分后再处理');
                        const kind=inputKind(text);
                        rows=kind==='json'?parseJson(text):parseCsv(text,kind==='tsv'?'\t':kind==='semicolon'?';':',');
                        if(!rows.length)throw new Error('没有解析到有效表格数据');
                        render(`已按 ${kind==='json'?'JSON':kind.toUpperCase()} 解析 ${rows.length.toLocaleString()} 行、${enforceLimits(rows).toLocaleString()} 列。`);
                        return true;
                    }catch(error){
                        rows=[];$('preview').replaceChildren();$('previewPanel').hidden=true;
                        setStatus(error instanceof SyntaxError?'JSON 语法错误：'+error.message:error.message,'error');
                        return false;
                    }
                }
                function spreadsheetSafe(value){
                    const text=displayValue(value);
                    return $('protectFormula').checked&&/^[\s\u0000]*[=+\-@\uFF1D\uFF0B\uFF0D\uFF20]/.test(text)?"'"+text:text;
                }
                function quote(value,separator){
                    const text=spreadsheetSafe(value);
                    return /["\r\n]/.test(text)||text.includes(separator)?'"'+text.replace(/"/g,'""')+'"':text;
                }
                function download(content,name,type,bom=false){
                    const blob=new Blob([bom?'\ufeff':'',content],{type:type+';charset=utf-8'});
                    const url=URL.createObjectURL(blob),link=document.createElement('a');
                    link.href=url;link.download=name;link.click();
                    setTimeout(()=>URL.revokeObjectURL(url),1000);
                }
                function exportTable(separator,name,type){
                    if(!rows.length&&!parse())return;
                    const content=rows.map(row=>row.map(value=>quote(value,separator)).join(separator)).join('\r\n');
                    download(content,name,type,true);
                    setStatus(`${name} 已下载${$('protectFormula').checked?'，已启用公式注入风险防护':''}。`,'success');
                }
                function uniqueHeaders(header){
                    const used=new Map();
                    return header.map((value,index)=>{
                        const base=displayValue(value).trim()||`column_${index+1}`;
                        const count=(used.get(base)||0)+1;used.set(base,count);
                        return count===1?base:`${base}_${count}`;
                    });
                }
                function exportJson(){
                    if(!rows.length&&!parse())return;
                    let data;
                    if($('hasHeader').checked){
                        const headers=uniqueHeaders(rows[0]);
                        data=rows.slice(1).map(row=>headers.reduce((item,header,index)=>{
                            item[header]=index in row?row[index]:'';
                            return item;
                        },Object.create(null)));
                    }else data=rows;
                    download(JSON.stringify(data,null,2),'table.json','application/json');
                    setStatus('table.json 已下载。','success');
                }

                $('dataFile').addEventListener('change',async event=>{
                    const file=event.target.files[0];
                    if(!file)return;
                    if(file.size>maxBytes){setStatus('文件超过 5 MB，请缩小后再试。','error');event.target.value='';return;}
                    const extension=file.name.split('.').pop().toLowerCase();
                    $('inputMode').value=extension==='json'?'json':extension==='tsv'?'tsv':'auto';
                    try{$('dataText').value=await file.text();parse();}
                    catch(error){setStatus('文件读取失败：'+error.message,'error');}
                });
                $('parse').addEventListener('click',parse);
                $('transpose').addEventListener('click',()=>{
                    if(!rows.length&&!parse())return;
                    try{normalise();rows=rows[0].map((_,column)=>rows.map(row=>row[column]));render(`行列转置完成：${rows.length.toLocaleString()} 行、${rows[0].length.toLocaleString()} 列。`);}
                    catch(error){setStatus(error.message,'error');}
                });
                $('dedupe').addEventListener('click',()=>{
                    if(!rows.length&&!parse())return;
                    const before=rows.length,start=$('hasHeader').checked?rows.slice(0,1):[],body=$('hasHeader').checked?rows.slice(1):rows,seen=new Set();
                    rows=start.concat(body.filter(row=>{const key=JSON.stringify(row);if(seen.has(key))return false;seen.add(key);return true;}));
                    render(`已删除 ${(before-rows.length).toLocaleString()} 行重复数据。`);
                });
                $('clear').addEventListener('click',()=>{
                    $('dataFile').value='';$('dataText').value='';$('inputMode').value='auto';rows=[];
                    $('preview').replaceChildren();$('previewPanel').hidden=true;
                    setStatus('已清空。可重新选择文件或粘贴内容。');
                    $('dataText').focus();
                });
                $('downloadCsv').addEventListener('click',()=>exportTable(',','table.csv','text/csv'));
                $('downloadTsv').addEventListener('click',()=>exportTable('\t','table.tsv','text/tab-separated-values'));
                $('downloadJson').addEventListener('click',exportJson);
                $('hasHeader').addEventListener('change',()=>{if(rows.length)render();});
            })();
            </script>
<?php include '_footer.php'; ?>
