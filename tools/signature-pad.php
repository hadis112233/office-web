<?php
$title = '电子签名板';
$desc = '使用鼠标、触控笔或手指书写签名，并导出透明或白底 PNG 图片。签名不会上传服务器。';
include '_header.php';
?>
            <div class="tool-panel signature-panel">
                <div class="signature-tools">
                    <label>笔迹颜色 <input id="penColor" type="color" value="#111827"></label>
                    <label>笔画粗细 <input id="penWidth" type="range" min="1" max="12" value="4"><span id="widthValue">4px</span></label>
                    <label><input id="whiteBackground" type="checkbox"> 导出白色背景</label>
                </div>
                <div class="canvas-wrap"><canvas id="signature" aria-label="电子签名绘制区域"></canvas><span id="placeholder">请在此处签名</span></div>
                <div class="btn-row"><button class="btn" id="undo" type="button">撤销</button><button class="btn secondary" id="clear" type="button">清空</button><button class="btn success" id="download" type="button">下载 PNG</button></div>
                <p class="helper" id="status" role="status">可使用鼠标、触控笔或手指书写，内容仅保留在当前页面。</p>
            </div>
            <style>
            .signature-tools{display:flex;align-items:center;flex-wrap:wrap;gap:12px 22px;margin-bottom:14px}.signature-tools label{display:flex;align-items:center;gap:8px;margin:0}.signature-tools input[type=color]{width:44px;height:34px;padding:2px}.canvas-wrap{position:relative;height:320px;border:2px dashed #cbd5e1;border-radius:12px;background-image:linear-gradient(#f1f5f9 1px,transparent 1px);background-size:100% 48px;overflow:hidden}.canvas-wrap canvas{display:block;width:100%;height:100%;touch-action:none;cursor:crosshair}.canvas-wrap>span{position:absolute;inset:50% auto auto 50%;transform:translate(-50%,-50%);color:#94a3b8;pointer-events:none}.helper{color:#64748b;font-size:12px}@media(max-width:600px){.canvas-wrap{height:240px}}
            </style>
            <script>
            (function(){
                const canvas=document.getElementById('signature'),ctx=canvas.getContext('2d'),placeholder=document.getElementById('placeholder'),status=document.getElementById('status'),history=[];let drawing=false,hasInk=false;
                function resize(){const rect=canvas.getBoundingClientRect(),ratio=Math.max(1,window.devicePixelRatio||1),snapshot=hasInk?canvas.toDataURL():'';canvas.width=Math.round(rect.width*ratio);canvas.height=Math.round(rect.height*ratio);ctx.setTransform(ratio,0,0,ratio,0,0);ctx.lineCap='round';ctx.lineJoin='round';if(snapshot){const image=new Image();image.onload=()=>ctx.drawImage(image,0,0,rect.width,rect.height);image.src=snapshot;}}
                function point(event){const rect=canvas.getBoundingClientRect();return [event.clientX-rect.left,event.clientY-rect.top];}
                function start(event){event.preventDefault();history.push(canvas.toDataURL());if(history.length>20)history.shift();drawing=true;canvas.setPointerCapture(event.pointerId);const [x,y]=point(event);ctx.beginPath();ctx.moveTo(x,y);ctx.strokeStyle=document.getElementById('penColor').value;ctx.lineWidth=Number(document.getElementById('penWidth').value);}
                function move(event){if(!drawing)return;const [x,y]=point(event);ctx.lineTo(x,y);ctx.stroke();hasInk=true;placeholder.hidden=true;}
                function stop(){if(!drawing)return;drawing=false;ctx.closePath();}
                canvas.addEventListener('pointerdown',start);canvas.addEventListener('pointermove',move);canvas.addEventListener('pointerup',stop);canvas.addEventListener('pointercancel',stop);
                document.getElementById('penWidth').addEventListener('input',event=>document.getElementById('widthValue').textContent=event.target.value+'px');
                function clearCanvas(){ctx.clearRect(0,0,canvas.width,canvas.height);hasInk=false;placeholder.hidden=false;}
                document.getElementById('clear').addEventListener('click',()=>{if(hasInk)history.push(canvas.toDataURL());clearCanvas();});document.getElementById('undo').addEventListener('click',()=>{const snapshot=history.pop();clearCanvas();if(!snapshot)return;const image=new Image();image.onload=()=>{ctx.drawImage(image,0,0,canvas.clientWidth,canvas.clientHeight);hasInk=true;placeholder.hidden=true;};image.src=snapshot;});
                document.getElementById('download').addEventListener('click',()=>{if(!hasInk){status.textContent='请先书写签名。';return;}const output=document.createElement('canvas');output.width=canvas.width;output.height=canvas.height;const outputCtx=output.getContext('2d');if(document.getElementById('whiteBackground').checked){outputCtx.fillStyle='#fff';outputCtx.fillRect(0,0,output.width,output.height);}outputCtx.drawImage(canvas,0,0);output.toBlob(blob=>{if(!blob)return;const url=URL.createObjectURL(blob),a=document.createElement('a');a.href=url;a.download='电子签名.png';a.click();setTimeout(()=>URL.revokeObjectURL(url),1000);status.textContent='签名图片已生成。';},'image/png');});
                const observer=new ResizeObserver(()=>resize());observer.observe(canvas.parentElement);window.addEventListener('beforeunload',()=>observer.disconnect());resize();
            })();
            </script>
<?php include '_footer.php'; ?>
