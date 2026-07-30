<?php
$title = '工资计算器';
$desc = '按月估算社保、公积金、个人所得税和到手工资，仅供参考。';
include '_header.php';
?>
            <div class="tool-panel salary-hero">
                <h2>工资计算器</h2>
                <p>输入常见扣缴比例，立即查看到手工资明细。</p>
            </div>
            <div class="salary-layout">
                <div class="tool-panel">
                    <div class="salary-form">
                        <label>税前月薪（元）<input id="gross" type="number" min="0" value="18000" inputmode="decimal"></label>
                        <label>社保比例（%）<input id="socialRate" type="number" min="0" max="100" step="0.1" value="8" inputmode="decimal"></label>
                        <label>公积金比例（%）<input id="housingRate" type="number" min="0" max="100" step="0.1" value="7" inputmode="decimal"></label>
                        <label>专项附加扣除（元）<input id="extra" type="number" min="0" value="0" inputmode="decimal"></label>
                        <label>起征点（元）<input id="threshold" type="number" min="0" value="5000" inputmode="decimal"></label>
                    </div>
                    <div class="btn-row"><button class="btn success" id="calculate" type="button">开始计算</button><button class="btn secondary" id="reset" type="button">恢复默认</button></div>
                    <p class="tip">提示：实际个税与当地缴费基数、累计收入及专项扣除有关，请以工资单为准。</p>
                </div>
                <div class="salary-results" aria-live="polite">
                    <article><span>税后到手</span><strong id="takeHome">¥0.00</strong><small>扣除税费与社保公积金</small></article>
                    <article><span>个税</span><strong id="incomeTax">¥0.00</strong><small id="taxMeta">税率：0%</small></article>
                    <article><span>社保公积金</span><strong id="contribution">¥0.00</strong><small>按比例估算</small></article>
                    <article><span>应纳税所得额</span><strong id="taxable">¥0.00</strong><small>税前 − 扣除项</small></article>
                </div>
            </div>
            <div class="tool-panel salary-breakdown"><h3>计算明细</h3><div id="breakdown"></div></div>
            <style>
            .salary-hero { color:#fff; background:linear-gradient(135deg,#f97316,#f59e0b); }.salary-hero h2{color:#fff;margin-bottom:5px}.salary-hero p{opacity:.9}
            .salary-layout{display:grid;grid-template-columns:minmax(280px,1fr) 1fr;gap:18px}.salary-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.salary-form label{font-size:13px;color:#475569}.salary-form input{display:block;width:100%;margin-top:6px;padding:11px;border:1px solid #dbe3f0;border-radius:8px;font:inherit}.salary-results{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.salary-results article{padding:18px;border:1px solid #fed7aa;border-radius:12px;background:#fff7ed}.salary-results span,.salary-results small{display:block;color:#9a3412;font-size:12px}.salary-results strong{display:block;margin:8px 0;color:#9a3412;font-size:24px}.salary-breakdown h3{margin-bottom:10px}.salary-breakdown div{display:grid;gap:8px}.salary-breakdown p{display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid #eef2f7;color:#475569}.salary-breakdown b{color:#1e293b}@media(max-width:700px){.salary-layout{grid-template-columns:1fr}.salary-form,.salary-results{grid-template-columns:1fr 1fr}.salary-results strong{font-size:20px}}
            </style>
            <script>
            (function(){
                const ids=['gross','socialRate','housingRate','extra','threshold']; const $=id=>document.getElementById(id);
                const money=n=>'¥'+Number(Math.max(0,n)).toLocaleString('zh-CN',{minimumFractionDigits:2,maximumFractionDigits:2});
                function read(id){return Math.max(0,Number($(id).value)||0)}
                function calc(){const gross=read('gross'),social=gross*read('socialRate')/100,housing=gross*read('housingRate')/100,extra=read('extra'),threshold=read('threshold');const taxable=Math.max(0,gross-social-housing-extra-threshold);const brackets=[[3000,.03,0],[12000,.1,210],[25000,.2,1410],[35000,.25,2660],[55000,.3,4410],[80000,.35,7160],[Infinity,.45,15160]];const bracket=brackets.find(item=>taxable<=item[0]);const tax=taxable*bracket[1]-bracket[2],takeHome=gross-social-housing-tax;$('takeHome').textContent=money(takeHome);$('incomeTax').textContent=money(tax);$('contribution').textContent=money(social+housing);$('taxable').textContent=money(taxable);$('taxMeta').textContent='税率：'+(bracket[1]*100)+'%';$('breakdown').innerHTML=[['税前工资',gross],['社保扣除',-social],['公积金扣除',-housing],['专项附加扣除',-extra],['个税',-tax],['预计到手',takeHome]].map(row=>'<p><span>'+row[0]+'</span><b>'+money(row[1])+'</b></p>').join('')}
                $('calculate').addEventListener('click',calc);ids.forEach(id=>$(id).addEventListener('input',calc));$('reset').addEventListener('click',()=>{const values=[18000,8,7,0,5000];ids.forEach((id,i)=>$(id).value=values[i]);calc()});calc();
            })();
            </script>
<?php include '_footer.php'; ?>
