# ☕ کافی‌نت گلستان - پلتفرم جامع هوش مصنوعی و خدمات کامپیوتری

![Version](https://img.shields.io/badge/version-3.0-blue)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-purple)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-orange)
![License](https://img.shields.io/badge/license-Private-red)

<div dir="rtl">

## 📋 فهرست محتوا

- [درباره پروژه](#درباره-پروژه)
- [امکانات](#امکانات)
- [ساختار پروژه](#ساختار-پروژه)
- [پیش‌نیازها](#پیش‌نیازها)
- [نصب و راه‌اندازی](#نصب-و-راه‌اندازی)
- [تکنولوژی‌ها](#تکنولوژی‌ها)
- [تنظیمات](#تنظیمات)
- [امنیت](#امنیت)
- [نقشه راه](#نقشه-راه)
- [تماس](#تماس)

## 🎯 درباره پروژه

**کافی‌نت گلستان** یک پلتفرم جامع تحت وب است که خدمات کامپیوتری، هوش مصنوعی، فروشگاه آنلاین و مدیریت مشتریان را در یک سامانه یکپارچه ارائه می‌دهد. این پروژه برای یک کافی‌نت واقعی در شهر یاسوج طراحی و پیاده‌سازی شده است.

### چرا گلستان؟

- 🦙 **چت هوشمند** با مدل Llama 4 (Cloudflare Workers AI)
- 🎨 **ساخت تصویر** با ۳ مدل مختلف هوش مصنوعی
- 🤖 **مشاور فروش** با قابلیت ورودی صوتی (Web Speech API)
- 🛒 **فروشگاه آنلاین** با ۱۰۰+ خدمت و کالا
- 👥 **مدیریت مشتریان (CRM)** با سیستم رنکینگ
- 📋 **مدیریت تسک‌ها** با Kanban Board و تقویم شمسی
- 🎨 **۹ تم رنگی** + حالت روشن/تاریک
- 📱 **PWA** (قابلیت نصب روی گوشی)

## ✨ امکانات

### 🤖 هوش مصنوعی

- چت پیشرفته با Llama 4 (حالت Think و Search)
- ساخت تصویر با SDXL، DreamShaper و Lightning
- تحلیل و ویرایش پروژه‌های گیت‌هاب با AI
- مشاور هوشمند فروش با Knowledge Base (۱۰۰+ خدمت)
- پشتیبانی از ورودی صوتی فارسی

### 🛒 فروشگاه

- دسته‌بندی ۱۵ گانه خدمات و کالاها
- سبد خرید و تسویه حساب
- جستجوی هوشمند در محصولات
- فاکتور و پیگیری سفارشات

### 👥 مدیریت مشتریان (CRM)

- ثبت اطلاعات مشتریان حضوری
- تگ‌گذاری و دسته‌بندی
- سیستم رنکینگ (برنزی تا الماس)
- یادداشت‌ها و یادآوری‌ها
- اتصال به اکانت کاربری سایت

### 🎨 طراحی

- ۹ تم رنگی (Sapphire, Emerald, Ruby, Amber, Amethyst, Teal, Rose, Indigo, Cyan)
- حالت روشن و تاریک
- آیکون‌های Phosphor (مدرن و یکدست)
- کاملاً واکنش‌گرا (Mobile-First)
- پشتیبانی از PWA

### 🔒 امنیت

- CSRF Protection
- Rate Limiting
- Prepared Statements (جلوگیری از SQL Injection)
- XSS Prevention
- کوکی‌های HttpOnly و Secure
- سیستم احراز هویت با OAuth (Google و GitHub)

## 📋 پیش‌نیازها

- **PHP** 8.0 یا بالاتر
- **MySQL** 5.7 یا بالاتر / MariaDB 10.3+
- **Apache** با mod_rewrite فعال
- **SSL Certificate** (برای PWA و OAuth)
- **Cloudflare Account** (برای API هوش مصنوعی)

### اکستنشن‌های PHP مورد نیاز:

- PDO + PDO_MySQL
- cURL
- GD یا Imagick
- mbstring
- json
- openssl

## 🚀 نصب و راه‌اندازی

### ۱. کلون کردن مخزن

````bash
git clone https://github.com/arctic4004/golestan-platform.git
cd golestan-platform
README.md ایجاد کنید:

markdown
# ☕ کافی‌نت گلستان - پلتفرم جامع هوش مصنوعی و خدمات کامپیوتری

![Version](https://img.shields.io/badge/version-3.0-blue)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-purple)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-orange)
![License](https://img.shields.io/badge/license-Private-red)

<div dir="rtl">

## 📋 فهرست محتوا

- [درباره پروژه](#درباره-پروژه)
- [امکانات](#امکانات)
- [ساختار پروژه](#ساختار-پروژه)
- [پیش‌نیازها](#پیش‌نیازها)
- [نصب و راه‌اندازی](#نصب-و-راه‌اندازی)
- [تکنولوژی‌ها](#تکنولوژی‌ها)
- [تنظیمات](#تنظیمات)
- [امنیت](#امنیت)
- [نقشه راه](#نقشه-راه)
- [تماس](#تماس)

## 🎯 درباره پروژه

**کافی‌نت گلستان** یک پلتفرم جامع تحت وب است که خدمات کامپیوتری، هوش مصنوعی، فروشگاه آنلاین و مدیریت مشتریان را در یک سامانه یکپارچه ارائه می‌دهد. این پروژه برای یک کافی‌نت واقعی در شهر یاسوج طراحی و پیاده‌سازی شده است.

### چرا گلستان؟
- 🦙 **چت هوشمند** با مدل Llama 4 (Cloudflare Workers AI)
- 🎨 **ساخت تصویر** با ۳ مدل مختلف هوش مصنوعی
- 🤖 **مشاور فروش** با قابلیت ورودی صوتی (Web Speech API)
- 🛒 **فروشگاه آنلاین** با ۱۰۰+ خدمت و کالا
- 👥 **مدیریت مشتریان (CRM)** با سیستم رنکینگ
- 📋 **مدیریت تسک‌ها** با Kanban Board و تقویم شمسی
- 🎨 **۹ تم رنگی** + حالت روشن/تاریک
- 📱 **PWA** (قابلیت نصب روی گوشی)

## ✨ امکانات

### 🤖 هوش مصنوعی
- چت پیشرفته با Llama 4 (حالت Think و Search)
- ساخت تصویر با SDXL، DreamShaper و Lightning
- تحلیل و ویرایش پروژه‌های گیت‌هاب با AI
- مشاور هوشمند فروش با Knowledge Base (۱۰۰+ خدمت)
- پشتیبانی از ورودی صوتی فارسی

### 🛒 فروشگاه
- دسته‌بندی ۱۵ گانه خدمات و کالاها
- سبد خرید و تسویه حساب
- جستجوی هوشمند در محصولات
- فاکتور و پیگیری سفارشات

### 👥 مدیریت مشتریان (CRM)
- ثبت اطلاعات مشتریان حضوری
- تگ‌گذاری و دسته‌بندی
- سیستم رنکینگ (برنزی تا الماس)
- یادداشت‌ها و یادآوری‌ها
- اتصال به اکانت کاربری سایت

### 🎨 طراحی
- ۹ تم رنگی (Sapphire, Emerald, Ruby, Amber, Amethyst, Teal, Rose, Indigo, Cyan)
- حالت روشن و تاریک
- آیکون‌های Phosphor (مدرن و یکدست)
- کاملاً واکنش‌گرا (Mobile-First)
- پشتیبانی از PWA

### 🔒 امنیت
- CSRF Protection
- Rate Limiting
- Prepared Statements (جلوگیری از SQL Injection)
- XSS Prevention
- کوکی‌های HttpOnly و Secure
- سیستم احراز هویت با OAuth (Google و GitHub)

## 📁 ساختار پروژه
public_html/
├── 📄 index.php # صفحه اصلی
├── 📄 login.php # ورود (Google + GitHub OAuth)
├── 📄 signup.php # ثبت‌نام
├── 📁 config/ # تنظیمات
│ ├── constants.php # ثابت‌های سایت
│ ├── database.php # اتصال PDO
│ └── oauth_config.php # تنظیمات OAuth
├── 📁 includes/ # فایل‌های مشترک
│ ├── header.php # هدر + SEO
│ ├── footer.php # فوتر + PWA
│ ├── navbar.php # نوبار
│ └── functions.php # توابع کمکی
├── 📁 api/ # API endpoints
│ ├── chat/ # چت هوشمند
│ ├── image/ # ساخت و ویرایش تصویر
│ └── tasks/ # مدیریت تسک‌ها
├── 📁 shop/ # فروشگاه
│ ├── index.php # صفحه اصلی فروشگاه
│ ├── product.php # صفحه محصول
│ ├── cart.php # سبد خرید
│ ├── agent.php # مشاور هوشمند
│ └── category.php # دسته‌بندی‌ها
├── 📁 user/dashboard/v2/ # داشبورد کاربری
│ ├── index.php # داشبورد
│ ├── chat.php # چت AI
│ ├── image.php # ساخت عکس
│ ├── tasks.php # تسک‌ها
│ ├── profile.php # پروفایل
│ └── settings.php # تنظیمات (تم‌ها)
├── 📁 admin/ # پنل مدیریت
│ ├── index.php # داشبورد ادمین
│ └── crm/ # مدیریت مشتریان
├── 📁 assets/ # فایل‌های استاتیک
│ ├── css/style.css # استایل اصلی
│ ├── js/ # اسکریپت‌ها
│ └── icons/ # آیکون‌های PWA
├── 📁 knowledge/ # پایگاه دانش
│ └── cafenet_knowledge.json # ۱۰۰+ خدمت با قیمت و مدارک
├── 📄 manifest.json # PWA manifest
├── 📄 sw.js # Service Worker
└── 📄 .htaccess # تنظیمات سرور

text

## 📋 پیش‌نیازها

- **PHP** 8.0 یا بالاتر
- **MySQL** 5.7 یا بالاتر / MariaDB 10.3+
- **Apache** با mod_rewrite فعال
- **SSL Certificate** (برای PWA و OAuth)
- **Cloudflare Account** (برای API هوش مصنوعی)

### اکستنشن‌های PHP مورد نیاز:
- PDO + PDO_MySQL
- cURL
- GD یا Imagick
- mbstring
- json
- openssl

## 🚀 نصب و راه‌اندازی

### ۱. کلون کردن مخزن
```bash
git clone https://github.com/arctic4004/golestan-platform.git
cd golestan-platform
۲. تنظیم دیتابیس
یک دیتابیس MySQL ایجاد کنید

فایل database.sql را import کنید (اگر موجود باشد)

یا جداول را از طریق پنل مدیریت phpMyAdmin ایجاد کنید

۳. تنظیم فایل‌های پیکربندی
فایل config/database.php را با اطلاعات دیتابیس خود ویرایش کنید:

php
$this->host = 'localhost';
$this->db_name = 'your_database_name';
$this->username = 'your_username';
$this->password = 'your_password';
۴. تنظیم API Keys
توکن‌های مورد نیاز را در جدول settings دیتابیس وارد کنید:

sql
INSERT INTO settings (setting_key, setting_value) VALUES
('deepseek_api_key', 'your_cloudflare_api_token');
۵. تنظیم OAuth (اختیاری)
برای فعال‌سازی ورود با Google و GitHub:

فایل config/oauth_config.php را با کلیدهای API خود تنظیم کنید

آدرس‌های callback را در کنسول توسعه‌دهندگان گوگل و گیت‌هاب ثبت کنید

۶. تنظیم PWA
فایل manifest.json را با اطلاعات سایت خود به‌روز کنید:

json
{
  "name": "نام سایت شما",
  "short_name": "نام کوتاه",
  "start_url": "/",
  "theme_color": "#4f46e5"
}
🛠️ تکنولوژی‌ها
دسته	تکنولوژی
Backend	PHP 8.x (OOP, MVC)
Database	MySQL (PDO)
Frontend	HTML5, CSS3 (Variables, Grid, Flexbox)
JavaScript	Vanilla JS (ES6+)
AI Models	Llama 4 (Cloudflare), SDXL, DreamShaper
Icons	Phosphor Icons
Font	Vazirmatn (فارسی), Inter (انگلیسی)
PWA	Service Worker, Web App Manifest
APIs	Google OAuth, GitHub OAuth, Cloudflare Workers AI
⚙️ تنظیمات
تمامی تنظیمات سایت از طریق پنل مدیریت (/admin/) قابل تغییر است:

🎨 تم سایت: ۹ رنگ + حالت روشن/تاریک

🔑 API Keys: مدیریت توکن‌های هوش مصنوعی

👥 کاربران: مدیریت کاربران و مشتریان

📦 محصولات: افزودن/ویرایش خدمات و کالاها

🛒 سفارشات: مشاهده و مدیریت سفارشات

🔒 امنیت
SQL Injection: استفاده از Prepared Statements در تمام کوئری‌ها

XSS: استفاده از htmlspecialchars() و sanitize()

CSRF: توکن‌های یکبار مصرف در تمام فرم‌ها

Rate Limiting: محدودیت ۵ تلاش ناموفق در ۵ دقیقه

Session Security: کوکی‌های HttpOnly و Secure

File Security: فایل‌های حساس در .gitignore

🗺️ نقشه راه
✅ انجام شده
سیستم احراز هویت (Google, GitHub, Mobile)

چت هوشمند با Llama 4

ساخت تصویر با ۳ مدل

فروشگاه ۱۰۰+ محصول

CRM مدیریت مشتریان

پنل مدیریت

PWA و قابلیت نصب

۹ تم رنگی

تقویم شمسی و تسک‌ها

🚧 در حال توسعه
ربات تلگرام (نوتیفیکیشن)

سیستم گزارش‌گیری (نمودار فروش)

درگاه پرداخت آنلاین

اپلیکیشن موبایل (PWA پیشرفته)

💡 برنامه‌های آینده
n8n اتوماسیون

پشتیبانی از مدل‌های بیشتر AI

سیستم امتیازدهی و گیمیفیکیشن

باشگاه مشتریان

📞 تماس
توسعه‌دهنده: Arctic4004

وب‌سایت: golestanyasuj.ir

گیت‌هاب: github.com/arctic4004/golestan-platform

📄 مجوز
این پروژه یک پروژه شخصی-تجاری است و تمامی حقوق محفوظ می‌باشد.

````
