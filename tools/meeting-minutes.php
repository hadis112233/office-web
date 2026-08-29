<?php
$title = '会议纪要';
$desc = '结构化记录议题、决策和行动项，生成 Markdown 并仅在本地保存草稿。';
include '_header.php';
?>
            <div class="minutes-grid">
                <section class="tool-panel">
                    <div class="minutes-fields">
                        <label>会议主题<input id="meetingTitle" type="text" maxlength="120" placeholder="例如：产品周例会"></label>
                        <label>日期与时间<input id="meetingTime" type="datetime-local"></label>
                        <label>主持人（可选）<input id="facilitator" type="text" maxlength="80"></label>
                        <label>参会人（逗号分隔）<input id="attendees" type="text" maxlength="500" placeholder="张三，李四，王五"></label>
                    </div>
                    <label for="agenda">议题 / 讨论记录</label><textarea id="agenda" placeholder="每行一个议题，或直接记录讨论重点。"></textarea>
                    <label for="decisions">已达成决策</label><textarea id="decisions" placeholder="每行一条明确的决策。"></textarea>
                    <div class="action-head"><label>行动项</label><button class="btn small" id="addAction" type="button">+ 添加行动项</button></div>
                    <div id="actionItems" class="action-items"></div>
                    <label for="nextMeeting">下次会议（可选）</label><input id="nextMeeting" type="text" maxlength="160" placeholder="例如：9 月 5 日 10:00，线上">
                    <div class="btn-row"><button class="btn success" id="generate" type="button">生成纪要</button><button class="btn" id="copy" type="button" disabled>复制 Markdown</button><button class="btn" id="download" type="button" disabled>下载 .md</button><button class="btn secondary" id="save" type="button">保存草稿</button><button class="btn secondary" id="clear" type="button">清空</button></div>
                    <p class="minutes-status" id="status" role="status" aria-live="polite">草稿仅保存在当前浏览器，不会上传服务器。</p>
                </section>
                <section class="tool-panel">
                    <h3>纪要预览</h3><pre class="minutes-preview" id="preview">填写内容后点击“生成纪要”。</pre>
                </section>
            </div>
            <style>
            .minutes-grid{display:grid;grid-template-columns:minmax(320px,1.1fr) minmax(300px,.9fr);gap:18px}.minutes-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-bottom:14px}.minutes-fields label{display:flex;flex-direction:column}.minutes-grid>section>label,.action-head>label{display:block;margin:13px 0 6px}.minutes-grid input,.minutes-grid textarea{width:100%}.minutes-grid textarea{min-height:100px}.action-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:13px}.action-head label{margin:0}.action-items{display:grid;gap:8px}.action-row{display:grid;grid-template-columns:minmax(0,1fr) 110px 130px auto;gap:7px;align-items:center}.action-row input{margin:0}.action-row button{min-width:38px}.minutes-preview{min-height:560px;margin:0;overflow:auto;padding:15px;white-space:pre-wrap;overflow-wrap:anywhere;border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc;color:#334155;font:13px/1.7 Consolas,Monaco,monospace}.minutes-status{min-height:20px;color:#64748b;font-size:12px}.minutes-status.success{color:#047857}.minutes-status.error{color:#b91c1c}html.theme-dark .minutes-preview{border-color:#475569;background:#0f172a;color:#dbeafe}@media(max-width:860px){.minutes-grid{grid-template-columns:1fr}.minutes-preview{min-height:300px}}@media(max-width:550px){.minutes-fields{grid-template-columns:1fr}.action-row{grid-template-columns:1fr 1fr 38px}.action-row .action-date{grid-column:1/-2}}
            </style>
            <script>
            (()=>{
                'use strict';
                const STORAGE_KEY='office_meeting_minutes_draft_v1',MAX_ACTIONS=100,$=id=>document.getElementById(id),fieldIds=['meetingTitle','meetingTime','facilitator','attendees','agenda','decisions','nextMeeting'];
                let markdown='';
                function status(message,type=''){const node=$('status');node.textContent=message;node.className='minutes-status '+type;}
                function text(value){return String(value||'').trim();}
                function lines(value){return text(value).split(/\r?\n/).map(item=>item.replace(/^[-•\s]+/,'').trim()).filter(Boolean);}
                function escapeCell(value){return text(value).replace(/[|\r\n]/g,' ').replace(/\\/g,'\\\\');}
                function actionRow(item={}){const row=document.createElement('div');row.className='action-row';row.innerHTML='<input class="action-task" type="text" maxlength="240" placeholder="待完成的事项"><input class="action-owner" type="text" maxlength="80" placeholder="负责人"><input class="action-date" type="date"><button class="btn secondary small" type="button" aria-label="删除此行动项">×</button>';row.querySelector('.action-task').value=item.task||'';row.querySelector('.action-owner').value=item.owner||'';row.querySelector('.action-date').value=item.due||'';row.querySelector('button').addEventListener('click',()=>{row.remove();if(!$('actionItems').children.length)actionRow();autosave();});$('actionItems').appendChild(row);}
                function actions(){return [...$('actionItems').querySelectorAll('.action-row')].map(row=>({task:text(row.querySelector('.action-task').value),owner:text(row.querySelector('.action-owner').value),due:text(row.querySelector('.action-date').value)})).filter(item=>item.task||item.owner||item.due);}
                function draft(){return {fields:Object.fromEntries(fieldIds.map(id=>[id,$(id).value])),actions:actions()};}
                function autosave(){try{localStorage.setItem(STORAGE_KEY,JSON.stringify(draft()));}catch{}}
                function restore(){try{const saved=JSON.parse(localStorage.getItem(STORAGE_KEY)||'null');if(saved){Object.entries(saved.fields||{}).forEach(([id,value])=>{if($(id))$(id).value=String(value||'');});$('actionItems').replaceChildren();(saved.actions||[]).slice(0,MAX_ACTIONS).forEach(actionRow);status('已恢复本机草稿。','success');}}catch{}if(!$('actionItems').children.length)actionRow();}
                function build(){
                    const title=text($('meetingTitle').value)||'未命名会议',time=text($('meetingTime').value),attendees=text($('attendees').value),facilitator=text($('facilitator').value),agenda=lines($('agenda').value),decisions=lines($('decisions').value),items=actions(),next=text($('nextMeeting').value);
                    const output=['# '+title,'','- 时间：'+(time?time.replace('T',' '):'未填写'),'- 主持人：'+(facilitator||'未填写'),'- 参会人：'+(attendees||'未填写'),''];
                    output.push('## 议题与讨论',...(agenda.length?agenda.map(item=>'- '+item):['- 暂无记录']),'','## 决策',...(decisions.length?decisions.map(item=>'- '+item):['- 暂无记录']),'','## 行动项');
                    if(items.length){output.push('| 行动项 | 负责人 | 截止日期 |','| --- | --- | --- |',...items.map(item=>'| '+escapeCell(item.task||'待确认')+' | '+escapeCell(item.owner||'待确认')+' | '+escapeCell(item.due||'待确认')+' |'));}else output.push('- 暂无行动项');
                    output.push('','## 后续安排','- 下次会议：'+(next||'待确认'),'','---','> 本纪要由办公工具站在本地生成。');
                    markdown=output.join('\n');$('preview').textContent=markdown;$('copy').disabled=false;$('download').disabled=false;autosave();status('纪要已生成，可复制或下载 Markdown。','success');
                }
                async function copy(){if(!markdown)return;try{await navigator.clipboard.writeText(markdown);status('Markdown 已复制。','success');}catch{$('preview').focus();status('请从右侧预览中手动复制。','error');}}
                function download(){if(!markdown)return;const safe=(text($('meetingTitle').value)||'会议纪要').replace(/[\\/:*?"<>|]/g,'-').slice(0,80),url=URL.createObjectURL(new Blob(['\ufeff'+markdown],{type:'text/markdown;charset=utf-8'})),link=document.createElement('a');link.href=url;link.download=safe+'-'+new Date().toISOString().slice(0,10)+'.md';link.click();setTimeout(()=>URL.revokeObjectURL(url),1000);}
                function clear(){fieldIds.forEach(id=>$(id).value='');$('meetingTime').value=new Date(Date.now()-new Date().getTimezoneOffset()*60000).toISOString().slice(0,16);$('actionItems').replaceChildren();actionRow();markdown='';$('preview').textContent='填写内容后点击“生成纪要”。';$('copy').disabled=true;$('download').disabled=true;try{localStorage.removeItem(STORAGE_KEY);}catch{}status('已清空本机草稿。','success');}
                $('addAction').addEventListener('click',()=>{if($('actionItems').children.length>=MAX_ACTIONS){status('行动项最多 '+MAX_ACTIONS+' 条。','error');return;}actionRow();});$('generate').addEventListener('click',build);$('copy').addEventListener('click',copy);$('download').addEventListener('click',download);$('save').addEventListener('click',()=>{autosave();status('草稿已保存到当前浏览器。','success');});$('clear').addEventListener('click',clear);fieldIds.forEach(id=>$(id).addEventListener('input',autosave));$('actionItems').addEventListener('input',autosave);restore();if(!$('meetingTime').value)$('meetingTime').value=new Date(Date.now()-new Date().getTimezoneOffset()*60000).toISOString().slice(0,16);
            })();
            </script>
<?php include '_footer.php'; ?>
