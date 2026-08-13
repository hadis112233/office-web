<?php
$title = '全球会议时间助手';
$desc = '输入会议发起地时间，同时查看各地时间、时差和工作时段；自动按当天夏令时规则换算，数据不会上传服务器。';
include '_header.php';
?>
            <style>
            .meeting-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.meeting-grid label{display:block}.meeting-grid input,.meeting-grid select{width:100%;margin-top:7px}.meeting-actions{display:flex;flex-wrap:wrap;gap:10px;margin-top:17px}.meeting-note{min-height:22px;margin:12px 0 0;color:#64748b;font-size:13px}.meeting-note.error{color:#b91c1c}.meeting-note.warning{color:#b45309}.city-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px}.city-head h3{margin:0}.city-actions{display:flex;gap:8px;flex-wrap:wrap}.city-actions button{font-size:13px}.city-choices{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:9px}.city-choice{display:flex;align-items:center;gap:8px;padding:10px;border:1px solid #dbe3ef;border-radius:9px;background:rgba(248,250,252,.62);cursor:pointer}.city-choice input{width:auto;margin:0}.city-choice span{min-width:0}.city-choice small{display:block;margin-top:2px;color:#64748b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.meeting-summary{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px}.meeting-summary h3{margin:0}.meeting-summary p{margin:5px 0 0;color:#64748b;font-size:13px}.meeting-result{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.time-card{padding:15px;border:1px solid #dbe3ef;border-radius:12px;background:rgba(255,255,255,.74)}.time-card .city{font-weight:700;color:#1e293b}.time-card .zone{margin-top:3px;color:#64748b;font-size:12px}.time-card .time{margin-top:12px;font-size:24px;line-height:1;font-weight:800;letter-spacing:.02em;color:#312e81}.time-card .date{margin-top:7px;color:#475569;font-size:13px}.time-card .meta{display:flex;justify-content:space-between;gap:8px;margin-top:12px;padding-top:10px;border-top:1px solid #e2e8f0;font-size:12px;color:#64748b}.time-card .available{color:#047857;font-weight:700}.time-card .outside{color:#b45309;font-weight:700}.meeting-empty{grid-column:1/-1;padding:30px;text-align:center;border:1px dashed #cbd5e1;border-radius:12px;color:#64748b}.meeting-work{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin-top:16px}.meeting-work input{width:100%;margin-top:7px}.privacy-note{margin:14px 0 0;color:#475569;font-size:13px}html.theme-dark .city-choice,html.theme-dark .time-card{background:rgba(30,41,59,.7);border-color:#475569}html.theme-dark .time-card .city{color:#e2e8f0}html.theme-dark .time-card .meta{border-color:#475569}@media(max-width:850px){.city-choices,.meeting-result{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:560px){.meeting-grid,.meeting-work,.city-choices,.meeting-result{grid-template-columns:1fr}.city-head,.meeting-summary{align-items:flex-start;flex-direction:column}.meeting-summary .btn{width:100%}}
            </style>
            <div class="tool-panel">
                <div class="meeting-grid">
                    <div><label for="sourceZone">会议发起地</label><select id="sourceZone"></select></div>
                    <div><label for="sourceTime">当地会议时间</label><input id="sourceTime" type="datetime-local" required></div>
                </div>
                <div class="meeting-work"><div><label for="workStart">当地工作开始</label><input id="workStart" type="time" value="09:00"></div><div><label for="workEnd">当地工作结束</label><input id="workEnd" type="time" value="18:00"></div></div>
                <div class="meeting-actions"><button class="btn" id="nowButton" type="button">使用发起地当前时间</button><button class="btn secondary" id="resetButton" type="button">恢复推荐城市</button></div>
                <p class="meeting-note" id="meetingNote" role="status">按各地当天的夏令时规则自动换算。</p>
            </div>
            <div class="tool-panel">
                <div class="city-head"><h3>参会城市</h3><div class="city-actions"><button class="btn secondary" id="selectRecommended" type="button">选择推荐城市</button><button class="btn secondary" id="clearCities" type="button">清空</button></div></div>
                <div class="city-choices" id="cityChoices"></div>
                <p class="privacy-note">最多选 9 个城市；选择会保存在当前浏览器，会议时间不会上传服务器。</p>
            </div>
            <div class="tool-panel">
                <div class="meeting-summary"><div><h3>会议时间对照</h3><p id="resultSubtitle">请先输入会议时间。</p></div><button class="btn secondary" id="copyButton" type="button" disabled>复制对照结果</button></div>
                <div class="meeting-result" id="meetingResult"><div class="meeting-empty">选择城市后，这里会显示各地时间和工作状态。</div></div>
            </div>
            <script>
            (()=>{
                'use strict';
                const MAX_SELECTED_ZONES=9,storageKey='office_meeting_time_zones';
                const zones=[
                    {id:'Asia/Shanghai',city:'北京',detail:'中国标准时间'}, {id:'Asia/Tokyo',city:'东京',detail:'日本标准时间'}, {id:'Asia/Singapore',city:'新加坡',detail:'新加坡时间'},
                    {id:'Asia/Dubai',city:'迪拜',detail:'海湾标准时间'}, {id:'Europe/London',city:'伦敦',detail:'英国时间'}, {id:'Europe/Paris',city:'巴黎',detail:'中欧时间'},
                    {id:'America/New_York',city:'纽约',detail:'美国东部时间'}, {id:'America/Los_Angeles',city:'洛杉矶',detail:'美国太平洋时间'}, {id:'Australia/Sydney',city:'悉尼',detail:'澳大利亚东部时间'}
                ];
                const recommended=['Asia/Shanghai','Europe/London','America/New_York','America/Los_Angeles'];
                const $=id=>document.getElementById(id),formatters=new Map();let latestRows=[];
                const pad=n=>String(n).padStart(2,'0');
                const dateInput=parts=>parts.year+'-'+pad(parts.month)+'-'+pad(parts.day)+'T'+pad(parts.hour)+':'+pad(parts.minute);
                function partsFromInput(value){const match=/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})$/.exec(value);if(!match)return null;const parts={year:+match[1],month:+match[2],day:+match[3],hour:+match[4],minute:+match[5]};const check=new Date(Date.UTC(parts.year,parts.month-1,parts.day,parts.hour,parts.minute));return check.getUTCFullYear()===parts.year&&check.getUTCMonth()+1===parts.month&&check.getUTCDate()===parts.day&&check.getUTCHours()===parts.hour&&check.getUTCMinutes()===parts.minute?parts:null;}
                function getFormatter(zone){if(!formatters.has(zone))formatters.set(zone,new Intl.DateTimeFormat('en-US-u-ca-gregory-nu-latn',{timeZone:zone,year:'numeric',month:'2-digit',day:'2-digit',hour:'2-digit',minute:'2-digit',second:'2-digit',hourCycle:'h23',timeZoneName:'longOffset'}));return formatters.get(zone);}
                function zonedParts(epoch,zone){const output={};getFormatter(zone).formatToParts(new Date(epoch)).forEach(part=>{if(['year','month','day','hour','minute','second','timeZoneName'].includes(part.type))output[part.type]=part.type==='timeZoneName'?part.value:+part.value;});if(output.hour===24)output.hour=0;return output;}
                const stamp=parts=>Date.UTC(parts.year,parts.month-1,parts.day,parts.hour,parts.minute,parts.second||0);
                const offsetAt=(epoch,zone)=>stamp(zonedParts(epoch,zone))-epoch;
                function zonedDateTimeToUtc(target,zone){
                    const guess=stamp(target),offsets=new Set();[-86400000,-43200000,0,43200000,86400000].forEach(delta=>offsets.add(offsetAt(guess+delta,zone)));
                    const matches=[...offsets].map(offset=>guess-offset).filter(epoch=>{const actual=zonedParts(epoch,zone);return actual.year===target.year&&actual.month===target.month&&actual.day===target.day&&actual.hour===target.hour&&actual.minute===target.minute;}).sort((a,b)=>a-b);
                    return matches.length?{epoch:matches[0],ambiguous:matches.length>1}:null;
                }
                const formatOffset=minutes=>{const sign=minutes>=0?'+':'-',absolute=Math.abs(Math.round(minutes));return 'UTC'+sign+pad(Math.floor(absolute/60))+':'+pad(absolute%60);};
                const zoneById=id=>zones.find(zone=>zone.id===id);
                function selectedZones(){return [...document.querySelectorAll('#cityChoices input:checked')].map(input=>input.value);}
                function persistSelection(){try{localStorage.setItem(storageKey,JSON.stringify(selectedZones()));}catch(e){}}
                function restoreSelection(){try{const saved=JSON.parse(localStorage.getItem(storageKey));return Array.isArray(saved)?saved.filter(id=>zoneById(id)).slice(0,MAX_SELECTED_ZONES):recommended;}catch(e){return recommended;}}
                function renderZoneControls(ids=restoreSelection()){
                    const source=$('sourceZone'),choices=$('cityChoices'),oldSource=source.value;source.replaceChildren();choices.replaceChildren();
                    zones.forEach(zone=>{const option=document.createElement('option');option.value=zone.id;option.textContent=zone.city+'（'+zone.detail+'）';source.appendChild(option);const label=document.createElement('label'),check=document.createElement('input'),text=document.createElement('span'),small=document.createElement('small');label.className='city-choice';check.type='checkbox';check.value=zone.id;check.checked=ids.includes(zone.id);text.textContent=zone.city;small.textContent=zone.id;text.appendChild(small);label.append(check,text);choices.appendChild(label);check.addEventListener('change',()=>{if(selectedZones().length>MAX_SELECTED_ZONES){check.checked=false;$('meetingNote').textContent='最多只能选择 '+MAX_SELECTED_ZONES+' 个城市。';$('meetingNote').className='meeting-note error';return;}persistSelection();renderResults();});});
                    source.value=zoneById(oldSource)?oldSource:'Asia/Shanghai';
                }
                const weekday=parts=>'星期'+'日一二三四五六'[new Date(Date.UTC(parts.year,parts.month-1,parts.day)).getUTCDay()];
                function workMinutes(value){const match=/^(\d{2}):(\d{2})$/.exec(value);return match?+match[1]*60+(+match[2]):null;}
                function inWorkHours(parts){const start=workMinutes($('workStart').value),end=workMinutes($('workEnd').value),now=parts.hour*60+parts.minute;if(start===null||end===null||start===end)return null;return start<end?now>=start&&now<end:now>=start||now<end;}
                function deltaText(minutes){if(!minutes)return '与发起地一致';const absolute=Math.abs(minutes),hours=Math.floor(absolute/60),rest=absolute%60;return (minutes>0?'比发起地晚 ':'比发起地早 ')+(hours?hours+' 小时':'')+(rest?rest+' 分钟':'');}
                function showNote(text,type=''){$('meetingNote').textContent=text;$('meetingNote').className='meeting-note '+type;}
                function renderResults(){
                    const input=partsFromInput($('sourceTime').value),sourceZone=$('sourceZone').value,zoneIds=selectedZones(),result=$('meetingResult');result.replaceChildren();latestRows=[];$('copyButton').disabled=true;
                    if(!input){showNote('请选择有效的会议发起地时间。','error');$('resultSubtitle').textContent='请先输入会议时间。';result.append(Object.assign(document.createElement('div'),{className:'meeting-empty',textContent:'请选择有效的会议时间。'}));return;}
                    const start=workMinutes($('workStart').value),end=workMinutes($('workEnd').value);if(start===null||end===null||start===end){showNote('工作开始和结束时间不能相同。','error');$('resultSubtitle').textContent='请调整工作时段。';return;}
                    const converted=zonedDateTimeToUtc(input,sourceZone);if(!converted){showNote('这个时刻正好落在夏令时切换时被跳过的时段，请调整到其他时间。','error');$('resultSubtitle').textContent='无法换算此时间。';result.append(Object.assign(document.createElement('div'),{className:'meeting-empty',textContent:'该当地时刻不存在，请调整会议时间后重试。'}));return;}
                    const sourceName=zoneById(sourceZone).city,sourceStamp=stamp(input);showNote(converted.ambiguous?'该时刻处于夏令时切换的重复时段，已采用较早的那一次。':'已按各地当天的夏令时规则换算。',converted.ambiguous?'warning':'');$('resultSubtitle').textContent=sourceName+' '+input.year+'年'+input.month+'月'+input.day+'日 '+pad(input.hour)+':'+pad(input.minute)+'（'+sourceZone+'）';
                    if(!zoneIds.length){result.append(Object.assign(document.createElement('div'),{className:'meeting-empty',textContent:'请至少选择一个参会城市。'}));return;}
                    zoneIds.forEach(id=>{const zone=zoneById(id),parts=zonedParts(converted.epoch,id),offset=Math.round(offsetAt(converted.epoch,id)/60000),difference=Math.round((stamp(parts)-sourceStamp)/60000),working=inWorkHours(parts),card=document.createElement('article'),city=document.createElement('div'),tz=document.createElement('div'),time=document.createElement('div'),date=document.createElement('div'),meta=document.createElement('div'),delta=document.createElement('span'),status=document.createElement('span');card.className='time-card';city.className='city';city.textContent=zone.city;tz.className='zone';tz.textContent=id+' · '+formatOffset(offset);time.className='time';time.textContent=pad(parts.hour)+':'+pad(parts.minute);date.className='date';date.textContent=parts.year+'年'+parts.month+'月'+parts.day+'日 '+weekday(parts);delta.textContent=deltaText(difference);status.className=working?'available':'outside';status.textContent=working?'工作时段':'非工作时段';meta.append(delta,status);card.append(city,tz,time,date,meta);result.appendChild(card);latestRows.push({zone,parts,offset,working,difference});});$('copyButton').disabled=false;
                }
                function setNow(){const parts=zonedParts(Date.now(),$('sourceZone').value);$('sourceTime').value=dateInput(parts);renderResults();}
                async function copyResults(){if(!latestRows.length)return;const source=zoneById($('sourceZone').value),input=partsFromInput($('sourceTime').value),lines=['会议时间对照：'+source.city+' '+input.year+'-'+pad(input.month)+'-'+pad(input.day)+' '+pad(input.hour)+':'+pad(input.minute)+'（'+source.id+'）'];latestRows.forEach(row=>lines.push(row.zone.city+'（'+row.zone.id+'，'+formatOffset(row.offset)+'）：'+row.parts.year+'-'+pad(row.parts.month)+'-'+pad(row.parts.day)+' '+weekday(row.parts)+' '+pad(row.parts.hour)+':'+pad(row.parts.minute)+'，'+deltaText(row.difference)+'，'+(row.working?'工作时段':'非工作时段')));try{await navigator.clipboard.writeText(lines.join('\n'));showNote('对照结果已复制。');}catch(e){showNote('复制失败，请手动选择复制。','error');}}
                renderZoneControls();setNow();$('sourceZone').addEventListener('change',()=>{setNow();});$('sourceTime').addEventListener('input',renderResults);$('workStart').addEventListener('input',renderResults);$('workEnd').addEventListener('input',renderResults);$('nowButton').addEventListener('click',setNow);$('copyButton').addEventListener('click',copyResults);$('selectRecommended').addEventListener('click',()=>{renderZoneControls(recommended);persistSelection();renderResults();});$('clearCities').addEventListener('click',()=>{renderZoneControls([]);persistSelection();renderResults();});$('resetButton').addEventListener('click',()=>{renderZoneControls(recommended);$('sourceZone').value='Asia/Shanghai';$('workStart').value='09:00';$('workEnd').value='18:00';persistSelection();setNow();});
            })();
            </script>
<?php include '_footer.php'; ?>
