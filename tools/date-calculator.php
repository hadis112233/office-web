<?php
$title = '日期计算器';
$desc = '计算两个日期的间隔、工作日数量，或推算指定天数后的日期。数据仅在浏览器中处理。';
include '_header.php';
?>
            <div class="tool-panel">
                <h3>日期间隔</h3>
                <div class="calc-grid">
                    <div><label for="startDate">开始日期</label><input type="date" id="startDate"></div>
                    <div><label for="endDate">结束日期</label><input type="date" id="endDate"></div>
                </div>
                <div class="stats">
                    <div class="stat-box"><div class="num" id="calendarDays">0</div><div class="label">相隔天数</div></div>
                    <div class="stat-box"><div class="num" id="inclusiveDays">0</div><div class="label">首尾都算</div></div>
                    <div class="stat-box"><div class="num" id="workDays">0</div><div class="label">工作日（周一至周五）</div></div>
                    <div class="stat-box"><div class="num" id="weekendDays">0</div><div class="label">周末天数</div></div>
                </div>
                <p class="helper">工作日结果未扣除法定节假日和调休。</p>
            </div>
            <div class="tool-panel">
                <h3>推算日期</h3>
                <div class="calc-grid three">
                    <div><label for="baseDate">起始日期</label><input type="date" id="baseDate"></div>
                    <div><label for="offsetDays">增加或减少天数</label><input type="number" id="offsetDays" value="30" min="-100000" max="100000" step="1"></div>
                    <div><label for="offsetMode">计算方式</label><select id="offsetMode"><option value="calendar">自然日</option><option value="work">工作日</option></select></div>
                </div>
                <div class="result-box" role="status">计算结果：<strong id="targetDate">—</strong> <span id="targetWeekday"></span></div>
            </div>
            <style>
            .calc-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin:14px 0 18px}.calc-grid.three{grid-template-columns:repeat(3,minmax(0,1fr))}.calc-grid input,.calc-grid select{width:100%;margin-top:7px}.helper{margin-top:12px;color:#64748b;font-size:12px}@media(max-width:700px){.calc-grid,.calc-grid.three{grid-template-columns:1fr}}
            </style>
            <script>
            (function(){
                const $=id=>document.getElementById(id);
                const startDate=$('startDate'),endDate=$('endDate'),baseDate=$('baseDate'),offsetDays=$('offsetDays'),offsetMode=$('offsetMode');
                const calendarDays=$('calendarDays'),inclusiveDays=$('inclusiveDays'),workDays=$('workDays'),weekendDays=$('weekendDays'),targetDate=$('targetDate'),targetWeekday=$('targetWeekday');
                const ids=['startDate','endDate','baseDate','offsetDays','offsetMode'];
                const pad=n=>String(n).padStart(2,'0');
                const format=d=>d.getFullYear()+'-'+pad(d.getMonth()+1)+'-'+pad(d.getDate());
                const parse=value=>{const parts=value.split('-').map(Number);return parts.length===3?new Date(parts[0],parts[1]-1,parts[2]):null;};
                const today=new Date(); const later=new Date(today); later.setDate(later.getDate()+30);
                startDate.value=format(today); endDate.value=format(later); baseDate.value=format(today);
                function updateInterval(){
                    let start=parse(startDate.value),end=parse(endDate.value); if(!start||!end)return;
                    const direction=end>=start?1:-1; let from=direction===1?start:end; let to=direction===1?end:start;
                    const days=Math.round((to-from)/86400000),total=days+1,fullWeeks=Math.floor(total/7);let work=fullWeeks*5;
                    for(let i=0;i<total%7;i++){const day=(from.getDay()+i)%7;if(day!==0&&day!==6)work++;}const weekend=total-work;
                    calendarDays.textContent=String(days*direction); inclusiveDays.textContent=String((days+1)*direction); workDays.textContent=String(work); weekendDays.textContent=String(weekend);
                }
                function updateTarget(){
                    const base=parse(baseDate.value); let count=Math.trunc(Number(offsetDays.value)); if(!base||!Number.isFinite(count)||Math.abs(count)>100000){targetDate.textContent='请输入 ±100000 以内的天数';targetWeekday.textContent='';return;}
                    const result=new Date(base); const direction=count>=0?1:-1;
                    if(offsetMode.value==='calendar') result.setDate(result.getDate()+count);
                    else {let remaining=Math.abs(count);while(remaining){result.setDate(result.getDate()+direction);if(result.getDay()!==0&&result.getDay()!==6)remaining--;}}
                    targetDate.textContent=format(result); targetWeekday.textContent='（星期'+'日一二三四五六'[result.getDay()]+'）';
                }
                ids.forEach(id=>$(id).addEventListener('input',()=>{updateInterval();updateTarget();})); updateInterval();updateTarget();
            })();
            </script>
<?php include '_footer.php'; ?>
