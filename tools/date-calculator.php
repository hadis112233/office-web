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
                    <div class="stat-box"><div class="num" id="workDays">0</div><div class="label">工作日（含自定义调整）</div></div>
                    <div class="stat-box"><div class="num" id="weekendDays">0</div><div class="label">休息日天数</div></div>
                </div>
                <p class="helper">工作日与休息日统计包含首尾日期。默认周一至周五上班；放假与调休请在下方填写。</p>
            </div>
            <div class="tool-panel">
                <h3>自定义放假与补班</h3>
                <div class="calc-grid">
                    <div><label for="holidayDates">额外休息日</label><textarea id="holidayDates" maxlength="6000" placeholder="例如：2026-01-01，每行一个日期"></textarea></div>
                    <div><label for="makeupDates">补班日</label><textarea id="makeupDates" maxlength="6000" placeholder="填写需要上班的周末日期，每行一个"></textarea></div>
                </div>
                <p class="helper">格式为 YYYY-MM-DD，也可以用空格或逗号分隔；每项最多 500 个日期。两处填写同一天时，补班优先。不会自动导入法定节假日。</p>
                <p id="scheduleStatus" role="status"></p>
            </div>
            <div class="tool-panel">
                <h3>推算日期</h3>
                <div class="calc-grid three">
                    <div><label for="baseDate">起始日期</label><input type="date" id="baseDate"></div>
                    <div><label for="offsetDays">增加或减少天数</label><input type="number" id="offsetDays" value="30" min="-100000" max="100000" step="1"></div>
                    <div><label for="offsetMode">计算方式</label><select id="offsetMode"><option value="calendar">自然日</option><option value="work">工作日</option></select></div>
                </div>
                <div class="result-box" role="status">计算结果：<strong id="targetDate">—</strong> <span id="targetWeekday"></span></div>
                <p class="helper">推算工作日从起始日期的下一天开始计数；输入负数向前推算，输入 0 保留起始日期。</p>
            </div>
            <style>
            .calc-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin:14px 0 18px}.calc-grid.three{grid-template-columns:repeat(3,minmax(0,1fr))}.calc-grid input,.calc-grid select{width:100%;margin-top:7px}.helper{margin-top:12px;color:#64748b;font-size:12px}@media(max-width:700px){.calc-grid,.calc-grid.three{grid-template-columns:1fr}}
            </style>
            <script>
            (function(){
                const $=id=>document.getElementById(id);
                const startDate=$('startDate'),endDate=$('endDate'),baseDate=$('baseDate'),offsetDays=$('offsetDays'),offsetMode=$('offsetMode');
                const calendarDays=$('calendarDays'),inclusiveDays=$('inclusiveDays'),workDays=$('workDays'),weekendDays=$('weekendDays'),targetDate=$('targetDate'),targetWeekday=$('targetWeekday');
                const ids=['startDate','endDate','baseDate','offsetDays','offsetMode','holidayDates','makeupDates'];
                let holidays=new Set(),makeup=new Set();
                const pad=n=>String(n).padStart(2,'0');
                const format=d=>d.getFullYear()+'-'+pad(d.getMonth()+1)+'-'+pad(d.getDate());
                const parse=value=>{if(!/^\d{4}-\d{2}-\d{2}$/.test(value))return null;const [year,month,day]=value.split('-').map(Number);if(year<1000||year>9999)return null;const date=new Date(year,month-1,day);return date.getFullYear()===year&&date.getMonth()===month-1&&date.getDate()===day?date:null;};
                function dateList(id){const tokens=$(id).value.trim().split(/[\s,，;；]+/).filter(Boolean);if(tokens.length>500)throw new Error('每项最多填写 500 个日期。');for(const token of tokens)if(!parse(token))throw new Error('日期格式或日期无效：'+token);return new Set(tokens);}
                function isWorkDay(date){const key=format(date);if(makeup.has(key))return true;if(holidays.has(key))return false;return date.getDay()!==0&&date.getDay()!==6;}
                const today=new Date(); const later=new Date(today); later.setDate(later.getDate()+30);
                startDate.value=format(today); endDate.value=format(later); baseDate.value=format(today);
                function updateInterval(){
                    let start=parse(startDate.value),end=parse(endDate.value); if(!start||!end){[calendarDays,inclusiveDays,workDays,weekendDays].forEach(node=>node.textContent='—');return;}
                    const direction=end>=start?1:-1; let from=direction===1?start:end; let to=direction===1?end:start;
                    const days=Math.round((to-from)/86400000),total=days+1,fullWeeks=Math.floor(total/7);let work=fullWeeks*5;
                    for(let i=0;i<total%7;i++){const day=(from.getDay()+i)%7;if(day!==0&&day!==6)work++;}
                    for(const key of new Set([...holidays,...makeup])){const date=parse(key);if(date<from||date>to)continue;const defaultWork=date.getDay()!==0&&date.getDay()!==6;work+=Number(isWorkDay(date))-Number(defaultWork);}const weekend=total-work;
                    calendarDays.textContent=String(days*direction); inclusiveDays.textContent=String((days+1)*direction); workDays.textContent=String(work); weekendDays.textContent=String(weekend);
                }
                function updateTarget(){
                    const base=parse(baseDate.value); let count=Number(offsetDays.value); if(!base||offsetDays.value.trim()===''||!Number.isInteger(count)||Math.abs(count)>100000){targetDate.textContent='请填写有效日期与 ±100000 以内的整数天数';targetWeekday.textContent='';return;}
                    const result=new Date(base); const direction=count>=0?1:-1;
                    if(offsetMode.value==='calendar') result.setDate(result.getDate()+count);
                    else {let remaining=Math.abs(count);while(remaining){result.setDate(result.getDate()+direction);if(isWorkDay(result))remaining--;}}
                    if(result.getFullYear()<1000||result.getFullYear()>9999){targetDate.textContent='结果超出支持的日期范围（1000–9999 年）';targetWeekday.textContent='';return;}
                    targetDate.textContent=format(result); targetWeekday.textContent='（星期'+'日一二三四五六'[result.getDay()]+'）';
                }
                function update(){try{const nextHolidays=dateList('holidayDates'),nextMakeup=dateList('makeupDates');holidays=nextHolidays;makeup=nextMakeup;$('scheduleStatus').textContent='已设置 '+holidays.size+' 个休息日、'+makeup.size+' 个补班日。';updateInterval();updateTarget();}catch(error){$('scheduleStatus').textContent=error.message;[calendarDays,inclusiveDays,workDays,weekendDays,targetDate].forEach(node=>node.textContent='—');targetWeekday.textContent='';}}
                ids.forEach(id=>$(id).addEventListener('input',update)); update();
            })();
            </script>
<?php include '_footer.php'; ?>
