<?php
session_start();
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || !$_SESSION['is_admin']) {
    header('Location: /login.php');
    exit;
}

$db = (new Database())->getConnection();
$id = $_GET['id'] ?? 0;

// اطلاعات مشتری
$stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$id]);
$customer = $stmt->fetch();

if (!$customer) {
    die('مشتری یافت نشد.');
}

// افزودن یادداشت
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['note'])) {
    $note = trim($_POST['note']);
    $type = $_POST['note_type'] ?? 'general';
    if (!empty($note)) {
        $stmt = $db->prepare("INSERT INTO customer_notes (customer_id, note, note_type) VALUES (?, ?, ?)");
        $stmt->execute([$id, $note, $type]);
    }
    header("Location: /admin/crm/view.php?id=$id");
    exit;
}

// افزودن یادآوری
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reminder_title'])) {
    $title = trim($_POST['reminder_title']);
    $date = $_POST['reminder_date'];
    if (!empty($title) && !empty($date)) {
        $stmt = $db->prepare("INSERT INTO customer_reminders (customer_id, title, reminder_date) VALUES (?, ?, ?)");
        $stmt->execute([$id, $title, $date]);
    }
    header("Location: /admin/crm/view.php?id=$id");
    exit;
}

// آپدیت بازدید
$stmt = $db->prepare("UPDATE customers SET visit_count = visit_count + 1, last_visit = NOW() WHERE id = ?");
$stmt->execute([$id]);

// یادداشت‌ها
$notes = $db->prepare("SELECT * FROM customer_notes WHERE customer_id = ? ORDER BY created_at DESC");
$notes->execute([$id]);
$notes = $notes->fetchAll();

// یادآوری‌ها
$reminders = $db->prepare("SELECT * FROM customer_reminders WHERE customer_id = ? ORDER BY reminder_date ASC");
$reminders->execute([$id]);
$reminders = $reminders->fetchAll();

$page_title = $customer['full_name'] . ' | CRM';
require_once __DIR__ . '/../../includes/header.php';
?>

<style>
.crm-view { max-width: 900px; margin: 20px auto; display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.crm-card { background: var(--card-bg, #fff); border-radius: 16px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
.crm-card.full { grid-column: 1 / -1; }
.crm-card h3 { margin-bottom: 15px; font-size: 18px; }
.info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }
.info-label { color: #666; font-size: 13px; }
.info-value { font-weight: bold; font-size: 14px; }
.notes-list { max-height: 300px; overflow-y: auto; }
.note-item { background: #f8f9fa; border-radius: 10px; padding: 12px; margin-bottom: 10px; }
.note-item .meta { font-size: 11px; color: #999; margin-top: 5px; }
.note-type { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 10px; margin-right: 5px; }
.note-type.request { background: #fef3cd; color: #856404; }
.note-type.feedback { background: #d1ecf1; color: #0c5460; }
.note-type.purchase { background: #d4edda; color: #155724; }
.reminder-item { display: flex; justify-content: space-between; align-items: center; padding: 10px; background: #fff3cd; border-radius: 10px; margin-bottom: 8px; }
.reminder-item.done { opacity: 0.5; background: #eee; }
.btn { padding: 10px 20px; border: none; border-radius: 10px; cursor: pointer; font-size: 14px; }
.btn-primary { background: #667eea; color: white; }
.btn-sm { padding: 5px 10px; font-size: 12px; }
textarea, input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 10px; font-family: inherit; margin-bottom: 10px; }
</style>

<div class="crm-view">
    <div class="crm-card">
        <h3>👤 اطلاعات مشتری</h3>
        <div class="info-row"><span class="info-label">نام</span><span class="info-value"><?= htmlspecialchars($customer['full_name']) ?></span></div>
        <div class="info-row"><span class="info-label">موبایل</span><span class="info-value"><?= $customer['phone'] ? '<a href="tel:'.$customer['phone'].'">'.$customer['phone'].'</a>' : '-' ?></span></div>
        <div class="info-row"><span class="info-label">ایمیل</span><span class="info-value"><?= $customer['email'] ?: '-' ?></span></div>
        <div class="info-row"><span class="info-label">تلگرام</span><span class="info-value"><?= $customer['telegram_id'] ?: '-' ?></span></div>
        <div class="info-row"><span class="info-label">تعداد بازدید</span><span class="info-value"><?= $customer['visit_count'] ?></span></div>
        <div class="info-row"><span class="info-label">آخرین بازدید</span><span class="info-value"><?= $customer['last_visit'] ? date('Y/m/d H:i', strtotime($customer['last_visit'])) : '-' ?></span></div>
        <div class="info-row"><span class="info-label">تگ‌ها</span><span class="info-value"><?= $customer['tags'] ?: '-' ?></span></div>
        <?php if ($customer['interests']): ?>
        <div class="info-row"><span class="info-label">علایق</span><span class="info-value"><?= nl2br(htmlspecialchars($customer['interests'])) ?></span></div>
        <?php endif; ?>
    </div>
    
    <div class="crm-card">
        <h3>📝 افزودن یادداشت</h3>
        <form method="POST">
            <select name="note_type">
                <option value="general">کلی</option>
                <option value="request">درخواست</option>
                <option value="feedback">بازخورد</option>
                <option value="purchase">خرید</option>
            </select>
            <textarea name="note" placeholder="یادداشت جدید..." required></textarea>
            <button type="submit" class="btn btn-primary">➕ ثبت یادداشت</button>
        </form>
        
        <h3 style="margin-top:20px;">⏰ یادآوری جدید</h3>
        <form method="POST">
            <input type="text" name="reminder_title" placeholder="عنوان یادآوری..." required>
            <input type="datetime-local" name="reminder_date" required>
            <button type="submit" class="btn btn-primary">🔔 ثبت یادآوری</button>
        </form>
    </div>
    
    <div class="crm-card full">
        <h3>📋 تاریخچه یادداشت‌ها (<?= count($notes) ?>)</h3>
        <div class="notes-list">
            <?php foreach ($notes as $n): ?>
            <div class="note-item">
                <span class="note-type <?= $n['note_type'] ?>"><?= $n['note_type'] ?></span>
                <?= nl2br(htmlspecialchars($n['note'])) ?>
                <div class="meta"><?= date('Y/m/d H:i', strtotime($n['created_at'])) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <?php if (!empty($reminders)): ?>
    <div class="crm-card full">
        <h3>⏰ یادآوری‌ها</h3>
        <?php foreach ($reminders as $r): ?>
        <div class="reminder-item <?= $r['is_done'] ? 'done' : '' ?>">
            <div>
                <strong><?= htmlspecialchars($r['title']) ?></strong>
                <br><small><?= date('Y/m/d H:i', strtotime($r['reminder_date'])) ?></small>
            </div>
            <span><?= $r['is_done'] ? '✅' : '⏳' ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>