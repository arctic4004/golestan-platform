<?php
// projects/index.php - پنل کامل پروژه‌های گیت‌هاب
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/constants.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';
requireAuth();

$db = (new Database())->getConnection();

// دریافت پروژه‌های متصل شده
$stmt = $db->prepare("SELECT * FROM github_projects WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$projects = $stmt->fetchAll();

// دریافت اطلاعات از گیت‌هاب برای هر پروژه
function getRepoInfo($url, $token = null) {
    $parsed = parse_url($url);
    $path = trim($parsed['path'] ?? '', '/');
    $parts = explode('/', $path);
    
    if (count($parts) >= 2) {
        $owner = $parts[0];
        $repo = $parts[1];
        $api_url = "https://api.github.com/repos/{$owner}/{$repo}";
        
        $ch = curl_init($api_url);
        $headers = ['User-Agent: GolestanAI'];
        if ($token) $headers[] = 'Authorization: Bearer ' . $token;
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 10
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    return null;
}

// آپدیت اطلاعات پروژه‌ها از گیت‌هاب
foreach ($projects as &$p) {
    $info = getRepoInfo($p['repo_url'], $p['access_token']);
    if ($info && !isset($info['message'])) {
        $db->prepare("UPDATE github_projects SET stars=?, forks=?, language=?, description=? WHERE id=?")
           ->execute([$info['stargazers_count']??0, $info['forks_count']??0, $info['language']??'', $info['description']??'', $p['id']]);
        $p['stars'] = $info['stargazers_count'] ?? 0;
        $p['forks'] = $info['forks_count'] ?? 0;
        $p['language'] = $info['language'] ?? '';
        $p['description'] = $info['description'] ?? $p['description'];
    }
}
unset($p);

$page_title = 'پروژه‌های گیت‌هاب | ' . SITE_NAME;
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
?>

<style>
.projects-hero {
    background: linear-gradient(135deg, #0d1117 0%, #161b22 50%, #6366f1 150%);
    color: white; text-align: center; padding: 60px 20px 40px; border-radius: 0 0 30px 30px;
    margin-top: 60px; margin-bottom: 30px; position: relative; overflow: hidden;
}
.projects-hero::before { content: ''; position: absolute; top: -60px; right: -60px; width: 350px; height: 350px; background: rgba(99,102,241,0.15); border-radius: 50%; }
.projects-hero::after { content: ''; position: absolute; bottom: -40px; left: -40px; width: 250px; height: 250px; background: rgba(99,102,241,0.1); border-radius: 50%; }
.projects-hero h1 { font-size: 2.2rem; font-weight: 800; position: relative; }
.projects-hero p { opacity: 0.85; position: relative; margin-top: 6px; }

.toolbar { display: flex; gap: 10px; margin-bottom: 24px; flex-wrap: wrap; align-items: center; }
.toolbar .btn i { font-size: 1rem; }

.projects-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 20px; margin-bottom: 40px; }
.project-card {
    background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px;
    padding: 24px; transition: all 0.2s; position: relative; overflow: hidden;
}
.project-card:hover { box-shadow: var(--shadow-lg); border-color: var(--primary); transform: translateY(-2px); }
.project-card .card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
.project-card .repo-icon { font-size: 2.5rem; }
.project-card .visibility-badge { font-size: 0.7rem; padding: 3px 10px; border-radius: 12px; border: 1px solid var(--border); }
.project-card h3 { font-size: 1.2rem; font-weight: 700; margin-bottom: 6px; }
.project-card .desc { color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 14px; line-height: 1.6; min-height: 40px; }
.project-card .lang-badge {
    display: inline-block; padding: 3px 10px; border-radius: 12px;
    font-size: 0.75rem; font-weight: 600; margin-right: 8px;
}
.lang-php { background: #787cb5; color: white; }
.lang-javascript { background: #f7df1e; color: #000; }
.lang-python { background: #3776ab; color: white; }
.lang-html { background: #e34c26; color: white; }
.lang-css { background: #563d7c; color: white; }
.lang-default { background: var(--bg-tertiary); color: var(--text-secondary); }

.project-card .stats { display: flex; gap: 16px; font-size: 0.85rem; color: var(--text-muted); margin-bottom: 14px; }
.project-card .stats span { display: flex; align-items: center; gap: 4px; }
.project-card .actions { display: flex; gap: 8px; flex-wrap: wrap; }

.empty-state { text-align: center; padding: 80px 20px; background: var(--bg-card); border: 1px solid var(--border); border-radius: 20px; }
.empty-state .icon { font-size: 5rem; margin-bottom: 16px; }
.empty-state h2 { font-size: 1.5rem; margin-bottom: 8px; }
.empty-state p { color: var(--text-secondary); margin-bottom: 20px; }
</style>

<div class="projects-hero">
    <h1>🚀 پروژه‌های گیت‌هاب</h1>
    <p>ریپازیتوری‌های خود را متصل کنید و با قدرت هوش مصنوعی تحلیل، ویرایش و توسعه دهید</p>
</div>

<div class="container">
    <div class="toolbar">
        <a href="/projects/connect.php" class="btn btn-primary"><i class="fab fa-github"></i> ➕ اتصال پروژه جدید</a>
        <a href="/projects/explore.php" class="btn btn-outline"><i class="fas fa-search"></i> جستجوی عمومی</a>
        <span style="margin-right:auto;color:var(--text-muted);font-size:0.85rem;"><?=count($projects)?> پروژه متصل</span>
    </div>

    <?php if (empty($projects)): ?>
    <div class="empty-state">
        <div class="icon">📂</div>
        <h2>هنوز پروژه‌ای متصل نکرده‌اید</h2>
        <p>یک ریپازیتوری گیت‌هاب را برای تحلیل کد، رفع باگ، بهینه‌سازی و توسعه با هوش مصنوعی متصل کنید.</p>
        <a href="/projects/connect.php" class="btn btn-primary btn-lg"><i class="fab fa-github"></i> اتصال اولین پروژه</a>
        
        <div style="margin-top:40px;display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;text-align:right;">
            <div style="background:var(--bg-secondary);padding:16px;border-radius:12px;">
                <strong>📖 تحلیل کد</strong>
                <p style="font-size:0.85rem;color:var(--text-secondary);">AI ساختار پروژه را تحلیل می‌کند</p>
            </div>
            <div style="background:var(--bg-secondary);padding:16px;border-radius:12px;">
                <strong>🐛 رفع باگ</strong>
                <p style="font-size:0.85rem;color:var(--text-secondary);">باگ‌ها را پیدا و رفع کنید</p>
            </div>
            <div style="background:var(--bg-secondary);padding:16px;border-radius:12px;">
                <strong>✏️ ویرایش کد</strong>
                <p style="font-size:0.85rem;color:var(--text-secondary);">دستور بدهید، AI کد را ویرایش کند</p>
            </div>
            <div style="background:var(--bg-secondary);padding:16px;border-radius:12px;">
                <strong>💬 چت با کد</strong>
                <p style="font-size:0.85rem;color:var(--text-secondary);">مستقیم با AI درباره کد گفتگو کنید</p>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="projects-grid">
        <?php foreach ($projects as $p): 
            $lang_class = 'lang-default';
            switch (strtolower($p['language'] ?? '')) {
                case 'php': $lang_class = 'lang-php'; break;
                case 'javascript': case 'typescript': $lang_class = 'lang-javascript'; break;
                case 'python': $lang_class = 'lang-python'; break;
                case 'html': $lang_class = 'lang-html'; break;
                case 'css': $lang_class = 'lang-css'; break;
            }
        ?>
        <div class="project-card">
            <div class="card-header">
                <div class="repo-icon">📁</div>
                <span class="visibility-badge"><?=$p['access_token'] ? '🔒 خصوصی' : '🌐 عمومی'?></span>
            </div>
            <h3><?=sanitize($p['repo_name'])?></h3>
            <p class="desc"><?=sanitize(mb_substr($p['description'] ?: 'بدون توضیح', 0, 100))?></p>
            <?php if ($p['language']): ?>
            <span class="lang-badge <?=$lang_class?>"><?=sanitize($p['language'])?></span>
            <?php endif; ?>
            <div class="stats">
                <span>⭐ <?=$p['stars']?></span>
                <span>🍴 <?=$p['forks']?></span>
                <span>📅 <?=date('Y/m/d', strtotime($p['created_at']))?></span>
            </div>
            <div class="actions">
                <a href="/projects/view.php?id=<?=$p['id']?>" class="btn btn-primary btn-sm">🔍 تحلیل و ویرایش</a>
                <a href="/projects/chat.php?id=<?=$p['id']?>" class="btn btn-outline btn-sm">💬 چت با کد</a>
                <a href="<?=sanitize($p['repo_url'])?>" target="_blank" class="btn btn-outline btn-sm">🔗 گیت‌هاب</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>