<?php
$title = '百分比计算器';
$desc = '快速计算占比、百分数对应值，以及数值变化的涨跌幅。';
include '_header.php';
?>
            <div class="tool-panel">
                <h3>常用百分比</h3>
                <div class="percent-row"><input id="part" type="number" value="25" step="any" aria-label="部分数值"><span>占</span><input id="whole" type="number" value="200" step="any" aria-label="总数值"><span>的</span><strong id="shareResult">—</strong></div>
                <div class="percent-row"><input id="percent" type="number" value="15" step="any" aria-label="百分数"><span>% ×</span><input id="base" type="number" value="800" step="any" aria-label="基础数值"><span>=</span><strong id="valueResult">—</strong></div>
            </div>
            <div class="tool-panel">
                <h3>涨跌幅</h3>
                <div class="percent-row"><span>从</span><input id="oldValue" type="number" value="100" step="any" aria-label="原数值"><span>变为</span><input id="newValue" type="number" value="120" step="any" aria-label="新数值"><strong id="changeResult">—</strong></div>
                <div class="stats">
                    <div class="stat-box"><div class="num" id="difference">0</div><div class="label">数值变化</div></div>
                    <div class="stat-box"><div class="num" id="changeRate">0%</div><div class="label">变化比例</div></div>
                </div>
            </div>
            <style>
            .percent-row{display:flex;align-items:center;justify-content:center;flex-wrap:wrap;gap:12px;margin:18px 0;font-size:16px}.percent-row input{width:min(190px,38vw)}.percent-row strong{min-width:110px;padding:11px 14px;border-radius:10px;background:#eef2ff;color:#4338ca;text-align:center}.positive{color:#047857!important;background:#ecfdf5!important}.negative{color:#b91c1c!important;background:#fef2f2!important}
            </style>
            <script>
            (function(){
                const $=id=>document.getElementById(id); const number=id=>Number($(id).value); const fmt=value=>Number.isFinite(value)?new Intl.NumberFormat('zh-CN',{maximumFractionDigits:4}).format(value):'—';
                function update(){
                    const whole=number('whole'); $('shareResult').textContent=whole===0?'不能除以 0':fmt(number('part')/whole*100)+'%';
                    $('valueResult').textContent=fmt(number('percent')/100*number('base'));
                    const old=number('oldValue'),next=number('newValue'),diff=next-old,rate=old===0?NaN:diff/Math.abs(old)*100;
                    $('difference').textContent=fmt(diff); $('changeRate').textContent=Number.isFinite(rate)?fmt(rate)+'%':'原数值不能为 0';
                    const result=$('changeResult'); result.textContent=diff>0?'上涨 '+fmt(rate)+'%':diff<0?'下降 '+fmt(Math.abs(rate))+'%':'没有变化'; result.className=diff>0?'positive':diff<0?'negative':'';
                }
                document.querySelectorAll('input').forEach(input=>input.addEventListener('input',update));update();
            })();
            </script>
<?php include '_footer.php'; ?>
