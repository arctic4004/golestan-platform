<?php
// shop/payment-manual.php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/constants.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = (int)($_POST['amount'] ?? 0);
    $card_number = $_POST['card_number'] ?? '';
    $ref_id = $_POST['ref_id'] ?? '';
    
    if ($amount < 10000 || empty($card_number) || empty($ref_id)) {
        $error = 'لطفاً تمام فیلدها را پر کنید. حداقل مبلغ ۱۰,۰۰۰ تومان.';
    } else {
        $db = (new Database())->getConnection();
        
        // ثبت درخواست شارژ (در انتظار تأیید ادمین)
        $stmt = $db->prepare("INSERT INTO wallet_transactions (user_id, amount, type, description, balance_after) VALUES (?, ?, 'deposit', ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $amount, "واریز کارت به کارت - شماره پیگیری: $ref_id", getUserData($_SESSION['user_id'])['wallet_balance']]);
        
        $success = '✅ درخواست شما ثبت شد. پس از تأیید ادمین، کیف پول شما شارژ می‌شود.';
    }
}

$page_title = 'شارژ کیف پول | ' . SITE_NAME;
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
?>

<div class="auth-container">
    <div class="auth-box" style="max-width:500px;">
        <h1>💳 شارژ کیف پول</h1>
        <p style="color:var(--text-secondary);margin-bottom:16px;">مبلغ را به شماره کارت زیر واریز کنید و اطلاعات را ثبت نمایید.</p>
        
        <div style="background:var(--bg-secondary);padding:16px;border-radius:12px;margin-bottom:16px;text-align:center;">
            <p style="font-size:0.9rem;">شماره کارت:</p>
            <strong style="font-size:1.3rem;direction:ltr;display:block;">۶۰۳۷-۹۹۱۸-XXXX-XXXX</strong>
            <p style="font-size:0.8rem;color:var(--text-muted);margin-top:4px;">به نام: صاحب کافی‌نت گلستان</p>
        </div>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?=$success?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?=$error?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>💰 مبلغ واریزی (تومان)</label>
                <input type="number" name="amount" placeholder="حداقل ۱۰,۰۰۰ تومان" min="10000" required>
            </div>
            <div class="form-group">
                <label>🔢 ۶ رقم آخر شماره کارت مبدأ</label>
                <input type="text" name="card_number" placeholder="XXXX" maxlength="4" required>
            </div>
            <div class="form-group">
                <label>📝 شماره پیگیری (اختیاری)</label>
                <input type="text" name="ref_id" placeholder="شماره پیگیری فیش">
            </div>
            <button type="submit" class="btn btn-primary btn-block">📤 ثبت واریز</button>
        </form>
        
        <p style="margin-top:12px;font-size:0.85rem;color:var(--text-muted);">
            ⚡ پس از تأیید ادمین (معمولاً کمتر از ۱ ساعت)، حساب شما شارژ می‌شود.
        </p>
    </div>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>