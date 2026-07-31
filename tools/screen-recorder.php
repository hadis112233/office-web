<?php
$title = '在线屏幕录制';
$desc = '录制整个屏幕、指定窗口或浏览器标签页，并在本地预览和下载 WebM 视频。录制内容不会上传服务器。';
include '_header.php';
?>
            <div class="tool-panel">
                <div class="recorder-options"><label><input id="includeAudio" type="checkbox" checked> 尝试录制系统或标签页声音</label><span>最长建议录制 60 分钟</span></div>
                <div class="recorder-state"><span class="record-dot" id="recordDot"></span><strong id="recordStatus">准备录制</strong><time id="duration">00:00</time></div>
                <div class="btn-row"><button class="btn success" id="start" type="button">开始录制</button><button class="btn warning" id="stop" type="button" disabled>停止录制</button><button class="btn" id="download" type="button" disabled>下载视频</button></div>
                <p class="helper" id="tip" role="status">开始后，浏览器会让你选择屏幕、窗口或标签页。声音能否录制取决于浏览器和所选来源。</p>
            </div>
            <div class="tool-panel preview-panel" id="previewPanel" hidden><h3>录制预览</h3><video id="preview" controls playsinline></video></div>
            <style>
            .recorder-options{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;color:#64748b;font-size:13px}.recorder-options label{display:flex;align-items:center;gap:8px;margin:0}.recorder-state{display:flex;align-items:center;justify-content:center;gap:12px;margin:24px 0;padding:20px;border-radius:12px;background:#f8fafc}.record-dot{width:14px;height:14px;border-radius:50%;background:#94a3b8}.record-dot.active{background:#ef4444;box-shadow:0 0 0 7px rgba(239,68,68,.14);animation:pulse 1.2s infinite}.recorder-state time{font:700 22px Consolas,monospace;color:#334155}.helper{color:#64748b;font-size:12px}.preview-panel video{display:block;width:100%;max-height:560px;border-radius:10px;background:#0f172a}@keyframes pulse{50%{opacity:.45}}
            </style>
            <script>
            (function(){
                const $=id=>document.getElementById(id);let stream=null,recorder=null,chunks=[],videoUrl='',startedAt=0,timer=0;
                function format(seconds){return String(Math.floor(seconds/60)).padStart(2,'0')+':'+String(seconds%60).padStart(2,'0');}
                function updateTimer(){const seconds=Math.floor((Date.now()-startedAt)/1000);$('duration').textContent=format(seconds);if(seconds>=3600){$('tip').textContent='已达到 60 分钟上限，正在停止并生成视频。';stop();}}
                function mimeType(){const types=['video/webm;codecs=vp9,opus','video/webm;codecs=vp8,opus','video/webm'];return types.find(type=>window.MediaRecorder&&MediaRecorder.isTypeSupported(type))||'';}
                async function start(){if(!navigator.mediaDevices||!navigator.mediaDevices.getDisplayMedia){$('tip').textContent='当前浏览器不支持屏幕录制，请使用最新版 Chrome、Edge 或 Firefox。';return;}try{stream=await navigator.mediaDevices.getDisplayMedia({video:{frameRate:{ideal:30,max:60}},audio:$('includeAudio').checked});chunks=[];const type=mimeType();recorder=new MediaRecorder(stream,type?{mimeType:type}:undefined);recorder.addEventListener('dataavailable',event=>{if(event.data.size)chunks.push(event.data);});recorder.addEventListener('stop',finish);stream.getVideoTracks()[0].addEventListener('ended',stop);recorder.start(1000);startedAt=Date.now();timer=setInterval(updateTimer,500);$('recordDot').classList.add('active');$('recordStatus').textContent='正在录制';$('start').disabled=true;$('stop').disabled=false;$('download').disabled=true;$('tip').textContent='录制只保存在当前浏览器内存中，停止后即可预览和下载。';}catch(error){$('tip').textContent=error.name==='NotAllowedError'?'你取消了屏幕共享，未开始录制。':'无法开始录制：'+error.message;}}
                function stop(){if(recorder&&recorder.state!=='inactive')recorder.stop();}
                function finish(){clearInterval(timer);timer=0;stream?.getTracks().forEach(track=>track.stop());stream=null;$('recordDot').classList.remove('active');$('recordStatus').textContent='录制完成';$('start').disabled=false;$('stop').disabled=true;if(videoUrl)URL.revokeObjectURL(videoUrl);const blob=new Blob(chunks,{type:recorder?.mimeType||'video/webm'});videoUrl=URL.createObjectURL(blob);$('preview').src=videoUrl;$('previewPanel').hidden=false;$('download').disabled=false;$('tip').textContent='录制完成，大小 '+(blob.size/1024/1024).toFixed(2)+' MB。关闭页面前请下载保存。';}
                $('start').addEventListener('click',start);$('stop').addEventListener('click',stop);$('download').addEventListener('click',()=>{if(!videoUrl)return;const a=document.createElement('a');a.href=videoUrl;a.download='屏幕录制-'+new Date().toISOString().slice(0,19).replace(/[:T]/g,'-')+'.webm';a.click();});window.addEventListener('beforeunload',()=>{clearInterval(timer);stream?.getTracks().forEach(track=>track.stop());if(videoUrl)URL.revokeObjectURL(videoUrl);});
            })();
            </script>
<?php include '_footer.php'; ?>
