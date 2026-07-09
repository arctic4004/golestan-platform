<?php
session_start();
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: /login.php');
    exit;
}

$success = false;
$error = '';
$db = (new Database())->getConnection();

// تگ‌های هوشمند
$all_tags = [
    'ثبت نام' => ['ایران خودرو', 'سایپا', 'کنکور', 'دانشگاه', 'قرعه کشی', 'خودرو', 'ثبت نام'],
    'اداری' => ['بیمه', 'مالیات', 'قبض', 'دفترچه', 'سند', 'استعلام'],
    'تحصیلی' => ['دانشجو', 'دانش‌آموز', 'تحقیق', 'مقاله', 'پایان‌نامه', 'ترجمه', 'پاورپوینت'],
    'کسب‌وکار' => ['فروشگاه', 'اینستاگرام', 'تبلیغات', 'لوگو', 'کارت ویزیت', 'سربرگ'],
    'کامپیوتر' => ['پرینت', 'اسکن', 'کپی', 'تایپ', 'نصب ویندوز', 'تعمیر', 'طراحی سایت'],
    'طراحی' => ['بنر', 'پوستر', 'فتوشاپ', 'موشن گرافیک', 'تدوین'],
];

// تگ‌های پرکاربرد (از دیتابیس)
$popular_tags = $db->query("SELECT tags FROM customers WHERE tags IS NOT NULL AND tags != ''")->fetchAll(PDO::FETCH_COLUMN);
$tag_counts = [];
foreach ($popular_tags as $t) {
    foreach (explode(',', $t) as $tag) {
        $tag = trim($tag);
        if (!empty($tag)) $tag_counts[$tag] = ($tag_counts[$tag] ?? 0) + 1;
    }
}
arsort($tag_counts);
$top_tags = array_slice(array_keys($tag_counts), 0, 10);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $birth_date = $_POST['birth_date'] ?: null;
    $tags = trim($_POST['tags'] ?? '');
    $interests = trim($_POST['interests'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $rank = $_POST['rank'] ?? 'bronze';
    $create_account = isset($_POST['create_account']);
    $telegram_id = trim($_POST['telegram_id'] ?? '');
    
    if (empty($full_name)) {
        $error = 'نام مشتری الزامی است.';
    } else {
        $user_id = null;
        
        if ($create_account && !empty($phone)) {
            $stmt = $db->prepare("SELECT id FROM users WHERE phone = ?");
            $stmt->execute([$phone]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                $user_id = $existing['id'];
                // آپدیت رنک کاربر
                $db->prepare("UPDATE users SET rank = ?, rank_score = rank_score + 10 WHERE id = ?")->execute([$rank, $user_id]);
            } else {
                $random_pass = bin2hex(random_bytes(4));
                $stmt = $db->prepare("INSERT INTO users (phone, email, full_name, password_hash, credits, rank, rank_score, is_active) VALUES (?, ?, ?, ?, 500, ?, 10, 1)");
                $stmt->execute([$phone, $email, $full_name, password_hash($random_pass, PASSWORD_BCRYPT), $rank]);
                $user_id = $db->lastInsertId();
            }
        }
        
        $stmt = $db->prepare("INSERT INTO customers (full_name, phone, email, city, birth_date, tags, interests, notes, rank, user_id, telegram_id, last_visit) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$full_name, $phone, $email, $city, $birth_date, $tags, $interests, $notes, $rank, $user_id, $telegram_id]);
        $customer_id = $db->lastInsertId();
        
        if (!empty($notes)) {
            $stmt = $db->prepare("INSERT INTO customer_notes (customer_id, note, note_type) VALUES (?, ?, 'general')");
            $stmt->execute([$customer_id, $notes]);
        }
        
        // ارسال نوتیفیکیشن تلگرام
        if (function_exists('sendTelegram')) {
            $msg = "🆕 مشتری جدید\n👤 {$full_name}\n📱 {$phone}\n🏷️ {$tags}\n⭐ {$rank}";
            @sendTelegram($msg);
        }
        
        $success = true;
    }
}

$page_title = 'افزودن مشتری | CRM';
require_once __DIR__ . '/../../includes/header.php';
?>

<style>
/* ریست z-index برای صفحه CRM */
.navbar { z-index: 50 !important; }
.crm-page { 
    max-width: 750px; 
    margin: 20px auto; 
    padding: 0 15px;
    position: relative;
    z-index: 1;
}
.crm-card {
    background: #fff;
    border-radius: 20px;
    padding: 35px;
    box-shadow: 0 4px 30px rgba(0,0,0,0.06);
}
.crm-card h2 { 
    font-size: 22px; 
    margin: 0 0 5px 0; 
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 10px;
}
.crm-card .subtitle { 
    color: #94a3b8; 
    font-size: 13px; 
    margin-bottom: 30px; 
}
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
}
.form-group { margin-bottom: 5px; }
.form-group.full { grid-column: 1 / -1; }
.form-group label { 
    display: block; 
    margin-bottom: 6px; 
    font-weight: 600; 
    font-size: 12px; 
    color: #475569; 
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.form-group input, 
.form-group textarea, 
.form-group select {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    font-size: 14px;
    font-family: inherit;
    transition: all 0.2s;
    background: #f8fafc;
}
.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    border-color: #6366f1;
    background: #fff;
    outline: none;
    box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
}
.form-group textarea { min-height: 90px; resize: vertical; }
.form-group small { color: #94a3b8; font-size: 11px; margin-top: 4px; display: block; }

/* تگ‌های هوشمند */
.tag-cloud {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 8px;
    padding: 12px;
    background: #f8fafc;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
}
.tag-cloud-title {
    width: 100%;
    font-size: 11px;
    color: #94a3b8;
    margin-bottom: 4px;
    font-weight: 600;
}
.tag-chip {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s;
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #475569;
    white-space: nowrap;
}
.tag-chip:hover { background: #6366f1; color: #fff; border-color: #6366f1; }
.tag-chip.selected { background: #6366f1; color: #fff; border-color: #6366f1; }
.tag-chip.popular { font-weight: 600; }
.tag-chip .count { font-size: 10px; opacity: 0.7; margin-right: 3px; }

/* رنک */
.rank-options { display: flex; gap: 8px; }
.rank-option { 
    flex: 1; 
    text-align: center; 
    padding: 12px 6px; 
    border: 2px solid #e2e8f0; 
    border-radius: 12px; 
    cursor: pointer; 
    transition: all 0.2s;
    background: #fff;
}
.rank-option:hover { border-color: #6366f1; background: #f8faff; }
.rank-option.selected { border-color: #6366f1; background: #eef2ff; }
.rank-option .icon { font-size: 26px; display: block; }
.rank-option .name { font-size: 11px; font-weight: 600; margin-top: 4px; color: #475569; }
.rank-option .score { font-size: 10px; color: #94a3b8; }

/* چک باکس */
.checkbox-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;
}
.checkbox-card:hover { background: #dcfce7; }
.checkbox-card input[type="checkbox"] { width: 20px; height: 20px; accent-color: #22c55e; }
.checkbox-card .checkbox-info { font-size: 13px; }
.checkbox-card .checkbox-info strong { color: #166534; }
.checkbox-card .checkbox-info small { color: #15803d; display: block; }

/* دکمه‌ها */
.btn-row { display: flex; gap: 12px; margin-top: 25px; }
.btn { 
    padding: 14px 28px; 
    border: none; 
    border-radius: 12px; 
    font-size: 15px; 
    font-weight: 600; 
    cursor: pointer; 
    transition: all 0.2s;
    font-family: inherit;
}
.btn-primary { background: #6366f1; color: #fff; flex: 1; }
.btn-primary:hover { background: #4f46e5; transform: translateY(-1px); }
.btn-secondary { background: #f1f5f9; color: #475569; text-decoration: none; display: flex; align-items: center; justify-content: center; }
.btn-secondary:hover { background: #e2e8f0; }

/* آلرت */
.alert { padding: 16px 20px; border-radius: 12px; margin-bottom: 20px; font-size: 14px; }
.alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
.alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
.alert a { color: inherit; font-weight: 600; }

@media (max-width: 600px) {
    .form-grid { grid-template-columns: 1fr; }
    .crm-card { padding: 20px; }
    .rank-options { flex-wrap: wrap; }
    .rank-option { min-width: 60px; }
}
</style>

<div class="crm-page">
    <div class="crm-card">
        <h2>➕ مشتری جدید</h2>
        <p class="subtitle">اطلاعات مشتری را وارد کنید. می‌توانید همزمان اکانت کاربری نیز ایجاد کنید.</p>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                ✅ مشتری با موفقیت ثبت شد!<br><br>
                <a href="/admin/?tab=customers">📋 مشاهده لیست مشتریان</a> &nbsp;|&nbsp;
                <a href="/admin/crm/add.php">➕ افزودن مشتری دیگر</a>
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="alert alert-error">⚠️ <?= $error ?></div>
            <?php endif; ?>
            
            <form method="POST" id="customerForm">
                <div class="form-grid">
                    <div class="form-group">
                        <label>👤 نام و نام خانوادگی *</label>
                        <input type="text" name="full_name" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" placeholder="علی محمدی" required>
                    </div>
                    <div class="form-group">
                        <label>📱 شماره موبایل</label>
                        <input type="tel" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" placeholder="۰۹xxxxxxxxx">
                    </div>
                    <div class="form-group">
                        <label>📧 ایمیل</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="example@gmail.com">
                    </div>
                    <div class="form-group">
                        <label>🏙️ شهر</label>
                        <input type="text" name="city" value="<?= htmlspecialchars($_POST['city'] ?? '') ?>" placeholder="یاسوج">
                    </div>
                    <div class="form-group">
                        <label>🎂 تاریخ تولد</label>
                        <input type="date" name="birth_date" value="<?= $_POST['birth_date'] ?? '' ?>">
                    </div>
                    <div class="form-group">
                        <label>🆔 تلگرام</label>
                        <input type="text" name="telegram_id" value="<?= htmlspecialchars($_POST['telegram_id'] ?? '') ?>" placeholder="@username">
                    </div>
                    
                    <!-- رنک -->
                    <div class="form-group full">
                        <label>⭐ رنک اولیه</label>
                        <div class="rank-options" id="rankOptions">
                            <?php 
                            $ranks = ['bronze'=>'🥉 برنزی','silver'=>'🥈 نقره‌ای','gold'=>'🥇 طلایی','platinum'=>'💎 پلاتینیوم','diamond'=>'👑 الماس'];
                            foreach ($ranks as $k => $v): 
                            ?>
                            <label class="rank-option <?= ($_POST['rank']??'bronze')==$k?'selected':'' ?>">
                                <input type="radio" name="rank" value="<?= $k ?>" <?= ($_POST['rank']??'bronze')==$k?'checked':'' ?> hidden>
                                <span class="icon"><?= explode(' ', $v)[0] ?></span>
                                <span class="name"><?= explode(' ', $v)[1] ?? $v ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- تگ‌ها -->
                    <div class="form-group full">
                        <label>🏷️ تگ‌ها</label>
                        <input type="text" name="tags" id="tagsInput" value="<?= htmlspecialchars($_POST['tags'] ?? '') ?>" placeholder="تگ‌ها با کاما جدا می‌شوند..." autocomplete="off">
                        <small>روی تگ‌های پیشنهادی کلیک کنید تا اضافه شوند</small>
                        
                        <div class="tag-cloud" id="tagCloud">
                            <div class="tag-cloud-title">📌 تگ‌های پیشنهادی (کلیک کنید)</div>
                            <?php foreach ($top_tags as $tag): ?>
                                <span class="tag-chip popular" onclick="toggleTag('<?= $tag ?>')">🔥 <?= $tag ?> <span class="count"><?= $tag_counts[$tag] ?></span></span>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php foreach ($all_tags as $cat => $cat_tags): ?>
                        <div class="tag-cloud" style="margin-top:8px">
                            <div class="tag-cloud-title"><?= $cat ?></div>
                            <?php foreach ($cat_tags as $tag): ?>
                                <span class="tag-chip" onclick="toggleTag('<?= $tag ?>')"><?= $tag ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- علایق -->
                    <div class="form-group full">
                        <label>📝 علایق و نیازها</label>
                        <textarea name="interests" placeholder="چه خدماتی نیاز دارد؟ چه موضوعاتی دوست دارد؟"><?= htmlspecialchars($_POST['interests'] ?? '') ?></textarea>
                    </div>
                    
                    <!-- یادداشت -->
                    <div class="form-group full">
                        <label>📌 یادداشت اولیه</label>
                        <textarea name="notes" placeholder="اطلاعات مهم درباره این مشتری..."><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                    </div>
                    
                    <!-- اکانت کاربری -->
                    <div class="form-group full">
                        <label class="checkbox-card" for="createAccount">
                            <input type="checkbox" name="create_account" id="createAccount" <?= isset($_POST['create_account']) ? 'checked' : '' ?>>
                            <div class="checkbox-info">
                                <strong>🔐 ایجاد اکانت کاربری در سایت</strong>
                                <small>۵۰۰ اعتبار هدیه + رنک انتخابی</small>
                            </div>
                        </label>
                    </div>
                </div>
                
                <div class="btn-row">
                    <button type="submit" class="btn btn-primary">💾 ثبت مشتری</button>
                    <a href="/admin/?tab=customers" class="btn btn-secondary">← بازگشت</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
// تگ‌های هوشمند
function toggleTag(tag) {
    var input = document.getElementById('tagsInput');
    var tags = input.value.split(',').map(function(t){ return t.trim(); }).filter(function(t){ return t; });
    var idx = tags.indexOf(tag);
    
    if (idx > -1) {
        tags.splice(idx, 1);
    } else {
        tags.push(tag);
    }
    input.value = tags.join(', ');
    
    // آپدیت ظاهر تگ‌ها
    document.querySelectorAll('.tag-chip').forEach(function(chip) {
        var chipTag = chip.textContent.replace(/[🔥\d]/g, '').trim();
        if (tags.includes(chipTag)) {
            chip.classList.add('selected');
        } else {
            chip.classList.remove('selected');
        }
    });
}

// لود تگ‌های انتخاب شده قبلی
document.addEventListener('DOMContentLoaded', function() {
    var input = document.getElementById('tagsInput');
    var currentTags = input.value.split(',').map(function(t){ return t.trim(); }).filter(function(t){ return t; });
    document.querySelectorAll('.tag-chip').forEach(function(chip) {
        var chipTag = chip.textContent.replace(/[🔥\d]/g, '').trim();
        if (currentTags.includes(chipTag)) chip.classList.add('selected');
    });
});

// رنک
document.querySelectorAll('.rank-option').forEach(function(opt) {
    opt.addEventListener('click', function() {
        document.querySelectorAll('.rank-option').forEach(function(o){ o.classList.remove('selected'); });
        this.classList.add('selected');
        this.querySelector('input').checked = true;
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>