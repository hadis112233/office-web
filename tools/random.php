<?php
$title = '安全随机生成';
$desc = '生成安全随机数、强密码、颜色和 UUID v4。使用浏览器 Web Crypto API，内容不会上传服务器。';
include '_header.php';
?>
            <div class="crypto-notice" id="cryptoNotice" role="status" aria-live="polite">
                <span>🔒</span><div><strong>安全随机源</strong><p>使用浏览器 Web Crypto API 生成，不依赖可预测的普通伪随机算法，所有结果仅保留在当前页面。</p></div>
            </div>

            <section class="tool-panel">
                <h3>随机整数</h3>
                <div class="field-grid">
                    <label>最小值<input type="number" id="min" value="1"></label>
                    <label>最大值<input type="number" id="max" value="100"></label>
                    <label>数量<input type="number" id="count" value="10" min="1" max="1000"></label>
                </div>
                <label class="check-option"><input type="checkbox" id="unique"> 结果不重复</label>
                <div class="btn-row"><button class="btn success" id="generateInt" type="button">生成随机数</button><button class="btn" data-copy="intOut" type="button">复制</button></div>
                <label class="sr-only" for="intOut">随机整数结果</label><textarea id="intOut" readonly placeholder="随机整数将显示在此处"></textarea>
            </section>

            <section class="tool-panel">
                <h3>强密码</h3>
                <div class="field-grid compact-grid">
                    <label>密码长度<input type="number" id="pwLen" value="16" min="8" max="128"></label>
                    <label>生成数量<input type="number" id="pwCount" value="5" min="1" max="20"></label>
                </div>
                <div class="password-options">
                    <label><input type="checkbox" id="pwUpper" checked> 大写字母</label>
                    <label><input type="checkbox" id="pwLower" checked> 小写字母</label>
                    <label><input type="checkbox" id="pwNum" checked> 数字</label>
                    <label><input type="checkbox" id="pwSym" checked> 特殊字符</label>
                    <label title="排除 I、O、l、0、1"><input type="checkbox" id="avoidAmbiguous" checked> 排除易混淆字符</label>
                </div>
                <div class="btn-row"><button class="btn success" id="generatePwd" type="button">生成密码</button><button class="btn" data-copy="pwdOut" type="button">复制全部</button></div>
                <div class="strength-row"><span>估算强度</span><div class="strength-track"><i id="strengthBar"></i></div><b id="strengthText">等待生成</b></div>
                <label class="sr-only" for="pwdOut">随机密码结果</label><textarea id="pwdOut" readonly placeholder="每行显示一个随机密码"></textarea>
            </section>

            <section class="tool-panel">
                <h3>随机颜色</h3>
                <div class="field-grid compact-grid"><label>生成数量<input type="number" id="colorCount" value="8" min="1" max="50"></label></div>
                <div class="btn-row"><button class="btn success" id="generateColor" type="button">生成颜色</button><button class="btn" id="copyColor" type="button">复制全部</button></div>
                <div id="colorBox" class="color-grid" aria-label="随机颜色结果"></div>
            </section>

            <section class="tool-panel">
                <h3>UUID v4</h3>
                <div class="field-grid compact-grid"><label>生成数量<input type="number" id="uuidCount" value="5" min="1" max="100"></label></div>
                <div class="btn-row"><button class="btn success" id="generateUuid" type="button">生成 UUID</button><button class="btn" data-copy="uuidOut" type="button">复制全部</button></div>
                <label class="sr-only" for="uuidOut">UUID 结果</label><textarea id="uuidOut" readonly placeholder="每行显示一个 UUID v4"></textarea>
            </section>

            <p class="generator-status" id="status" role="status" aria-live="polite">请选择参数并开始生成。</p>
            <style>
            .crypto-notice{display:flex;align-items:flex-start;gap:12px;margin-bottom:18px;padding:14px 16px;border:1px solid #a7f3d0;border-radius:12px;color:#065f46;background:#ecfdf5}.crypto-notice>span{font-size:24px}.crypto-notice strong{display:block;margin-bottom:3px}.crypto-notice p{margin:0;font-size:12px;line-height:1.6}.crypto-notice.error{border-color:#fecaca;color:#991b1b;background:#fef2f2}.field-grid{display:grid;grid-template-columns:repeat(3,minmax(120px,1fr));gap:14px;margin:12px 0}.field-grid.compact-grid{grid-template-columns:repeat(2,minmax(120px,220px))}.field-grid label{margin:0}.field-grid input{margin-top:7px}.check-option,.password-options label{display:flex!important;align-items:center;gap:7px;margin:0!important}.password-options{display:flex;flex-wrap:wrap;gap:10px 18px;margin:14px 0}.strength-row{display:grid;grid-template-columns:auto minmax(100px,260px) auto;align-items:center;gap:10px;margin:10px 0;color:#64748b;font-size:12px}.strength-track{height:8px;overflow:hidden;border-radius:99px;background:#e2e8f0}.strength-track i{display:block;width:0;height:100%;border-radius:inherit;background:#94a3b8;transition:.25s}.color-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:10px;margin-top:15px}.color-item{overflow:hidden;padding:0;border:1px solid #dbe3f0;border-radius:9px;background:#fff;cursor:pointer}.color-swatch{height:72px}.color-code{display:block;padding:7px;color:#334155;font:12px/1.2 Consolas,monospace}.generator-status{position:sticky;bottom:12px;z-index:5;margin:14px auto 0;padding:9px 14px;max-width:max-content;border-radius:999px;color:#475569;background:rgba(255,255,255,.96);box-shadow:0 5px 18px rgba(15,23,42,.14);font-size:13px}.generator-status.error{color:#b91c1c}.generator-status.success{color:#047857}@media(max-width:650px){.field-grid,.field-grid.compact-grid{grid-template-columns:1fr}.strength-row{grid-template-columns:auto 1fr}.strength-row b{grid-column:1/-1}.color-grid{grid-template-columns:repeat(2,1fr)}}html.theme-dark .crypto-notice{border-color:#047857;color:#a7f3d0;background:#064e3b}html.theme-dark .crypto-notice.error{border-color:#b91c1c;color:#fecaca;background:#450a0a}html.theme-dark .color-item{border-color:#475569;background:#1e293b}html.theme-dark .color-code{color:#e2e8f0}html.theme-dark .generator-status{color:#cbd5e1;background:rgba(30,41,59,.96)}html.theme-dark .generator-status.error{color:#fca5a5}html.theme-dark .generator-status.success{color:#6ee7b7}
            </style>
            <script>
            (function(){
                const $=id=>document.getElementById(id),cryptoApi=window.crypto,uint32Range=0x100000000;
                function setStatus(message,type=''){$('status').textContent=message;$('status').className='generator-status'+(type?' '+type:'');}
                function readInteger(id,min,max,label){const value=Number($(id).value);if(!Number.isSafeInteger(value)||value<min||value>max)throw new Error(`${label}需为 ${min}–${max} 的整数`);return value;}
                function randomBelow(limit){
                    if(!Number.isSafeInteger(limit)||limit<1||limit>uint32Range)throw new Error('随机范围必须在 1–4,294,967,296 之间');
                    const cutoff=uint32Range-(uint32Range%limit),buffer=new Uint32Array(1);let value;
                    do{cryptoApi.getRandomValues(buffer);value=buffer[0];}while(value>=cutoff);
                    return value%limit;
                }
                function secureShuffle(values){for(let i=values.length-1;i>0;i--){const j=randomBelow(i+1);[values[i],values[j]]=[values[j],values[i]];}return values;}
                function sampleUnique(min,range,count){
                    const swaps=new Map(),result=[];
                    for(let i=0;i<count;i++){
                        const selected=i+randomBelow(range-i),selectedValue=swaps.has(selected)?swaps.get(selected):selected,currentValue=swaps.has(i)?swaps.get(i):i;
                        swaps.set(selected,currentValue);swaps.set(i,selectedValue);result.push(min+selectedValue);
                    }
                    return result;
                }
                async function copyText(value){
                    if(!value){setStatus('暂无可复制内容。','error');return;}
                    try{if(!navigator.clipboard)throw new Error();await navigator.clipboard.writeText(value);setStatus('已复制到剪贴板。','success');}
                    catch(error){const helper=document.createElement('textarea');helper.value=value;helper.style.position='fixed';helper.style.opacity='0';document.body.appendChild(helper);helper.select();const ok=document.execCommand('copy');helper.remove();setStatus(ok?'已复制到剪贴板。':'自动复制失败，请手动选择结果复制。',ok?'success':'error');}
                }
                function generateIntegers(){
                    try{
                        const min=readInteger('min',Number.MIN_SAFE_INTEGER,Number.MAX_SAFE_INTEGER,'最小值'),max=readInteger('max',Number.MIN_SAFE_INTEGER,Number.MAX_SAFE_INTEGER,'最大值'),count=readInteger('count',1,1000,'数量');
                        if(min>max)throw new Error('最小值不能大于最大值');const range=max-min+1;
                        if(!Number.isSafeInteger(range)||range>uint32Range)throw new Error('最大值与最小值的跨度不能超过 4,294,967,296');
                        if($('unique').checked&&count>range)throw new Error('不重复数量不能大于可选整数总数');
                        const result=$('unique').checked?sampleUnique(min,range,count):Array.from({length:count},()=>min+randomBelow(range));
                        $('intOut').value=result.join(', ');setStatus(`已安全生成 ${count} 个随机整数${$('unique').checked?'，结果不重复':''}。`,'success');
                    }catch(error){setStatus(error.message,'error');}
                }
                function passwordPools(){
                    const avoid=$('avoidAmbiguous').checked,pools=[];
                    if($('pwUpper').checked)pools.push(avoid?'ABCDEFGHJKLMNPQRSTUVWXYZ':'ABCDEFGHIJKLMNOPQRSTUVWXYZ');
                    if($('pwLower').checked)pools.push(avoid?'abcdefghijkmnopqrstuvwxyz':'abcdefghijklmnopqrstuvwxyz');
                    if($('pwNum').checked)pools.push(avoid?'23456789':'0123456789');
                    if($('pwSym').checked)pools.push('!@#$%^&*()_+-=[]{}|;:,.<>?');
                    return pools;
                }
                function onePassword(length,pools){const all=pools.join(''),chars=pools.map(pool=>pool[randomBelow(pool.length)]);while(chars.length<length)chars.push(all[randomBelow(all.length)]);return secureShuffle(chars).join('');}
                function showStrength(length,poolSize){
                    const entropy=Math.round(length*Math.log2(poolSize)),level=entropy<50?['偏弱','#ef4444',35]:entropy<80?['中等','#f59e0b',65]:['强','#10b981',100];
                    $('strengthText').textContent=`${level[0]} · 约 ${entropy} 位熵`;$('strengthBar').style.width=level[2]+'%';$('strengthBar').style.background=level[1];
                }
                function generatePasswords(){
                    try{
                        const length=readInteger('pwLen',8,128,'密码长度'),count=readInteger('pwCount',1,20,'生成数量'),pools=passwordPools();
                        if(!pools.length)throw new Error('请至少选择一种字符类型');if(length<pools.length)throw new Error(`密码长度不能小于已选字符类型数量 ${pools.length}`);
                        $('pwdOut').value=Array.from({length:count},()=>onePassword(length,pools)).join('\n');showStrength(length,pools.join('').length);
                        setStatus(`已生成 ${count} 个强密码，每个密码都包含所有已选字符类型。`,'success');
                    }catch(error){setStatus(error.message,'error');}
                }
                function generateColors(){
                    try{
                        const count=readInteger('colorCount',1,50,'生成数量'),bytes=new Uint8Array(count*3);cryptoApi.getRandomValues(bytes);const box=$('colorBox');box.replaceChildren();
                        for(let i=0;i<count;i++){
                            const hex='#'+[bytes[i*3],bytes[i*3+1],bytes[i*3+2]].map(value=>value.toString(16).padStart(2,'0')).join('').toUpperCase();
                            const button=document.createElement('button'),swatch=document.createElement('span'),code=document.createElement('span');button.type='button';button.className='color-item';button.dataset.hex=hex;button.title='点击复制 '+hex;swatch.className='color-swatch';swatch.style.backgroundColor=hex;code.className='color-code';code.textContent=hex;button.append(swatch,code);button.addEventListener('click',()=>copyText(hex));box.appendChild(button);
                        }
                        setStatus(`已生成 ${count} 个随机颜色，点击色块可复制色值。`,'success');
                    }catch(error){setStatus(error.message,'error');}
                }
                function uuidV4(){
                    if(typeof cryptoApi.randomUUID==='function')return cryptoApi.randomUUID();
                    const bytes=new Uint8Array(16);cryptoApi.getRandomValues(bytes);bytes[6]=(bytes[6]&15)|64;bytes[8]=(bytes[8]&63)|128;const hex=[...bytes].map(value=>value.toString(16).padStart(2,'0')).join('');return `${hex.slice(0,8)}-${hex.slice(8,12)}-${hex.slice(12,16)}-${hex.slice(16,20)}-${hex.slice(20)}`;
                }
                function generateUuids(){try{const count=readInteger('uuidCount',1,100,'生成数量');$('uuidOut').value=Array.from({length:count},uuidV4).join('\n');setStatus(`已生成 ${count} 个 UUID v4。`,'success');}catch(error){setStatus(error.message,'error');}}
                if(!cryptoApi||typeof cryptoApi.getRandomValues!=='function'){
                    $('cryptoNotice').classList.add('error');$('cryptoNotice').querySelector('strong').textContent='当前浏览器不支持安全随机数';$('cryptoNotice').querySelector('p').textContent='请升级浏览器后再使用，页面不会退回到不安全的普通伪随机算法。';document.querySelectorAll('.tool-panel button').forEach(button=>button.disabled=true);setStatus('缺少 Web Crypto API，已停止生成。','error');return;
                }
                $('generateInt').addEventListener('click',generateIntegers);$('generatePwd').addEventListener('click',generatePasswords);$('generateColor').addEventListener('click',generateColors);$('generateUuid').addEventListener('click',generateUuids);$('copyColor').addEventListener('click',()=>copyText([...document.querySelectorAll('#colorBox .color-item')].map(item=>item.dataset.hex).join('\n')));document.querySelectorAll('[data-copy]').forEach(button=>button.addEventListener('click',()=>copyText($(button.dataset.copy).value)));
                generateColors();generatePasswords();generateUuids();
            })();
            </script>
<?php include '_footer.php'; ?>
