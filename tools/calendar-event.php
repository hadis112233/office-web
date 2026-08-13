<?php
$title = '日历事件生成器';
$desc = '创建可导入 Outlook、Apple 日历等应用的 ICS 日程文件，支持全天、重复和提前提醒。文件只在浏览器中生成。';
include '_header.php';
?>
            <div class="tool-panel">
                <form id="eventForm" novalidate>
                    <div class="form-grid">
                        <div class="wide">
                            <label for="eventTitle">事件标题 <span aria-hidden="true">*</span></label>
                            <input id="eventTitle" maxlength="200" required placeholder="例如：项目周会" autocomplete="off">
                        </div>
                        <label class="check-row wide"><input type="checkbox" id="allDay"> 全天事件</label>
                        <div class="timed-field"><label for="startTime">开始时间</label><input type="datetime-local" id="startTime"></div>
                        <div class="timed-field"><label for="endTime">结束时间</label><input type="datetime-local" id="endTime"></div>
                        <div class="all-day-field" hidden><label for="startDate">开始日期</label><input type="date" id="startDate"></div>
                        <div class="all-day-field" hidden><label for="endDate">结束日期（包含当天）</label><input type="date" id="endDate"></div>
                        <div><label for="location">地点</label><input id="location" maxlength="300" placeholder="例如：三楼会议室"></div>
                        <div><label for="reminder">提前提醒</label><select id="reminder"><option value="">不提醒</option><option value="0">事件开始时</option><option value="5">5 分钟</option><option value="10">10 分钟</option><option value="15" selected>15 分钟</option><option value="30">30 分钟</option><option value="60">1 小时</option><option value="1440">1 天</option></select></div>
                        <div><label for="repeat">重复</label><select id="repeat"><option value="">不重复</option><option value="DAILY">每天</option><option value="WEEKLY">每周</option><option value="MONTHLY">每月</option></select></div>
                        <div id="repeatCountWrap" hidden><label for="repeatCount">重复次数（含首次）</label><input type="number" id="repeatCount" min="2" max="365" value="4" inputmode="numeric"></div>
                        <div class="wide"><label for="description">备注</label><textarea id="description" maxlength="5000" rows="4" placeholder="填写议程、联系人或其他说明"></textarea></div>
                    </div>
                    <div id="formError" class="error-message" role="alert" hidden></div>
                    <div class="actions"><button class="primary-btn" type="submit">生成日程文件</button><button class="secondary-btn" type="button" id="resetBtn">清空重填</button></div>
                </form>
            </div>

            <div class="tool-panel result-panel" id="resultPanel" hidden>
                <div class="result-head"><div><h3>日程文件已生成</h3><p id="resultSummary" class="helper"></p></div><span class="ready-badge">可下载</span></div>
                <label for="icsPreview">ICS 内容预览</label>
                <textarea id="icsPreview" class="preview" rows="13" readonly spellcheck="false"></textarea>
                <div id="copyStatus" class="helper" role="status" aria-live="polite"></div>
                <div class="actions"><button class="primary-btn" type="button" id="downloadBtn">下载 .ics 文件</button><button class="secondary-btn" type="button" id="copyBtn">复制内容</button></div>
                <p class="privacy-note">🔒 文件只在浏览器中生成，不会上传事件标题、地点或备注。</p>
            </div>

            <style>
            .form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.form-grid .wide{grid-column:1/-1}.form-grid label:not(.check-row){display:block}.form-grid input,.form-grid select,.form-grid textarea{width:100%;margin-top:7px}.form-grid textarea{resize:vertical;min-height:92px}.check-row{display:flex;align-items:center;gap:9px;font-weight:600}.check-row input{width:auto;margin:0}.actions{display:flex;flex-wrap:wrap;gap:10px;margin-top:18px}.error-message{margin-top:14px;padding:11px 13px;border:1px solid #fecaca;border-radius:9px;background:#fff1f2;color:#b91c1c}.result-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:14px}.result-head h3{margin:0 0 5px}.helper{color:#64748b;font-size:13px;margin:0}.ready-badge{padding:5px 10px;border-radius:999px;background:#dcfce7;color:#166534;font-size:12px;white-space:nowrap}.preview{width:100%;margin-top:7px;font-family:ui-monospace,SFMono-Regular,Consolas,monospace;font-size:12px;line-height:1.55;resize:vertical}.privacy-note{margin:14px 0 0;color:#475569;font-size:13px}@media(max-width:700px){.form-grid{grid-template-columns:1fr}.form-grid .wide{grid-column:auto}.result-head{align-items:center}}
            </style>
            <script>
            (function(){
                'use strict';
                const $=id=>document.getElementById(id);
                const form=$('eventForm'),allDay=$('allDay'),repeat=$('repeat'),repeatCount=$('repeatCount');
                const startTime=$('startTime'),endTime=$('endTime'),startDate=$('startDate'),endDate=$('endDate');
                const resultPanel=$('resultPanel'),preview=$('icsPreview'),errorBox=$('formError'),copyStatus=$('copyStatus');
                const encoder=new TextEncoder();
                let generatedName='event.ics';
                const pad=n=>String(n).padStart(2,'0');
                const localDateTime=d=>d.getFullYear()+pad(d.getMonth()+1)+pad(d.getDate())+'T'+pad(d.getHours())+pad(d.getMinutes())+pad(d.getSeconds());
                const utcDateTime=d=>d.getUTCFullYear()+pad(d.getUTCMonth()+1)+pad(d.getUTCDate())+'T'+pad(d.getUTCHours())+pad(d.getUTCMinutes())+pad(d.getUTCSeconds())+'Z';
                const compactDate=value=>value.replaceAll('-','');
                const inputDateTime=d=>d.getFullYear()+'-'+pad(d.getMonth()+1)+'-'+pad(d.getDate())+'T'+pad(d.getHours())+':'+pad(d.getMinutes());
                const inputDate=d=>d.getFullYear()+'-'+pad(d.getMonth()+1)+'-'+pad(d.getDate());
                function parseDate(value){const p=value.split('-').map(Number);return p.length===3&&p.every(Number.isFinite)?new Date(p[0],p[1]-1,p[2]):null;}
                function escapeIcsText(value){return value.replaceAll('\\','\\\\').replace(/\r\n|\r|\n/g,'\\n').replaceAll(',','\\,').replaceAll(';','\\;');}
                function foldIcsLine(line){
                    const output=[];let part='';
                    for(const char of line){
                        if(part&&encoder.encode(part+char).length>75){output.push(part);part=' '+char;}else part+=char;
                    }
                    if(part)output.push(part);return output.join('\r\n');
                }
                function alarmTrigger(minutes){if(minutes===0)return 'PT0M';if(minutes%1440===0)return '-P'+(minutes/1440)+'D';if(minutes%60===0)return '-PT'+(minutes/60)+'H';return '-PT'+minutes+'M';}
                function safeFileName(title){const clean=title.replace(/[<>:"/\\|?*\x00-\x1f]/g,'_').replace(/[. ]+$/g,'').trim().slice(0,60);return (clean||'日历事件')+'.ics';}
                function makeUid(){return (crypto.randomUUID?crypto.randomUUID():Array.from(crypto.getRandomValues(new Uint32Array(4)),n=>n.toString(16)).join('-'))+'@office-tools.local';}
                function showError(message){errorBox.textContent=message;errorBox.hidden=false;resultPanel.hidden=true;}
                function clearError(){errorBox.textContent='';errorBox.hidden=true;}
                function setDefaults(){
                    const now=new Date();now.setSeconds(0,0);now.setMinutes(Math.ceil(now.getMinutes()/30)*30);
                    const later=new Date(now.getTime()+60*60*1000);
                    startTime.value=inputDateTime(now);endTime.value=inputDateTime(later);startDate.value=inputDate(now);endDate.value=inputDate(now);
                }
                function syncAllDay(){document.querySelectorAll('.timed-field').forEach(el=>el.hidden=allDay.checked);document.querySelectorAll('.all-day-field').forEach(el=>el.hidden=!allDay.checked);}
                function buildCalendar(){
                    clearError();copyStatus.textContent='';
                    const title=$('eventTitle').value.trim();if(!title){showError('请填写事件标题。');$('eventTitle').focus();return;}
                    const lines=['BEGIN:VCALENDAR','VERSION:2.0','PRODID:-//Office Tools//Calendar Event Generator//ZH-CN','CALSCALE:GREGORIAN','METHOD:PUBLISH','BEGIN:VEVENT','UID:'+makeUid(),'DTSTAMP:'+utcDateTime(new Date())];
                    let humanTime='';
                    if(allDay.checked){
                        const from=parseDate(startDate.value),through=parseDate(endDate.value);
                        if(!from||!through){showError('请选择开始和结束日期。');return;}
                        if(through<from){showError('结束日期不能早于开始日期。');return;}
                        const exclusiveEnd=new Date(through);exclusiveEnd.setDate(exclusiveEnd.getDate()+1);
                        lines.push('DTSTART;VALUE=DATE:'+compactDate(startDate.value),'DTEND;VALUE=DATE:'+inputDate(exclusiveEnd).replaceAll('-',''));
                        humanTime=startDate.value+(endDate.value!==startDate.value?' 至 '+endDate.value:'')+'（全天）';
                    }else{
                        const from=new Date(startTime.value),to=new Date(endTime.value);
                        if(!startTime.value||!endTime.value||Number.isNaN(from.getTime())||Number.isNaN(to.getTime())){showError('请选择有效的开始和结束时间。');return;}
                        if(to<=from){showError('结束时间必须晚于开始时间。');return;}
                        lines.push('DTSTART:'+localDateTime(from),'DTEND:'+localDateTime(to));
                        humanTime=startTime.value.replace('T',' ')+' 至 '+endTime.value.replace('T',' ');
                    }
                    lines.push('SUMMARY:'+escapeIcsText(title));
                    const location=$('location').value.trim(),description=$('description').value.trim();
                    if(location)lines.push('LOCATION:'+escapeIcsText(location));
                    if(description)lines.push('DESCRIPTION:'+escapeIcsText(description));
                    if(repeat.value){const count=Math.trunc(Number(repeatCount.value));if(!Number.isFinite(count)||count<2||count>365){showError('重复次数请输入 2 到 365 之间的整数。');return;}lines.push('RRULE:FREQ='+repeat.value+';COUNT='+count);}
                    if($('reminder').value!==''){const minutes=Number($('reminder').value);lines.push('BEGIN:VALARM','TRIGGER:'+alarmTrigger(minutes),'ACTION:DISPLAY','DESCRIPTION:'+escapeIcsText(title),'END:VALARM');}
                    lines.push('STATUS:CONFIRMED','END:VEVENT','END:VCALENDAR');
                    preview.value=lines.map(foldIcsLine).join('\r\n')+'\r\n';generatedName=safeFileName(title);
                    $('resultSummary').textContent=title+' · '+humanTime;resultPanel.hidden=false;resultPanel.scrollIntoView({behavior:'smooth',block:'nearest'});
                }
                allDay.addEventListener('change',syncAllDay);
                repeat.addEventListener('change',()=>{$('repeatCountWrap').hidden=!repeat.value;});
                form.addEventListener('submit',event=>{event.preventDefault();buildCalendar();});
                $('downloadBtn').addEventListener('click',()=>{const url=URL.createObjectURL(new Blob(['\uFEFF'+preview.value],{type:'text/calendar;charset=utf-8'}));const a=document.createElement('a');a.href=url;a.download=generatedName;document.body.appendChild(a);a.click();a.remove();setTimeout(()=>URL.revokeObjectURL(url),1000);});
                $('copyBtn').addEventListener('click',async()=>{try{await navigator.clipboard.writeText(preview.value);copyStatus.textContent='已复制 ICS 内容。';}catch(e){preview.focus();preview.select();copyStatus.textContent=document.execCommand('copy')?'已复制 ICS 内容。':'复制失败，请手动选择复制。';}});
                $('resetBtn').addEventListener('click',()=>{form.reset();setDefaults();syncAllDay();$('repeatCountWrap').hidden=true;clearError();resultPanel.hidden=true;$('eventTitle').focus();});
                setDefaults();syncAllDay();
            })();
            </script>
<?php include '_footer.php'; ?>
