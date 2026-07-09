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
$view   = $_GET['view']   ?? 'board';
$filter = $_GET['filter'] ?? 'all';
$sort   = $_GET['sort']   ?? 'position';
$order  = $_GET['order']  ?? 'ASC';
$priority = $_GET['priority'] ?? '';

$query = "SELECT * FROM tasks WHERE user_id = ?";
$params = [$user_id];

switch ($filter) {
    case 'today':   $query .= " AND due_date = CURDATE()"; break;
    case 'week':    $query .= " AND due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)"; break;
    case 'month':   $query .= " AND due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)"; break;
    case 'overdue': $query .= " AND due_date < CURDATE() AND status != 'done'"; break;
    case 'todo':    $query .= " AND status = 'todo'"; break;
    case 'in_progress': $query .= " AND status = 'in_progress'"; break;
    case 'done':    $query .= " AND status = 'done'"; break;
}

if ($priority) {
    $query .= " AND priority = ?";
    $params[] = $priority;
}

$allowed_sorts = ['position','priority','due_date','created_at','title'];
if (!in_array($sort, $allowed_sorts)) $sort = 'position';
$order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
$query .= $sort === 'priority'
    ? " ORDER BY FIELD(priority, 'urgent','high','medium','low') $order"
    : " ORDER BY $sort $order";

$stmt = $db->prepare($query);
$stmt->execute($params);
$tasks = $stmt->fetchAll();

$board = ['todo' => [], 'in_progress' => [], 'done' => [], 'cancelled' => []];
foreach ($tasks as $t) { $board[$t['status']][] = $t; }

$stats = [
    'total'       => count($tasks),
    'todo'        => count($board['todo']),
    'in_progress' => count($board['in_progress']),
    'done'        => count($board['done']),
    'overdue'     => 0,
    'today'       => 0,
];
foreach ($tasks as $t) {
    if ($t['due_date'] && $t['due_date'] < date('Y-m-d') && $t['status'] !== 'done') $stats['overdue']++;
    if ($t['due_date'] === date('Y-m-d')) $stats['today']++;
}

$user_data = getUserData($user_id);
$page_title = 'مدیریت تسک‌ها | ' . SITE_NAME;
$extra_css  = ['user/dashboard/v2/assets/css/dashboard.css'];
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';

// ========== تقویم شمسی ==========
$jalali_today = jalali_date('Y/m/d');
list($jy, $jm, $jd) = explode('/', $jalali_today);
$jy = (int)$jy; $jm = (int)$jm; $jd = (int)$jd;
$days_in_month = $jm <= 6 ? 31 : ($jm <= 11 ? 30 : ($jy % 4 == 3 ? 30 : 29));

$month_names = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
$holidays = [
    [1,1,'عید نوروز'],[1,2,'نوروز'],[1,3,'نوروز'],[1,4,'نوروز'],
    [1,12,'روز جمهوری اسلامی'],[1,13,'سیزده به در'],
    [2,15,'مبعث'],[6,3,'رحلت امام'],[6,4,'قیام ۱۵ خرداد'],
    [8,22,'پیروزی انقلاب'],[12,29,'ملی شدن نفت']
];
$holiday_map = [];
foreach ($holidays as $h) { $holiday_map["{$h[0]}-{$h[1]}"] = $h[2]; }
?>

