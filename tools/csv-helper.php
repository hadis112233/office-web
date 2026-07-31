<?php
$title = 'CSV 表格助手';
$desc = '上传或粘贴 CSV/TSV 表格，进行预览、行列转置、整行去重并重新导出。文件只在浏览器中处理。';
include '_header.php';
?>
            <div class="tool-panel">
                <label for="csvFile">选择 CSV 或 TSV 文件（最大 5 MB）</label><input id="csvFile" type="file" accept=".csv,.tsv,text/csv,text/tab-separated-values">
                <label for="csvText">或粘贴表格内容</label><textarea id="csvText" placeholder="姓名,部门,电话&#10;张三,销售部,13800000000"></textarea>
                <div class="options"><label for="delimiter">分隔符</label><select id="delimiter"><option value="auto">自动识别</option><option value=",">逗号</option><option value="tab">制表符</option><option value=";">分号</option></select><label><input id="hasHeader" type="checkbox" checked> 第一行是表头</label></div>
                <div class="btn-row"><button class="btn success" id="parse" type="button">解析预览</button><button class="btn" id="transpose" type="button">行列转置</button><button class="btn" id="dedupe" type="button">整行去重</button><button class="btn secondary" id="clear" type="button">清空</button></div>
                <p class="helper" id="status" role="status">尚未解析表格。</p>
            </div>
            <div class="tool-panel" id="previewPanel" hidden>
                <div class="preview-head"><h3>表格预览（最多显示前 100 行）</h3><div><button class="btn small success" id="downloadCsv" type="button">下载 CSV</button> <button class="btn small" id="downloadTsv" type="button">下载 TSV</button></div></div>
                <div class="table-scroll"><table id="preview"></table></div>
            </div>
            <style>
            #csvText{min-height:180px;margin-top:8px}.options{display:flex;align-items:center;flex-wrap:wrap;gap:10px 16px;margin:14px 0}.options select{min-width:150px}.options label{display:flex;align-items:center;gap:7px;margin:0}.helper{color:#64748b;font-size:12px}.preview-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px}.preview-head h3{margin:0}.table-scroll{overflow:auto;max-height:520px;border:1px solid #e2e8f0;border-radius:10px}table{width:100%;border-collapse:collapse;font-size:13px}th,td{padding:8px 10px;border:1px solid #e2e8f0;text-align:left;white-space:pre-wrap;min-width:90px}th{position:sticky;top:0;background:#eef2ff;color:#3730a3;z-index:1}@media(max-width:650px){.preview-head{align-items:flex-start;flex-direction:column}}
            </style>
            <script>
            (function(){
                const $=id=>document.getElementById(id);let rows=[];
                function detect(text){const first=(text.split(/\r?\n/,1)[0]||'');const candidates=[',','\t',';'];return candidates.sort((a,b)=>first.split(b).length-first.split(a).length)[0];}
                function parseCsv(text,separator){const data=[];let row=[],cell='',quoted=false;for(let i=0;i<text.length;i++){const char=text[i];if(quoted){if(char==='"'&&text[i+1]==='"'){cell+='"';i++;}else if(char==='"')quoted=false;else cell+=char;}else if(char==='"')quoted=true;else if(char===separator){row.push(cell);cell='';}else if(char==='\n'){row.push(cell.replace(/\r$/,''));data.push(row);row=[];cell='';}else cell+=char;}row.push(cell.replace(/\r$/,''));if(row.some(value=>value!==''))data.push(row);return data;}
                function separator(){const selected=$('delimiter').value;return selected==='auto'?detect($('csvText').value):selected==='tab'?'\t':selected;}
                function normalise(){const width=rows.reduce((max,row)=>Math.max(max,row.length),0);rows=rows.map(row=>Array.from({length:width},(_,i)=>row[i]??''));}
                function render(message){normalise();const table=$('preview');table.replaceChildren();rows.slice(0,100).forEach((row,index)=>{const tr=document.createElement('tr');row.forEach(value=>{const cell=document.createElement(index===0&&$('hasHeader').checked?'th':'td');cell.textContent=value;tr.appendChild(cell);});table.appendChild(tr);});$('previewPanel').hidden=!rows.length;$('status').textContent=message||('已解析 '+rows.length+' 行、'+(rows[0]?.length||0)+' 列。');}
                function parse(){const text=$('csvText').value;if(!text.trim()){rows=[];render('请先选择文件或粘贴表格内容。');return false;}if(new Blob([text]).size>5*1024*1024){rows=[];render('内容超过 5 MB，请拆分后再处理。');return false;}rows=parseCsv(text,separator());render();return true;}
                function quote(value,sep){const text=String(value??'');return /["\r\n]/.test(text)||text.includes(sep)?'"'+text.replace(/"/g,'""')+'"':text;}
                function exportFile(sep,name,type){if(!rows.length&&!parse())return;const content=rows.map(row=>row.map(value=>quote(value,sep)).join(sep)).join('\r\n');const blob=new Blob(['\ufeff'+content],{type:type+';charset=utf-8'}),url=URL.createObjectURL(blob),a=document.createElement('a');a.href=url;a.download=name;a.click();setTimeout(()=>URL.revokeObjectURL(url),1000);}
                $('csvFile').addEventListener('change',async event=>{const file=event.target.files[0];if(!file)return;if(file.size>5*1024*1024){$('status').textContent='文件超过 5 MB，请缩小后再试。';event.target.value='';return;}$('csvText').value=await file.text();parse();});
                $('parse').addEventListener('click',parse);$('transpose').addEventListener('click',()=>{if(!rows.length&&!parse())return;normalise();rows=rows[0].map((_,column)=>rows.map(row=>row[column]));render('行列转置完成：'+rows.length+' 行、'+(rows[0]?.length||0)+' 列。');});$('dedupe').addEventListener('click',()=>{if(!rows.length&&!parse())return;const before=rows.length,start=$('hasHeader').checked?rows.slice(0,1):[],body=$('hasHeader').checked?rows.slice(1):rows,seen=new Set();rows=start.concat(body.filter(row=>{const key=JSON.stringify(row);if(seen.has(key))return false;seen.add(key);return true;}));render('已删除 '+(before-rows.length)+' 行重复数据。');});
                $('clear').addEventListener('click',()=>{$('csvFile').value='';$('csvText').value='';rows=[];$('preview').replaceChildren();$('previewPanel').hidden=true;$('status').textContent='尚未解析表格。';});$('downloadCsv').addEventListener('click',()=>exportFile(',','table.csv','text/csv'));$('downloadTsv').addEventListener('click',()=>exportFile('\t','table.tsv','text/tab-separated-values'));
            })();
            </script>
<?php include '_footer.php'; ?>
