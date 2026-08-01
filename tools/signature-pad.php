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
                <div class="btn-row"><button class="btn" id="undo" type="button" disabled>撤销</button><button class="btn secondary" id="clear" type="button" disabled>清空</button><button class="btn success" id="download" type="button" disabled>下载 PNG</button></div>
                <p class="helper" id="status" role="status">可使用鼠标、触控笔或手指书写，内容仅保留在当前页面。</p>
            </div>
            <style>
            .signature-tools{display:flex;align-items:center;flex-wrap:wrap;gap:12px 22px;margin-bottom:14px}.signature-tools label{display:flex;align-items:center;gap:8px;margin:0}.signature-tools input[type=color]{width:44px;height:34px;padding:2px}.canvas-wrap{position:relative;height:320px;border:2px dashed #cbd5e1;border-radius:12px;background-image:linear-gradient(#f1f5f9 1px,transparent 1px);background-size:100% 48px;overflow:hidden}.canvas-wrap canvas{display:block;width:100%;height:100%;touch-action:none;cursor:crosshair}.canvas-wrap>span{position:absolute;inset:50% auto auto 50%;transform:translate(-50%,-50%);color:#94a3b8;pointer-events:none}.helper{color:#64748b;font-size:12px}@media(max-width:600px){.canvas-wrap{height:240px}}
            </style>
            <script>
            (function(){
                const canvas=document.getElementById('signature');
                const ctx=canvas.getContext('2d');
                const placeholder=document.getElementById('placeholder');
                const status=document.getElementById('status');
                const undoButton=document.getElementById('undo');
                const clearButton=document.getElementById('clear');
                const downloadButton=document.getElementById('download');
                const strokes=[];
                const MAX_STROKES=500;
                const MAX_POINTS=100000;
                let pointCount=0;
                let currentStroke=null;
                let activePointerId=null;
                let clearedStrokes=null;
                let resizeFrame=0;

                function canvasRect(){return canvas.getBoundingClientRect();}
                function updateControls(){
                    const hasInk=strokes.length>0;
                    placeholder.hidden=hasInk;
                    undoButton.disabled=!hasInk&&!clearedStrokes;
                    clearButton.disabled=!hasInk;
                    downloadButton.disabled=!hasInk;
                }
                function configureContext(context){
                    context.lineCap='round';
                    context.lineJoin='round';
                }
                function drawStroke(context,stroke,width,height){
                    if(!stroke.points.length)return;
                    context.strokeStyle=stroke.color;
                    context.fillStyle=stroke.color;
                    context.lineWidth=stroke.width;
                    if(stroke.points.length===1){
                        const point=stroke.points[0];
                        context.beginPath();
                        context.arc(point.x*width,point.y*height,stroke.width/2,0,Math.PI*2);
                        context.fill();
                        return;
                    }
                    context.beginPath();
                    context.moveTo(stroke.points[0].x*width,stroke.points[0].y*height);
                    for(let index=1;index<stroke.points.length;index+=1){
                        const point=stroke.points[index];
                        context.lineTo(point.x*width,point.y*height);
                    }
                    context.stroke();
                }
                function drawSegment(context,stroke,from,to,width,height){
                    context.strokeStyle=stroke.color;
                    context.lineWidth=stroke.width;
                    context.beginPath();
                    context.moveTo(from.x*width,from.y*height);
                    context.lineTo(to.x*width,to.y*height);
                    context.stroke();
                }
                function redraw(){
                    const rect=canvasRect();
                    ctx.clearRect(0,0,rect.width,rect.height);
                    configureContext(ctx);
                    for(const stroke of strokes)drawStroke(ctx,stroke,rect.width,rect.height);
                    updateControls();
                }
                function resize(){
                    const rect=canvasRect();
                    if(rect.width<1||rect.height<1)return;
                    const ratio=Math.min(3,Math.max(1,window.devicePixelRatio||1));
                    const width=Math.round(rect.width*ratio);
                    const height=Math.round(rect.height*ratio);
                    if(canvas.width===width&&canvas.height===height)return;
                    canvas.width=width;
                    canvas.height=height;
                    ctx.setTransform(ratio,0,0,ratio,0,0);
                    redraw();
                }
                function scheduleResize(){
                    cancelAnimationFrame(resizeFrame);
                    resizeFrame=requestAnimationFrame(resize);
                }
                function normalizedPoint(event){
                    const rect=canvasRect();
                    return {
                        x:Math.max(0,Math.min(1,(event.clientX-rect.left)/rect.width)),
                        y:Math.max(0,Math.min(1,(event.clientY-rect.top)/rect.height))
                    };
                }
                function appendPoint(event){
                    if(!currentStroke||pointCount>=MAX_POINTS)return false;
                    const next=normalizedPoint(event);
                    const previous=currentStroke.points[currentStroke.points.length-1];
                    if(previous&&Math.abs(previous.x-next.x)<0.0005&&Math.abs(previous.y-next.y)<0.0005)return null;
                    currentStroke.points.push(next);
                    pointCount+=1;
                    return next;
                }
                function start(event){
                    if(activePointerId!==null||(event.pointerType==='mouse'&&event.button!==0))return;
                    event.preventDefault();
                    if(strokes.length>=MAX_STROKES||pointCount>=MAX_POINTS){
                        status.textContent='笔画数量已达上限，请撤销或清空后继续。';
                        return;
                    }
                    clearedStrokes=null;
                    activePointerId=event.pointerId;
                    currentStroke={
                        color:document.getElementById('penColor').value,
                        width:Number(document.getElementById('penWidth').value),
                        points:[]
                    };
                    strokes.push(currentStroke);
                    canvas.setPointerCapture(event.pointerId);
                    appendPoint(event);
                    const rect=canvasRect();
                    drawStroke(ctx,currentStroke,rect.width,rect.height);
                    updateControls();
                    status.textContent='正在书写…';
                }
                function move(event){
                    if(event.pointerId!==activePointerId||!currentStroke)return;
                    event.preventDefault();
                    let events=[event];
                    if(typeof event.getCoalescedEvents==='function'){
                        try{const coalesced=event.getCoalescedEvents();if(coalesced.length)events=coalesced;}catch(error){}
                    }
                    const rect=canvasRect();
                    for(const item of events){
                        const previous=currentStroke.points[currentStroke.points.length-1];
                        const next=appendPoint(item);
                        if(next===false){
                            status.textContent='记录点数已达上限，请撤销或清空后继续。';
                            finish(event);
                            break;
                        }
                        if(next&&previous)drawSegment(ctx,currentStroke,previous,next,rect.width,rect.height);
                    }
                }
                function finish(event){
                    if(event.pointerId!==activePointerId)return;
                    if(canvas.hasPointerCapture(event.pointerId))canvas.releasePointerCapture(event.pointerId);
                    activePointerId=null;
                    currentStroke=null;
                    status.textContent='签名已保存在当前页面，可继续书写、撤销或下载。';
                    updateControls();
                }
                canvas.addEventListener('pointerdown',start);
                canvas.addEventListener('pointermove',move);
                canvas.addEventListener('pointerup',finish);
                canvas.addEventListener('pointercancel',finish);
                document.getElementById('penWidth').addEventListener('input',event=>document.getElementById('widthValue').textContent=event.target.value+'px');
                clearButton.addEventListener('click',()=>{
                    clearedStrokes=strokes.splice(0);
                    pointCount=0;
                    currentStroke=null;
                    activePointerId=null;
                    redraw();
                    status.textContent='签名已清空。';
                });
                undoButton.addEventListener('click',()=>{
                    if(!strokes.length&&clearedStrokes){
                        strokes.push(...clearedStrokes);
                        pointCount=strokes.reduce((sum,item)=>sum+item.points.length,0);
                        clearedStrokes=null;
                        redraw();
                        status.textContent='已恢复清空前的签名。';
                        return;
                    }
                    const stroke=strokes.pop();
                    if(stroke)pointCount=Math.max(0,pointCount-stroke.points.length);
                    currentStroke=null;
                    activePointerId=null;
                    redraw();
                    clearedStrokes=null;
                    status.textContent=strokes.length?'已撤销上一笔。':'签名已清空。';
                });
                downloadButton.addEventListener('click',()=>{
                    if(!strokes.length){status.textContent='请先书写签名。';return;}
                    const output=document.createElement('canvas');
                    output.width=canvas.width;
                    output.height=canvas.height;
                    const outputCtx=output.getContext('2d');
                    if(!outputCtx){status.textContent='浏览器无法创建导出画布。';return;}
                    if(document.getElementById('whiteBackground').checked){
                        outputCtx.fillStyle='#fff';
                        outputCtx.fillRect(0,0,output.width,output.height);
                    }
                    outputCtx.drawImage(canvas,0,0);
                    output.toBlob(blob=>{
                        if(!blob){status.textContent='浏览器无法生成签名图片。';return;}
                        const url=URL.createObjectURL(blob);
                        const link=document.createElement('a');
                        link.href=url;
                        link.download='电子签名.png';
                        link.click();
                        setTimeout(()=>URL.revokeObjectURL(url),1000);
                        status.textContent='签名图片已生成。';
                    },'image/png');
                });
                const observer=new ResizeObserver(scheduleResize);
                observer.observe(canvas.parentElement);
                window.addEventListener('beforeunload',()=>{
                    observer.disconnect();
                    cancelAnimationFrame(resizeFrame);
                });
                resize();
                updateControls();
            })();
            </script>
<?php include '_footer.php'; ?>
