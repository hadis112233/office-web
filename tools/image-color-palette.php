<?php
$title = '图片取色与调色板';
$desc = '从本地图片点击取色并自动提取主色，支持 HEX、RGB 和 HSL；图片只在浏览器中处理，不会上传服务器。';
include '_header.php';
?>
            <style>
            .picker-layout{display:grid;grid-template-columns:minmax(0,1.45fr) minmax(280px,.75fr);gap:18px}
            .drop-zone{display:flex;min-height:130px;align-items:center;justify-content:center;text-align:center;border:2px dashed #c7d2e4;border-radius:12px;padding:20px;cursor:pointer;transition:.2s;background:rgba(248,250,252,.72)}
            .drop-zone.dragging{border-color:#6366f1;background:rgba(99,102,241,.08)}
            .drop-zone strong{display:block;margin-bottom:7px;color:#334155}.drop-zone small{color:#64748b}
            .preview-wrap{position:relative;display:none;margin-top:16px;padding:10px;border:1px solid #dbe3ef;border-radius:12px;background:repeating-conic-gradient(#eef2f7 0 25%,#fff 0 50%) 50%/20px 20px;overflow:auto;text-align:center}
            #previewCanvas{display:block;max-width:100%;height:auto;margin:auto;cursor:crosshair;border-radius:6px;touch-action:none}
            .color-card{display:grid;grid-template-columns:78px minmax(0,1fr);gap:14px;align-items:center}
            #currentSwatch{width:78px;height:78px;border-radius:13px;border:1px solid rgba(15,23,42,.14);box-shadow:inset 0 0 0 1px rgba(255,255,255,.3)}
            .color-values{display:grid;gap:7px}.value-row{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:7px 9px;border:1px solid #e2e8f0;border-radius:8px;background:rgba(255,255,255,.72)}
            .value-row code{overflow:hidden;text-overflow:ellipsis}.copy-btn{padding:4px 8px;border:0;border-radius:6px;background:#eef2ff;color:#4f46e5;cursor:pointer;white-space:nowrap}
            .palette-head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:20px}.palette-controls{display:flex;align-items:center;gap:8px}
            #paletteSize{width:105px}.palette-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:9px;margin-top:11px}
            .palette-swatch{display:flex;align-items:center;gap:9px;min-width:0;padding:7px;border:1px solid #e2e8f0;border-radius:9px;background:rgba(255,255,255,.78);cursor:pointer;text-align:left}
            .palette-swatch span:first-child{width:38px;height:38px;flex:0 0 38px;border-radius:7px;border:1px solid rgba(15,23,42,.12)}.palette-swatch code{overflow:hidden;text-overflow:ellipsis}
            .empty-palette{grid-column:1/-1;color:#64748b;padding:10px 0}.privacy-note{margin-top:12px;color:#64748b;font-size:13px}
            html.theme-dark .drop-zone,html.theme-dark .value-row,html.theme-dark .palette-swatch{background:rgba(30,41,59,.72);border-color:#475569}
            html.theme-dark .drop-zone strong{color:#e2e8f0}html.theme-dark .preview-wrap{border-color:#475569}html.theme-dark .copy-btn{background:#312e81;color:#c7d2fe}
            @media(max-width:780px){.picker-layout{grid-template-columns:1fr}.palette-grid{grid-template-columns:1fr}.color-card{grid-template-columns:64px minmax(0,1fr)}#currentSwatch{width:64px;height:64px}.palette-head{align-items:flex-start;flex-direction:column}.palette-controls{width:100%}#paletteSize{flex:1}}
            </style>
            <div class="picker-layout">
                <div class="tool-panel">
                    <label class="drop-zone" id="dropZone" for="imageFile">
                        <span><strong>点击、拖放或粘贴图片</strong><small>支持 JPG、PNG、WebP、GIF、BMP，最大 25 MB</small></span>
                    </label>
                    <input type="file" id="imageFile" accept="image/jpeg,image/png,image/webp,image/gif,image/bmp" hidden>
                    <div class="preview-wrap" id="previewWrap"><canvas id="previewCanvas" aria-label="图片取色画布"></canvas></div>
                    <p class="tip" id="status" role="status">选择图片后，点击预览中的任意位置即可取色。</p>
                    <div class="btn-row"><button class="btn secondary" id="resetButton" type="button" disabled>重新选择</button></div>
                </div>
                <div class="tool-panel">
                    <h2 style="margin-top:0">当前颜色</h2>
                    <div class="color-card">
                        <div id="currentSwatch" style="background:#6366f1"></div>
                        <div class="color-values">
                            <div class="value-row"><code id="hexValue">#6366F1</code><button class="copy-btn" data-copy="hexValue" type="button">复制</button></div>
                            <div class="value-row"><code id="rgbValue">rgb(99, 102, 241)</code><button class="copy-btn" data-copy="rgbValue" type="button">复制</button></div>
                            <div class="value-row"><code id="hslValue">hsl(239, 84%, 67%)</code><button class="copy-btn" data-copy="hslValue" type="button">复制</button></div>
                        </div>
                    </div>
                    <div class="palette-head">
                        <h2 style="margin:0">主色调色板</h2>
                        <div class="palette-controls"><label for="paletteSize">颜色数</label><input id="paletteSize" type="range" min="3" max="10" value="6"><output id="paletteCount">6</output></div>
                    </div>
                    <div class="palette-grid" id="paletteGrid"><div class="empty-palette">选择图片后自动生成</div></div>
                    <p class="privacy-note">🔒 全程本地处理；为保证流畅，大图会等比例缩小后分析。</p>
                </div>
            </div>
            <script>
            (()=>{
                'use strict';
                const MAX_FILE_BYTES=25*1024*1024;
                const MAX_SOURCE_PIXELS=60000000;
                const MAX_CANVAS_PIXELS=5000000;
                const MAX_CANVAS_SIDE=4096;
                const MAX_SAMPLES=120000;
                const allowedTypes=new Set(['image/jpeg','image/png','image/webp','image/gif','image/bmp']);
                const $=id=>document.getElementById(id);
                const fileInput=$('imageFile'),dropZone=$('dropZone'),canvas=$('previewCanvas'),ctx=canvas.getContext('2d',{willReadFrequently:true});
                let sourceUrl='';

                const rgbToHex=(r,g,b)=>'#'+[r,g,b].map(value=>value.toString(16).padStart(2,'0')).join('').toUpperCase();
                function rgbToHsl(r,g,b){
                    r/=255;g/=255;b/=255;const max=Math.max(r,g,b),min=Math.min(r,g,b);let h=0,s=0;const l=(max+min)/2;
                    if(max!==min){const d=max-min;s=l>.5?d/(2-max-min):d/(max+min);if(max===r)h=(g-b)/d+(g<b?6:0);else if(max===g)h=(b-r)/d+2;else h=(r-g)/d+4;h/=6;}
                    return [Math.round(h*360),Math.round(s*100),Math.round(l*100)];
                }
                function setColor(r,g,b){
                    const hex=rgbToHex(r,g,b),hsl=rgbToHsl(r,g,b);
                    $('currentSwatch').style.background=hex;$('hexValue').textContent=hex;$('rgbValue').textContent=`rgb(${r}, ${g}, ${b})`;$('hslValue').textContent=`hsl(${hsl[0]}, ${hsl[1]}%, ${hsl[2]}%)`;
                }
                async function copyText(value,button){
                    try{if(navigator.clipboard&&window.isSecureContext)await navigator.clipboard.writeText(value);else{const area=document.createElement('textarea');area.value=value;area.style.position='fixed';area.style.opacity='0';document.body.appendChild(area);area.select();document.execCommand('copy');area.remove();}const old=button.textContent;button.textContent='已复制';setTimeout(()=>button.textContent=old,1000);}catch(error){$('status').textContent='复制失败，请手动选择色值。';}
                }
                function renderPalette(colors){
                    const grid=$('paletteGrid');grid.replaceChildren();
                    colors.forEach(color=>{const hex=rgbToHex(...color);const button=document.createElement('button');button.type='button';button.className='palette-swatch';button.title='点击复制 '+hex;const swatch=document.createElement('span');swatch.style.background=hex;const code=document.createElement('code');code.textContent=hex;button.append(swatch,code);button.addEventListener('click',()=>{setColor(...color);copyText(hex,code);});grid.appendChild(button);});
                    if(!colors.length){const empty=document.createElement('div');empty.className='empty-palette';empty.textContent='未找到可用颜色';grid.appendChild(empty);}
                }
                function extractPalette(){
                    if(!canvas.width)return;
                    const data=ctx.getImageData(0,0,canvas.width,canvas.height).data,total=canvas.width*canvas.height,step=Math.max(1,Math.ceil(total/MAX_SAMPLES));
                    const buckets=new Map();
                    for(let pixel=0;pixel<total;pixel+=step){const i=pixel*4;if(data[i+3]<128)continue;const r=data[i],g=data[i+1],b=data[i+2];const key=((r>>4)<<8)|((g>>4)<<4)|(b>>4);const bucket=buckets.get(key)||[0,0,0,0];bucket[0]+=r;bucket[1]+=g;bucket[2]+=b;bucket[3]++;buckets.set(key,bucket);}
                    const candidates=[...buckets.values()].sort((a,b)=>b[3]-a[3]).map(v=>[Math.round(v[0]/v[3]),Math.round(v[1]/v[3]),Math.round(v[2]/v[3]),v[3]]);
                    const wanted=Number($('paletteSize').value),picked=[];
                    for(const candidate of candidates){if(picked.every(color=>Math.hypot(candidate[0]-color[0],candidate[1]-color[1],candidate[2]-color[2])>=42)){picked.push(candidate);if(picked.length===wanted)break;}}
                    renderPalette(picked.map(color=>color.slice(0,3)));if(picked[0])setColor(...picked[0]);
                }
                function reset(){if(sourceUrl)URL.revokeObjectURL(sourceUrl);sourceUrl='';fileInput.value='';canvas.width=0;canvas.height=0;$('previewWrap').style.display='none';$('resetButton').disabled=true;renderPalette([]);$('status').textContent='选择图片后，点击预览中的任意位置即可取色。';}
                function loadFile(file){
                    if(!file)return;if(!allowedTypes.has(file.type)||file.size>MAX_FILE_BYTES){$('status').textContent='请选择 25 MB 以内的 JPG、PNG、WebP、GIF 或 BMP 图片。';return;}
                    if(sourceUrl)URL.revokeObjectURL(sourceUrl);sourceUrl=URL.createObjectURL(file);const image=new Image();
                    image.onload=()=>{const sourcePixels=image.naturalWidth*image.naturalHeight;if(!image.naturalWidth||sourcePixels>MAX_SOURCE_PIXELS){$('status').textContent='图片像素过大（上限 6000 万像素），为保护浏览器已停止读取。';URL.revokeObjectURL(sourceUrl);sourceUrl='';return;}const scale=Math.min(1,MAX_CANVAS_SIDE/image.naturalWidth,MAX_CANVAS_SIDE/image.naturalHeight,Math.sqrt(MAX_CANVAS_PIXELS/sourcePixels));canvas.width=Math.max(1,Math.round(image.naturalWidth*scale));canvas.height=Math.max(1,Math.round(image.naturalHeight*scale));ctx.clearRect(0,0,canvas.width,canvas.height);ctx.drawImage(image,0,0,canvas.width,canvas.height);$('previewWrap').style.display='block';$('resetButton').disabled=false;$('status').textContent=`已载入 ${image.naturalWidth} × ${image.naturalHeight}，点击图片可取色。`;extractPalette();URL.revokeObjectURL(sourceUrl);sourceUrl='';};
                    image.onerror=()=>{$('status').textContent='图片读取失败或文件已损坏。';URL.revokeObjectURL(sourceUrl);sourceUrl='';};image.src=sourceUrl;
                }
                fileInput.addEventListener('change',event=>loadFile(event.target.files[0]));
                ['dragenter','dragover'].forEach(name=>dropZone.addEventListener(name,event=>{event.preventDefault();dropZone.classList.add('dragging');}));
                ['dragleave','drop'].forEach(name=>dropZone.addEventListener(name,event=>{event.preventDefault();dropZone.classList.remove('dragging');}));
                dropZone.addEventListener('drop',event=>loadFile([...event.dataTransfer.files].find(file=>file.type.startsWith('image/'))));
                document.addEventListener('paste',event=>{const file=[...event.clipboardData.files].find(item=>item.type.startsWith('image/'));if(file){event.preventDefault();loadFile(file);}});
                canvas.addEventListener('pointerdown',event=>{if(!canvas.width)return;const rect=canvas.getBoundingClientRect(),x=Math.min(canvas.width-1,Math.max(0,Math.floor((event.clientX-rect.left)*canvas.width/rect.width))),y=Math.min(canvas.height-1,Math.max(0,Math.floor((event.clientY-rect.top)*canvas.height/rect.height))),pixel=ctx.getImageData(x,y,1,1).data;setColor(pixel[0],pixel[1],pixel[2]);$('status').textContent=`取色位置：${x}, ${y}`;});
                $('paletteSize').addEventListener('input',event=>{$('paletteCount').textContent=event.target.value;if(canvas.width)extractPalette();});
                document.querySelectorAll('[data-copy]').forEach(button=>button.addEventListener('click',()=>copyText($(button.dataset.copy).textContent,button)));
                $('resetButton').addEventListener('click',reset);window.addEventListener('beforeunload',()=>{if(sourceUrl)URL.revokeObjectURL(sourceUrl);});
            })();
            </script>
<?php include '_footer.php'; ?>
