<?php
$title = '汇率换算';
$desc = '世界主要货币汇率换算，支持国外兑本国、国外兑国外。';
include '_header.php';
?>
            <div class="tool-panel">
                <div class="currency-header">
                    <span class="currency-flag">🌍</span>
                    <span>汇率换算</span>
                </div>
                <div class="currency-row">
                    <div class="currency-box">
                        <label>源货币</label>
                        <select id="fromCurrency">
                            <option value="CNY">🇨🇳 人民币 (CNY)</option>
                            <option value="USD">🇺🇸 美元 (USD)</option>
                            <option value="EUR">🇪🇺 欧元 (EUR)</option>
                            <option value="GBP">🇬🇧 英镑 (GBP)</option>
                            <option value="JPY">🇯🇵 日元 (JPY)</option>
                            <option value="KRW">🇰🇷 韩元 (KRW)</option>
                            <option value="AUD">🇦🇺 澳元 (AUD)</option>
                            <option value="CAD">🇨🇦 加元 (CAD)</option>
                            <option value="CHF">🇨🇭 瑞士法郎 (CHF)</option>
                            <option value="HKD">🇭🇰 港币 (HKD)</option>
                            <option value="SGD">🇸🇬 新加坡元 (SGD)</option>
                            <option value="THB">🇹🇭 泰铢 (THB)</option>
                            <option value="MYR">🇲🇾 马来西亚林吉特 (MYR)</option>
                            <option value="INR">🇮🇳 印度卢比 (INR)</option>
                            <option value="RUB">🇷🇺 俄罗斯卢布 (RUB)</option>
                            <option value="BRL">🇧🇷 巴西雷亚尔 (BRL)</option>
                            <option value="MXN">🇲🇽 墨西哥比索 (MXN)</option>
                            <option value="ZAR">🇿🇦 南非兰特 (ZAR)</option>
                            <option value="AED">🇦🇪 阿联酋迪拉姆 (AED)</option>
                            <option value="TRY">🇹🇷 土耳其里拉 (TRY)</option>
                        </select>
                        <input type="number" id="amount" placeholder="输入金额" value="100" />
                    </div>
                    <button class="swap-btn" onclick="swapCurrencies()">⇄</button>
                    <div class="currency-box">
                        <label>目标货币</label>
                        <select id="toCurrency">
                            <option value="CNY">🇨🇳 人民币 (CNY)</option>
                            <option value="USD">🇺🇸 美元 (USD)</option>
                            <option value="EUR">🇪🇺 欧元 (EUR)</option>
                            <option value="GBP">🇬🇧 英镑 (GBP)</option>
                            <option value="JPY">🇯🇵 日元 (JPY)</option>
                            <option value="KRW">🇰🇷 韩元 (KRW)</option>
                            <option value="AUD">🇦🇺 澳元 (AUD)</option>
                            <option value="CAD">🇨🇦 加元 (CAD)</option>
                            <option value="CHF">🇨🇭 瑞士法郎 (CHF)</option>
                            <option value="HKD">🇭🇰 港币 (HKD)</option>
                            <option value="SGD">🇸🇬 新加坡元 (SGD)</option>
                            <option value="THB">🇹🇭 泰铢 (THB)</option>
                            <option value="MYR">🇲🇾 马来西亚林吉特 (MYR)</option>
                            <option value="INR">🇮🇳 印度卢比 (INR)</option>
                            <option value="RUB">🇷🇺 俄罗斯卢布 (RUB)</option>
                            <option value="BRL">🇧🇷 巴西雷亚尔 (BRL)</option>
                            <option value="MXN">🇲🇽 墨西哥比索 (MXN)</option>
                            <option value="ZAR">🇿🇦 南非兰特 (ZAR)</option>
                            <option value="AED">🇦🇪 阿联酋迪拉姆 (AED)</option>
                            <option value="TRY">🇹🇷 土耳其里拉 (TRY)</option>
                        </select>
                        <input type="number" id="result" readonly placeholder="换算结果" />
                    </div>
                </div>
                <div class="btn-row">
                    <button class="btn success" onclick="convertCurrency()">开始换算</button>
                    <button class="btn" onclick="clearAll()">清空</button>
                    <button class="btn secondary" onclick="quickCNY()">快速转人民币</button>
                </div>
            </div>
            <div class="tool-panel">
                <h3>汇率参考表（基准：人民币 CNY）</h3>
                <div class="rate-table">
                    <div class="rate-row header">
                        <div>货币</div>
                        <div>1 CNY 可兑换</div>
                        <div>1 单位折合 CNY</div>
                    </div>
                    <div id="rateRows"></div>
                </div>
                <p class="rate-note" id="rateStatus" role="status" aria-live="polite">正在读取本机汇率缓存…</p>
                <p class="rate-note">参考汇率不等于银行、信用卡或现金兑换成交价。</p>
            </div>
            <style>
            .currency-header {
                display: flex;
                align-items: center;
                gap: 10px;
                font-size: 18px;
                font-weight: 700;
                color: #4f46e5;
                margin-bottom: 20px;
            }
            .currency-flag { font-size: 24px; }
            .currency-row {
                display: flex;
                align-items: center;
                gap: 15px;
                margin-bottom: 20px;
            }
            .currency-box {
                flex: 1;
                display: flex;
                flex-direction: column;
                gap: 10px;
            }
            .currency-box select {
                padding: 12px 14px;
                border: 2px solid #e2e8f0;
                border-radius: 10px;
                font-size: 14px;
                background: #fff;
                cursor: pointer;
                transition: all 0.2s;
            }
            .currency-box select:focus {
                outline: none;
                border-color: #6366f1;
                box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            }
            .currency-box input {
                padding: 12px 14px;
                border: 2px solid #e2e8f0;
                border-radius: 10px;
                font-size: 16px;
                font-weight: 600;
            }
            .currency-box input:focus {
                outline: none;
                border-color: #6366f1;
            }
            .swap-btn {
                width: 48px;
                height: 48px;
                border-radius: 50%;
                border: 2px solid #6366f1;
                background: linear-gradient(135deg, #eef2ff, #ddd6fe);
                color: #4f46e5;
                font-size: 20px;
                cursor: pointer;
                transition: all 0.2s;
            }
            .swap-btn:hover {
                transform: rotate(180deg);
                background: linear-gradient(135deg, #4f46e5, #6366f1);
                color: #fff;
            }
            .rate-table {
                background: #f8fafc;
                border-radius: 10px;
                overflow: hidden;
                margin-bottom: 15px;
            }
            .rate-row {
                display: flex;
                padding: 10px 15px;
                border-bottom: 1px solid #e2e8f0;
            }
            .rate-row:last-child { border-bottom: none; }
            .rate-row.header {
                background: #6366f1;
                color: #fff;
                font-weight: 600;
            }
            .rate-row > div { flex: 1; text-align: center; }
            .rate-note {
                font-size: 12px;
                color: #64748b;
                text-align: center;
                margin: 0;
            }
            #rateStatus { margin-bottom: 6px; font-weight: 600; }
            #rateStatus.live { color: #047857; }
            #rateStatus.warning { color: #b45309; }
            </style>
            <script>
            let exchangeRates = {
                CNY: 1,
                USD: 0.14779,
                EUR: 0.12974,
                GBP: 0.11105,
                JPY: 24.215,
                KRW: 216.11,
                AUD: 0.21187,
                CAD: 0.20845,
                CHF: 0.12079,
                HKD: 1.16,
                SGD: 0.19097,
                THB: 4.9659,
                MYR: 0.60415,
                INR: 14.1712,
                RUB: 11.5711,
                BRL: 0.75487,
                MXN: 2.5805,
                ZAR: 2.4749,
                AED: 0.54277,
                TRY: 6.9986
            };
            const supportedCurrencies = Object.keys(exchangeRates);
            const popularCurrencies = [
                ['USD', '🇺🇸'], ['EUR', '🇪🇺'], ['GBP', '🇬🇧'], ['JPY', '🇯🇵'],
                ['KRW', '🇰🇷'], ['AUD', '🇦🇺'], ['CAD', '🇨🇦'], ['HKD', '🇭🇰']
            ];
            function $(id) { return document.getElementById(id); }
            function formatRate(value) {
                if (value >= 100) return value.toFixed(2);
                if (value >= 1) return value.toFixed(4).replace(/\.?0+$/, '');
                return value.toFixed(6).replace(/\.?0+$/, '');
            }
            function renderRateTable() {
                const rows = $('rateRows');
                rows.innerHTML = '';
                popularCurrencies.forEach(function(item) {
                    const code = item[0];
                    const rate = exchangeRates[code];
                    if (!Number.isFinite(rate) || rate <= 0) return;
                    const row = document.createElement('div');
                    row.className = 'rate-row';
                    [item[1] + ' ' + code, formatRate(rate), formatRate(1 / rate)].forEach(function(value) {
                        const cell = document.createElement('div');
                        cell.textContent = value;
                        row.appendChild(cell);
                    });
                    rows.appendChild(row);
                });
            }
            function convertCurrency() {
                const from = $('fromCurrency').value;
                const to = $('toCurrency').value;
                const amount = parseFloat($('amount').value);
                if (isNaN(amount) || amount <= 0) {
                    return alert('请输入有效的金额');
                }
                // 先转成人民币，再转成目标货币
                const cnyAmount = amount / exchangeRates[from];
                const result = cnyAmount * exchangeRates[to];
                $('result').value = result.toFixed(4).replace(/\.?0+$/, '');
            }
            function swapCurrencies() {
                const from = $('fromCurrency');
                const to = $('toCurrency');
                const tmp = from.value;
                from.value = to.value;
                to.value = tmp;
                convertCurrency();
            }
            function clearAll() {
                $('amount').value = '';
                $('result').value = '';
            }
            function quickCNY() {
                $('toCurrency').value = 'CNY';
                convertCurrency();
            }
            async function loadExchangeRates() {
                const status = $('rateStatus');
                const controller = new AbortController();
                const timeout = setTimeout(function() { controller.abort(); }, 10000);
                try {
                    const response = await fetch('../api/exchange.php', { cache: 'no-store', signal: controller.signal });
                    const data = await response.json();
                    if (!response.ok || !data.ok || !data.rates) throw new Error(data.error || '汇率服务不可用');
                    const nextRates = {};
                    supportedCurrencies.forEach(function(code) {
                        const rate = Number(data.rates[code]);
                        if (Number.isFinite(rate) && rate > 0) nextRates[code] = rate;
                    });
                    if (Object.keys(nextRates).length !== supportedCurrencies.length || nextRates.CNY !== 1) throw new Error('汇率数据不完整');
                    exchangeRates = nextRates;
                    status.className = 'rate-note ' + (data.stale ? 'warning' : 'live');
                    status.textContent = data.stale
                        ? '当前断网，使用 ' + data.date + ' 的本机缓存'
                        : '参考汇率日期：' + data.date + (data.cached ? '（本机缓存）' : '（已更新）');
                } catch (error) {
                    status.className = 'rate-note warning';
                    status.textContent = '无法获取在线或缓存汇率，当前使用 2026-07-29 内置参考值';
                } finally {
                    clearTimeout(timeout);
                    renderRateTable();
                    convertCurrency();
                }
            }
            ['amount', 'fromCurrency', 'toCurrency'].forEach(function(id) {
                $(id).addEventListener(id === 'amount' ? 'input' : 'change', convertCurrency);
            });
            renderRateTable();
            convertCurrency();
            loadExchangeRates();
            </script>
<?php include '_footer.php'; ?>
