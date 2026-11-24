<?php
$title = '单位换算';
$desc = '换算常用长度、重量、面积、温度和数据容量单位，支持双向交换。';
include '_header.php';
?>
            <div class="tool-panel">
                <label for="category">换算类型</label>
                <select id="category"><option value="length">长度</option><option value="weight">重量</option><option value="area">面积</option><option value="temperature">温度</option><option value="storage">数据容量</option></select>
                <div class="convert-row">
                    <div><label for="fromUnit">从</label><select id="fromUnit"></select><input id="fromValue" type="number" value="1" step="any"></div>
                    <button class="swap" id="swap" type="button" aria-label="交换单位">⇄</button>
                    <div><label for="toUnit">到</label><select id="toUnit"></select><input id="toValue" type="text" readonly></div>
                </div>
                <div class="result-box" id="formula" role="status"></div>
            </div>
            <section class="tool-panel" aria-labelledby="printHeading">
                <h3 id="printHeading">打印尺寸与像素换算</h3>
                <div class="print-fields">
                    <label>换算方向<select id="printDirection"><option value="toPixels">毫米 → 像素</option><option value="toMm">像素 → 毫米</option></select></label>
                    <label>图像分辨率（PPI，每英寸像素数）<input id="printPpi" type="number" value="300" min="0" step="any"></label>
                    <label><span id="widthLabel">宽度（毫米）</span><input id="printWidth" type="number" value="25" min="0" step="any"></label>
                    <label><span id="heightLabel">高度（毫米）</span><input id="printHeight" type="number" value="35" min="0" step="any"></label>
                </div>
                <div class="result-box" id="printResult" role="status"></div>
            </section>
            <style>
            .print-fields{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,200px),1fr));gap:14px;margin:14px 0}.print-fields label{display:grid;align-content:end;gap:7px}.print-fields input,.print-fields select{width:100%}
            #category{width:100%;margin:8px 0 20px}.convert-row{display:grid;grid-template-columns:1fr auto 1fr;align-items:end;gap:14px}.convert-row>div{display:grid;gap:8px}.swap{width:48px;height:48px;margin-bottom:1px;border:0;border-radius:50%;background:#4f46e5;color:#fff;font-size:21px;cursor:pointer}@media(max-width:650px){.convert-row{grid-template-columns:1fr}.swap{justify-self:center;transform:rotate(90deg)}}
            </style>
            <script>
            (function(){
                const sets={
                    length:{units:{m:['米',1],km:['千米',1000],cm:['厘米',.01],mm:['毫米',.001],inch:['英寸',.0254],ft:['英尺',.3048]},defaults:['m','cm']},
                    weight:{units:{kg:['千克',1],g:['克',.001],mg:['毫克',.000001],t:['吨',1000],lb:['磅',.45359237],oz:['盎司',.028349523]},defaults:['kg','g']},
                    area:{units:{sqm:['平方米',1],sqkm:['平方千米',1000000],mu:['亩',666.6666667],ha:['公顷',10000],sqft:['平方英尺',.09290304]},defaults:['sqm','mu']},
                    storage:{units:{B:['字节',1],KB:['KB',1024],MB:['MB',1048576],GB:['GB',1073741824],TB:['TB',1099511627776]},defaults:['MB','KB']},
                    temperature:{units:{C:['摄氏度'],F:['华氏度'],K:['开尔文']},defaults:['C','F']}
                };
                const $=id=>document.getElementById(id); const format=n=>Number.isFinite(n)?new Intl.NumberFormat('zh-CN',{maximumFractionDigits:8}).format(n):'—';
                function updatePrintSize(){
                    const toPixels = $('printDirection').value === 'toPixels';
                    const unit = toPixels ? '毫米' : '像素';
                    $('widthLabel').textContent = '宽度（' + unit + '）';
                    $('heightLabel').textContent = '高度（' + unit + '）';
                    const dimensions = [$('printWidth').valueAsNumber, $('printHeight').valueAsNumber];
                    const ppi = $('printPpi').valueAsNumber;
                    const result = $('printResult');
                    if (![...dimensions, ppi].every(n => Number.isFinite(n) && n > 0)) {
                        result.textContent = '请填写大于零的宽度、高度和图像分辨率。';
                        return;
                    }
                    if (!toPixels && !dimensions.every(Number.isSafeInteger)) {
                        result.textContent = '像素宽度和高度需要填写整数。';
                        return;
                    }
                    const sizes = dimensions.map(n => toPixels ? Math.round(n / 25.4 * ppi) : n / ppi * 25.4);
                    if (!sizes.every(n => Number.isFinite(n) && n > 0 && (!toPixels || Number.isSafeInteger(n)))) {
                        result.textContent = '尺寸超出换算范围，请检查输入值。';
                        return;
                    }
                    result.textContent = sizes.map(format).join(' × ') + (toPixels ? ' 像素（四舍五入）' : ' 毫米');
                }
                $('printDirection').addEventListener('change', updatePrintSize);
                ['printWidth','printHeight','printPpi'].forEach(id => $(id).addEventListener('input', updatePrintSize));
                updatePrintSize();
                function temp(value,from,to){let c=from==='C'?value:from==='F'?(value-32)*5/9:value-273.15;return to==='C'?c:to==='F'?c*9/5+32:c+273.15;}
                function fill(){const set=sets[$('category').value];['fromUnit','toUnit'].forEach((id,index)=>{$(id).innerHTML='';Object.entries(set.units).forEach(([key,item])=>{const option=document.createElement('option');option.value=key;option.textContent=item[0];$(id).appendChild(option);});$(id).value=set.defaults[index];});convert();}
                function convert(){const set=sets[$('category').value],value=Number($('fromValue').value),from=$('fromUnit').value,to=$('toUnit').value;let result=$('category').value==='temperature'?temp(value,from,to):value*set.units[from][1]/set.units[to][1];$('toValue').value=format(result);$('formula').textContent=format(value)+' '+set.units[from][0]+' = '+format(result)+' '+set.units[to][0];}
                $('category').addEventListener('change',fill);['fromUnit','toUnit','fromValue'].forEach(id=>$(id).addEventListener(id==='fromValue'?'input':'change',convert));$('swap').addEventListener('click',()=>{const value=$('fromUnit').value;$('fromUnit').value=$('toUnit').value;$('toUnit').value=value;convert();});fill();
            })();
            </script>
<?php include '_footer.php'; ?>
