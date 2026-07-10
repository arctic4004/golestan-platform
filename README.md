<div align="center">

# <picture><source media="(prefers-color-scheme: dark)" srcset="https://raw.githubusercontent.com/arctic4004/golestan-platform/main/assets/icons/icon-192x192.png"><img alt="Golestan" src="https://raw.githubusercontent.com/arctic4004/golestan-platform/main/assets/icons/icon-192x192.png" width="48"></picture> کافی‌نت گلستان

**پلتفرم جامع هوش مصنوعی، فروشگاه و مدیریت مشتریان**

[![PHP](https://img.shields.io/badge/PHP-8.0%2B-777bb4?logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-4479a1?logo=mysql&logoColor=white)](https://mysql.com)
[![License](https://img.shields.io/badge/license-Private-red)](LICENSE)

</div>

---

## ✨ ویژگی‌های کلیدی

- **🦙 چت هوشمند** با Llama 4 · حالت تفکر عمیق و جستجو
- **🎨 ساخت تصویر** با ۳ مدل هوش مصنوعی · SDXL، DreamShaper، Lightning
- **🤖 مشاور فروش** با ورودی صوتی فارسی · Web Speech API
- **🛒 فروشگاه آنلاین** با ۱۰۰+ خدمت · سبد خرید و تسویه حساب
- **👥 CRM** با رنکینگ مشتریان · برنزی تا الماس
- **📋 مدیریت تسک** با Kanban Board و تقویم شمسی
- **🎨 ۹ تم رنگی** + حالت روشن و تاریک
- **📱 PWA** · نصب روی گوشی بدون نیاز به فروشگاه

---

## 🛠️ تکنولوژی‌ها

`PHP` `MySQL` `PDO` `JavaScript` `CSS3` `Llama 4` `SDXL` `Cloudflare Workers AI` `Phosphor Icons` `PWA` `OAuth 2.0`

---

## 🚀 نصب سریع

````bash
git clone https://github.com/arctic4004/golestan-platform.git
cd golestan-platform
۱. دیتابیس MySQL ایجاد کنید
۲. فایل config/database.php را با اطلاعات دیتابیس تنظیم کنید
۳. توکن Cloudflare را در جدول settings ذخیره کنید:

sql
INSERT INTO settings (setting_key, setting_value)
VALUES ('deepseek_api_key', 'YOUR_CLOUDFLARE_TOKEN');
۴. فایل‌ها را روی سرور Apache آپلود کنید

📁 ساختار پروژه
text
.
├── api/             # APIها (چت، تصویر، تسک)
├── admin/           # پنل مدیریت و CRM
├── assets/          # CSS، JS، آیکون‌ها
├── config/          # تنظیمات دیتابیس و ثابت‌ها
├── includes/        # هدر، فوتر، نوبار، توابع
├── knowledge/       # پایگاه دانش ۱۰۰+ خدمت
├── shop/            # فروشگاه (محصولات، سبد خرید)
├── user/            # داشبورد کاربری
└── index.php        # صفحه اصلی
📄 مجوز
این پروژه شخصی-تجاری است و تمامی حقوق محفوظ می‌باشد.

<p align="center"> <sub>ساخته شده با ☕ در یاسوج، ایران</sub> </p> ```
````
