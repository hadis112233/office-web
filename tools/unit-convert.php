<?php
$title = '单位换算';
$desc = '长度/重量/温度 单位互转。';
include '_header.php';
?>
            <div class="tool-panel">
                <label>类型</label>
                <select id="type" onchange="updateUnits()">
                    <option value="length">长度</option>
                    <option value="weight">重量</option>
                    <option value="temp">温度</option>
                </select>
                <label>源单位</label>
                <select id="from"></select>
                <label>目标单位</label>
                <select id="to"></select>
                <label>输入数值</label>
                <input type="number" id="value" placeholder="请输入数值" />
                <div class="btn-row">
                    <button class="btn success" onclick="convert()">换算</button>
                    <button class="btn secondary" onclick="swapUnits()">↕ 交换</button>
                    <button class="btn" onclick="clearAll()">清空</button>
                </div>
            </div>
            <div class="tool-panel">
                <label>换算结果</label>
                <textarea id="output" readonly placeholder="结果将显示在此处..."></textarea>
            </div>
            <script>
            const units = {
                length: { m: 1, cm: 0.01, mm: 0.001, km: 1000, inch: 0.0254, foot: 0.3048, mile: 1609.344 },
                weight: { g: 1, kg: 1000, ton: 1000000, pound: 453.59237, ounce: 28.3495231 },
                temp: { C: 'C', F: 'F', K: 'K' }
            };
            const names = {
                m: '米 (m)', cm: '厘米 (cm)', mm: '毫米 (mm)', km: '千米 (km)',
                inch: '英寸 (inch)', foot: '英尺 (foot)', mile: '英里 (mile)',
                g: '克 (g)', kg: '千克 (kg)', ton: '吨 (ton)', pound: '磅 (pound)', ounce: '盎司 (ounce)',
                C: '摄氏度 (°C)', F: '华氏度 (°F)', K: '开尔文 (K)'
            };
            function $(id) { return document.getElementById(id); }
            function updateUnits() {
                const t = $('type').value;
                const from = $('from');
                const to = $('to');
                from.innerHTML = '';
                to.innerHTML = '';
                Object.keys(units[t]).forEach(k => {
                    from.innerHTML += `<option value="${k}">${names[k]}</option>`;
                    to.innerHTML += `<option value="${k}">${names[k]}</option>`;
                });
                to.selectedIndex = 1 % to.options.length;
            }
            function convert() {
                const v = parseFloat($('value').value);
                if (isNaN(v)) return alert('请输入有效的数值');
                const t = $('type').value;
                const f = $('from').value;
                const to = $('to').value;
                let result;
                if (t === 'temp') {
                    let c;
                    if (f === 'C') c = v;
                    else if (f === 'F') c = (v - 32) * 5 / 9;
                    else c = v - 273.15;
                    if (to === 'C') result = c;
                    else if (to === 'F') result = c * 9 / 5 + 32;
                    else result = c + 273.15;
                } else {
                    result = v * units[t][f] / units[t][to];
                }
                $('output').value = `${v} ${names[f]} = ${result.toFixed(6).replace(/\.?0+$/, '')} ${names[to]}`;
            }
            function swapUnits() {
                const from = $('from');
                const to = $('to');
                const tmp = from.value;
                from.value = to.value;
                to.value = tmp;
            }
            function clearAll() {
                $('value').value = '';
                $('output').value = '';
            }
            updateUnits();
            </script>
<?php include '_footer.php'; ?>
