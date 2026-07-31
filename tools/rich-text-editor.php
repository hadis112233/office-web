<?php
$title = '本地富文本编辑器';
$desc = '快速编辑带格式的文字，支持本机自动保存、打印和导出 HTML。粘贴内容会转为纯文本以保护安全。';
include '_header.php';
?>
            <div class="tool-panel editor-panel">
                <div class="toolbar" role="toolbar" aria-label="文字格式">
                    <button type="button" data-command="bold"><b>B</b></button><button type="button" data-command="italic"><i>I</i></button><button type="button" data-command="underline"><u>U</u></button>
                    <button type="button" data-command="insertUnorderedList">项目符号</button><button type="button" data-command="insertOrderedList">编号</button>
                    <button type="button" data-command="justifyLeft">左对齐</button><button type="button" data-command="justifyCenter">居中</button><button type="button" data-command="justifyRight">右对齐</button>
                    <select id="fontSize" aria-label="字号"><option value="3">正文</option><option value="2">小号</option><option value="4">中号</option><option value="5">大号</option><option value="6">特大</option></select>
                </div>
                <div id="editor" class="editor" contenteditable="true" role="textbox" aria-multiline="true" data-placeholder="在这里输入或粘贴文字…"></div>
                <div class="editor-footer"><span id="count">0 字 · 0 段</span><span id="saveStatus" role="status">内容自动保存在当前浏览器</span></div>
                <div class="btn-row"><button class="btn success" id="print" type="button">打印 / 另存 PDF</button><button class="btn" id="download" type="button">导出 HTML</button><button class="btn secondary" id="clear" type="button">清空</button></div>
            </div>
            <style>
            .editor-panel{padding:0;overflow:hidden}.toolbar{display:flex;flex-wrap:wrap;gap:6px;padding:12px;background:#f8fafc;border-bottom:1px solid #e2e8f0}.toolbar button,.toolbar select{min-height:36px;padding:7px 11px;border:1px solid #cbd5e1;border-radius:7px;background:#fff;color:#334155;cursor:pointer}.toolbar button:hover{border-color:#6366f1;color:#4338ca}.editor{min-height:420px;padding:26px;background:#fff;outline:none;line-height:1.8;overflow-wrap:anywhere}.editor:empty:before{content:attr(data-placeholder);color:#94a3b8}.editor-footer{display:flex;justify-content:space-between;gap:10px;padding:9px 14px;border-top:1px solid #e2e8f0;color:#64748b;font-size:12px}.editor-panel>.btn-row{padding:0 14px 14px}
            </style>
            <script>
            (function(){
                const editor=document.getElementById('editor'),count=document.getElementById('count'),status=document.getElementById('saveStatus'),storageKey='office_rich_text_draft';let timer;
                function safeHtml(){const source=editor.cloneNode(true),allowed=new Set(['B','STRONG','I','EM','U','DIV','P','BR','UL','OL','LI','FONT']);source.querySelectorAll('*').forEach(node=>{if(!allowed.has(node.tagName)){node.replaceWith(...node.childNodes);return;}Array.from(node.attributes).forEach(attribute=>{if(!(node.tagName==='FONT'&&attribute.name==='size'))node.removeAttribute(attribute.name);});});return source.innerHTML;}
                function update(){const text=editor.innerText.replace(/\u00a0/g,' ').trim(),paragraphs=text?text.split(/\n+/).filter(Boolean).length:0;count.textContent=text.replace(/\s/g,'').length+' 字 · '+paragraphs+' 段';clearTimeout(timer);status.textContent='正在保存…';timer=setTimeout(()=>{try{localStorage.setItem(storageKey,safeHtml());status.textContent='已保存到当前浏览器';}catch(e){status.textContent='浏览器存储空间不足，未能自动保存';}},350);}
                document.querySelectorAll('[data-command]').forEach(button=>button.addEventListener('click',()=>{editor.focus();document.execCommand(button.dataset.command,false,null);update();}));document.getElementById('fontSize').addEventListener('change',event=>{editor.focus();document.execCommand('fontSize',false,event.target.value);update();});
                editor.addEventListener('paste',event=>{event.preventDefault();const text=event.clipboardData.getData('text/plain');document.execCommand('insertText',false,text);});editor.addEventListener('drop',event=>{event.preventDefault();const text=event.dataTransfer.getData('text/plain');editor.focus();document.execCommand('insertText',false,text);update();});editor.addEventListener('input',update);
                document.getElementById('clear').addEventListener('click',()=>{if(editor.innerText.trim()&&!confirm('确定清空当前内容吗？'))return;editor.replaceChildren();localStorage.removeItem(storageKey);update();editor.focus();});
                function documentHtml(){return '<!doctype html><html lang="zh-CN"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>办公文档</title><style>body{max-width:800px;margin:40px auto;padding:0 24px;font-family:Arial,"Microsoft YaHei",sans-serif;line-height:1.8;color:#222}@media print{body{margin:0;max-width:none}}</style></head><body>'+safeHtml()+'</body></html>';}
                document.getElementById('download').addEventListener('click',()=>{const blob=new Blob([documentHtml()],{type:'text/html;charset=utf-8'}),url=URL.createObjectURL(blob),a=document.createElement('a');a.href=url;a.download='办公文档.html';a.click();setTimeout(()=>URL.revokeObjectURL(url),1000);});document.getElementById('print').addEventListener('click',()=>{const win=window.open('','_blank');if(!win){alert('浏览器阻止了打印窗口，请允许弹窗后重试。');return;}win.opener=null;win.document.write(documentHtml());win.document.close();win.focus();setTimeout(()=>win.print(),250);});
                try{const saved=localStorage.getItem(storageKey);if(saved){const holder=document.createElement('div');holder.innerHTML=saved;editor.replaceChildren(...holder.childNodes);editor.innerHTML=safeHtml();}}catch(e){}update();
            })();
            </script>
<?php include '_footer.php'; ?>
