<?php
$title = '番茄专注钟';
$desc = '通过专注与休息循环安排工作节奏，可自定义时长并记录当前浏览器中的完成次数。';
include '_header.php';
?>
            <div class="tool-panel pomodoro-panel">
                <div class="mode" id="mode">专注时间</div><div class="clock" id="clock">25:00</div><div class="progress"><span id="progress"></span></div>
                <div class="btn-row"><button class="btn success" id="toggle" type="button">开始</button><button class="btn secondary" id="reset" type="button">重置</button><button class="btn" id="skip" type="button">跳到下一阶段</button></div>
                <div class="settings"><label>专注 <input id="focusMinutes" type="number" min="1" max="120" value="25"> 分钟</label><label>休息 <input id="breakMinutes" type="number" min="1" max="60" value="5"> 分钟</label><button class="btn small" id="notify" type="button">开启桌面提醒</button></div>
                <div class="stats"><div class="stat-box"><div class="num" id="todayCount">0</div><div class="label">今日完成番茄</div></div><div class="stat-box"><div class="num" id="totalCount">0</div><div class="label">累计完成番茄</div></div></div>
                <p class="helper" id="status" role="status">点击“开始”进入第一个专注阶段。</p>
            </div>
            <style>
            .pomodoro-panel{text-align:center}.mode{font-weight:700;color:#4f46e5}.clock{margin:12px 0 8px;font:800 clamp(58px,10vw,92px) Consolas,monospace;color:#1e293b}.progress{height:9px;margin:0 auto 20px;max-width:520px;border-radius:99px;background:#e2e8f0;overflow:hidden}.progress span{display:block;width:0;height:100%;background:linear-gradient(90deg,#4f46e5,#8b5cf6);transition:width .4s}.settings{display:flex;align-items:center;justify-content:center;flex-wrap:wrap;gap:12px 18px;margin:20px 0}.settings label{display:flex;align-items:center;gap:7px;margin:0}.settings input{width:76px}.helper{color:#64748b;font-size:12px}
            </style>
            <script>
            (function(){
                const $=id=>document.getElementById(id),todayKey=()=>new Date().toLocaleDateString('zh-CN');let phase='focus',running=false,remaining=25*60,total=25*60,deadline=0,timer=0,originalTitle=document.title;
                function valid(id,min,max){const value=Math.trunc(Number($(id).value));return Number.isFinite(value)&&value>=min&&value<=max?value:null;}
                function render(){const minutes=Math.floor(remaining/60),seconds=remaining%60;$('clock').textContent=String(minutes).padStart(2,'0')+':'+String(seconds).padStart(2,'0');$('mode').textContent=phase==='focus'?'专注时间':'休息时间';$('progress').style.width=((total-remaining)/total*100)+'%';$('toggle').textContent=running?'暂停':'开始';document.title=(running?$('clock').textContent+' · ':'')+(phase==='focus'?'专注':'休息')+' - 番茄钟';}
                function counts(){let data={date:todayKey(),today:0,total:0};try{data=Object.assign(data,JSON.parse(localStorage.getItem('office_pomodoro_stats')||'{}'));}catch(e){}if(data.date!==todayKey()){data.date=todayKey();data.today=0;}return data;}
                function showCounts(){const data=counts();$('todayCount').textContent=data.today||0;$('totalCount').textContent=data.total||0;}
                function saveFocus(){const data=counts();data.today=(data.today||0)+1;data.total=(data.total||0)+1;localStorage.setItem('office_pomodoro_stats',JSON.stringify(data));showCounts();}
                function beep(){try{const audio=new AudioContext(),osc=audio.createOscillator(),gain=audio.createGain();osc.connect(gain);gain.connect(audio.destination);osc.frequency.value=880;gain.gain.setValueAtTime(.12,audio.currentTime);gain.gain.exponentialRampToValueAtTime(.001,audio.currentTime+.45);osc.start();osc.stop(audio.currentTime+.45);setTimeout(()=>audio.close(),600);}catch(e){}}
                function announce(message){beep();if('Notification'in window&&Notification.permission==='granted')new Notification('办公工具站番茄钟',{body:message});$('status').textContent=message;}
                function setPhase(next,completed,withAlert){const minutes=next==='focus'?valid('focusMinutes',1,120):valid('breakMinutes',1,60);if(minutes===null){$('status').textContent='请填写有效的专注和休息时长。';running=false;return false;}if(completed&&phase==='focus'&&next==='break')saveFocus();phase=next;total=remaining=minutes*60;if(withAlert)announce(next==='break'?'专注结束，休息一下吧。':'休息结束，继续专注吧。');render();return true;}
                function tick(){remaining=Math.max(0,Math.ceil((deadline-Date.now())/1000));if(remaining===0){clearInterval(timer);running=false;if(setPhase(phase==='focus'?'break':'focus',true,true))start();}render();}
                function start(){if(running)return;const focus=valid('focusMinutes',1,120),rest=valid('breakMinutes',1,60);if(focus===null||rest===null){$('status').textContent='专注需 1–120 分钟，休息需 1–60 分钟。';return;}running=true;deadline=Date.now()+remaining*1000;timer=setInterval(tick,250);$('status').textContent=phase==='focus'?'保持专注，暂时放下无关事项。':'休息一下，活动身体和眼睛。';render();}
                function pause(){remaining=Math.max(0,Math.ceil((deadline-Date.now())/1000));running=false;clearInterval(timer);timer=0;$('status').textContent='计时已暂停。';render();}
                $('toggle').addEventListener('click',()=>running?pause():start());$('reset').addEventListener('click',()=>{running=false;clearInterval(timer);phase='focus';const minutes=valid('focusMinutes',1,120)||25;total=remaining=minutes*60;$('status').textContent='已重置。';render();});$('skip').addEventListener('click',()=>{running=false;clearInterval(timer);if(setPhase(phase==='focus'?'break':'focus',false,false))$('status').textContent='已跳到'+(phase==='focus'?'专注':'休息')+'阶段。';});['focusMinutes','breakMinutes'].forEach(id=>$(id).addEventListener('change',()=>{if(!running&&((phase==='focus'&&id==='focusMinutes')||(phase==='break'&&id==='breakMinutes'))){const minutes=valid(id,1,id==='focusMinutes'?120:60);if(minutes){total=remaining=minutes*60;render();}}}));$('notify').addEventListener('click',async()=>{if(!('Notification'in window)){$('status').textContent='当前浏览器不支持桌面提醒。';return;}const permission=await Notification.requestPermission();$('status').textContent=permission==='granted'?'桌面提醒已开启。':'未获得提醒权限，计时结束时仍会播放提示音。';});window.addEventListener('beforeunload',()=>{clearInterval(timer);document.title=originalTitle;});showCounts();render();
            })();
            </script>
<?php include '_footer.php'; ?>
