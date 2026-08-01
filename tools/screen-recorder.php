<?php
$title = '在线屏幕录制';
$desc = '录制整个屏幕、指定窗口或浏览器标签页，并在本地预览和下载视频。录制内容不会上传服务器。';
include '_header.php';
?>
            <div class="tool-panel">
                <div class="recorder-options">
                    <label><input id="includeAudio" type="checkbox" checked> 尝试录制系统或标签页声音</label>
                    <label>画质 <select id="quality"><option value="balanced" selected>均衡（1080p / 30fps）</option><option value="small">省空间（720p / 24fps）</option><option value="high">高清（1440p / 60fps）</option></select></label>
                    <span>最长 60 分钟或约 500 MB</span>
                </div>
                <div class="recorder-state"><span class="record-dot" id="recordDot"></span><strong id="recordStatus">准备录制</strong><time id="duration">00:00</time></div>
                <div class="btn-row"><button class="btn success" id="start" type="button">开始录制</button><button class="btn warning" id="stop" type="button" disabled>停止录制</button><button class="btn" id="download" type="button" disabled>下载视频</button></div>
                <p class="helper" id="tip" role="status">开始后，浏览器会让你选择屏幕、窗口或标签页。成品为 WebM 或 MP4，取决于浏览器支持。</p>
            </div>
            <div class="tool-panel preview-panel" id="previewPanel" hidden><h3>录制预览</h3><video id="preview" controls playsinline></video></div>
            <style>
            .recorder-options{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;color:#64748b;font-size:13px}.recorder-options label{display:flex;align-items:center;gap:8px;margin:0}.recorder-options select{padding:7px 9px;border:1px solid #cbd5e1;border-radius:7px;background:#fff;color:#334155}.recorder-state{display:flex;align-items:center;justify-content:center;gap:12px;margin:24px 0;padding:20px;border-radius:12px;background:#f8fafc}.record-dot{width:14px;height:14px;border-radius:50%;background:#94a3b8}.record-dot.active{background:#ef4444;box-shadow:0 0 0 7px rgba(239,68,68,.14);animation:pulse 1.2s infinite}.recorder-state time{font:700 22px Consolas,monospace;color:#334155}.helper{color:#64748b;font-size:12px}.preview-panel video{display:block;width:100%;max-height:560px;border-radius:10px;background:#0f172a}@keyframes pulse{50%{opacity:.45}}
            </style>
            <script>
            (function(){
                const $=id=>document.getElementById(id);
                const MAX_RECORDING_BYTES=500*1024*1024;
                const MAX_RECORDING_SECONDS=3600;
                const presets={
                    small:{width:1280,height:720,fps:24,videoBitsPerSecond:2500000},
                    balanced:{width:1920,height:1080,fps:30,videoBitsPerSecond:4500000},
                    high:{width:2560,height:1440,fps:60,videoBitsPerSecond:8000000}
                };
                let stream=null;
                let recorder=null;
                let chunks=[];
                let recordedBytes=0;
                let videoUrl='';
                let videoBlob=null;
                let startedAt=0;
                let timer=0;
                let stopReason='manual';
                let finishing=false;

                function format(seconds){
                    const hours=Math.floor(seconds/3600);
                    const minutes=Math.floor(seconds%3600/60);
                    const remaining=seconds%60;
                    return hours>0
                        ? String(hours).padStart(2,'0')+':'+String(minutes).padStart(2,'0')+':'+String(remaining).padStart(2,'0')
                        : String(minutes).padStart(2,'0')+':'+String(remaining).padStart(2,'0');
                }
                function size(bytes){return (bytes/1024/1024).toFixed(1)+' MB';}
                function setRecordingUi(active){
                    $('recordDot').classList.toggle('active',active);
                    $('recordStatus').textContent=active?'正在录制':'准备录制';
                    $('start').disabled=active;
                    $('stop').disabled=!active;
                    $('quality').disabled=active;
                    $('includeAudio').disabled=active;
                }
                function releaseResult(){
                    if(videoUrl)URL.revokeObjectURL(videoUrl);
                    videoUrl='';
                    videoBlob=null;
                    const preview=$('preview');
                    preview.pause();
                    preview.removeAttribute('src');
                    preview.load();
                    $('previewPanel').hidden=true;
                    $('download').disabled=true;
                }
                function stopTracks(){
                    if(stream)stream.getTracks().forEach(track=>track.stop());
                    stream=null;
                }
                function updateTimer(){
                    const seconds=Math.floor((Date.now()-startedAt)/1000);
                    $('duration').textContent=format(seconds);
                    $('tip').textContent='已录制 '+format(seconds)+'，当前约 '+size(recordedBytes)+'；内容只保存在浏览器内存中。';
                    if(seconds>=MAX_RECORDING_SECONDS)stop('duration-limit');
                }
                function supportedMimeType(){
                    const types=['video/webm;codecs=vp9,opus','video/webm;codecs=vp8,opus','video/webm','video/mp4;codecs=avc1.42E01E,mp4a.40.2','video/mp4'];
                    return types.find(type=>window.MediaRecorder&&MediaRecorder.isTypeSupported(type))||'';
                }
                function createRecorder(source,preset){
                    const mimeType=supportedMimeType();
                    const options={videoBitsPerSecond:preset.videoBitsPerSecond,audioBitsPerSecond:128000};
                    if(mimeType)options.mimeType=mimeType;
                    try{return new MediaRecorder(source,options);}catch(error){return new MediaRecorder(source,mimeType?{mimeType}:undefined);}
                }
                async function start(){
                    if(!navigator.mediaDevices||!navigator.mediaDevices.getDisplayMedia||!window.MediaRecorder){
                        $('tip').textContent='当前浏览器不支持屏幕录制，请使用最新版 Chrome、Edge 或 Firefox。';
                        return;
                    }
                    releaseResult();
                    chunks=[];
                    recordedBytes=0;
                    stopReason='manual';
                    finishing=false;
                    $('duration').textContent='00:00';
                    $('start').disabled=true;
                    try{
                        const preset=presets[$('quality').value]||presets.balanced;
                        stream=await navigator.mediaDevices.getDisplayMedia({
                            video:{width:{ideal:preset.width},height:{ideal:preset.height},frameRate:{ideal:preset.fps,max:preset.fps}},
                            audio:$('includeAudio').checked
                        });
                        recorder=createRecorder(stream,preset);
                        recorder.addEventListener('dataavailable',event=>{
                            if(!event.data.size)return;
                            chunks.push(event.data);
                            recordedBytes+=event.data.size;
                            if(recordedBytes>=MAX_RECORDING_BYTES)stop('size-limit');
                        });
                        recorder.addEventListener('error',event=>{
                            stopReason='error';
                            $('tip').textContent='录制发生错误：'+(event.error?.message||event.error?.name||'未知错误')+'，正在保留已录内容。';
                        });
                        recorder.addEventListener('stop',finish,{once:true});
                        const videoTrack=stream.getVideoTracks()[0];
                        if(!videoTrack)throw new Error('没有获得可录制的视频轨道');
                        videoTrack.addEventListener('ended',()=>stop('sharing-ended'),{once:true});
                        recorder.start(1000);
                        startedAt=Date.now();
                        timer=setInterval(updateTimer,500);
                        setRecordingUi(true);
                        const audioStatus=stream.getAudioTracks().length?'，已包含声音':'，未获得声音轨道';
                        $('tip').textContent='正在录制'+audioStatus+'；内容只保存在当前浏览器内存中。';
                    }catch(error){
                        clearInterval(timer);
                        timer=0;
                        stopTracks();
                        recorder=null;
                        setRecordingUi(false);
                        $('tip').textContent=error.name==='NotAllowedError'?'你取消了屏幕共享，未开始录制。':'无法开始录制：'+error.message;
                    }
                }
                function stop(reason='manual'){
                    if(!recorder||recorder.state==='inactive'||finishing)return;
                    if(stopReason==='manual')stopReason=reason;
                    finishing=true;
                    $('stop').disabled=true;
                    $('recordStatus').textContent='正在生成视频';
                    if(stopReason==='duration-limit')$('tip').textContent='已达到 60 分钟上限，正在生成视频。';
                    if(stopReason==='size-limit')$('tip').textContent='已接近 500 MB 内存上限，正在生成视频。';
                    recorder.stop();
                }
                function finish(){
                    clearInterval(timer);
                    timer=0;
                    stopTracks();
                    setRecordingUi(false);
                    recorder=null;
                    finishing=false;
                    videoBlob=new Blob(chunks,{type:chunks[0]?.type||'video/webm'});
                    chunks=[];
                    recordedBytes=videoBlob.size;
                    if(!videoBlob.size){
                        $('recordStatus').textContent='录制失败';
                        $('tip').textContent='浏览器没有生成可用视频，请重新选择录制来源。';
                        return;
                    }
                    videoUrl=URL.createObjectURL(videoBlob);
                    $('preview').src=videoUrl;
                    $('previewPanel').hidden=false;
                    $('download').disabled=false;
                    $('recordStatus').textContent=stopReason==='error'?'录制异常结束':'录制完成';
                    const reasonText=stopReason==='size-limit'?'（达到内存上限）':stopReason==='duration-limit'?'（达到时长上限）':stopReason==='sharing-ended'?'（已停止共享）':'';
                    $('tip').textContent='录制完成'+reasonText+'，大小 '+size(videoBlob.size)+'。关闭页面前请下载保存。';
                }
                $('start').addEventListener('click',start);
                $('stop').addEventListener('click',()=>stop('manual'));
                $('download').addEventListener('click',()=>{
                    if(!videoUrl||!videoBlob)return;
                    const link=document.createElement('a');
                    const extension=videoBlob.type.includes('mp4')?'mp4':'webm';
                    link.href=videoUrl;
                    link.download='屏幕录制-'+new Date().toISOString().slice(0,19).replace(/[:T]/g,'-')+'.'+extension;
                    link.click();
                });
                window.addEventListener('beforeunload',()=>{
                    clearInterval(timer);
                    stopTracks();
                    if(videoUrl)URL.revokeObjectURL(videoUrl);
                });
            })();
            </script>
<?php include '_footer.php'; ?>
