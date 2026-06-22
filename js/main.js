(function () {
    // Background image from body data attribute
    const bg = document.body.getAttribute('data-bg');
    if (bg) {
        document.documentElement.style.setProperty('--bg', 'url("' + bg + '")');
    }

    // Current time updater
    function updateTime() {
        const el = document.getElementById('current-time');
        if (!el) return;
        const now = new Date();
        const y = now.getFullYear();
        const m = String(now.getMonth() + 1).padStart(2, '0');
        const d = String(now.getDate()).padStart(2, '0');
        const hh = String(now.getHours()).padStart(2, '0');
        const mm = String(now.getMinutes()).padStart(2, '0');
        const ss = String(now.getSeconds()).padStart(2, '0');
        const weekdays = ['星期日', '星期一', '星期二', '星期三', '星期四', '星期五', '星期六'];
        el.textContent = y + '年' + m + '月' + d + '日 ' + hh + ':' + mm + ':' + ss + ' ' + weekdays[now.getDay()];
    }
    updateTime();
    setInterval(updateTime, 1000);

    // Weather (free API, fallback if unavailable)
    function loadWeather() {
        const el = document.getElementById('current-weather');
        if (!el) return;
        const fallback = '☀️ 晴 25°C';
        try {
            // Use Open-Meteo free API with a default location (Beijing-ish)
            fetch('https://api.open-meteo.com/v1/forecast?latitude=39.9042&longitude=116.4074&current=temperature_2m,weather_code&timezone=Asia%2FShanghai')
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data && data.current) {
                        const temp = Math.round(data.current.temperature_2m);
                        const code = data.current.weather_code;
                        el.textContent = weatherDesc(code) + ' ' + temp + '°C';
                    } else {
                        el.textContent = fallback;
                    }
                })
                .catch(function () {
                    el.textContent = fallback;
                });
        } catch (e) {
            el.textContent = fallback;
        }
    }
    function weatherDesc(code) {
        if ([0].indexOf(code) !== -1) return '☀️ 晴';
        if ([1, 2].indexOf(code) !== -1) return '⛅ 少云';
        if ([3].indexOf(code) !== -1) return '☁️ 多云';
        if ([45, 48].indexOf(code) !== -1) return '🌫️ 雾';
        if ([51, 53, 55, 56, 57].indexOf(code) !== -1) return '🌦️ 毛毛雨';
        if ([61, 63, 65, 66, 67, 80, 81, 82].indexOf(code) !== -1) return '🌧️ 雨';
        if ([71, 73, 75, 77, 85, 86].indexOf(code) !== -1) return '❄️ 雪';
        if ([95, 96, 99].indexOf(code) !== -1) return '⛈️ 雷暴';
        return '🌤️ 天气';
    }
    loadWeather();

    // 导航：点击弹出对应工具区块的悬浮弹框
    const navItems = document.querySelectorAll('.nav-item');

    // 构建悬浮弹框 DOM（工具展示用）
    const toolsModal = document.createElement('div');
    toolsModal.className = 'tools-modal';
    toolsModal.id = 'tools-modal';
    toolsModal.hidden = true;
    toolsModal.innerHTML =
        '<div class="tools-panel">' +
            '<button class="tools-close" id="tools-close" title="关闭">✕</button>' +
            '<div class="tools-header">' +
                '<h2 class="tools-title" id="tools-title"></h2>' +
                '<div class="tools-sub" id="tools-sub"></div>' +
            '</div>' +
            '<div class="tools-body" id="tools-body"></div>' +
        '</div>';
    document.body.appendChild(toolsModal);

    const toolsTitle = document.getElementById('tools-title');
    const toolsSub = document.getElementById('tools-sub');
    const toolsBody = document.getElementById('tools-body');
    const toolsClose = document.getElementById('tools-close');

    function openToolsModal(navItem) {
        const targetId = navItem.getAttribute('data-target');
        const section = document.getElementById(targetId);
        if (!section) return;
        // 读取标题与卡片
        const titleNode = section.querySelector('.section-title');
        const gridNode = section.querySelector('.tool-grid');
        toolsTitle.textContent = titleNode ? titleNode.textContent : '';
        toolsSub.textContent = '点击卡片进入对应工具';
        if (gridNode) {
            toolsBody.innerHTML = gridNode.outerHTML;
        } else {
            toolsBody.innerHTML = '';
        }
        toolsModal.hidden = false;
        navItems.forEach(function (n) { n.classList.remove('active'); });
        navItem.classList.add('active');
    }
    function closeToolsModal() {
        toolsModal.hidden = true;
    }
    navItems.forEach(function (item) {
        item.addEventListener('click', function (e) {
            e.preventDefault();
            openToolsModal(item);
        });
    });
    if (toolsClose) toolsClose.addEventListener('click', closeToolsModal);
    toolsModal.addEventListener('click', function (e) { if (e.target === toolsModal) closeToolsModal(); });
    document.addEventListener('keydown', function (e) {
        if (!toolsModal.hidden && e.key === 'Escape') closeToolsModal();
    });

    // Generate anonymous user ID
    if (!localStorage.getItem('chat_uid')) {
        const uid = 'u' + Math.random().toString(36).slice(2, 8);
        localStorage.setItem('chat_uid', uid);
        const nicknames = ['访客', '匿名者', '路人', '小伙伴', '朋友', '同事', '同学'];
        const colors = ['#6366f1', '#059669', '#ea580c', '#0891b2', '#be185d', '#7c3aed', '#0891b2'];
        localStorage.setItem('chat_nick', nicknames[Math.floor(Math.random() * nicknames.length)]);
        localStorage.setItem('chat_color', colors[Math.floor(Math.random() * colors.length)]);
    }

    // Chat modal
    const toggleBtn = document.getElementById('chat-toggle');
    const modal = document.getElementById('chat-modal');
    const closeBtn = document.getElementById('chat-close');
    const msgBox = document.getElementById('chat-messages');
    const msgInput = document.getElementById('chat-input');
    const sendBtn = document.getElementById('chat-send');
    const onlineCountEl = document.getElementById('online-count');
    const onlinePeopleEl = document.getElementById('online-people');
    function updateOnline(n) {
        if (onlineCountEl) onlineCountEl.textContent = n;
        if (onlinePeopleEl) onlinePeopleEl.textContent = (n || 1) + ' 人';
    }

    let poller = null;
    let lastCheck = 0;

    function openChat() {
        modal.hidden = false;
        heartbeat();
        fetchMessages(true);
        if (!poller) poller = setInterval(function () { fetchMessages(false); }, 3000);
        setTimeout(function () { msgInput.focus(); }, 100);
    }
    function closeChat() {
        modal.hidden = true;
        if (poller) { clearInterval(poller); poller = null; }
    }
    if (toggleBtn) toggleBtn.addEventListener('click', openChat);
    if (closeBtn) closeBtn.addEventListener('click', closeChat);
    if (modal) modal.addEventListener('click', function (e) { if (e.target === modal) closeChat(); });

    function formatTime(ts) {
        const d = new Date(ts * 1000);
        const hh = String(d.getHours()).padStart(2, '0');
        const mm = String(d.getMinutes()).padStart(2, '0');
        return hh + ':' + mm;
    }
    function renderMessages(list) {
        if (!msgBox) return;
        const uid = localStorage.getItem('chat_uid');
        const nick = localStorage.getItem('chat_nick') || '访客';
        const color = localStorage.getItem('chat_color') || '#6366f1';
        msgBox.innerHTML = '';

        // group with day dividers
        let prevDate = '';
        list.forEach(function (m) {
            const d = new Date(m.time * 1000);
            const dateStr = d.getMonth() + 1 + '月' + d.getDate() + '日';
            if (dateStr !== prevDate) {
                const top = document.createElement('div');
                top.className = 'meta-top';
                top.textContent = dateStr + ' ' + formatTime(m.time);
                msgBox.appendChild(top);
                prevDate = dateStr;
            }
            const isSelf = m.uid === uid;
            const row = document.createElement('div');
            row.className = 'msg' + (isSelf ? ' self' : '');
            const avatar = document.createElement('div');
            avatar.className = 'bubble-avatar';
            avatar.style.background = isSelf ? color : (m.color || '#64748b');
            avatar.textContent = (m.nick || '访客').slice(0, 1);
            const bubbleWrap = document.createElement('div');
            const bubble = document.createElement('div');
            bubble.className = 'bubble';
            bubble.textContent = m.text;
            const meta = document.createElement('div');
            meta.className = 'meta';
            meta.textContent = (m.nick || '访客') + ' · ' + formatTime(m.time);
            bubbleWrap.appendChild(bubble);
            bubbleWrap.appendChild(meta);
            row.appendChild(avatar);
            row.appendChild(bubbleWrap);
            msgBox.appendChild(row);
        });
        msgBox.scrollTop = msgBox.scrollHeight;
    }

    function fetchMessages(initial) {
        fetch('api/chat.php?action=list&since=' + lastCheck)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data && data.messages) {
                    renderMessages(data.messages);
                    if (data.online !== undefined) updateOnline(data.online);
                    lastCheck = data.last_time || lastCheck;
                }
            })
            .catch(function () {});
    }

    function heartbeat() {
        fetch('api/chat.php?action=heartbeat', { method: 'POST' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data && data.online !== undefined) updateOnline(data.online);
            })
            .catch(function () {});
    }
    // 页面打开时立刻获取一次在线人数（不依赖聊天弹框）
    (function initialOnline() {
        fetch('api/chat.php?action=heartbeat', { method: 'POST' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data && data.online !== undefined) updateOnline(data.online);
            })
            .catch(function () {});
    })();

    function sendMessage() {
        const text = (msgInput.value || '').trim();
        if (!text) return;
        if (text.length > 500) { alert('单条消息最多 500 字'); return; }
        const uid = localStorage.getItem('chat_uid');
        const nick = localStorage.getItem('chat_nick') || '访客';
        const color = localStorage.getItem('chat_color') || '#6366f1';
        msgInput.value = '';
        fetch('api/chat.php?action=send', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'text=' + encodeURIComponent(text) + '&uid=' + encodeURIComponent(uid) + '&nick=' + encodeURIComponent(nick) + '&color=' + encodeURIComponent(color)
        })
            .then(function () { fetchMessages(false); })
            .catch(function () {});
    }
    if (sendBtn) sendBtn.addEventListener('click', sendMessage);
    if (msgInput) {
        msgInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); sendMessage(); }
        });
    }

    // Periodic heartbeat to maintain "online" status
    setInterval(heartbeat, 20000);
    heartbeat();

    // ==================== 快捷文件传输 ====================
    (function () {
        function showModal(id) {
            const modal = document.getElementById(id);
            if (!modal) return;
            modal.hidden = false;
            document.body.style.overflow = 'hidden';
        }
        function hideModal(modal) {
            if (!modal) return;
            modal.hidden = true;
            document.body.style.overflow = '';
        }

        // 按钮点击 → 打开对应弹框
        document.querySelectorAll('.transfer-action-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const target = btn.getAttribute('data-modal');
                showModal(target);
                // 触发 resetReceiveState（如果弹框是 receive-modal）
                const modal = document.getElementById(target);
                if (modal) {
                    const event = new Event('show');
                    modal.dispatchEvent(event);
                }
            });
        });

        // 关闭事件：关闭按钮 / 点击遮罩 / ESC
        document.querySelectorAll('.tfile-modal').forEach(function (modal) {
            modal.querySelectorAll('[data-close-modal]').forEach(function (closeBtn) {
                closeBtn.addEventListener('click', function () { hideModal(modal); });
            });
            modal.addEventListener('click', function (e) {
                if (e.target === modal) hideModal(modal);
            });
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.tfile-modal').forEach(function (modal) {
                    if (!modal.hidden) hideModal(modal);
                });
            }
        });

        function formatSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            if (bytes < 1024 * 1024 * 1024) return (bytes / 1024 / 1024).toFixed(2) + ' MB';
            return (bytes / 1024 / 1024 / 1024).toFixed(2) + ' GB';
        }

        // ------ 发送文件：拖拽 + 点击 ------
        const sendModal = document.getElementById('send-modal');
        if (sendModal) {
            const drop = document.getElementById('send-transfer-drop');
            const fileInput = document.getElementById('send-transfer-file');
            const progressBox = document.getElementById('send-transfer-progress');
            const progressFill = document.getElementById('send-progress-fill');
            const progressText = document.getElementById('send-progress-text');
            const resultBox = document.getElementById('send-transfer-result');
            const resultCode = document.getElementById('send-result-code');
            const resultName = document.getElementById('send-result-name');

            function uploadFile(file) {
                if (!file) return;
                if (file.size > 50 * 1024 * 1024) {
                    alert('文件超过 50 MB 限制');
                    return;
                }
                if (resultBox) resultBox.hidden = true;
                if (progressBox) {
                    progressBox.hidden = false;
                    if (progressFill) progressFill.style.width = '0%';
                    if (progressText) progressText.textContent = '上传中 0%';
                }

                const fd = new FormData();
                fd.append('file', file);

                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'api/transfer.php?action=upload', true);
                xhr.upload.onprogress = function (e) {
                    if (e.lengthComputable && progressFill && progressText) {
                        const p = Math.round((e.loaded / e.total) * 100);
                        progressFill.style.width = p + '%';
                        progressText.textContent = '上传中 ' + p + '%';
                    }
                };
                xhr.onload = function () {
                    if (progressBox) progressBox.hidden = true;
                    var resp = xhr.responseText || '';
                    var status = xhr.status;
                    if (status >= 200 && status < 300) {
                        try {
                            var data = JSON.parse(resp);
                            if (data && data.ok) {
                                if (resultCode) resultCode.textContent = data.code;
                                if (resultName) resultName.textContent = '📄 ' + data.name + ' (' + formatSize(data.size) + ')';
                                if (resultBox) resultBox.hidden = false;
                            } else {
                                alert('上传失败：' + ((data && data.error) || '未知错误'));
                            }
                        } catch (err) {
                            alert('上传失败：服务器响应异常（' + status + '）\n原始响应: ' + resp.substring(0, 200));
                        }
                    } else {
                        alert('上传失败：HTTP ' + status + '\n' + resp.substring(0, 200));
                    }
                };
                xhr.onerror = function () {
                    if (progressBox) progressBox.hidden = true;
                    alert('上传失败，请检查网络连接');
                };
                xhr.send(fd);
            }

            if (drop && fileInput) {
                // label 已经内嵌了 input type=file，点击 label 会自动触发 input click，
                // 不要再用 fileInput.click() 二次触发，否则会打开两次文件选择器
                fileInput.addEventListener('change', function (e) {
                    if (e.target.files && e.target.files[0]) uploadFile(e.target.files[0]);
                    fileInput.value = '';
                });
                ['dragenter', 'dragover'].forEach(function (ev) {
                    drop.addEventListener(ev, function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        drop.classList.add('drag-over');
                    });
                });
                ['dragleave', 'drop'].forEach(function (ev) {
                    drop.addEventListener(ev, function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        drop.classList.remove('drag-over');
                    });
                });
                drop.addEventListener('drop', function (e) {
                    if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0]) {
                        uploadFile(e.dataTransfer.files[0]);
                    }
                });
            }
        }

        // ------ 接收文件：输入 4 位码 → 下载 ------
        const receiveModal = document.getElementById('receive-modal');
        if (receiveModal) {
            const receiveBtn = document.getElementById('receive-file-btn');
            const receiveInput = document.getElementById('receive-code-input');
            const receiveMsg = document.getElementById('receive-file-msg');
            const receiveDownload = document.getElementById('receive-file-download');
            const receiveFilename = document.getElementById('receive-file-dl-name');
            const receiveDownloadBtn = document.getElementById('receive-file-dl-btn');

            // 当前有效提取码（仅在 checkCode 校验通过后写入）
            let currentCode = '';
            let currentName = '';
            let currentUrl = '';

            // 弹框打开时重置
            receiveModal.addEventListener('show', resetReceiveState);
            resetReceiveState();

            function resetReceiveState() {
                if (receiveInput) receiveInput.value = '';
                if (receiveMsg) { receiveMsg.textContent = ''; receiveMsg.style.color = ''; }
                if (receiveDownload) receiveDownload.hidden = true;
                if (receiveDownloadBtn) {
                    receiveDownloadBtn.disabled = true;
                    receiveDownloadBtn.removeAttribute('data-url');
                    receiveDownloadBtn.removeAttribute('data-name');
                    receiveDownloadBtn.removeAttribute('data-code');
                }
                currentCode = '';
                currentName = '';
                currentUrl = '';
            }

            function checkCode() {
                const code = (receiveInput.value || '').trim();
                if (!/^\d{4}$/.test(code)) {
                    receiveMsg.textContent = '请输入有效的 4 位数字提取码';
                    receiveMsg.style.color = '#dc2626';
                    receiveDownload.hidden = true;
                    currentCode = '';
                    currentName = '';
                    currentUrl = '';
                    return;
                }
                receiveMsg.textContent = '查询中...';
                receiveMsg.style.color = '#64748b';
                receiveDownload.hidden = true;
                currentCode = '';
                currentName = '';
                currentUrl = '';

                fetch('api/transfer.php?action=check&code=' + encodeURIComponent(code))
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.ok) {
                            receiveMsg.textContent = '✅ 找到文件：' + data.name + ' (' + formatSize(data.size) + ')';
                            receiveMsg.style.color = '#059669';
                            receiveFilename.textContent = '📄 ' + data.name;
                            currentCode = code;
                            currentName = data.name || 'file';
                            currentUrl = 'api/transfer.php?action=download&code=' + encodeURIComponent(code);
                            receiveDownloadBtn.setAttribute('data-url', currentUrl);
                            receiveDownloadBtn.setAttribute('data-name', currentName);
                            receiveDownloadBtn.setAttribute('data-code', currentCode);
                            receiveDownloadBtn.disabled = false;
                            receiveDownload.hidden = false;
                        } else {
                            receiveMsg.textContent = '❌ ' + (data.error || '提取码无效或已过期');
                            receiveMsg.style.color = '#dc2626';
                            receiveDownload.hidden = true;
                            currentCode = '';
                            currentName = '';
                            currentUrl = '';
                        }
                    })
                    .catch(function () {
                        receiveMsg.textContent = '❌ 查询失败，请检查网络';
                        receiveMsg.style.color = '#dc2626';
                        receiveDownload.hidden = true;
                        currentCode = '';
                        currentName = '';
                        currentUrl = '';
                    });
            }

            // 点击「获取文件」
            if (receiveBtn) receiveBtn.addEventListener('click', checkCode);
            if (receiveInput) {
                receiveInput.addEventListener('input', function () {
                    receiveInput.value = receiveInput.value.replace(/\D/g, '').slice(0, 4);
                });
                receiveInput.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') checkCode();
                });
            }

            // 点击「下载文件」
            if (receiveDownloadBtn) {
                receiveDownloadBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    // 没经过校验 → 提示用户输入提取码
                    if (!currentCode || !currentUrl) {
                        receiveMsg.textContent = '请输入提取码下载';
                        receiveMsg.style.color = '#dc2626';
                        return;
                    }
                    if (receiveDownloadBtn.disabled) {
                        receiveMsg.textContent = '请输入提取码下载';
                        receiveMsg.style.color = '#dc2626';
                        return;
                    }
                    // ✅ 用浏览器原生下载方式：创建临时 <a href download> → click → 删除
                    // 这样服务器返回什么浏览器就下载什么；如果后端返回错误，浏览器会显示错误页面（不会误下载 index.php）
                    const a = document.createElement('a');
                    a.href = currentUrl;
                    a.download = currentName || 'file';
                    a.target = '_blank';
                    a.rel = 'noopener';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);

                    // 下载触发后清空状态（文件已下载过 → 服务端会删除）
                    setTimeout(function () {
                        resetReceiveState();
                        receiveMsg.textContent = '✅ 下载已开始，文件接收后已从服务器删除';
                        receiveMsg.style.color = '#059669';
                    }, 200);
                });
            }
        }
    })();

    // ==================== 授权码功能 ====================
    (function () {
        const authBtn = document.getElementById('auth-btn');
        const authStatus = document.getElementById('auth-status');
        const authModal = document.getElementById('auth-modal');
        const authClose = document.getElementById('auth-close');
        const authInput = document.getElementById('auth-code-input');
        const authMsg = document.getElementById('auth-msg');
        const authSubmit = document.getElementById('auth-submit');
        const authModalSub = document.getElementById('auth-modal-sub');
        const authResetBtn = document.getElementById('auth-reset-btn');

        // 全局授权状态
        let isAuthorized = false;
        let isInTrial = false;

        // 创建全屏锁定层
        const lockOverlay = document.createElement('div');
        lockOverlay.className = 'auth-lock-overlay';
        lockOverlay.id = 'auth-lock-overlay';
        lockOverlay.hidden = true;
        lockOverlay.innerHTML =
            '<div class="auth-lock-card">' +
                '<div class="auth-lock-icon">🔒</div>' +
                '<div class="auth-lock-title">需要授权</div>' +
                '<div class="auth-lock-sub">本系统需要输入授权码才能使用，授权后即可永久使用</div>' +
                '<button class="auth-lock-btn" id="auth-lock-go">立即授权</button>' +
            '</div>';
        document.body.appendChild(lockOverlay);

        const lockGoBtn = document.getElementById('auth-lock-go');
        if (lockGoBtn) {
            lockGoBtn.addEventListener('click', function () {
                lockOverlay.hidden = true;
                openAuthModal();
            });
        }

        // 检查授权状态
        function checkAuth() {
            fetch('api/auth.php?action=check', { cache: 'no-store' })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data || !data.ok) return;
                    isAuthorized = !!data.authorized;
                    isInTrial = !!data.in_trial;
                    updateAuthUI(data);
                    applyLockState();
                })
                .catch(function () {
                    if (authStatus) authStatus.textContent = '未连接';
                });
        }

        function updateAuthUI(data) {
            if (!authBtn) return;
            if (data.permanent || data.authorized && data.days_left >= 9999) {
                authBtn.setAttribute('data-auth-state', 'permanent');
                if (authStatus) authStatus.textContent = '✓ 已永久授权';
            } else if (data.authorized) {
                const left = data.days_left;
                if (data.expired_red) {
                    authBtn.setAttribute('data-auth-state', 'red');
                    if (authStatus) authStatus.textContent = '⚠️ 即将到期';
                } else {
                    authBtn.setAttribute('data-auth-state', 'ok');
                    if (authStatus) authStatus.textContent = '剩余 ' + left + ' 天';
                }
            } else if (data.in_trial) {
                const hours = data.trial_hours_left || 0;
                authBtn.setAttribute('data-auth-state', 'trial');
                if (authStatus) authStatus.textContent = '试用中 · 剩余 ' + hours + ' 小时';
            } else {
                authBtn.setAttribute('data-auth-state', 'none');
                if (authStatus) authStatus.textContent = '未授权 · 点击输入';
            }
        }

        // 应用锁定状态：已授权 或 试用期内 → 解锁；否则 → 锁定
        function applyLockState() {
            const body = document.body;
            if (isAuthorized || isInTrial) {
                lockOverlay.hidden = true;
                body.classList.remove('auth-locked');
            } else {
                body.classList.add('auth-locked');
            }
        }

        // 拦截所有功能按钮（除授权码按钮自身外）
        function interceptClicks(e) {
            if (isAuthorized || isInTrial) return;
            if (!lockOverlay || !lockOverlay.hidden) return;

            const target = e.target;
            // 找到最近的 button / a 或 data-action
            const actionable = target.closest && target.closest('button, a, [data-action], .tool-card, .nav-item, .transfer-action-btn, .floating-btn');
            if (!actionable) return;
            // 排除授权码按钮自身
            if (actionable.id === 'auth-btn' || actionable.id === 'auth-lock-go' || actionable.id === 'auth-close') return;
            // 排除授权弹框内的元素
            if (target.closest('#auth-modal')) return;

            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            lockOverlay.hidden = false;
        }

        // 用捕获阶段拦截，优先级最高
        document.addEventListener('click', interceptClicks, true);
        document.addEventListener('pointerdown', interceptClicks, true);

        // 点击授权码按钮 → 始终打开弹框
        if (authBtn) {
            authBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                openAuthModal();
            }, true);
        }

        // 弹框内锁按钮重置授权
        if (authResetBtn) {
            authResetBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                if (!confirm('确定要重置授权吗？重置后所有功能将被锁定，需要重新输入授权码。')) return;
                fetch('api/auth.php?action=reset', { method: 'POST', cache: 'no-store' })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data && data.ok) {
                            // 立即强制锁定
                            isAuthorized = false;
                            isInTrial = false;
                            if (authBtn) authBtn.setAttribute('data-auth-state', 'none');
                            if (authStatus) authStatus.textContent = '未授权 · 点击输入';
                            document.body.classList.add('auth-locked');
                            // 关闭弹框并显示锁定层
                            closeAuthModal();
                            lockOverlay.hidden = false;
                            // 异步再同步一次状态
                            setTimeout(checkAuth, 200);
                        }
                    })
                    .catch(function () {
                        alert('重置失败，请稍后重试');
                    });
            });
        }
        if (authClose) authClose.addEventListener('click', closeAuthModal);
        if (authModal) authModal.addEventListener('click', function (e) {
            if (e.target === authModal) closeAuthModal();
        });
        document.addEventListener('keydown', function (e) {
            if (authModal && !authModal.hidden && e.key === 'Escape') closeAuthModal();
        });

        function openAuthModal() {
            if (authModal) {
                authModal.hidden = false;
                document.body.style.overflow = 'hidden';
                // 根据授权状态显示/隐藏重置锁按钮
                if (authResetBtn) {
                    authResetBtn.hidden = !isAuthorized;
                }
                setTimeout(function () { if (authInput) authInput.focus(); }, 100);
            }
        }

        function closeAuthModal() {
            if (authModal) authModal.hidden = true;
            document.body.style.overflow = '';
            if (authInput) authInput.value = '';
            if (authMsg) { authMsg.textContent = ''; authMsg.style.color = ''; }
        }

        // 只允许数字
        if (authInput) {
            authInput.addEventListener('input', function () {
                authInput.value = authInput.value.replace(/\D/g, '').slice(0, 8);
            });
            authInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); submitCode(); }
            });
        }
        if (authSubmit) authSubmit.addEventListener('click', submitCode);

        function submitCode() {
            if (!authInput) return;
            const code = (authInput.value || '').trim();
            if (code.length !== 8) {
                if (authMsg) { authMsg.textContent = '请输入 8 位数字'; authMsg.style.color = '#dc2626'; }
                return;
            }
            if (authMsg) { authMsg.textContent = '验证中...'; authMsg.style.color = '#64748b'; }

            const fd = new FormData();
            fd.append('code', code);
            fetch('api/auth.php?action=verify', { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data && data.ok) {
                        if (authMsg) { authMsg.textContent = '✅ ' + (data.message || '授权成功'); authMsg.style.color = '#059669'; }
                        isAuthorized = true;
                        checkAuth();
                        applyLockState();
                        setTimeout(closeAuthModal, 1200);
                    } else {
                        if (authMsg) { authMsg.textContent = '❌ ' + (data.error || '授权码错误'); authMsg.style.color = '#dc2626'; }
                    }
                })
                .catch(function () {
                    if (authMsg) { authMsg.textContent = '网络错误，请稍后重试'; authMsg.style.color = '#dc2626'; }
                });
        }

        // 页面打开时立即检查
        checkAuth();
        // 每 60 秒 检查一次
        setInterval(checkAuth, 60 * 1000);
    })();
})();
