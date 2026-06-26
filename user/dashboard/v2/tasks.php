<?php
// user/dashboard/v2/tasks.php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/constants.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

if (!isLoggedIn()) {
    redirect('/login.php?redirect=/user/dashboard/v2/tasks.php');
}

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

// فیلترها
$view = $_GET['view'] ?? 'board';
$filter = $_GET['filter'] ?? 'all';
$sort = $_GET['sort'] ?? 'position';
$order = $_GET['order'] ?? 'ASC';
$category = $_GET['category'] ?? '';
$priority = $_GET['priority'] ?? '';

// ساخت کوئری
$query = "SELECT * FROM tasks WHERE user_id = ?";
$params = [$user_id];

if ($filter === 'today') {
    $query .= " AND (due_date = CURDATE() OR start_date = CURDATE())";
} elseif ($filter === 'week') {
    $query .= " AND due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
} elseif ($filter === 'month') {
    $query .= " AND due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
} elseif ($filter === 'overdue') {
    $query .= " AND due_date < CURDATE() AND status != 'done'";
} elseif ($filter === 'todo') {
    $query .= " AND status = 'todo'";
} elseif ($filter === 'in_progress') {
    $query .= " AND status = 'in_progress'";
} elseif ($filter === 'done') {
    $query .= " AND status = 'done'";
}

if ($category) {
    $query .= " AND category = ?";
    $params[] = $category;
}

if ($priority) {
    $query .= " AND priority = ?";
    $params[] = $priority;
}

// مرتب‌سازی
$allowed_sorts = ['position', 'priority', 'due_date', 'created_at', 'title'];
if (!in_array($sort, $allowed_sorts)) $sort = 'position';
$order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';

if ($sort === 'priority') {
    $query .= " ORDER BY FIELD(priority, 'urgent', 'high', 'medium', 'low') $order";
} else {
    $query .= " ORDER BY $sort $order";
}

$stmt = $db->prepare($query);
$stmt->execute($params);
$tasks = $stmt->fetchAll();

// گروه‌بندی برای Kanban
$board = ['todo' => [], 'in_progress' => [], 'done' => [], 'cancelled' => []];
foreach ($tasks as $task) {
    $board[$task['status']][] = $task;
}

// آمار
$stats = [
    'total' => count($tasks),
    'todo' => count($board['todo']),
    'in_progress' => count($board['in_progress']),
    'done' => count($board['done']),
    'overdue' => 0,
    'today' => 0,
];
foreach ($tasks as $t) {
    if ($t['due_date'] && $t['due_date'] < date('Y-m-d') && $t['status'] != 'done') $stats['overdue']++;
    if ($t['due_date'] == date('Y-m-d')) $stats['today']++;
}

$user_data = getUserData($user_id);
$page_title = 'مدیریت تسک‌ها | ' . SITE_NAME;
$extra_css = ['user/dashboard/v2/assets/css/dashboard.css'];
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
?>

<style>
/* ========== Task Manager Styles ========== */
.task-manager { display: flex; flex-direction: column; gap: 16px; }

/* Toolbar */
.task-toolbar { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; padding: 12px 16px; background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; }
.task-toolbar select, .task-toolbar input { padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg-input); color: var(--text-primary); font-family: var(--font); font-size: 0.85rem; }
.view-toggle { display: flex; gap: 2px; background: var(--bg-tertiary); border-radius: 8px; padding: 3px; }
.view-btn { padding: 8px 14px; border-radius: 6px; border: none; cursor: pointer; font-size: 0.85rem; font-family: var(--font); background: transparent; color: var(--text-secondary); }
.view-btn.active { background: var(--bg-card); color: var(--primary); font-weight: 600; box-shadow: var(--shadow-sm); }

/* Stats bar */
.stats-bar { display: flex; gap: 12px; flex-wrap: wrap; }
.stat-chip { display: flex; align-items: center; gap: 6px; padding: 8px 14px; background: var(--bg-card); border: 1px solid var(--border); border-radius: 20px; font-size: 0.8rem; cursor: pointer; transition: all 0.2s; }
.stat-chip:hover { border-color: var(--primary); }
.stat-chip .dot { width: 8px; height: 8px; border-radius: 50%; }

