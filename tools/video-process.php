<?php
$title = '视频转 GIF / 视频压缩';
$desc = '视频在本站服务器内处理，生成后可下载，临时文件最长保留 1 小时。';
include '_header.php';
?>
            <div class="video-tabs" role="tablist">
                <button class="video-tab" data-mode="gif" role="tab" type="button">🎞️ 视频转 GIF</button>
                <button class="video-tab" data-mode="compress" role="tab" type="button">🗜️ 视频压缩</button>
            </div>
            <div class="tool-panel video-panel">
                <label class="video-drop" id="video-drop"><input id="video-file" type="file" accept="video/mp4,video/webm,video/quicktime,video/x-m4v" hidden><span class="video-drop-icon">🎬</span><strong>点击选择视频，或拖拽到这里</strong><small>支持 MP4 / WebM / MOV / M4V，单个文件最大 150 MB</small></label>
                <div class="video-file-info" id="video-file-info" hidden></div>
                <div id="gif-options" class="video-options">
                    <label>截取开始时间（秒）<input id="gif-start" type="number" min="0" max="3600" value="0"></label>
                    <label>截取时长（秒，最多 20 秒）<input id="gif-duration" type="number" min="1" max="20" value="5"></label>
                    <label>帧率<input id="gif-fps" type="number" min="5" max="20" value="10"></label>
                    <label>输出宽度<input id="gif-width" type="number" min="160" max="960" value="480"></label>
                </div>
                <div id="compress-options" class="video-options" hidden><label>压缩质量<select id="video-quality"><option value="high">高质量（文件较大）</option><option value="standard" selected>中等质量（推荐）</option><option value="small">更小文件（清晰度较低）</option></select></label></div>
                <div class="btn-row"><button class="btn success" id="process-video" type="button">开始处理</button></div>
                <div class="video-progress" id="video-progress" hidden><div><span id="video-progress-bar"></span></div><p id="video-progress-text">准备上传…</p></div>
                <div class="video-result" id="video-result" hidden><strong>✅ 处理完成</strong><span id="video-result-meta"></span><a class="btn success" id="video-download" href="#">下载文件（下载后即删除）</a></div>
                <p class="tip">请勿上传包含敏感信息的视频。下载完成后服务端会立刻删除成品，未下载文件会在 1 小时后自动清理。</p>
            </div>
            <style>
            .video-tabs{display:flex;gap:10px;margin:0 auto 16px;max-width:820px}.video-tab{flex:1;padding:12px;border:1px solid #dbe3f0;border-radius:10px;background:#fff;color:#475569;font:inherit;font-weight:600;cursor:pointer}.video-tab.active{border-color:#6366f1;color:#fff;background:linear-gradient(135deg,#6366f1,#8b5cf6)}.video-panel{max-width:820px;margin:auto}.video-drop{display:flex;min-height:180px;flex-direction:column;align-items:center;justify-content:center;gap:8px;padding:22px;border:2px dashed #c7d2fe;border-radius:14px;color:#475569;cursor:pointer;background:#fafaff;text-align:center}.video-drop.drag-over{border-color:#6366f1;background:#eef2ff}.video-drop-icon{font-size:42px}.video-drop small{color:#94a3b8}.video-file-info{margin-top:14px;padding:11px 13px;border-radius:8px;color:#1e40af;background:#eff6ff;word-break:break-all}.video-options{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;margin-top:18px}.video-options label{font-size:13px;color:#475569}.video-options input,.video-options select{display:block;width:100%;margin-top:6px;padding:10px;border:1px solid #dbe3f0;border-radius:8px;background:#fff;font:inherit}.video-progress{margin-top:16px}.video-progress>div{height:9px;overflow:hidden;border-radius:9px;background:#e2e8f0}.video-progress span{display:block;width:4%;height:100%;border-radius:inherit;background:linear-gradient(90deg,#6366f1,#a855f7);transition:width .25s}.video-progress p{margin-top:7px;color:#64748b;font-size:13px}.video-result{display:flex;flex-direction:column;align-items:flex-start;gap:10px;margin-top:16px;padding:16px;border:1px solid #a7f3d0;border-radius:10px;color:#065f46;background:#ecfdf5}.video-result .btn{color:#fff;text-decoration:none}@media(max-width:600px){.video-options{grid-template-columns:1fr}.video-tabs{flex-direction:column}}
            </style>
            <script>
            (function(){
                const $=id=>document.getElementById(id);let file=null;let mode=new URLSearchParams(location.search).get('mode')==='compress'?'compress':'gif';
                function size(bytes){return bytes<1024*1024?(bytes/1024).toFixed(1)+' KB':(bytes/1024/1024).toFixed(2)+' MB'}
                function setMode(next){mode=next;document.querySelectorAll('.video-tab').forEach(btn=>btn.classList.toggle('active',btn.dataset.mode===mode));$('gif-options').hidden=mode!=='gif';$('compress-options').hidden=mode!=='compress';$('process-video').textContent=mode==='gif'?'开始转 GIF':'开始压缩'}
                function setFile(next){if(!next)return;if(next.size>150*1024*1024)return alert('视频不能超过 150 MB');file=next;$('video-file-info').hidden=false;$('video-file-info').textContent='已选择：'+file.name+'（'+size(file.size)+'）';$('video-result').hidden=true}
                document.querySelectorAll('.video-tab').forEach(btn=>btn.addEventListener('click',()=>setMode(btn.dataset.mode)));$('video-file').addEventListener('change',event=>setFile(event.target.files[0]));['dragenter','dragover'].forEach(event=>$('video-drop').addEventListener(event,e=>{e.preventDefault();$('video-drop').classList.add('drag-over')}));['dragleave','drop'].forEach(event=>$('video-drop').addEventListener(event,e=>{e.preventDefault();$('video-drop').classList.remove('drag-over')}));$('video-drop').addEventListener('drop',event=>setFile(event.dataTransfer.files[0]));
                $('process-video').addEventListener('click',()=>{if(!file)return alert('请先选择视频');const form=new FormData();form.append('video',file);form.append('mode',mode);if(mode==='gif'){['start','duration','fps','width'].forEach(name=>form.append(name,$('gif-'+name).value))}else form.append('quality',$('video-quality').value);const progress=$('video-progress'),bar=$('video-progress-bar'),text=$('video-progress-text'),button=$('process-video');progress.hidden=false;$('video-result').hidden=true;bar.style.width='3%';text.textContent='正在上传视频…';button.disabled=true;const xhr=new XMLHttpRequest();xhr.open('POST','../api/media.php?action=process');xhr.timeout=250000;xhr.upload.onprogress=e=>{if(e.lengthComputable){bar.style.width=Math.max(3,Math.round(e.loaded/e.total*65))+'%';text.textContent='正在上传视频…'}};xhr.upload.onload=()=>{bar.style.width='70%';text.textContent='上传完成，服务器正在处理…'};xhr.onload=()=>{button.disabled=false;bar.style.width='100%';try{const data=JSON.parse(xhr.responseText);if(!data.ok)throw new Error(data.error||'处理失败');text.textContent='处理完成';const saved=data.saved_percent>0?'，节省 '+data.saved_percent+'%':'';$('video-result-meta').textContent='原文件：'+size(data.input_size)+' → 成品：'+size(data.size)+saved;$('video-download').href='../api/'+data.download_url;$('video-download').download=data.name;$('video-result').hidden=false}catch(error){progress.hidden=true;alert(error.message||'处理失败，请稍后重试')}};xhr.onerror=()=>{button.disabled=false;progress.hidden=true;alert('网络异常，请重试')};xhr.ontimeout=()=>{button.disabled=false;progress.hidden=true;alert('处理等待超过 250 秒，请缩短视频或降低分辨率后重试')};xhr.send(form)});setMode(mode);
            })();
            </script>
<?php include '_footer.php'; ?>
