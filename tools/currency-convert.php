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
                        <div>汇率</div>
                        <div>100 CNY = ?</div>
                    </div>
                    <div class="rate-row"><div>🇺🇸 USD</div><div>7.2450</div><div>13.80</div></div>
                    <div class="rate-row"><div>🇪🇺 EUR</div><div>7.8650</div><div>12.71</div></div>
                    <div class="rate-row"><div>🇬🇧 GBP</div><div>9.1230</div><div>10.96</div></div>
                    <div class="rate-row"><div>🇯🇵 JPY</div><div>0.0485</div><div>2061.86</div></div>
                    <div class="rate-row"><div>🇰🇷 KRW</div><div>0.0053</div><div>18867.92</div></div>
                    <div class="rate-row"><div>🇦🇺 AUD</div><div>4.6850</div><div>21.34</div></div>
                    <div class="rate-row"><div>🇨🇦 CAD</div><div>5.1800</div><div>19.31</div></div>
                    <div class="rate-row"><div>🇭🇰 HKD</div><div>0.9230</div><div>108.34</div></div>
                </div>
                <p class="rate-note">* 汇率仅供参考，实际汇率以银行实时报价为准</p>
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
            </style>
            <script>
            const exchangeRates = {
                CNY: 1,
                USD: 0.1380,
                EUR: 0.1271,
                GBP: 0.1096,
                JPY: 20.6186,
                KRW: 188.6792,
                AUD: 0.2134,
                CAD: 0.1931,
                CHF: 0.1415,
                HKD: 1.0834,
                SGD: 0.1825,
                THB: 4.8520,
                MYR: 0.6420,
                INR: 11.9800,
                RUB: 12.3500,
                BRL: 0.6850,
                MXN: 2.4800,
                ZAR: 2.5850,
                AED: 0.5070,
                TRY: 2.1500
            };
            function $(id) { return document.getElementById(id); }
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
            convertCurrency();
            </script>
<?php include '_footer.php'; ?>