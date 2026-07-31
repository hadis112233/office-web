<?php
$title = '图片拼接';
$desc = '将多张图片纵向、横向或按网格拼接为一张图片，适合长截图、报表和图片合集。图片只在浏览器中处理。';
include '_header.php';
?>
            <div class="tool-panel">
                <label for="files">选择图片（最多 20 张，总计不超过 100 MB）</label><input id="files" type="file" accept="image/jpeg,image/png,image/webp" multiple>
                <div class="stitch-options">
                    <label>拼接方式 <select id="mode"><option value="vertical">纵向长图</option><option value="horizontal">横向长图</option><option value="grid">网格排列</option></select></label>
                    <label>目标尺寸 <input id="targetSize" type="number" min="300" max="5000" value="1200"><span id="sizeHint">输出宽度（px）</span></label>
                    <label id="columnsLabel" hidden>网格列数 <input id="columns" type="number" min="2" max="6" value="3"></label>
                    <label>图片间距 <input id="gap" type="number" min="0" max="100" value="10"> px</label>
                    <label>背景颜色 <input id="background" type="color" value="#ffffff"></label>
                    <label>输出格式 <select id="format"><option value="image/jpeg">JPG</option><option value="image/png">PNG</option></select></label>
                </div>
                <div class="btn-row"><button class="btn success" id="generate" type="button">生成拼接图</button><button class="btn" id="download" type="button" disabled>下载图片</button><button class="btn secondary" id="clear" type="button">清空</button></div>
                <p class="helper" id="status" role="status">图片按选择顺序拼接，超大结果会自动等比缩小以保护浏览器内存。</p>
            </div>
            <div class="tool-panel" id="previewPanel" hidden><h3>拼接预览</h3><div class="stitch-preview"><img id="preview" alt="拼接结果预览"></div></div>
            <style>
            .stitch-options{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin:14px 0}.stitch-options label{display:grid;align-content:start;gap:7px}.stitch-options label span{color:#64748b;font-size:11px}.stitch-options input,.stitch-options select{width:100%}.stitch-preview{max-height:650px;overflow:auto;text-align:center;border:1px dashed #cbd5e1;border-radius:10px;background:#e2e8f0}.stitch-preview img{display:block;max-width:100%;height:auto;margin:auto}.helper{color:#64748b;font-size:12px}@media(max-width:700px){.stitch-options{grid-template-columns:1fr 1fr}}@media(max-width:460px){.stitch-options{grid-template-columns:1fr}}
            </style>
            <script>
            (function(){
                const $=id=>document.getElementById(id);let files=[],resultUrl='',resultBlob=null,resultFormat='';
                function releaseResult(){if(resultUrl)URL.revokeObjectURL(resultUrl);resultUrl='';resultBlob=null;resultFormat='';$('download').disabled=true;}
                $('files').addEventListener('change',event=>{const selected=Array.from(event.target.files||[]).filter(file=>['image/jpeg','image/png','image/webp'].includes(file.type));if(selected.length>20||selected.reduce((sum,file)=>sum+file.size,0)>100*1024*1024){$('status').textContent='最多选择 20 张且总计不超过 100 MB。';event.target.value='';return;}files=selected;$('status').textContent=files.length?'已选择 '+files.length+' 张图片。':'请先选择图片。';releaseResult();});
                $('mode').addEventListener('change',()=>{const grid=$('mode').value==='grid';$('columnsLabel').hidden=!grid;$('sizeHint').textContent=$('mode').value==='horizontal'?'输出高度（px）':'输出宽度（px）';});
                function load(file){return new Promise((resolve,reject)=>{const url=URL.createObjectURL(file),image=new Image();image.onload=()=>{URL.revokeObjectURL(url);resolve(image);};image.onerror=()=>{URL.revokeObjectURL(url);reject(new Error('无法读取 '+file.name));};image.src=url;});}
                $('generate').addEventListener('click',async()=>{if(!files.length){$('status').textContent='请先选择图片。';return;}const target=Math.trunc(Number($('targetSize').value)),gap=Math.trunc(Number($('gap').value));if(!Number.isFinite(target)||target<300||target>5000||!Number.isFinite(gap)||gap<0||gap>100){$('status').textContent='目标尺寸需为 300–5000，间距需为 0–100。';return;}const button=$('generate');button.disabled=true;releaseResult();try{$('status').textContent='正在读取图片…';const images=await Promise.all(files.map(load)),mode=$('mode').value,columns=Math.max(2,Math.min(6,Math.trunc(Number($('columns').value))||3));let width,height,placements=[];if(mode==='vertical'){width=target;let y=0;images.forEach(image=>{const h=image.naturalHeight*target/image.naturalWidth;placements.push({image,x:0,y,w:target,h});y+=h+gap;});height=y-gap;}else if(mode==='horizontal'){height=target;let x=0;images.forEach(image=>{const w=image.naturalWidth*target/image.naturalHeight;placements.push({image,x,y:0,w,h:target});x+=w+gap;});width=x-gap;}else{const cell=(target-gap*(columns-1))/columns;if(cell<10)throw new Error('目标宽度过小或间距过大，无法生成网格');const rows=Math.ceil(images.length/columns);width=target;height=rows*cell+gap*(rows-1);images.forEach((image,index)=>{const ratio=Math.min(cell/image.naturalWidth,cell/image.naturalHeight),w=image.naturalWidth*ratio,h=image.naturalHeight*ratio,column=index%columns,row=Math.floor(index/columns);placements.push({image,x:column*(cell+gap)+(cell-w)/2,y:row*(cell+gap)+(cell-h)/2,w,h});});}const safeScale=Math.min(1,30000/width,30000/height,Math.sqrt(80000000/(width*height)));const canvas=document.createElement('canvas');canvas.width=Math.max(1,Math.floor(width*safeScale));canvas.height=Math.max(1,Math.floor(height*safeScale));const ctx=canvas.getContext('2d');ctx.fillStyle=$('background').value;ctx.fillRect(0,0,canvas.width,canvas.height);placements.forEach(place=>ctx.drawImage(place.image,place.x*safeScale,place.y*safeScale,place.w*safeScale,place.h*safeScale));resultFormat=$('format').value;resultBlob=await new Promise(resolve=>canvas.toBlob(resolve,resultFormat,resultFormat==='image/jpeg'?.9:undefined));if(!resultBlob)throw new Error('浏览器未能生成图片');resultUrl=URL.createObjectURL(resultBlob);$('preview').src=resultUrl;$('previewPanel').hidden=false;$('download').disabled=false;$('status').textContent='生成完成：'+canvas.width+' × '+canvas.height+' px，'+(resultBlob.size/1024/1024).toFixed(2)+' MB。';}catch(error){$('status').textContent='拼接失败：'+error.message;}finally{button.disabled=false;}});
                $('download').addEventListener('click',()=>{if(!resultUrl)return;const a=document.createElement('a');a.href=resultUrl;a.download='拼接图片.'+(resultFormat==='image/png'?'png':'jpg');a.click();});$('clear').addEventListener('click',()=>{files=[];$('files').value='';releaseResult();$('preview').removeAttribute('src');$('previewPanel').hidden=true;$('status').textContent='图片按选择顺序拼接，超大结果会自动等比缩小以保护浏览器内存。';});window.addEventListener('beforeunload',releaseResult);
            })();
            </script>
<?php include '_footer.php'; ?>
