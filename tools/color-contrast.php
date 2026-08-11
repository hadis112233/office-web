<?php
$title = '颜色对比度检查器';
$desc = '检查文字与背景颜色是否符合 WCAG 2.2 对比度要求，并生成更易读的替代色；所有计算均在浏览器本地完成。';
include '_header.php';
?>
            <style>
            .contrast-layout{display:grid;grid-template-columns:minmax(0,.85fr) minmax(320px,1.15fr);gap:18px}
            .color-fields{display:grid;grid-template-columns:1fr 1fr;gap:14px}.color-field{padding:14px;border:1px solid #dbe3ef;border-radius:12px;background:rgba(248,250,252,.72)}
            .color-row{display:grid;grid-template-columns:54px minmax(0,1fr);gap:10px;margin-top:8px}.color-picker{width:54px;height:44px;padding:2px;border:1px solid #cbd5e1;border-radius:9px;background:none;cursor:pointer}.hex-input{text-transform:uppercase}
            .swap-row{display:flex;justify-content:center;margin:13px 0}.swap-button{border:1px solid #cbd5e1;border-radius:999px;padding:8px 15px;background:#fff;color:#334155;cursor:pointer}
            .preview-box{min-height:180px;display:flex;align-items:center;justify-content:center;text-align:center;padding:28px;border-radius:14px;transition:background-color .15s,color .15s;overflow-wrap:anywhere}.preview-box strong{display:block;font-size:27px;margin-bottom:8px}.preview-box span{font-size:16px}
            .ratio-line{display:flex;align-items:baseline;justify-content:center;gap:7px;margin:18px 0 14px}.ratio-number{font-size:46px;font-weight:800;line-height:1;color:#4f46e5}.ratio-unit{font-size:20px;color:#64748b}
            .result-grid{display:grid;grid-template-columns:1fr 1fr;gap:9px}.result-item{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:11px;border:1px solid #e2e8f0;border-radius:10px}.result-item small{display:block;color:#64748b;margin-top:3px}.badge{font-weight:700;padding:4px 8px;border-radius:999px}.badge.pass{color:#047857;background:#d1fae5}.badge.fail{color:#b91c1c;background:#fee2e2}
            .suggestions{margin-top:18px}.suggestion-list{display:grid;gap:9px;margin-top:9px}.suggestion{display:flex;align-items:center;justify-content:space-between;gap:9px;padding:10px;border:1px solid #e2e8f0;border-radius:10px;background:rgba(255,255,255,.74)}.suggestion-color{width:34px;height:34px;border-radius:7px;border:1px solid rgba(15,23,42,.15);flex:0 0 auto}.suggestion-info{min-width:0;flex:1}.suggestion-info code{font-weight:700}.suggestion-info small{display:block;color:#64748b;margin-top:2px}.suggestion button{white-space:nowrap;padding:6px 9px;border:0;border-radius:7px;background:#eef2ff;color:#4f46e5;cursor:pointer}
            .invalid{border-color:#dc2626!important;box-shadow:0 0 0 2px rgba(220,38,38,.1)}.formula-note{margin-top:14px;color:#64748b;font-size:13px;line-height:1.6}
            html.theme-dark .color-field,html.theme-dark .suggestion{background:rgba(30,41,59,.72);border-color:#475569}html.theme-dark .result-item{border-color:#475569}html.theme-dark .swap-button{background:#1e293b;color:#e2e8f0;border-color:#475569}
            @media(max-width:780px){.contrast-layout{grid-template-columns:1fr}.color-fields{grid-template-columns:1fr}.result-grid{grid-template-columns:1fr}.preview-box{min-height:150px}.ratio-number{font-size:40px}}
            </style>
            <div class="contrast-layout">
                <div class="tool-panel">
                    <div class="color-fields">
                        <div class="color-field"><label for="foregroundText">文字颜色</label><div class="color-row"><input class="color-picker" id="foregroundPicker" type="color" value="#1e293b" aria-label="选择文字颜色"><input class="hex-input" id="foregroundText" value="#1E293B" maxlength="7" spellcheck="false" inputmode="text"></div></div>
                        <div class="color-field"><label for="backgroundText">背景颜色</label><div class="color-row"><input class="color-picker" id="backgroundPicker" type="color" value="#ffffff" aria-label="选择背景颜色"><input class="hex-input" id="backgroundText" value="#FFFFFF" maxlength="7" spellcheck="false" inputmode="text"></div></div>
                    </div>
                    <div class="swap-row"><button class="swap-button" id="swapButton" type="button">⇄ 交换文字与背景</button></div>
                    <div class="preview-box" id="previewBox"><div><strong>办公工具站</strong><span>清晰的文字让每个人都更容易阅读。</span></div></div>
                    <p class="formula-note">对比度范围为 1:1（完全相同）到 21:1（黑白）。大字指至少 24px，或至少 18.5px 的粗体文字。</p>
                </div>
                <div class="tool-panel">
                    <div class="ratio-line"><span class="ratio-number" id="ratioValue">13.96</span><span class="ratio-unit">: 1</span></div>
                    <div class="result-grid" id="resultGrid">
                        <div class="result-item"><div><strong>普通文字 AA</strong><small>至少 4.5:1</small></div><span class="badge" data-threshold="4.5"></span></div>
                        <div class="result-item"><div><strong>普通文字 AAA</strong><small>至少 7:1</small></div><span class="badge" data-threshold="7"></span></div>
                        <div class="result-item"><div><strong>大字 AA</strong><small>至少 3:1</small></div><span class="badge" data-threshold="3"></span></div>
                        <div class="result-item"><div><strong>大字 AAA</strong><small>至少 4.5:1</small></div><span class="badge" data-threshold="4.5"></span></div>
                        <div class="result-item"><div><strong>界面与图形</strong><small>建议至少 3:1</small></div><span class="badge" data-threshold="3"></span></div>
                    </div>
                    <div class="suggestions"><h2 style="margin:0">可读文字色建议</h2><div class="suggestion-list" id="suggestionList"></div></div>
                </div>
            </div>
            <script>
            (()=>{
                'use strict';
                const SRGB_THRESHOLD=0.04045;
                const $=id=>document.getElementById(id);
                const pairs=[
                    {text:$('foregroundText'),picker:$('foregroundPicker')},
                    {text:$('backgroundText'),picker:$('backgroundPicker')}
                ];
                function normalizeHex(value){const raw=value.trim().replace(/^#/,'');if(/^[0-9a-f]{3}$/i.test(raw))return '#'+raw.split('').map(char=>char+char).join('').toUpperCase();if(/^[0-9a-f]{6}$/i.test(raw))return '#'+raw.toUpperCase();return null;}
                function hexToRgb(hex){return [parseInt(hex.slice(1,3),16),parseInt(hex.slice(3,5),16),parseInt(hex.slice(5,7),16)];}
                function rgbToHex(rgb){return '#'+rgb.map(value=>Math.max(0,Math.min(255,Math.round(value))).toString(16).padStart(2,'0')).join('').toUpperCase();}
                function relativeLuminance(hex){const rgb=hexToRgb(hex).map(value=>{const channel=value/255;return channel<=SRGB_THRESHOLD?channel/12.92:Math.pow((channel+0.055)/1.055,2.4);});return .2126*rgb[0]+.7152*rgb[1]+.0722*rgb[2];}
                function contrastRatio(first,second){const a=relativeLuminance(first),b=relativeLuminance(second);return (Math.max(a,b)+.05)/(Math.min(a,b)+.05);}
                function mixColor(from,to,amount){const a=hexToRgb(from),b=hexToRgb(to);return rgbToHex(a.map((value,index)=>value+(b[index]-value)*amount));}
                function closestPassingColor(foreground,background,target){
                    if(contrastRatio(foreground,background)>=target)return foreground;
                    const options=['#000000','#FFFFFF'].map(endpoint=>{if(contrastRatio(endpoint,background)<target)return null;let low=0,high=1;for(let i=0;i<20;i++){const mid=(low+high)/2;if(contrastRatio(mixColor(foreground,endpoint,mid),background)>=target)high=mid;else low=mid;}const hex=mixColor(foreground,endpoint,high);const start=hexToRgb(foreground),end=hexToRgb(hex);return {hex,distance:Math.hypot(end[0]-start[0],end[1]-start[1],end[2]-start[2])};}).filter(Boolean);
                    return options.sort((a,b)=>a.distance-b.distance)[0]?.hex||foreground;
                }
                function addSuggestion(label,hex,background){const row=document.createElement('div');row.className='suggestion';const swatch=document.createElement('span');swatch.className='suggestion-color';swatch.style.background=hex;const info=document.createElement('div');info.className='suggestion-info';const code=document.createElement('code');code.textContent=hex;const small=document.createElement('small');small.textContent=`${label} · ${contrastRatio(hex,background).toFixed(2)}:1`;info.append(code,small);const button=document.createElement('button');button.type='button';button.textContent='应用';button.addEventListener('click',()=>{pairs[0].text.value=hex;pairs[0].picker.value=hex.toLowerCase();update();});row.append(swatch,info,button);$('suggestionList').appendChild(row);}
                function update(){
                    const foreground=normalizeHex(pairs[0].text.value),background=normalizeHex(pairs[1].text.value);
                    pairs.forEach(pair=>pair.text.classList.toggle('invalid',!normalizeHex(pair.text.value)));
                    if(!foreground||!background){$('ratioValue').textContent='—';return;}
                    pairs[0].picker.value=foreground.toLowerCase();pairs[1].picker.value=background.toLowerCase();$('previewBox').style.color=foreground;$('previewBox').style.backgroundColor=background;
                    const ratio=contrastRatio(foreground,background);$('ratioValue').textContent=ratio.toFixed(2);document.querySelectorAll('[data-threshold]').forEach(badge=>{const pass=ratio>=Number(badge.dataset.threshold);badge.textContent=pass?'通过':'未通过';badge.className='badge '+(pass?'pass':'fail');});
                    const aa=closestPassingColor(foreground,background,4.5),aaa=closestPassingColor(foreground,background,7);$('suggestionList').replaceChildren();addSuggestion('普通文字 AA',aa,background);if(aaa!==aa)addSuggestion('普通文字 AAA',aaa,background);
                }
                pairs.forEach(pair=>{pair.picker.addEventListener('input',()=>{pair.text.value=pair.picker.value.toUpperCase();update();});pair.text.addEventListener('input',update);pair.text.addEventListener('blur',()=>{const normalized=normalizeHex(pair.text.value);if(normalized)pair.text.value=normalized;update();});});
                $('swapButton').addEventListener('click',()=>{const foreground=pairs[0].text.value;pairs[0].text.value=pairs[1].text.value;pairs[1].text.value=foreground;update();});
                update();
            })();
            </script>
<?php include '_footer.php'; ?>