/* Kanban Board */
.kanban-board { display: flex; gap: 14px; overflow-x: auto; padding-bottom: 16px; min-height: 50vh; }
.kanban-col { flex: 1; min-width: 270px; max-width: 340px; background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; padding: 12px; display: flex; flex-direction: column; }
.kanban-col-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; padding-bottom: 8px; border-bottom: 2px solid var(--border); }
.kanban-list { flex: 1; min-height: 80px; display: flex; flex-direction: column; gap: 6px; border-radius: 8px; transition: background 0.2s; }
.kanban-list.drag-over { background: var(--primary-light); }

/* Task Card */
.task-card { background: var(--bg-primary); border: 1px solid var(--border); border-radius: 10px; padding: 12px; cursor: grab; transition: all 0.2s; position: relative; }
.task-card:hover { box-shadow: var(--shadow-md); border-color: var(--primary); }
.task-card.dragging { opacity: 0.4; transform: rotate(1deg); }
.task-card .card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px; }
.task-card .card-title { font-size: 0.9rem; font-weight: 600; flex: 1; }
.task-card .card-priority { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; margin-top: 4px; }
.priority-urgent { background: #7c3aed; box-shadow: 0 0 8px #7c3aed; }
.priority-high { background: #f44336; }
.priority-medium { background: #ff9800; }
.priority-low { background: #4caf50; }

.task-card .card-meta { display: flex; gap: 12px; font-size: 0.7rem; color: var(--text-muted); flex-wrap: wrap; }
.task-card .card-actions { display: flex; gap: 4px; margin-top: 8px; opacity: 0; transition: opacity 0.2s; }
.task-card:hover .card-actions { opacity: 1; }
.card-actions button { background: none; border: 1px solid var(--border); padding: 3px 8px; border-radius: 4px; cursor: pointer; font-size: 0.7rem; color: var(--text-secondary); font-family: var(--font); }
.card-actions button:hover { background: var(--bg-hover); }

.add-task-btn { background: none; border: 1px dashed var(--border); color: var(--text-muted); padding: 10px; border-radius: 8px; cursor: pointer; text-align: center; font-size: 0.8rem; margin-top: 6px; }
.add-task-btn:hover { border-color: var(--primary); color: var(--primary); }

/* Table View */
.table-view { background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; }
.table-view table { width: 100%; border-collapse: collapse; }
.table-view th { padding: 12px; background: var(--bg-tertiary); font-size: 0.85rem; cursor: pointer; user-select: none; }
.table-view th:hover { background: var(--bg-hover); }
.table-view td { padding: 10px 12px; border-bottom: 1px solid var(--border-light); font-size: 0.85rem; }
.table-row { cursor: pointer; transition: background 0.15s; }
.table-row:hover { background: var(--bg-hover); }
.sort-icon { font-size: 0.7rem; margin-right: 4px; }

/* Calendar Mini */
.calendar-mini { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; padding: 12px; }
.calendar-mini .day-header { text-align: center; font-size: 0.7rem; color: var(--text-muted); padding: 4px; }
.calendar-mini .day { aspect-ratio: 1; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 0.8rem; cursor: pointer; transition: all 0.2s; }
.calendar-mini .day:hover { background: var(--bg-hover); }
.calendar-mini .day.today { background: var(--primary); color: white; font-weight: 700; }
.calendar-mini .day.has-task { position: relative; }
.calendar-mini .day.has-task::after { content: ''; position: absolute; bottom: 2px; width: 4px; height: 4px; border-radius: 50%; background: var(--primary); }
.calendar-mini .day.other-month { opacity: 0.3; }

/* Modal */
.modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 1000; align-items: center; justify-content: center; }
.modal-overlay.open { display: flex; }
.modal-box { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; padding: 24px; width: 90%; max-width: 500px; max-height: 85vh; overflow-y: auto; }
.modal-box h2 { margin-bottom: 16px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

@media (max-width: 768px) {
    .kanban-board { flex-direction: column; }
    .kanban-col { max-width: 100%; }
    .form-row { grid-template-columns: 1fr; }
    .task-toolbar { flex-direction: column; align-items: stretch; }
}
</style>

<div class="dashboard-container">
    <aside class="dashboard-sidebar">
        <div class="user-profile-summary">
            <div class="avatar"><?php echo mb_substr($user_data['full_name'] ?? 'U', 0, 1); ?></div>
            <h3><?php echo sanitize($user_data['full_name'] ?? 'کاربر'); ?></h3>
        </div>
        <nav class="dashboard-nav">
            <a href="/user/dashboard/v2/" class="nav-item"><i class="fas fa-home"></i> داشبورد</a>
            <a href="/user/dashboard/v2/chat.php" class="nav-item"><i class="fas fa-comments"></i> چت AI</a>
            <a href="/projects/" class="nav-item"><i class="fab fa-github"></i> پروژه‌ها</a>
            <a href="/user/dashboard/v2/image.php" class="nav-item"><i class="fas fa-image"></i> ساخت عکس</a>
            <a href="/user/dashboard/v2/tasks.php" class="nav-item active"><i class="fas fa-tasks"></i> تسک‌ها</a>
            <a href="/user/dashboard/v2/history.php" class="nav-item"><i class="fas fa-history"></i> تاریخچه</a>
            <a href="/user/dashboard/v2/profile.php" class="nav-item"><i class="fas fa-user"></i> پروفایل</a>
            <a href="/logout.php" class="nav-item nav-item-danger"><i class="fas fa-sign-out-alt"></i> خروج</a>
        </nav>
    </aside>
    
    <main class="dashboard-main">
        <h1>📋 مدیریت تسک‌ها</h1>
        
        <!-- Stats Bar -->
        <div class="stats-bar" style="margin-bottom:16px;">
            <a href="?view=<?php echo $view; ?>&filter=all" class="stat-chip" style="text-decoration:none;">
                📊 همه: <strong><?php echo $stats['total']; ?></strong>
            </a>
            <a href="?view=<?php echo $view; ?>&filter=today" class="stat-chip" style="text-decoration:none;">
                📅 امروز: <strong><?php echo $stats['today']; ?></strong>
            </a>
            <a href="?view=<?php echo $view; ?>&filter=overdue" class="stat-chip" style="text-decoration:none;<?php echo $stats['overdue']>0?'border-color:#f44336;color:#f44336;':''; ?>">
                ⚠️ عقب‌افتاده: <strong><?php echo $stats['overdue']; ?></strong>
            </a>
            <span class="stat-chip"><span class="dot" style="background:#4caf50;"></span> انجام شده: <strong><?php echo $stats['done']; ?></strong></span>
        </div>
        
        <!-- Toolbar -->
        <div class="task-toolbar" style="margin-bottom:16px;">
            <!-- View Toggle -->
            <div class="view-toggle">
                <a href="?view=board<?php echo $filter!='all'?'&filter='.$filter:''; ?>" class="view-btn <?php echo $view=='board'?'active':''; ?>">📋 کانبان</a>
                <a href="?view=table<?php echo $filter!='all'?'&filter='.$filter:''; ?>" class="view-btn <?php echo $view=='table'?'active':''; ?>">📊 جدول</a>
                <a href="?view=calendar<?php echo $filter!='all'?'&filter='.$filter:''; ?>" class="view-btn <?php echo $view=='calendar'?'active':''; ?>">📅 تقویم</a>
            </div>
            
            <select onchange="location.href='?view=<?php echo $view; ?>&filter='+this.value">
                <option value="all" <?php echo $filter=='all'?'selected':''; ?>>همه تسک‌ها</option>
                <option value="today" <?php echo $filter=='today'?'selected':''; ?>>📅 امروز</option>
                <option value="week" <?php echo $filter=='week'?'selected':''; ?>>📆 این هفته</option>
                <option value="month" <?php echo $filter=='month'?'selected':''; ?>>🗓️ این ماه</option>
                <option value="overdue" <?php echo $filter=='overdue'?'selected':''; ?>>⚠️ عقب‌افتاده</option>
                <option value="todo" <?php echo $filter=='todo'?'selected':''; ?>>📋 انجام نشده</option>
                <option value="in_progress" <?php echo $filter=='in_progress'?'selected':''; ?>>⏳ در حال انجام</option>
                <option value="done" <?php echo $filter=='done'?'selected':''; ?>>✅ انجام شده</option>
            </select>
            
            <select onchange="location.href='?view=<?php echo $view; ?>&filter=<?php echo $filter; ?>&priority='+this.value">
                <option value="">🎯 همه اولویت‌ها</option>
                <option value="urgent" <?php echo $priority=='urgent'?'selected':''; ?>>🟣 فوری</option>
                <option value="high" <?php echo $priority=='high'?'selected':''; ?>>🔴 بالا</option>
                <option value="medium" <?php echo $priority=='medium'?'selected':''; ?>>🟡 متوسط</option>
                <option value="low" <?php echo $priority=='low'?'selected':''; ?>>🟢 کم</option>
            </select>
            
            <button class="btn btn-primary btn-sm" onclick="openNewModal()">+ تسک جدید</button>
        </div>
        
        <?php if ($view === 'board'): ?>
        <!-- ==================== KANBAN BOARD ==================== -->
        <div class="kanban-board">
            <?php 
            $columns = [
                'todo' => ['title' => '📋 انجام نشده', 'color' => '#f44336'],
                'in_progress' => ['title' => '⏳ در حال انجام', 'color' => '#ff9800'],
                'done' => ['title' => '✅ انجام شده', 'color' => '#4caf50'],
                'cancelled' => ['title' => '❌ لغو شده', 'color' => '#9e9e9e'],
            ];
            foreach ($columns as $status => $col): ?>
            <div class="kanban-col">
                <div class="kanban-col-header">
                    <h3 style="font-size:0.85rem;color:<?php echo $col['color']; ?>;"><?php echo $col['title']; ?></h3>
                    <span style="font-size:0.7rem;color:var(--text-muted);"><?php echo count($board[$status]); ?></span>
                </div>
                <div class="kanban-list" data-status="<?php echo $status; ?>" 
                     ondragover="event.preventDefault(); this.classList.add('drag-over');" 
                     ondragleave="this.classList.remove('drag-over');" 
                     ondrop="handleDrop(event, '<?php echo $status; ?>')">
                    <?php foreach ($board[$status] as $task): ?>
                    <div class="task-card" draggable="true" data-id="<?php echo $task['id']; ?>" ondragstart="handleDragStart(event)" ondragend="handleDragEnd(event)">
                        <div class="card-header">
                            <span class="card-title"><?php echo sanitize($task['title']); ?></span>
                            <span class="card-priority priority-<?php echo $task['priority']; ?>"></span>
                        </div>
                        <?php if ($task['due_date']): ?>
                        <div class="card-meta">
                            <span>📅 <?php echo $task['due_date']; ?></span>
                            <?php if ($task['due_time']): ?><span>🕐 <?php echo $task['due_time']; ?></span><?php endif; ?>
                        </div>
                        <?php endif; ?>
                        <div class="card-actions" onclick="event.stopPropagation();">
                            <button onclick="openEditModal(<?php echo $task['id']; ?>)">✏️</button>
                            <button onclick="deleteTask(<?php echo $task['id']; ?>)">🗑️</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <button class="add-task-btn" onclick="openNewModal('<?php echo $status; ?>')">+ افزودن</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php elseif ($view === 'table'): ?>
        <!-- ==================== TABLE VIEW ==================== -->
        <div class="table-view">
            <table>
                <thead>
                    <tr>
                        <th><a href="?view=table&filter=<?php echo $filter; ?>&sort=title&order=<?php echo $sort=='title'&&$order=='ASC'?'DESC':'ASC'; ?>" style="color:inherit;">عنوان <?php echo $sort=='title'?($order=='ASC'?'▲':'▼'):''; ?></a></th>
                        <th>وضعیت</th>
                        <th><a href="?view=table&filter=<?php echo $filter; ?>&sort=priority&order=<?php echo $sort=='priority'&&$order=='ASC'?'DESC':'ASC'; ?>" style="color:inherit;">اولویت</a></th>
                        <th><a href="?view=table&filter=<?php echo $filter; ?>&sort=due_date&order=<?php echo $sort=='due_date'&&$order=='ASC'?'DESC':'ASC'; ?>" style="color:inherit;">تاریخ سررسید</a></th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tasks as $task): ?>
                    <tr class="table-row" onclick="openEditModal(<?php echo $task['id']; ?>)">
                        <td>
                            <strong><?php echo sanitize($task['title']); ?></strong>
                            <?php if ($task['category']): ?><br><small style="color:var(--text-muted);"><?php echo $task['category']; ?></small><?php endif; ?>
                        </td>
                        <td><span class="status-badge status-<?php echo $task['status']; ?>"><?php echo ['todo'=>'📋','in_progress'=>'⏳','done'=>'✅','cancelled'=>'❌'][$task['status']]; ?></span></td>
                        <td><span class="card-priority priority-<?php echo $task['priority']; ?>" style="display:inline-block;"></span> <?php echo ['urgent'=>'فوری','high'=>'بالا','medium'=>'متوسط','low'=>'کم'][$task['priority']]; ?></td>
                        <td style="font-size:0.85rem;">
                            <?php echo $task['due_date'] ? $task['due_date'] : '---'; ?>
                            <?php echo $task['due_time'] ? '<br>'.$task['due_time'] : ''; ?>
                        </td>
                        <td onclick="event.stopPropagation();">
                            <button onclick="deleteTask(<?php echo $task['id']; ?>)" class="btn btn-sm btn-outline" style="font-size:0.7rem;padding:3px 8px;">🗑️</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php elseif ($view === 'calendar'): ?>
        <!-- ==================== CALENDAR VIEW ==================== -->
        <div class="calendar-mini" id="calendarView"></div>
        <div id="calendarTasks" style="margin-top:16px;"></div>
        <?php endif; ?>
    </main>
</div>

<!-- Modal -->
<div class="modal-overlay" id="taskModal">
    <div class="modal-box">
        <h2 id="modalTitle">➕ تسک جدید</h2>
        <form id="taskForm" onsubmit="saveTask(event)">
            <input type="hidden" id="taskId">
            <input type="hidden" id="taskStatus" value="todo">
            
            <div class="form-group">
                <label>عنوان *</label>
                <input type="text" id="taskTitle" required>
            </div>
            <div class="form-group">
                <label>توضیحات</label>
                <textarea id="taskDesc" rows="2"></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>اولویت</label>
                    <select id="taskPriority">
                        <option value="low">🟢 کم</option>
                        <option value="medium" selected>🟡 متوسط</option>
                        <option value="high">🔴 بالا</option>
                        <option value="urgent">🟣 فوری</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>دسته‌بندی</label>
                    <select id="taskCategory">
                        <option value="general">📂 عمومی</option>
                        <option value="programming">💻 برنامه‌نویسی</option>
                        <option value="design">🎨 طراحی</option>
                        <option value="marketing">📢 بازاریابی</option>
                        <option value="personal">👤 شخصی</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>تاریخ سررسید</label>
                    <input type="date" id="taskDueDate">
                </div>
                <div class="form-group">
                    <label>ساعت (اختیاری)</label>
                    <input type="time" id="taskDueTime">
                </div>
            </div>
            <div style="display:flex;gap:8px;margin-top:16px;">
                <button type="submit" class="btn btn-primary">💾 ذخیره</button>
                <button type="button" class="btn btn-outline" onclick="closeModal()">انصراف</button>
            </div>
        </form>
    </div>
</div>

<script>
// ==================== متغیرها ====================
let draggedTask = null;
const currentView = '<?php echo $view; ?>';
const currentFilter = '<?php echo $filter; ?>';

// ==================== Modal ====================
function openNewModal(status = 'todo') {
    document.getElementById('modalTitle').textContent = '➕ تسک جدید';
    document.getElementById('taskId').value = '';
    document.getElementById('taskStatus').value = status;
    document.getElementById('taskTitle').value = '';
    document.getElementById('taskDesc').value = '';
    document.getElementById('taskPriority').value = 'medium';
    document.getElementById('taskCategory').value = 'general';
    document.getElementById('taskDueDate').value = '';
    document.getElementById('taskDueTime').value = '';
    document.getElementById('taskModal').classList.add('open');
}

function openEditModal(id) {
    // اینجا میتونیم با fetch اطلاعات تسک رو بگیریم
    // فعلاً با prompt ساده
    document.getElementById('taskId').value = id;
    document.getElementById('modalTitle').textContent = '✏️ ویرایش تسک';
    document.getElementById('taskModal').classList.add('open');
}

function closeModal() {
    document.getElementById('taskModal').classList.remove('open');
}

async function saveTask(e) {
    e.preventDefault();
    
    const id = document.getElementById('taskId').value;
    const status = document.getElementById('taskStatus').value;
    const title = document.getElementById('taskTitle').value.trim();
    const desc = document.getElementById('taskDesc').value.trim();
    const priority = document.getElementById('taskPriority').value;
    const category = document.getElementById('taskCategory').value;
    const dueDate = document.getElementById('taskDueDate').value;
    const dueTime = document.getElementById('taskDueTime').value;
    
    if (!title) return;
    
    const formData = new FormData();
    formData.append('action', id ? 'update' : 'create');
    if (id) formData.append('task_id', id);
    formData.append('title', title);
    formData.append('description', desc);
    formData.append('status', status);
    formData.append('priority', priority);
    formData.append('category', category);
    formData.append('due_date', dueDate);
    formData.append('due_time', dueTime);
    
    await fetch('/api/tasks/kanban.php', { method: 'POST', body: formData });
    closeModal();
    location.reload();
}

async function deleteTask(id) {
    if (!confirm('حذف این تسک؟')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('task_id', id);
    
    await fetch('/api/tasks/kanban.php', { method: 'POST', body: formData });
    location.reload();
}

// ==================== Drag & Drop ====================
function handleDragStart(e) {
    draggedTask = e.target.closest('.task-card');
    if (draggedTask) draggedTask.classList.add('dragging');
}

function handleDragEnd(e) {
    if (draggedTask) draggedTask.classList.remove('dragging');
    draggedTask = null;
    document.querySelectorAll('.drag-over').forEach(el => el.classList.remove('drag-over'));
}

async function handleDrop(e, newStatus) {
    e.preventDefault();
    const list = e.target.closest('.kanban-list');
    if (list) list.classList.remove('drag-over');
    if (!draggedTask) return;
    
    const taskId = draggedTask.dataset.id;
    
    // جابجایی در DOM
    const targetList = document.querySelector(`.kanban-list[data-status="${newStatus}"]`);
    if (targetList) {
        targetList.insertBefore(draggedTask, targetList.querySelector('.add-task-btn'));
    }
    
    // ذخیره
    const formData = new FormData();
    formData.append('action', 'move');
    formData.append('task_id', taskId);
    formData.append('status', newStatus);
    formData.append('position', 0);
    
    await fetch('/api/tasks/kanban.php', { method: 'POST', body: formData });
    location.reload();
}

// ==================== Calendar Mini ====================
<?php if ($view === 'calendar'): ?>
function renderMiniCalendar() {
    const now = new Date();
    const year = now.getFullYear();
    const month = now.getMonth();
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const daysInPrevMonth = new Date(year, month, 0).getDate();
    
    const dayNames = ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'];
    const taskDates = <?php echo json_encode(array_filter(array_column($tasks, 'due_date'))); ?>;
    
    let html = dayNames.map(d => `<div class="day-header">${d}</div>`).join('');
    
    // روزهای ماه قبل
    for (let i = firstDay - 1; i >= 0; i--) {
        html += `<div class="day other-month">${daysInPrevMonth - i}</div>`;
    }
    
    // روزهای این ماه
    for (let d = 1; d <= daysInMonth; d++) {
        const dateStr = `${year}-${String(month+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
        const isToday = d === now.getDate();
        const hasTask = taskDates.includes(dateStr);
        
        html += `<div class="day ${isToday?'today':''} ${hasTask?'has-task':''}" 
                     onclick="showTasksForDate('${dateStr}')">${d}</div>`;
    }
    
    document.getElementById('calendarView').innerHTML = html;
}

function showTasksForDate(date) {
    const tasks = <?php echo json_encode($tasks); ?>;
    const filtered = tasks.filter(t => t.due_date === date);
    
    const container = document.getElementById('calendarTasks');
    if (filtered.length === 0) {
        container.innerHTML = `<p style="text-align:center;padding:20px;">هیچ تسکی برای ${date} نیست</p>`;
    } else {
        container.innerHTML = filtered.map(t => `
            <div class="task-card" style="margin-bottom:8px;">
                <div class="card-header">
                    <span class="card-title">${t.title}</span>
                    <span class="card-priority priority-${t.priority}"></span>
                </div>
                <div class="card-meta">${t.due_time || ''} | ${t.category}</div>
            </div>
        `).join('');
    }
}

renderMiniCalendar();
<?php endif; ?>
</script>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>