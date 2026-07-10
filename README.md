<p align="center">
  <img src="https://raw.githubusercontent.com/arctic4004/golestan-platform/main/assets/icons/icon-192x192.png" alt="Golestan Cafénet" width="80">
</p>

<h1 align="center">کافی‌نت گلستان</h1>
<h3 align="center">پلتفرم هوش مصنوعی، فروشگاه آنلاین و مدیریت مشتریان</h3>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.0%2B-777bb4?style=flat-square&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/MySQL-5.7%2B-4479a1?style=flat-square&logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/JavaScript-ES6%2B-f7df1e?style=flat-square&logo=javascript&logoColor=black" alt="JavaScript">
  <img src="https://img.shields.io/badge/Llama-4-0ea5e9?style=flat-square&logo=meta&logoColor=white" alt="Llama 4">
  <img src="https://img.shields.io/badge/PWA-Ready-5a0fc8?style=flat-square&logo=pwa&logoColor=white" alt="PWA">
  <img src="https://img.shields.io/badge/license-Private-red?style=flat-square" alt="License">
</p>

---

## درباره پروژه

یک پلتفرم تحت وب کامل برای **کافی‌نت گلستان** در یاسوج که خدمات کامپیوتری، هوش مصنوعی، فروشگاه آنلاین و مدیریت مشتریان را در یک سامانه یکپارچه ارائه می‌دهد. این پروژه برای استفاده واقعی در یک کسب‌وکار فیزیکی طراحی و پیاده‌سازی شده است.

---

## ویژگی‌های کلیدی

<table>
  <tr>
    <td width="50%">
      <h3>🤖 هوش مصنوعی</h3>
      <ul>
        <li>چت پیشرفته با <strong>Llama 4</strong> (Cloudflare Workers AI)</li>
        <li>حالت تفکر عمیق (Think) و جستجو (Search)</li>
        <li>ساخت تصویر با <strong>۳ مدل</strong> (SDXL، DreamShaper، Lightning)</li>
        <li>مشاور فروش با <strong>ورودی صوتی فارسی</strong></li>
        <li>پایگاه دانش <strong>۱۰۰+ خدمت</strong> با قیمت و مدارک</li>
      </ul>
    </td>
    <td width="50%">
      <h3>🛒 فروشگاه</h3>
      <ul>
        <li><strong>۱۵ دسته‌بندی</strong> خدمات و کالاها</li>
        <li>سبد خرید و تسویه حساب</li>
        <li>جستجوی هوشمند در محصولات</li>
        <li>فاکتور و پیگیری سفارشات</li>
      </ul>
    </td>
  </tr>
  <tr>
    <td>
      <h3>👥 مدیریت مشتریان (CRM)</h3>
      <ul>
        <li>ثبت اطلاعات مشتریان حضوری</li>
        <li>تگ‌گذاری و دسته‌بندی هوشمند</li>
        <li>سیستم رنکینگ (🥉 برنزی تا 👑 الماس)</li>
        <li>یادداشت‌ها و یادآوری‌ها</li>
      </ul>
    </td>
    <td>
      <h3>🎨 طراحی و تجربه کاربری</h3>
      <ul>
        <li><strong>۹ تم رنگی</strong> + حالت روشن و تاریک</li>
        <li>آیکون‌های Phosphor (مدرن و یکدست)</li>
        <li>کاملاً واکنش‌گرا (Mobile-First)</li>
        <li>تقویم شمسی با مناسبت‌ها</li>
        <li><strong>PWA</strong> – نصب روی گوشی بدون فروشگاه</li>
      </ul>
    </td>
  </tr>
</table>

---

## تکنولوژی‌ها

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.0%2B-777bb4?style=for-the-badge&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/MySQL-5.7%2B-4479a1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/JavaScript-ES6%2B-f7df1e?style=for-the-badge&logo=javascript&logoColor=black" alt="JS">
  <img src="https://img.shields.io/badge/CSS3-1572b6?style=for-the-badge&logo=css3&logoColor=white" alt="CSS3">
  <img src="https://img.shields.io/badge/Cloudflare-F38020?style=for-the-badge&logo=cloudflare&logoColor=white" alt="Cloudflare">
  <img src="https://img.shields.io/badge/Phosphor-Icons-0ea5e9?style=for-the-badge" alt="Phosphor">
</p>

---

## نصب و راه‌اندازی

````bash
# کلون کردن مخزن
git clone https://github.com/arctic4004/golestan-platform.git
cd golestan-platform
------
۱. دیتابیس — یک دیتابیس MySQL ایجاد کنید و جداول مورد نیاز را import کنید.

۲. تنظیمات — فایل config/database.php را با اطلاعات دیتابیس خود ویرایش کنید:
$this->host     = 'localhost';
$this->db_name  = 'your_database';
$this->username = 'your_username';
$this->password = 'your_password';
۳. API Token — توکن Cloudflare را در جدول settings ذخیره کنید:
INSERT INTO settings (setting_key, setting_value)
VALUES ('deepseek_api_key', 'YOUR_CLOUDFLARE_TOKEN');
۴. آپلود — فایل‌ها را روی سرور Apache با PHP 8.0+ آپلود کنید.
------
ساختار پروژه
.
├── api/chat/         # API چت هوشمند (Llama 4)
├── api/image/        # API ساخت و ویرایش تصویر
├── admin/            # پنل مدیریت و CRM
├── assets/           # CSS، JavaScript، آیکون‌ها
├── config/           # تنظیمات دیتابیس و ثابت‌ها
├── includes/         # هدر، فوتر، نوبار، توابع کمکی
├── knowledge/        # پایگاه دانش ۱۰۰+ خدمت (JSON)
├── shop/             # فروشگاه آنلاین
├── user/dashboard/   # داشبورد کاربری
└── index.php         # صفحه اصلی

امنیت
Prepared Statements (PDO) برای جلوگیری از SQL Injection

CSRF Tokens در تمام فرم‌ها

Rate Limiting برای جلوگیری از Brute Force

کوکی‌های HttpOnly و Secure

فایل‌های حساس در .gitignore

<p align="center"> <sub>ساخته شده در یاسوج، ایران</sub> </p> ```
````
