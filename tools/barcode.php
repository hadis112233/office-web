<?php
$title = '条形码生成';
$desc = '本地生成 EAN-13 商品条码和 Code 39 资产条码，可自动计算校验位并下载 PNG。';
include '_header.php';
?>
            <div class="barcode-layout">
                <section class="tool-panel">
                    <label for="barcodeType">条码类型</label>
                    <select id="barcodeType">
                        <option value="ean13">EAN-13（商品条码）</option>
                        <option value="code39">Code 39（资产 / 设备编号）</option>
                    </select>
                    <label for="barcodeText" id="barcodeLabel">输入 12 位或 13 位数字</label>
                    <input id="barcodeText" type="text" inputmode="numeric" maxlength="13" autocomplete="off" placeholder="例如：690123456789">
                    <p class="barcode-help" id="barcodeHelp">输入 12 位时会自动补全校验位；输入 13 位时会检查最后一位是否正确。</p>
                    <label for="barcodeHeight">条码高度</label>
                    <select id="barcodeHeight"><option value="120">标准（120 px）</option><option value="160" selected>清晰（160 px）</option><option value="220">打印（220 px）</option></select>
                    <div class="btn-row"><button class="btn success" id="generateButton" type="button">生成条形码</button><button class="btn" id="downloadButton" type="button" disabled>下载 PNG</button><button class="btn secondary" id="clearButton" type="button">清空</button></div>
                    <p class="barcode-status" id="barcodeStatus" role="status" aria-live="polite">条码在浏览器本地生成，不会发送到服务器。</p>
                </section>
                <section class="tool-panel">
                    <div class="barcode-preview" id="barcodePreview"><span>填写编号后生成条形码</span></div>
                    <p class="barcode-result" id="barcodeResult"></p>
                </section>
            </div>
            <p class="tip">提示：EAN-13 适用于已分配的商品编码；自动计算校验位不等于获得商品条码授权。Code 39 仅支持大写字母、数字及 <code>- . 空格 $ / + %</code>。</p>
            <style>
            .barcode-layout{display:grid;grid-template-columns:minmax(280px,.8fr) minmax(0,1.2fr);gap:18px}.barcode-layout label{display:block;margin-top:12px}.barcode-layout label:first-child{margin-top:0}.barcode-layout input,.barcode-layout select{width:100%;margin-top:6px}.barcode-help,.barcode-status,.barcode-result{color:#64748b;font-size:13px;line-height:1.6}.barcode-status.success{color:#047857}.barcode-status.error{color:#b91c1c}.barcode-preview{display:flex;min-height:290px;padding:20px;align-items:center;justify-content:center;overflow:auto;border:1px dashed #cbd5e1;border-radius:12px;background:#f8fafc;color:#94a3b8}.barcode-preview canvas{display:block;max-width:100%;height:auto;background:#fff;box-shadow:0 6px 18px rgba(15,23,42,.1)}html.theme-dark .barcode-preview{border-color:#475569;background:#0f172a}@media(max-width:760px){.barcode-layout{grid-template-columns:1fr}}
            </style>
            <script>
            (()=>{
                'use strict';
                const $=id=>document.getElementById(id),eanL=['0001101','0011001','0010011','0111101','0100011','0110001','0101111','0111011','0110111','0001011'],eanG=['0100111','0110011','0011011','0100001','0011101','0111001','0000101','0010001','0001001','0010111'],eanParity=['LLLLLL','LLGLGG','LLGGLG','LLGGGL','LGLLGG','LGGLLG','LGGGLL','LGLGLG','LGLGGL','LGGLGL'],code39={0:'nnnwwnwnn',1:'wnnwnnnnw',2:'nnwwnnnnw',3:'wnwwnnnnn',4:'nnnwwnnnw',5:'wnnwwnnnn',6:'nnwwwnnnn',7:'nnnwnnwnw',8:'wnnwnnwnn',9:'nnwwnnwnn',A:'wnnnnwnnw',B:'nnwnnwnnw',C:'wnwnnwnnn',D:'nnnnwwnnw',E:'wnnnwwnnn',F:'nnwnwwnnn',G:'nnnnnwwnw',H:'wnnnnwwnn',I:'nnwnnwwnn',J:'nnnnwwwnn',K:'wnnnnnnww',L:'nnwnnnnww',M:'wnwnnnnwn',N:'nnnnwnnww',O:'wnnnwnnwn',P:'nnwnwnnwn',Q:'nnnnnnwww',R:'wnnnnnwwn',S:'nnwnnnwwn',T:'nnnnwnwwn',U:'wwnnnnnnw',V:'nwwnnnnnw',W:'wwwnnnnnn',X:'nwnnwnnnw',Y:'wwnnwnnnn',Z:'nwwnwnnnn','-':'nwnnnnwnw','.':'wwnnnnwnn',' ':'nwwnnnwnn','$':'nwnwnwnnn','/':'nwnwnnnwn','+':'nwnnnwnwn','%':'nnnwnwnwn','*':'nwnnwnwnn'};
                let resultUrl='',resultName='barcode';
                function setStatus(message,type=''){const status=$('barcodeStatus');status.textContent=message;status.className='barcode-status '+type;}
                function checkDigit(value){let sum=0;for(let i=0;i<12;i++)sum+=Number(value[i])*(i%2?3:1);return String((10-sum%10)%10);}
                function eanBits(value){const first=Number(value[0]),parity=eanParity[first];let bits='101';for(let index=1;index<=6;index++){const digit=Number(value[index]);bits+=(parity[index-1]==='L'?eanL:eanG)[digit];}bits+='01010';for(let index=7;index<=12;index++){const bitsForDigit=eanL[Number(value[index])].split('').map(bit=>bit==='1'?'0':'1').join('');bits+=bitsForDigit;}return bits+'101';}
                function code39Runs(value){let runs=[];for(const char of '*'+value+'*'){for(let index=0;index<9;index++)runs.push({bar:index%2===0,width:code39[char][index]==='w'?3:1});runs.push({bar:false,width:1});}runs.pop();return runs;}
                function makeCanvas(width,height){const canvas=document.createElement('canvas');canvas.width=width;canvas.height=height;const context=canvas.getContext('2d');context.fillStyle='#fff';context.fillRect(0,0,width,height);context.fillStyle='#111';context.imageSmoothingEnabled=false;return {canvas,context};}
                function renderEan(value,height){const unit=Math.max(2,Math.floor(680/95)),quiet=unit*10,width=quiet*2+95*unit,{canvas,context}=makeCanvas(width,height+42),bits=eanBits(value),barHeight=height;for(let i=0;i<bits.length;i++)if(bits[i]==='1')context.fillRect(quiet+i*unit,8,unit,barHeight);context.font=Math.max(16,unit*5)+'px Arial';context.textAlign='center';context.fillText(value.slice(0,1),quiet-unit*4,height+30);context.fillText(value.slice(1,7),quiet+unit*24,height+30);context.fillText(value.slice(7),quiet+unit*71,height+30);return canvas;}
                function renderCode39(value,height){const runs=code39Runs(value),unit=3,quiet=30,width=quiet*2+runs.reduce((sum,run)=>sum+run.width*unit,0),{canvas,context}=makeCanvas(width,height+42);let x=quiet;for(const run of runs){if(run.bar)context.fillRect(x,8,run.width*unit,height);x+=run.width*unit;}context.font='18px Arial';context.textAlign='center';context.fillText(value,width/2,height+30);return canvas;}
                function render(){
                    const type=$('barcodeType').value,height=Number($('barcodeHeight').value);let value=$('barcodeText').value.trim(),canvas;
                    try{
                        if(type==='ean13'){if(!/^\d{12,13}$/.test(value))throw new Error('EAN-13 请输入 12 位或 13 位数字。');if(value.length===12)value+=checkDigit(value);else if(value[12]!==checkDigit(value.slice(0,12)))throw new Error('EAN-13 校验位不正确，请检查最后一位。');canvas=renderEan(value,height);resultName='ean13-'+value;setStatus(value===$('barcodeText').value.trim()?'校验位正确，条形码已生成。':'已自动补全校验位：'+value,'success');}
                        else{value=value.toUpperCase();if(!value||value.length>48||![...value].every(char=>Object.hasOwn(code39,char)&&char!=='*'))throw new Error('Code 39 请输入 1–48 位允许字符，不能包含星号 *。');canvas=renderCode39(value,height);resultName='code39-'+value.replace(/[^A-Z0-9]+/g,'-');setStatus('Code 39 条形码已生成。','success');}
                        if(resultUrl)URL.revokeObjectURL(resultUrl);resultUrl=canvas.toDataURL('image/png');const image=new Image();image.src=resultUrl;image.alt='生成的 '+(type==='ean13'?'EAN-13':'Code 39')+' 条形码';$('barcodePreview').replaceChildren(image);$('barcodeResult').textContent=(type==='ean13'?'EAN-13：':'Code 39：')+value;$('downloadButton').disabled=false;
                    }catch(error){$('downloadButton').disabled=true;$('barcodePreview').replaceChildren(Object.assign(document.createElement('span'),{textContent:'请检查输入内容后重试'}));$('barcodeResult').textContent='';setStatus(error.message,'error');}
                }
                function updateType(){const ean=$('barcodeType').value==='ean13';$('barcodeLabel').textContent=ean?'输入 12 位或 13 位数字':'输入资产或设备编号';$('barcodeText').inputMode=ean?'numeric':'text';$('barcodeText').maxLength=ean?13:48;$('barcodeText').placeholder=ean?'例如：690123456789':'例如：ASSET-2026-001';$('barcodeHelp').textContent=ean?'输入 12 位时会自动补全校验位；输入 13 位时会检查最后一位。':'会自动转为大写，并添加 Code 39 起止标记。';$('barcodeText').value='';$('downloadButton').disabled=true;$('barcodePreview').replaceChildren(Object.assign(document.createElement('span'),{textContent:'填写编号后生成条形码'}));$('barcodeResult').textContent='';setStatus('条码在浏览器本地生成，不会发送到服务器。');}
                $('barcodeType').addEventListener('change',updateType);$('generateButton').addEventListener('click',render);$('barcodeText').addEventListener('keydown',event=>{if(event.key==='Enter')render();});$('clearButton').addEventListener('click',updateType);$('downloadButton').addEventListener('click',()=>{if(!resultUrl)return;const link=document.createElement('a');link.href=resultUrl;link.download=resultName+'.png';link.click();});window.addEventListener('beforeunload',()=>{if(resultUrl)URL.revokeObjectURL(resultUrl);});
            })();
            </script>
<?php include '_footer.php'; ?>
