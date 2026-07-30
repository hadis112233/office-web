<?php
$title = '文本对比';
$desc = '逐行比较两段文本，快速找出新增、删除和未变化的内容。文本不会上传服务器。';
include '_header.php';
?>
            <div class="tool-panel">
                <div class="diff-inputs">
                    <div><label for="leftText">原文本</label><textarea id="leftText" placeholder="粘贴修改前的文本…"></textarea></div>
                    <div><label for="rightText">新文本</label><textarea id="rightText" placeholder="粘贴修改后的文本…"></textarea></div>
                </div>
                <div class="btn-row"><button class="btn success" id="compare" type="button">开始对比</button><button class="btn secondary" id="clear" type="button">清空</button></div>
                <p class="helper" id="status" role="status">最多比较两侧各 500 行。</p>
            </div>
            <div class="tool-panel" id="resultPanel" hidden>
                <div class="legend"><span class="same">未变化</span><span class="removed">删除</span><span class="added">新增</span></div>
                <div class="diff-output" id="diffOutput"></div>
            </div>
            <style>
            .diff-inputs{display:grid;grid-template-columns:1fr 1fr;gap:16px}.diff-inputs textarea{min-height:230px}.helper{color:#64748b;font-size:12px}.legend{display:flex;gap:10px;margin-bottom:12px}.legend span,.diff-line{padding:7px 10px;border-radius:7px}.same{background:#f8fafc}.removed{background:#fef2f2;color:#b91c1c}.added{background:#ecfdf5;color:#047857}.diff-output{display:grid;gap:4px;max-height:520px;overflow:auto;font-family:Consolas,monospace;font-size:13px}.diff-line{display:grid;grid-template-columns:34px 1fr;white-space:pre-wrap;overflow-wrap:anywhere}.diff-line .mark{font-weight:700}@media(max-width:720px){.diff-inputs{grid-template-columns:1fr}}
            </style>
            <script>
            (function(){
                const $=id=>document.getElementById(id);
                function compareLines(a,b){const n=a.length,m=b.length,dp=Array.from({length:n+1},()=>new Uint16Array(m+1));for(let i=n-1;i>=0;i--)for(let j=m-1;j>=0;j--)dp[i][j]=a[i]===b[j]?dp[i+1][j+1]+1:Math.max(dp[i+1][j],dp[i][j+1]);const rows=[];let i=0,j=0;while(i<n||j<m){if(i<n&&j<m&&a[i]===b[j]){rows.push(['same',' ',a[i]]);i++;j++;}else if(j<m&&(i===n||dp[i][j+1]>=dp[i+1][j])){rows.push(['added','+',b[j++]]);}else{rows.push(['removed','−',a[i++]]);}}return rows;}
                $('compare').addEventListener('click',()=>{const left=$('leftText').value.split(/\r?\n/),right=$('rightText').value.split(/\r?\n/);if(left.length>500||right.length>500){$('status').textContent='内容超过 500 行，请拆分后再比较。';return;}const rows=compareLines(left,right),output=$('diffOutput');output.replaceChildren();let added=0,removed=0;rows.forEach(row=>{if(row[0]==='added')added++;if(row[0]==='removed')removed++;const line=document.createElement('div');line.className='diff-line '+row[0];const mark=document.createElement('span');mark.className='mark';mark.textContent=row[1];const text=document.createElement('span');text.textContent=row[2]||' ';line.append(mark,text);output.appendChild(line);});$('resultPanel').hidden=false;$('status').textContent='对比完成：新增 '+added+' 行，删除 '+removed+' 行。';});
                $('clear').addEventListener('click',()=>{$('leftText').value='';$('rightText').value='';$('diffOutput').replaceChildren();$('resultPanel').hidden=true;$('status').textContent='最多比较两侧各 500 行。';$('leftText').focus();});
            })();
            </script>
<?php include '_footer.php'; ?>
