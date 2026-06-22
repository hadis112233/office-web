<?php
$title = '工作计划';
$desc = '每日工作计划安排，从早6点到晚24点，支持重要提醒。';
include '_header.php';
?>
            <div class="tool-panel time-header">
                <div class="current-time">
                    <span id="currentTime"></span>
                    <span id="currentDate"></span>
                </div>
            </div>

            <div class="tool-panel">
                <h3>📋 今日工作计划</h3>
                <div class="schedule-table">
                    <div class="table-row" onclick="openAddModal()">
                        <div class="table-cell task-cell" id="cell-0"></div>
                    </div>
                    <div class="table-row" onclick="openAddModal()">
                        <div class="table-cell task-cell" id="cell-1"></div>
                    </div>
                    <div class="table-row" onclick="openAddModal()">
                        <div class="table-cell task-cell" id="cell-2"></div>
                    </div>
                    <div class="table-row" onclick="openAddModal()">
                        <div class="table-cell task-cell" id="cell-3"></div>
                    </div>
                    <div class="table-row" onclick="openAddModal()">
                        <div class="table-cell task-cell" id="cell-4"></div>
                    </div>
                    <div class="table-row" onclick="openAddModal()">
                        <div class="table-cell task-cell" id="cell-5"></div>
                    </div>
                </div>
                <div class="clear-all-btn">
                    <button class="btn danger" onclick="clearAllTasks()">🗑️ 清空所有任务</button>
                </div>
            </div>

            <div id="addModal" class="modal-overlay" hidden>
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>➕ 添加任务</h3>
                        <button class="modal-close" onclick="closeAddModal()">✕</button>
                    </div>
                    <div class="modal-body">
                        <div class="modal-field">
                            <label>时间</label>
                            <div class="time-select">
                                <select id="modalHour">
                                    <option value="06">06</option>
                                    <option value="07">07</option>
                                    <option value="08">08</option>
                                    <option value="09">09</option>
                                    <option value="10">10</option>
                                    <option value="11">11</option>
                                    <option value="12">12</option>
                                    <option value="13">13</option>
                                    <option value="14">14</option>
                                    <option value="15">15</option>
                                    <option value="16">16</option>
                                    <option value="17">17</option>
                                    <option value="18">18</option>
                                    <option value="19">19</option>
                                    <option value="20">20</option>
                                    <option value="21">21</option>
                                    <option value="22">22</option>
                                    <option value="23">23</option>
                                    <option value="24">24</option>
                                </select>
                                <span>:</span>
                                <select id="modalMinute">
                                    <option value="00">00</option>
                                    <option value="05">05</option>
                                    <option value="10">10</option>
                                    <option value="15">15</option>
                                    <option value="20">20</option>
                                    <option value="25">25</option>
                                    <option value="30">30</option>
                                    <option value="35">35</option>
                                    <option value="40">40</option>
                                    <option value="45">45</option>
                                    <option value="50">50</option>
                                    <option value="55">55</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-field">
                            <label>任务内容</label>
                            <input type="text" id="modalTask" placeholder="输入任务内容..." />
                        </div>
                        <div class="modal-field">
                            <label class="important-checkbox">
                                <input type="checkbox" id="modalImportant" />
                                ⭐ 重要提醒
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn" onclick="closeAddModal()">取消</button>
                        <button class="btn success" onclick="saveTask()">保存</button>
                    </div>
                </div>
            </div>

            <div id="reminderModal" class="modal-overlay" hidden>
                <div class="modal-content">
                    <div class="modal-header reminder-header">
                        <h3>🔔 任务提醒</h3>
                        <button class="modal-close" onclick="closeReminder()">✕</button>
                    </div>
                    <div class="modal-body">
                        <div id="reminderText"></div>
                        <div id="reminderTime"></div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn" onclick="markCompleted()">✅ 已完成</button>
                        <button class="btn warning" onclick="closeReminder()">⏰ 稍后提醒</button>
                    </div>
                </div>
            </div>

            <style>
            .time-header {
                text-align: center;
                padding: 20px;
                background: linear-gradient(135deg, #6366f1, #8b5cf6);
                color: #fff;
                border-radius: 12px;
            }
            .time-header h3 {
                color: rgba(255,255,255,0.9);
                margin-bottom: 15px;
            }
            .current-time {
                text-align: center;
            }
            #currentTime {
                display: block;
                font-size: 56px;
                font-weight: bold;
                font-family: monospace;
                letter-spacing: 4px;
            }
            #currentDate {
                display: block;
                font-size: 16px;
                opacity: 0.9;
                margin-top: 5px;
            }
            .schedule-table {
                width: 100%;
                border-collapse: collapse;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                overflow: hidden;
            }
            .table-row {
                display: flex;
                border-bottom: 1px solid #e2e8f0;
                transition: background 0.2s;
            }
            .table-row:last-child {
                border-bottom: none;
            }
            .table-row:hover {
                background: #f8fafc;
            }
            .table-cell {
                padding: 16px;
                min-height: 60px;
                display: flex;
                align-items: center;
                cursor: pointer;
            }
            .time-cell {
                width: 80px;
                flex-shrink: 0;
                font-weight: 600;
                color: #475569;
                border-right: 1px solid #e2e8f0;
                background: #f8fafc;
            }
            .task-cell {
                flex: 1;
                color: #1e293b;
                font-size: 14px;
            }
            .task-cell.lunch {
                background: #fffbeb;
                color: #d97706;
                font-weight: 500;
            }
            .task-cell.empty {
                color: #94a3b8;
                font-style: italic;
            }
            .task-item {
                padding: 8px 12px;
                border-radius: 6px;
                margin-bottom: 6px;
                cursor: pointer;
                transition: all 0.2s;
                position: relative;
            }
            .task-item.normal {
                background: #eef2ff;
                color: #4f46e5;
            }
            .task-item.important {
                background: #fee2e2;
                color: #dc2626;
                border-left: 3px solid #dc2626;
            }
            .task-time {
                font-weight: 600;
                font-size: 13px;
                opacity: 0.8;
            }
            .btn.danger {
                background: #ef4444;
                color: #fff;
                border: none;
            }
            .btn.danger:hover {
                background: #dc2626;
            }
            .task-item:hover {
                transform: translateX(4px);
            }
            .task-delete {
                position: absolute;
                right: 8px;
                top: 50%;
                transform: translateY(-50%);
                opacity: 0;
                transition: opacity 0.2s;
                font-size: 14px;
            }
            .task-item:hover .task-delete {
                opacity: 1;
            }
            .modal-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 1000;
            }
            .modal-content {
                background: #fff;
                border-radius: 12px;
                width: 90%;
                max-width: 420px;
                overflow: hidden;
                box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            }
            .time-select {
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .time-select select {
                flex: 1;
            }
            .time-select span {
                font-size: 18px;
                font-weight: bold;
                color: #475569;
            }
            .clear-all-btn {
                margin-top: 15px;
                text-align: right;
            }
            .modal-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 20px;
                background: linear-gradient(135deg, #6366f1, #8b5cf6);
                color: #fff;
            }
            .reminder-header {
                background: linear-gradient(135deg, #dc2626, #ef4444);
            }
            .modal-header h3 {
                margin: 0;
                font-size: 18px;
            }
            .modal-close {
                background: none;
                border: none;
                color: #fff;
                font-size: 20px;
                cursor: pointer;
                padding: 0;
                width: 30px;
                height: 30px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .modal-body {
                padding: 20px;
            }
            .modal-field {
                margin-bottom: 18px;
            }
            .modal-field label {
                display: block;
                font-size: 13px;
                font-weight: 500;
                color: #475569;
                margin-bottom: 8px;
            }
            .modal-field input, .modal-field select {
                width: 100%;
                padding: 12px 14px;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                font-size: 15px;
                box-sizing: border-box;
            }
            .modal-field input:focus, .modal-field select:focus {
                outline: none;
                border-color: #6366f1;
                box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            }
            .important-checkbox {
                display: flex;
                align-items: center;
                gap: 8px;
                cursor: pointer;
                color: #f59e0b;
                font-weight: 500;
            }
            .important-checkbox input {
                width: auto;
                padding: 0;
            }
            #reminderText {
                font-size: 18px;
                font-weight: 600;
                color: #1e293b;
                margin-bottom: 10px;
                text-align: center;
            }
            #reminderTime {
                font-size: 14px;
                color: #64748b;
                text-align: center;
            }
            .modal-footer {
                padding: 15px 20px;
                border-top: 1px solid #e2e8f0;
                display: flex;
                gap: 10px;
            }
            .modal-footer .btn {
                flex: 1;
            }
            </style>

            <script>
            let tasks = [];
            let currentReminder = null;

            function $(id) { return document.getElementById(id); }

            function getTodayDate() {
                const now = new Date();
                return now.getFullYear() + '-' + String(now.getMonth()+1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0');
            }

            function updateTime() {
                const now = new Date();
                const h = String(now.getHours()).padStart(2, '0');
                const m = String(now.getMinutes()).padStart(2, '0');
                const s = String(now.getSeconds()).padStart(2, '0');
                $('currentTime').textContent = h + ':' + m + ':' + s;
                const weekdays = ['周日', '周一', '周二', '周三', '周四', '周五', '周六'];
                $('currentDate').textContent = now.getFullYear() + '年' + (now.getMonth()+1) + '月' + now.getDate() + '日 ' + weekdays[now.getDay()];
            }

            function openAddModal() {
                const now = new Date();
                $('modalHour').value = String(now.getHours()).padStart(2, '0');
                $('modalMinute').value = String(now.getMinutes()).padStart(2, '0');
                $('modalTask').value = '';
                $('modalImportant').checked = false;
                $('addModal').hidden = false;
            }

            function closeAddModal() {
                $('addModal').hidden = true;
            }

            function saveTask() {
                const hour = $('modalHour').value;
                const minute = $('modalMinute').value;
                const taskText = $('modalTask').value.trim();
                const important = $('modalImportant').checked;
                
                if (!taskText) {
                    alert('请输入任务内容');
                    return;
                }
                
                const task = {
                    id: Date.now(),
                    hour: hour,
                    minute: minute,
                    text: taskText,
                    important: important,
                    date: getTodayDate()
                };
                
                tasks.push(task);
                tasks.sort(function(a, b) {
                    const timeA = parseInt(a.hour) * 60 + parseInt(a.minute);
                    const timeB = parseInt(b.hour) * 60 + parseInt(b.minute);
                    return timeA - timeB;
                });
                
                renderTasks();
                saveToStorage();
                closeAddModal();
            }

            function renderTasks() {
                for (let i = 0; i < 6; i++) {
                    const cell = $('cell-' + i);
                    if (!cell) continue;
                    cell.innerHTML = '';
                }
                
                tasks.forEach(function(task, index) {
                    const rowIndex = index % 6;
                    const cell = $('cell-' + rowIndex);
                    if (!cell) return;
                    
                    const taskDiv = document.createElement('div');
                    taskDiv.className = 'task-item ' + (task.important ? 'important' : 'normal');
                    const displayHour = task.hour === '24' ? '00' : task.hour;
                    taskDiv.innerHTML = '<span class="task-time">' + displayHour + ':' + task.minute + '</span> ' + task.text + '<span class="task-delete" onclick="deleteTask(event,' + index + ')">✕</span>';
                    taskDiv.onclick = function(e) {
                        if (e.target.classList.contains('task-delete')) return;
                        if (task.important) {
                            showReminder(task.text, task.hour + task.minute, task.id);
                        }
                    };
                    cell.appendChild(taskDiv);
                });
            }

            function deleteTask(event, index) {
                event.stopPropagation();
                if (confirm('确定删除此任务？')) {
                    tasks.splice(index, 1);
                    renderTasks();
                    saveToStorage();
                }
            }

            function clearAllTasks() {
                if (confirm('确定清空所有任务？')) {
                    tasks = [];
                    renderTasks();
                    saveToStorage();
                }
            }

            function saveToStorage() {
                localStorage.setItem('workSchedule', JSON.stringify({
                    tasks: tasks,
                    date: getTodayDate()
                }));
            }

            function loadFromStorage() {
                const saved = localStorage.getItem('workSchedule');
                if (saved) {
                    try {
                        const data = JSON.parse(saved);
                        if (data.date === getTodayDate()) {
                            tasks = data.tasks || [];
                        } else {
                            tasks = [];
                        }
                    } catch (e) {
                        tasks = [];
                    }
                    renderTasks();
                }
            }

            function checkReminders() {
                const now = new Date();
                const currentHour = String(now.getHours()).padStart(2, '0');
                const currentMinute = String(now.getMinutes()).padStart(2, '0');
                
                tasks.forEach(function(task) {
                    let taskHour = task.hour;
                    if (taskHour === '24') taskHour = '00';
                    
                    if (taskHour === currentHour && task.minute === currentMinute && task.important && !currentReminder) {
                        showReminder(task.text, task.hour + task.minute, task.id);
                    }
                });
            }

            function showReminder(text, time, id) {
                currentReminder = { text, time, id };
                $('reminderText').textContent = text;
                let h = time.substring(0, 2);
                const m = time.substring(2);
                if (h === '00' && time === '0000') h = '24';
                $('reminderTime').textContent = '⏰ ' + h + ':' + m + ' 提醒';
                $('reminderModal').hidden = false;
                
                try {
                    const audio = new Audio('data:audio/wav;base64,UklGRl9vT19XQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2teleBoAMpj');
                    audio.volume = 0.5;
                    audio.play();
                } catch (e) {}
            }

            function closeReminder() {
                $('reminderModal').hidden = true;
                currentReminder = null;
            }

            function markCompleted() {
                if (currentReminder) {
                    tasks = tasks.filter(function(t) { return t.id !== currentReminder.id; });
                    renderTasks();
                    saveToStorage();
                }
                closeReminder();
            }

            updateTime();
            setInterval(updateTime, 1000);
            setInterval(checkReminders, 60000);
            loadFromStorage();
            </script>
<?php include '_footer.php'; ?>