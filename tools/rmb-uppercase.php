<?php
$title = '人民币大写转换';
$desc = '将数字金额转换为财务票据常用的中文大写格式，结果仅供填写前核对。';
include '_header.php';
?>
            <div class="tool-panel">
                <label for="amount">人民币金额</label>
                <div class="amount-row"><span>¥</span><input id="amount" type="number" value="12345.67" min="0" max="999999999999.99" step="0.01"><button class="btn success" id="copy" type="button">复制结果</button></div>
                <div class="result-box rmb-result" id="result" role="status" aria-live="polite"></div>
                <p class="helper">支持 0 至 9999.99 亿元，按四舍五入保留到“分”。正式票据请再次核对金额。</p>
            </div>
            <style>
            .amount-row{display:grid;grid-template-columns:auto minmax(0,1fr) auto;align-items:center;gap:10px;margin:10px 0 18px}.amount-row>span{font-size:25px;color:#4f46e5;font-weight:700}.rmb-result{font-size:20px;font-weight:700;line-height:1.8;color:#7c2d12;background:#fff7ed}.helper{margin-top:12px;color:#64748b;font-size:12px}@media(max-width:560px){.amount-row{grid-template-columns:auto 1fr}.amount-row .btn{grid-column:1/-1}}
            </style>
            <script>
            (function(){
                const digits='零壹贰叁肆伍陆柒捌玖',small=['','拾','佰','仟'],groups=['','万','亿','兆'];
                const amount=document.getElementById('amount'),result=document.getElementById('result'),copy=document.getElementById('copy');
                function four(value){let text='',zero=false;for(let pos=3;pos>=0;pos--){const unit=Math.pow(10,pos),digit=Math.floor(value/unit)%10;if(digit){if(zero&&text)text+='零';text+=digits[digit]+small[pos];zero=false;}else if(text)zero=true;}return text;}
                function integerText(value){if(value===0)return digits[0];let text='',groupIndex=0,needZero=false;while(value>0){const part=value%10000;if(part){const partText=four(part);text=partText+groups[groupIndex]+(needZero&&text?'零':'')+text;needZero=part<1000;}else if(text)needZero=true;value=Math.floor(value/10000);groupIndex++;}return text.replace(/零+/g,'零').replace(/零$/,'');}
                function convert(){const value=Number(amount.value);if(!Number.isFinite(value)||value<0||value>999999999999.99){result.textContent='请输入 0 至 999999999999.99 之间的金额';return;}const cents=Math.round((value+Number.EPSILON)*100),integer=Math.floor(cents/100),jiao=Math.floor(cents/10)%10,fen=cents%10;let text=integerText(integer)+'元';if(jiao===0&&fen===0)text+='整';else{if(jiao)text+=digits[jiao]+'角';else if(integer>0&&fen)text+='零';if(fen)text+=digits[fen]+'分';}result.textContent=text;}
                amount.addEventListener('input',convert);copy.addEventListener('click',async()=>{if(!result.textContent)return;try{await navigator.clipboard.writeText(result.textContent);copy.textContent='已复制';setTimeout(()=>copy.textContent='复制结果',1200);}catch(e){alert('复制失败，请手动选择结果复制。');}});convert();
            })();
            </script>
<?php include '_footer.php'; ?>
