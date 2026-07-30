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

    // 首页工具搜索：只筛选页面内已有工具，不会改变任何工具入口
    (function initToolSearch() {
        const input = document.getElementById('tool-search-input');
        const clear = document.getElementById('tool-search-clear');
        const status = document.getElementById('tool-search-status');
        const empty = document.getElementById('search-empty');
        const sections = Array.prototype.slice.call(document.querySelectorAll('.tool-section'));
        if (!input || !sections.length) return;

        function filterTools() {
            const keyword = input.value.trim().toLowerCase();
            let visibleCount = 0;
            sections.forEach(function (section) {
                let sectionHasVisibleCard = false;
                section.querySelectorAll('.tool-card').forEach(function (card) {
                    const content = (card.textContent || '').toLowerCase();
                    const matched = !keyword || content.indexOf(keyword) !== -1;
                    card.hidden = !matched;
                    if (matched) { visibleCount += 1; sectionHasVisibleCard = true; }
                });
                section.hidden = !!keyword && !sectionHasVisibleCard;
            });
            clear.hidden = !keyword;
            empty.hidden = !keyword || visibleCount > 0;
            status.textContent = keyword ? (visibleCount ? '找到 ' + visibleCount + ' 个相关工具' : '') : '';
        }

        input.addEventListener('input', filterTools);
        clear.addEventListener('click', function () { input.value = ''; filterTools(); input.focus(); });
        document.addEventListener('keydown', function (event) {
            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
                event.preventDefault(); input.focus();
            }
        });
    })();

    // 收藏与最近使用：仅保存到当前浏览器，不上传任何个人使用记录。
    (function initToolShortcuts() {
        const favoriteKey = 'office_tool_favorites';
        const recentKey = 'office_tool_recent';
        const favoriteSection = document.getElementById('favorite-section');
        const favoriteGrid = document.getElementById('favorite-grid');
        const recentBox = document.getElementById('recent-tools');
        const recentList = document.getElementById('recent-tools-list');
        const cards = Array.prototype.slice.call(document.querySelectorAll('.tool-grid > a.tool-card'));
        if (!cards.length) return;
        const catalog = {};
        cards.forEach(function (card) {
            const href = card.getAttribute('href');
            const name = (card.querySelector('.tool-name') || {}).textContent || href;
            const icon = (card.querySelector('.tool-icon') || {}).textContent || '🛠️';
            const desc = (card.querySelector('.tool-desc') || {}).textContent || '';
            if (href && !catalog[href]) catalog[href] = { href: href, name: name.trim(), icon: icon.trim(), desc: desc.trim() };
        });
        function load(key) { try { const value = JSON.parse(localStorage.getItem(key) || '[]'); return Array.isArray(value) ? value : []; } catch (e) { return []; } }
        function save(key, value) { localStorage.setItem(key, JSON.stringify(value)); }
        function render() {
            const favorites = load(favoriteKey).filter(function (href) { return !!catalog[href]; });
            const recents = load(recentKey).filter(function (href) { return !!catalog[href]; });
            favoriteGrid.innerHTML = '';
            favorites.forEach(function (href) {
                const item = catalog[href];
                const card = document.createElement('a');
                card.className = 'tool-card'; card.href = item.href;
                card.innerHTML = '<div class="tool-icon"></div><div class="tool-name"></div><div class="tool-desc"></div>';
                card.querySelector('.tool-icon').textContent = item.icon;
                card.querySelector('.tool-name').textContent = item.name;
                card.querySelector('.tool-desc').textContent = item.desc;
                card.addEventListener('click', function () { recordRecent(item.href); });
                favoriteGrid.appendChild(card);
            });
            favoriteSection.hidden = favorites.length === 0;
            recentList.innerHTML = '';
            recents.slice(0, 5).forEach(function (href) {
                const item = catalog[href]; const link = document.createElement('a');
                link.className = 'recent-tool-chip'; link.href = item.href; link.textContent = item.icon + ' ' + item.name;
                link.addEventListener('click', function () { recordRecent(item.href); }); recentList.appendChild(link);
            });
            recentBox.hidden = recents.length === 0;
            document.querySelectorAll('.favorite-toggle').forEach(function (button) {
                button.setAttribute('aria-pressed', String(favorites.indexOf(button.dataset.href) !== -1));
                button.title = favorites.indexOf(button.dataset.href) !== -1 ? '取消收藏' : '收藏工具';
            });
        }
        function recordRecent(href) {
            const recents = load(recentKey).filter(function (item) { return item !== href; });
            recents.unshift(href); save(recentKey, recents.slice(0, 5));
        }
        cards.forEach(function (card) {
            const href = card.getAttribute('href'); if (!href) return;
            const wrapper = document.createElement('div'); wrapper.className = 'tool-card-wrapper';
            card.parentNode.insertBefore(wrapper, card); wrapper.appendChild(card);
            const button = document.createElement('button'); button.type = 'button'; button.className = 'favorite-toggle'; button.dataset.href = href; button.setAttribute('aria-label', '收藏工具'); button.textContent = '★';
            button.addEventListener('click', function (event) {
                event.preventDefault(); event.stopPropagation();
                const favorites = load(favoriteKey); const index = favorites.indexOf(href);
                if (index === -1) favorites.unshift(href); else favorites.splice(index, 1);
                save(favoriteKey, favorites); render();
            });
            card.addEventListener('click', function () { recordRecent(href); });
            wrapper.appendChild(button);
        });
        render();
    })();

    // 导航：点击弹出对应工具区块的悬浮弹框
    const navItems = document.querySelectorAll('.nav-item');

    // 构建悬浮弹框 DOM（工具展示用）
    const toolsModal = document.createElement('div');
    toolsModal.className = 'tools-modal';
    toolsModal.id = 'tools-modal';
    toolsModal.setAttribute('role', 'dialog');
    toolsModal.setAttribute('aria-modal', 'true');
    toolsModal.setAttribute('aria-labelledby', 'tools-title');
    toolsModal.hidden = true;
    toolsModal.innerHTML =
        '<div class="tools-panel">' +
            '<button class="tools-close" id="tools-close" title="关闭" aria-label="关闭工具分类窗口">✕</button>' +
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
    let toolsReturnFocus = null;

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
        toolsReturnFocus = document.activeElement;
        toolsModal.hidden = false;
        document.body.style.overflow = 'hidden';
        navItems.forEach(function (n) { n.classList.remove('active'); });
        navItem.classList.add('active');
        navItems.forEach(function (n) { n.setAttribute('aria-expanded', String(n === navItem)); });
        setTimeout(function () { if (toolsClose) toolsClose.focus(); }, 0);
    }
    function closeToolsModal() {
        toolsModal.hidden = true;
        document.body.style.overflow = '';
        navItems.forEach(function (n) { n.setAttribute('aria-expanded', 'false'); });
        if (toolsReturnFocus && toolsReturnFocus.focus) toolsReturnFocus.focus();
        toolsReturnFocus = null;
    }
    navItems.forEach(function (item) {
        item.setAttribute('aria-haspopup', 'dialog');
        item.setAttribute('aria-controls', 'tools-modal');
        item.setAttribute('aria-expanded', 'false');
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

    // 生成不可预测的匿名用户标识；旧版短标识会在本次访问自动升级。
    const existingChatUid = localStorage.getItem('chat_uid') || '';
    if (!/^u_[a-f0-9]{32}$/.test(existingChatUid)) {
        const uidBytes = new Uint8Array(16);
        window.crypto.getRandomValues(uidBytes);
        const uid = 'u_' + Array.from(uidBytes, function(byte) {
            return byte.toString(16).padStart(2, '0');
        }).join('');
        localStorage.setItem('chat_uid', uid);
        const nicknames = ['访客', '匿名者', '路人', '小伙伴', '朋友', '同事', '同学'];
        const colors = ['#6366f1', '#059669', '#ea580c', '#0891b2', '#be185d', '#7c3aed', '#0891b2'];
        if (!localStorage.getItem('chat_nick')) localStorage.setItem('chat_nick', nicknames[Math.floor(Math.random() * nicknames.length)]);
        if (!localStorage.getItem('chat_color')) localStorage.setItem('chat_color', colors[Math.floor(Math.random() * colors.length)]);
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
    let chatCursor = '';
    let chatMessages = [];
    let chatRequestInFlight = false;
    let chatSendInFlight = false;
    let pollTicks = 0;
    let chatReturnFocus = null;

    function openChat() {
        chatReturnFocus = document.activeElement;
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
        if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'true');
        heartbeat();
        fetchMessages(true);
        if (!poller) poller = setInterval(function () {
            fetchMessages(false);
            pollTicks += 1;
            if (pollTicks % 5 === 0) heartbeat();
        }, 3000);
        setTimeout(function () { msgInput.focus(); }, 100);
    }
    function closeChat() {
        modal.hidden = true;
        document.body.style.overflow = '';
        if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'false');
        if (poller) { clearInterval(poller); poller = null; }
        if (chatReturnFocus && chatReturnFocus.focus) chatReturnFocus.focus();
        chatReturnFocus = null;
    }
    if (toggleBtn) toggleBtn.addEventListener('click', openChat);
    if (closeBtn) closeBtn.addEventListener('click', closeChat);
    if (modal) modal.addEventListener('click', function (e) { if (e.target === modal) closeChat(); });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal && !modal.hidden) closeChat();
    });

    function formatTime(ts) {
        const d = new Date(ts * 1000);
        const hh = String(d.getHours()).padStart(2, '0');
        const mm = String(d.getMinutes()).padStart(2, '0');
        return hh + ':' + mm;
    }
    function renderMessages(list, keepPosition) {
        if (!msgBox) return;
        const uid = localStorage.getItem('chat_uid');
        const nick = localStorage.getItem('chat_nick') || '访客';
        const color = localStorage.getItem('chat_color') || '#6366f1';
        const stickToBottom = !keepPosition || msgBox.scrollTop + msgBox.clientHeight >= msgBox.scrollHeight - 40;
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
        if (stickToBottom) msgBox.scrollTop = msgBox.scrollHeight;
    }

    function fetchMessages(initial) {
        if (chatRequestInFlight) return;
        chatRequestInFlight = true;
        const url = 'api/chat.php?action=list' + (chatCursor ? '&cursor=' + encodeURIComponent(chatCursor) : '');
        fetch(url)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data && data.messages) {
                    if (initial || !chatCursor || data.reset) {
                        chatMessages = data.messages;
                        renderMessages(chatMessages, false);
                    } else if (data.messages.length) {
                        chatMessages = chatMessages.concat(data.messages);
                        if (chatMessages.length > 100) chatMessages = chatMessages.slice(-100);
                        renderMessages(chatMessages, true);
                    }
                    if (data.online !== undefined) updateOnline(data.online);
                    chatCursor = data.cursor || chatCursor;
                }
            })
            .catch(function () {})
            .finally(function () { chatRequestInFlight = false; });
    }

    function heartbeat() {
        const fd = new FormData();
        fd.append('uid', localStorage.getItem('chat_uid') || '');
        fetch('api/chat.php?action=heartbeat', { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data && data.online !== undefined) updateOnline(data.online);
            })
            .catch(function () {});
    }
    // 在线人数仅在打开聊天室后刷新，避免首页加载时产生额外请求。

    function sendMessage() {
        const text = (msgInput.value || '').trim();
        if (!text) return;
        if (chatSendInFlight) return;
        if (text.length > 500) { alert('单条消息最多 500 字'); return; }
        const uid = localStorage.getItem('chat_uid');
        const nick = localStorage.getItem('chat_nick') || '访客';
        const color = localStorage.getItem('chat_color') || '#6366f1';
        chatSendInFlight = true;
        if (sendBtn) sendBtn.disabled = true;
        fetch('api/chat.php?action=send', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'text=' + encodeURIComponent(text) + '&uid=' + encodeURIComponent(uid) + '&nick=' + encodeURIComponent(nick) + '&color=' + encodeURIComponent(color)
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (!data || !data.ok) throw new Error((data && data.error) || '发送失败');
                if ((msgInput.value || '').trim() === text) msgInput.value = '';
                fetchMessages(false);
            })
            .catch(function (error) { alert(error.message || '消息发送失败，请重试'); })
            .finally(function () {
                chatSendInFlight = false;
                if (sendBtn) sendBtn.disabled = false;
                if (msgInput) msgInput.focus();
            });
    }
    if (sendBtn) sendBtn.addEventListener('click', sendMessage);
    if (msgInput) {
        msgInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); sendMessage(); }
        });
    }

    // ==================== 快捷文件传输 ====================
    (function () {
        function showModal(id) {
            const modal = document.getElementById(id);
            if (!modal) return;
            modal._returnFocus = document.activeElement;
            if (modal._returnFocus && modal._returnFocus.setAttribute) modal._returnFocus.setAttribute('aria-expanded', 'true');
            modal.hidden = false;
            document.body.style.overflow = 'hidden';
            setTimeout(function () {
                const focusTarget = modal.querySelector('input:not([type="file"]), button, [tabindex]:not([tabindex="-1"])');
                if (focusTarget) focusTarget.focus();
            }, 0);
        }
        function hideModal(modal) {
            if (!modal) return;
            modal.hidden = true;
            document.body.style.overflow = '';
            if (modal._returnFocus && modal._returnFocus.setAttribute) modal._returnFocus.setAttribute('aria-expanded', 'false');
            if (modal._returnFocus && modal._returnFocus.focus) modal._returnFocus.focus();
            modal._returnFocus = null;
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
            const resultExpiry = document.getElementById('send-result-expiry');
            let uploadInFlight = false;
            let expiryTimer = null;

            function startExpiryCountdown(seconds) {
                if (expiryTimer) clearInterval(expiryTimer);
                const expiresAt = Date.now() + Math.max(0, Number(seconds) || 600) * 1000;
                function updateExpiry() {
                    const remaining = Math.max(0, Math.ceil((expiresAt - Date.now()) / 1000));
                    if (resultExpiry) {
                        if (remaining > 0) {
                            const minutes = Math.floor(remaining / 60);
                            const secs = String(remaining % 60).padStart(2, '0');
                            resultExpiry.textContent = '接收后立即删除，未接收将在 ' + minutes + ':' + secs + ' 后过期。';
                        } else {
                            resultExpiry.textContent = '该提取码已过期，请重新上传文件。';
                        }
                    }
                    if (remaining <= 0 && expiryTimer) {
                        clearInterval(expiryTimer);
                        expiryTimer = null;
                    }
                }
                updateExpiry();
                expiryTimer = setInterval(updateExpiry, 1000);
            }

            function uploadFile(file) {
                if (!file) return;
                if (uploadInFlight) return;
                if (file.size > 50 * 1024 * 1024) {
                    alert('文件超过 50 MB 限制');
                    return;
                }
                uploadInFlight = true;
                if (expiryTimer) {
                    clearInterval(expiryTimer);
                    expiryTimer = null;
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
                    uploadInFlight = false;
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
                                startExpiryCountdown(data.expires_in);
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
                    uploadInFlight = false;
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

        // ------ 接收文件：输入 6 位码 → 下载 ------
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
                if (!/^\d{6}$/.test(code)) {
                    receiveMsg.textContent = '请输入有效的 6 位数字提取码';
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
                    receiveInput.value = receiveInput.value.replace(/\D/g, '').slice(0, 6);
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

})();