<style>
.task-manager { display: flex; flex-direction: column; gap: 16px; }
.task-toolbar { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; padding: 12px 16px; background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; }
.task-toolbar select, .task-toolbar input { padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg-input); color: var(--text-primary); font-family: var(--font); font-size: 0.85rem; }
.view-toggle { display: flex; gap: 2px; background: var(--bg-secondary); border-radius: 8px; padding: 3px; }
.view-btn { padding: 8px 14px; border-radius: 6px; border: none; cursor: pointer; font-size: 0.85rem; font-family: var(--font); background: transparent; color: var(--text-secondary); transition: all 0.2s; display: flex; align-items: center; gap: 6px; }
.view-btn.active { background: var(--bg-card); color: var(--primary); font-weight: 600; box-shadow: var(--shadow-sm); }
.stats-bar { display: flex; gap: 10px; flex-wrap: wrap; }
.stat-chip { display: flex; align-items: center; gap: 6px; padding: 8px 14px; background: var(--bg-card); border: 1px solid var(--border); border-radius: 20px; font-size: 0.8rem; cursor: pointer; transition: all 0.2s; text-decoration: none; color: var(--text-primary); }
.stat-chip:hover { border-color: var(--primary); }
.kanban-board { display: flex; gap: 14px; overflow-x: auto; padding-bottom: 16px; min-height: 50vh; }
.kanban-col { flex: 1; min-width: 270px; max-width: 340px; background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; padding: 12px; display: flex; flex-direction: column; }
.kanban-col-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; padding-bottom: 8px; border-bottom: 2px solid var(--border); }
.kanban-list { flex: 1; min-height: 80px; display: flex; flex-direction: column; gap: 8px; border-radius: 8px; transition: background 0.2s; }
.kanban-list.drag-over { background: var(--primary-light); }
.task-card { background: var(--bg-primary); border: 1px solid var(--border); border-radius: 10px; padding: 12px; cursor: grab; transition: all 0.2s; position: relative; }
.task-card:hover { box-shadow: var(--shadow-md); border-color: var(--primary); }
.task-card .card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px; }
.task-card .card-title { font-size: 0.9rem; font-weight: 600; flex: 1; color: var(--text-primary); }
.task-card .card-priority { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; margin-top: 4px; }
.priority-urgent { background: #7c3aed; box-shadow: 0 0 8px #7c3aed; }
.priority-high { background: #f44336; }
.priority-medium { background: #ff9800; }
.priority-low { background: #4caf50; }
.task-card .card-meta { display: flex; gap: 12px; font-size: 0.7rem; color: var(--text-muted); flex-wrap: wrap; }
.task-card .card-actions { display: flex; gap: 4px; margin-top: 8px; opacity: 0; transition: opacity 0.2s; }
.task-card:hover .card-actions { opacity: 1; }
.card-actions button { background: none; border: 1px solid var(--border); padding: 4px 8px; border-radius: 6px; cursor: pointer; font-size: 0.75rem; color: var(--text-secondary); font-family: var(--font); transition: all 0.2s; }
.card-actions button:hover { background: var(--bg-hover); color: var(--text-primary); }
.add-task-btn { background: none; border: 2px dashed var(--border); color: var(--text-muted); padding: 10px; border-radius: 8px; cursor: pointer; text-align: center; font-size: 0.8rem; margin-top: 6px; transition: all 0.2s; }
.add-task-btn:hover { border-color: var(--primary); color: var(--primary); }
.table-view { background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; }
.table-view table { width: 100%; border-collapse: collapse; }
.table-view th { padding: 12px; background: var(--bg-secondary); font-size: 0.85rem; color: var(--text-secondary); cursor: pointer; }
.table-view td { padding: 10px 12px; border-bottom: 1px solid var(--border); font-size: 0.85rem; color: var(--text-primary); }
.table-row { cursor: pointer; transition: background 0.15s; }
.table-row:hover { background: var(--bg-hover); }
.calendar-mini { background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; padding: 16px; }
.calendar-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; font-weight: 700; color: var(--text-primary); }
.calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; text-align: center; }
.calendar-grid .day-name { font-size: 0.75rem; color: var(--text-muted); padding: 6px 0; }
.calendar-grid .day { aspect-ratio: 1; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 0.85rem; cursor: pointer; transition: all 0.2s; color: var(--text-primary); position: relative; }
.calendar-grid .day:hover { background: var(--bg-hover); }
.calendar-grid .day.today { background: var(--primary); color: #fff; font-weight: 700; }
.calendar-grid .day.has-task::after { content: ''; position: absolute; bottom: 3px; width: 5px; height: 5px; border-radius: 50%; background: var(--primary); }
.calendar-grid .day.holiday { color: #ef4444; }
.calendar-grid .day.holiday.today { color: #fff; background: #ef4444; }
.day-tooltip { display: none; position: absolute; bottom: 120%; left: 50%; transform: translateX(-50%); background: var(--bg-card); border: 1px solid var(--border); border-radius: 8px; padding: 6px 10px; font-size: 0.7rem; white-space: nowrap; z-index: 10; }
.day:hover .day-tooltip { display: block; }
.modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 1000; align-items: center; justify-content: center; }
.modal-overlay.open { display: flex; }
.modal-box { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; padding: 24px; width: 90%; max-width: 500px; max-height: 85vh; overflow-y: auto; }
.modal-box h2 { margin-bottom: 16px; color: var(--text-primary); }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
@media (max-width: 768px) { .kanban-board { flex-direction: column; } .kanban-col { max-width: 100%; } .form-row { grid-template-columns: 1fr; } .task-toolbar { flex-direction: column; align-items: stretch; } }
</style>

<div class="dashboard-container">
    <aside class="dashboard-sidebar">
        <div class="user-profile-summary">
            <div class="avatar"><?= mb_substr($user_data['full_name'] ?? 'U', 0, 1) ?></div>
            <h3><?= sanitize($user_data['full_name'] ?? 'کاربر') ?></h3>
        </div>
        <nav class="dashboard-nav">
            <a href="/user/dashboard/v2/" class="nav-item"><i class="ph ph-house"></i> داشبورد</a>
            <a href="/user/dashboard/v2/chat.php" class="nav-item"><i class="ph ph-chats-circle"></i> چت AI</a>
            <a href="/projects/" class="nav-item"><i class="ph ph-github-logo"></i> پروژه‌ها</a>
            <a href="/user/dashboard/v2/image.php" class="nav-item"><i class="ph ph-image"></i> ساخت عکس</a>
            <a href="/user/dashboard/v2/tasks.php" class="nav-item active"><i class="ph ph-kanban"></i> تسک‌ها</a>
            <a href="/user/dashboard/v2/history.php" class="nav-item"><i class="ph ph-clock-counter-clockwise"></i> تاریخچه</a>
            <a href="/user/dashboard/v2/profile.php" class="nav-item"><i class="ph ph-user"></i> پروفایل</a>
            <a href="/logout.php" class="nav-item nav-item-danger"><i class="ph ph-sign-out"></i> خروج</a>
        </nav>
    </aside>
    
    <main class="dashboard-main">
        <button class="sidebar-toggle" onclick="toggleDashboardSidebar()"><i class="ph ph-list"></i></button>
        <h1><i class="ph ph-kanban"></i> مدیریت تسک‌ها</h1>
        
        <div class="stats-bar" style="margin-bottom:16px;">
            <a href="?view=<?= $view ?>&filter=all" class="stat-chip"><i class="ph ph-list-checks"></i> همه: <strong><?= $stats['total'] ?></strong></a>
            <a href="?view=<?= $view ?>&filter=today" class="stat-chip"><i class="ph ph-calendar-check"></i> امروز: <strong><?= $stats['today'] ?></strong></a>
            <a href="?view=<?= $view ?>&filter=overdue" class="stat-chip" style="<?= $stats['overdue']>0 ? 'border-color:#ef4444;color:#ef4444;' : '' ?>"><i class="ph ph-warning-circle"></i> عقب‌افتاده: <strong><?= $stats['overdue'] ?></strong></a>
            <span class="stat-chip"><i class="ph ph-check-circle" style="color:#4caf50"></i> انجام شده: <strong><?= $stats['done'] ?></strong></span>
        </div>
        
        <div class="task-toolbar" style="margin-bottom:16px;">
            <div class="view-toggle">
                <a href="?view=board<?= $filter!='all'?'&filter='.$filter:'' ?>" class="view-btn <?= $view=='board'?'active':'' ?>"><i class="ph ph-columns"></i> کانبان</a>
                <a href="?view=table<?= $filter!='all'?'&filter='.$filter:'' ?>" class="view-btn <?= $view=='table'?'active':'' ?>"><i class="ph ph-table"></i> جدول</a>
                <a href="?view=calendar<?= $filter!='all'?'&filter='.$filter:'' ?>" class="view-btn <?= $view=='calendar'?'active':'' ?>"><i class="ph ph-calendar"></i> تقویم</a>
            </div>
            
            <select onchange="location.href='?view=<?= $view ?>&filter='+this.value">
                <option value="all" <?= $filter=='all'?'selected':'' ?>>همه تسک‌ها</option>
                <option value="today" <?= $filter=='today'?'selected':'' ?>>امروز</option>
                <option value="week" <?= $filter=='week'?'selected':'' ?>>این هفته</option>
                <option value="month" <?= $filter=='month'?'selected':'' ?>>این ماه</option>
                <option value="overdue" <?= $filter=='overdue'?'selected':'' ?>>عقب‌افتاده</option>
                <option value="todo" <?= $filter=='todo'?'selected':'' ?>>انجام نشده</option>
                <option value="in_progress" <?= $filter=='in_progress'?'selected':'' ?>>در حال انجام</option>
                <option value="done" <?= $filter=='done'?'selected':'' ?>>انجام شده</option>
            </select>
            
            <select onchange="location.href='?view=<?= $view ?>&filter=<?= $filter ?>&priority='+this.value">
                <option value="">همه اولویت‌ها</option>
                <option value="urgent" <?= $priority=='urgent'?'selected':'' ?>>فوری</option>
                <option value="high" <?= $priority=='high'?'selected':'' ?>>بالا</option>
                <option value="medium" <?= $priority=='medium'?'selected':'' ?>>متوسط</option>
                <option value="low" <?= $priority=='low'?'selected':'' ?>>کم</option>
            </select>
            
            <button class="btn btn-primary btn-sm" onclick="openNewModal()"><i class="ph ph-plus"></i> تسک جدید</button>
        </div>
        
        <?php if ($view === 'board'): ?>
        <div class="kanban-board">
            <?php 
            $columns = [
                'todo'        => ['icon' => 'ph-circle', 'title' => 'انجام نشده', 'color' => '#ef4444'],
                'in_progress' => ['icon' => 'ph-circle-half', 'title' => 'در حال انجام', 'color' => '#f59e0b'],
                'done'        => ['icon' => 'ph-check-circle', 'title' => 'انجام شده', 'color' => '#10b981'],
                'cancelled'   => ['icon' => 'ph-x-circle', 'title' => 'لغو شده', 'color' => '#6b7280'],
            ];
            foreach ($columns as $status => $col): ?>
            <div class="kanban-col">
                <div class="kanban-col-header">
                    <h3 style="font-size:0.85rem;color:<?= $col['color'] ?>;display:flex;align-items:center;gap:6px;"><i class="ph <?= $col['icon'] ?>"></i> <?= $col['title'] ?></h3>
                    <span style="font-size:0.7rem;color:var(--text-muted);"><?= count($board[$status]) ?></span>
                </div>
                <div class="kanban-list" data-status="<?= $status ?>" 
                     ondragover="event.preventDefault(); this.classList.add('drag-over');" 
                     ondragleave="this.classList.remove('drag-over');" 
                     ondrop="handleDrop(event, '<?= $status ?>')">
                    <?php foreach ($board[$status] as $task): ?>
                    <div class="task-card" draggable="true" data-id="<?= $task['id'] ?>" ondragstart="handleDragStart(event)" ondragend="handleDragEnd(event)">
                        <div class="card-header">
                            <span class="card-title"><?= sanitize($task['title']) ?></span>
                            <span class="card-priority priority-<?= $task['priority'] ?>"></span>
                        </div>
                        <?php if ($task['due_date']): ?>
                        <div class="card-meta">
                            <span><i class="ph ph-calendar"></i> <?= jalali_date('Y/m/d', strtotime($task['due_date'])) ?></span>
                            <?php if ($task['due_time']): ?><span><i class="ph ph-clock"></i> <?= $task['due_time'] ?></span><?php endif; ?>
                        </div>
                        <?php endif; ?>
                        <div class="card-actions" onclick="event.stopPropagation();">
                            <button onclick="openEditModal(<?= $task['id'] ?>)"><i class="ph ph-pencil"></i></button>
                            <button onclick="deleteTask(<?= $task['id'] ?>)"><i class="ph ph-trash"></i></button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <button class="add-task-btn" onclick="openNewModal('<?= $status ?>')"><i class="ph ph-plus"></i> افزودن</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php elseif ($view === 'table'): ?>
        <div class="table-view">
            <table>
                <thead>
                    <tr>
                        <th><a href="?view=table&filter=<?= $filter ?>&sort=title&order=<?= $sort=='title'&&$order=='ASC'?'DESC':'ASC' ?>" style="color:inherit;text-decoration:none;">عنوان <?= $sort=='title'?($order=='ASC'?'<i class="ph ph-caret-up"></i>':'<i class="ph ph-caret-down"></i>'):'' ?></a></th>
                        <th>وضعیت</th>
                        <th>اولویت</th>
                        <th>تاریخ</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tasks as $task): ?>
                    <tr class="table-row" onclick="openEditModal(<?= $task['id'] ?>)">
                        <td><strong><?= sanitize($task['title']) ?></strong></td>
                        <td><?= ['todo'=>'انجام نشده','in_progress'=>'در حال انجام','done'=>'انجام شده','cancelled'=>'لغو شده'][$task['status']] ?></td>
                        <td><span class="card-priority priority-<?= $task['priority'] ?>" style="display:inline-block;"></span></td>
                        <td><?= $task['due_date'] ? jalali_date('Y/m/d', strtotime($task['due_date'])) : '---' ?></td>
                        <td onclick="event.stopPropagation();"><button onclick="deleteTask(<?= $task['id'] ?>)" style="background:none;border:none;color:var(--danger);cursor:pointer;"><i class="ph ph-trash"></i></button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php elseif ($view === 'calendar'): ?>
        <div class="calendar-mini">
            <div class="calendar-header">
                <span><?= $month_names[$jm-1] ?> <?= $jy ?></span>
                <span style="font-size:0.8rem;color:var(--text-muted)">امروز: <?= jalali_date('Y/m/d') ?></span>
            </div>
            <div class="calendar-grid" id="calendarGrid">
                <?php foreach (['ش','ی','د','س','چ','پ','ج'] as $dn): ?>
                <div class="day-name"><?= $dn ?></div>
                <?php endforeach; ?>
                <?php
                $start_offset = (date('N', strtotime("{$jy}-{$jm}-01")) - 1 + 7) % 7;
                for ($i = 0; $i < $start_offset; $i++) echo '<div class="day other-month"></div>';
                for ($d = 1; $d <= $days_in_month; $d++):
                    $is_today = ($d == $jd);
                    $date_key = "$jm-$d";
                    $is_holiday = isset($holiday_map[$date_key]) || ($start_offset + $d - 1) % 7 == 6;
                    $holiday_title = $holiday_map[$date_key] ?? (($start_offset + $d - 1) % 7 == 6 ? 'جمعه' : '');
                    $gdate = date('Y-m-d', strtotime("{$jy}-{$jm}-{$d}"));
                    $has_task = false;
                    foreach ($tasks as $t) if ($t['due_date'] == $gdate) { $has_task = true; break; }
                ?>
                <div class="day <?= $is_today?'today':'' ?> <?= $is_holiday?'holiday':'' ?> <?= $has_task?'has-task':'' ?>" 
                     onclick="showTasksForDate('<?= $gdate ?>')">
                    <?= $d ?>
                    <?php if ($holiday_title): ?>
                    <span class="day-tooltip"><?= $holiday_title ?></span>
                    <?php endif; ?>
                </div>
                <?php endfor; ?>
            </div>
        </div>
        <div id="calendarTasks" style="margin-top:16px;"></div>
        <?php endif; ?>
    </main>
</div>

<!-- Modal -->
<div class="modal-overlay" id="taskModal">
    <div class="modal-box">
        <h2 id="modalTitle"><i class="ph ph-plus"></i> تسک جدید</h2>
        <form id="taskForm" onsubmit="saveTask(event)">
            <input type="hidden" id="taskId">
            <input type="hidden" id="taskStatus" value="todo">
            
            <div class="form-group"><label>عنوان *</label><input type="text" id="taskTitle" required></div>
            <div class="form-group"><label>توضیحات</label><textarea id="taskDesc" rows="2"></textarea></div>
            <div class="form-row">
                <div class="form-group"><label>اولویت</label><select id="taskPriority">
                    <option value="low">کم</option><option value="medium" selected>متوسط</option><option value="high">بالا</option><option value="urgent">فوری</option>
                </select></div>
                <div class="form-group"><label>دسته‌بندی</label><select id="taskCategory">
                    <option value="general">عمومی</option><option value="programming">برنامه‌نویسی</option><option value="design">طراحی</option><option value="marketing">بازاریابی</option><option value="personal">شخصی</option>
                </select></div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>تاریخ سررسید (شمسی)</label>
                    <input type="text" id="taskDueDateJalali" placeholder="مثال: 1405/04/15" readonly onclick="openJalaliPicker()" style="cursor:pointer; background: var(--bg-input); color: var(--text-primary);">
                    <input type="hidden" id="taskDueDate">
                </div>
                <div class="form-group"><label>ساعت</label><input type="time" id="taskDueTime"></div>
            </div>
            <div style="display:flex;gap:8px;margin-top:16px;">
                <button type="submit" class="btn btn-primary"><i class="ph ph-floppy-disk"></i> ذخیره</button>
                <button type="button" class="btn btn-outline" onclick="closeModal()">انصراف</button>
            </div>
        </form>
        <!-- تقویم شمسی شناور -->
        <div id="jalaliPicker" style="display:none; position:absolute; background:var(--bg-card); border:1px solid var(--border); border-radius:12px; padding:12px; z-index:1001; box-shadow:var(--shadow-lg);"></div>
    </div>
</div>

<script>
// ==================== داده‌های تسک‌ها ====================
const tasksData = <?= json_encode($tasks) ?>;
const jalaliMonths = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
let jalaliPickerYear, jalaliPickerMonth;

// ==================== تبدیل تاریخ ====================
function jalaliToGregorian(jy, jm, jd) {
    let days = 0;
    for (let y = 1; y < jy; y++) days += (y % 4 == 3) ? 366 : 365;
    for (let m = 1; m < jm; m++) days += (m <= 6) ? 31 : ((m <= 11) ? 30 : ((jy % 4 == 3) ? 30 : 29));
    days += jd - 1;
    let gDate = new Date(621, 2, 22);
    gDate.setDate(gDate.getDate() + days);
    let gy = gDate.getFullYear();
    let gm = String(gDate.getMonth() + 1).padStart(2, '0');
    let gd = String(gDate.getDate()).padStart(2, '0');
    return `${gy}-${gm}-${gd}`;
}

function gregorianToJalali(date) {
    let d = new Date(date);
    let days = Math.floor((d - new Date(621, 2, 22)) / (24*60*60*1000));
    let jy = 1, jm = 1, jd = 1;
    while (true) {
        let daysInYear = (jy % 4 == 3) ? 366 : 365;
        if (days < daysInYear) break;
        days -= daysInYear; jy++;
    }
    for (jm = 1; jm <= 12; jm++) {
        let daysInMonth = (jm <= 6) ? 31 : ((jm <= 11) ? 30 : ((jy % 4 == 3) ? 30 : 29));
        if (days < daysInMonth) break;
        days -= daysInMonth;
    }
    jd = days + 1;
    return {jy, jm, jd};
}

// ==================== تقویم شمسی ====================
function openJalaliPicker() {
    const picker = document.getElementById('jalaliPicker');
    if (picker.style.display === 'block') { picker.style.display = 'none'; return; }
    let current = document.getElementById('taskDueDateJalali').value;
    let jy, jm;
    if (current) {
        let parts = current.split('/');
        jy = parseInt(parts[0]); jm = parseInt(parts[1]);
    } else {
        let today = new Date();
        let jalali = gregorianToJalali(today);
        jy = jalali.jy; jm = jalali.jm;
    }
    jalaliPickerYear = jy; jalaliPickerMonth = jm;
    renderJalaliPicker();
    picker.style.display = 'block';
    let input = document.getElementById('taskDueDateJalali');
    let rect = input.getBoundingClientRect();
    picker.style.position = 'fixed';
    picker.style.top = (rect.bottom + 4) + 'px';
    picker.style.left = rect.left + 'px';
}

function renderJalaliPicker() {
    let y = jalaliPickerYear, m = jalaliPickerMonth;
    let daysInMonth = (m <= 6) ? 31 : ((m <= 11) ? 30 : ((y % 4 == 3) ? 30 : 29));
    let firstDayGregorian = jalaliToGregorian(y, m, 1);
    let firstDayOfWeek = new Date(firstDayGregorian).getDay(); // 0=Sun, 6=Sat
    let startOffset = (firstDayOfWeek + 1) % 7; // 0=Sat, 1=Sun...6=Fri
    let today = new Date();
    let todayJalali = gregorianToJalali(today);
    let html = `<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
        <button onclick="jalaliPickerMonth--; if(jalaliPickerMonth<1){jalaliPickerMonth=12;jalaliPickerYear--;} renderJalaliPicker();" style="background:none;border:none;color:var(--primary);cursor:pointer;font-size:1.2rem;"><i class="ph ph-caret-right"></i></button>
        <span style="font-weight:700;">${jalaliMonths[m-1]} ${y}</span>
        <button onclick="jalaliPickerMonth++; if(jalaliPickerMonth>12){jalaliPickerMonth=1;jalaliPickerYear++;} renderJalaliPicker();" style="background:none;border:none;color:var(--primary);cursor:pointer;font-size:1.2rem;"><i class="ph ph-caret-left"></i></button>
    </div><div style="display:grid;grid-template-columns:repeat(7,1fr);gap:4px;text-align:center;">`;
    ['ش','ی','د','س','چ','پ','ج'].forEach(d => html += `<div style="font-size:0.7rem;color:var(--text-muted);">${d}</div>`);
    for (let i = 0; i < startOffset; i++) html += '<div></div>';
    for (let d = 1; d <= daysInMonth; d++) {
        let isToday = (y === todayJalali.jy && m === todayJalali.jm && d === todayJalali.jd);
        let isFriday = (startOffset + d - 1) % 7 == 6;
        let bg = isToday ? (isFriday ? '#ef4444' : 'var(--primary)') : 'transparent';
        let color = isToday ? '#fff' : (isFriday ? '#ef4444' : 'var(--text-primary)');
        html += `<div onclick="selectJalaliDate(${d})" style="cursor:pointer;padding:4px;border-radius:50%;background:${bg};color:${color};font-size:0.85rem;">${d}</div>`;
    }
    html += '</div>';
    document.getElementById('jalaliPicker').innerHTML = html;
}

function selectJalaliDate(day) {
    let y = jalaliPickerYear, m = jalaliPickerMonth;
    let jd = String(day).padStart(2, '0');
    let jm = String(m).padStart(2, '0');
    document.getElementById('taskDueDateJalali').value = `${y}/${jm}/${jd}`;
    document.getElementById('taskDueDate').value = jalaliToGregorian(y, m, day);
    document.getElementById('jalaliPicker').style.display = 'none';
}

document.addEventListener('click', function(e) {
    let picker = document.getElementById('jalaliPicker');
    if (picker && picker.style.display === 'block' && !picker.contains(e.target) && e.target.id !== 'taskDueDateJalali') {
        picker.style.display = 'none';
    }
});

// ==================== Modal Functions ====================
function openNewModal(status = 'todo') {
    document.getElementById('modalTitle').innerHTML = '<i class="ph ph-plus"></i> تسک جدید';
    document.getElementById('taskId').value = '';
    document.getElementById('taskStatus').value = status;
    document.getElementById('taskTitle').value = '';
    document.getElementById('taskDesc').value = '';
    document.getElementById('taskPriority').value = 'medium';
    document.getElementById('taskCategory').value = 'general';
    let today = new Date();
    let jalali = gregorianToJalali(today);
    document.getElementById('taskDueDateJalali').value = `${jalali.jy}/${String(jalali.jm).padStart(2,'0')}/${String(jalali.jd).padStart(2,'0')}`;
    document.getElementById('taskDueDate').value = today.toISOString().split('T')[0];
    document.getElementById('taskDueTime').value = '';
    document.getElementById('taskModal').classList.add('open');
}

function openEditModal(id) {
    const task = tasksData.find(t => t.id == id);
    if (!task) return;
    document.getElementById('taskId').value = task.id;
    document.getElementById('taskStatus').value = task.status;
    document.getElementById('taskTitle').value = task.title || '';
    document.getElementById('taskDesc').value = task.description || '';
    document.getElementById('taskPriority').value = task.priority || 'medium';
    document.getElementById('taskCategory').value = task.category || 'general';
    if (task.due_date) {
        let jalali = gregorianToJalali(task.due_date);
        document.getElementById('taskDueDateJalali').value = `${jalali.jy}/${String(jalali.jm).padStart(2,'0')}/${String(jalali.jd).padStart(2,'0')}`;
        document.getElementById('taskDueDate').value = task.due_date;
    } else {
        document.getElementById('taskDueDateJalali').value = '';
        document.getElementById('taskDueDate').value = '';
    }
    document.getElementById('taskDueTime').value = task.due_time || '';
    document.getElementById('modalTitle').innerHTML = '<i class="ph ph-pencil"></i> ویرایش تسک';
    document.getElementById('taskModal').classList.add('open');
}

function closeModal() { document.getElementById('taskModal').classList.remove('open'); }

async function saveTask(e) {
    e.preventDefault();
    const id = document.getElementById('taskId').value;
    const fd = new FormData();
    fd.append('action', id ? 'update' : 'create');
    if (id) fd.append('task_id', id);
    fd.append('title', document.getElementById('taskTitle').value);
    fd.append('description', document.getElementById('taskDesc').value);
    fd.append('status', document.getElementById('taskStatus').value);
    fd.append('priority', document.getElementById('taskPriority').value);
    fd.append('category', document.getElementById('taskCategory').value);
    fd.append('due_date', document.getElementById('taskDueDate').value);
    fd.append('due_time', document.getElementById('taskDueTime').value);
    await fetch('/api/tasks/kanban.php', { method: 'POST', body: fd });
    closeModal();
    location.reload();
}

async function deleteTask(id) {
    if (!confirm('حذف این تسک؟')) return;
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('task_id', id);
    await fetch('/api/tasks/kanban.php', { method: 'POST', body: fd });
    location.reload();
}

// ==================== Drag & Drop ====================
let draggedTask = null;
function handleDragStart(e) { draggedTask = e.target.closest('.task-card'); if (draggedTask) draggedTask.classList.add('dragging'); }
function handleDragEnd(e) { if (draggedTask) draggedTask.classList.remove('dragging'); draggedTask = null; document.querySelectorAll('.drag-over').forEach(el => el.classList.remove('drag-over')); }
async function handleDrop(e, newStatus) {
    e.preventDefault();
    const list = e.target.closest('.kanban-list');
    if (list) list.classList.remove('drag-over');
    if (!draggedTask) return;
    const taskId = draggedTask.dataset.id;
    const targetList = document.querySelector(`.kanban-list[data-status="${newStatus}"]`);
    if (targetList) targetList.insertBefore(draggedTask, targetList.querySelector('.add-task-btn'));
    const fd = new FormData();
    fd.append('action', 'move');
    fd.append('task_id', taskId);
    fd.append('status', newStatus);
    fd.append('position', 0);
    await fetch('/api/tasks/kanban.php', { method: 'POST', body: fd });
    location.reload();
}

<?php if ($view === 'calendar'): ?>
function showTasksForDate(date) {
    const filtered = tasksData.filter(t => t.due_date === date);
    const container = document.getElementById('calendarTasks');
    container.innerHTML = filtered.length === 0
        ? `<p style="text-align:center;padding:20px;color:var(--text-muted)">هیچ تسکی برای ${date} نیست</p>`
        : filtered.map(t => `<div class="task-card" style="margin-bottom:8px;"><div class="card-header"><span class="card-title">${t.title}</span><span class="card-priority priority-${t.priority}"></span></div><div class="card-meta">${t.due_time||''} | ${t.category}</div></div>`).join('');
}
<?php endif; ?>
</script>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>