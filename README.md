<div align="center">

<img src="https://raw.githubusercontent.com/arctic4004/golestan-platform/main/assets/icons/icon-192x192.png" width="80" alt="Golestan Cafénet Logo" />

# کافی‌نت گلستان

### پلتفرم جامع مدیریت کافی‌نت، خدمات دیجیتال و هوش مصنوعی

<p>
سامانه‌ای مدرن برای مدیریت خدمات کافی‌نت، فروشگاه آنلاین، مدیریت مشتریان، گفتگوی هوشمند، تولید تصویر، مدیریت وظایف و اتوماسیون کسب‌وکار.
</p>

<p>
<img src="https://img.shields.io/badge/PHP-8%2B-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP">
<img src="https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat-square&logo=mysql&logoColor=white" alt="MySQL">
<img src="https://img.shields.io/badge/JavaScript-ES6-F7DF1E?style=flat-square&logo=javascript&logoColor=black" alt="JavaScript">
<img src="https://img.shields.io/badge/Llama-4-8A2BE2?style=flat-square" alt="Llama 4">
<img src="https://img.shields.io/badge/PWA-Ready-5A0FC8?style=flat-square&logo=pwa&logoColor=white" alt="PWA">
<img src="https://img.shields.io/badge/License-MIT-success?style=flat-square" alt="License">
</p>

</div>

---

# درباره پروژه

**کافی‌نت گلستان** یک پلتفرم جامع تحت وب برای مدیریت کامل کافی‌نت و خدمات دیجیتال در **یاسوج، ایران** است.

این سامانه با هدف یکپارچه‌سازی خدمات حضوری و آنلاین طراحی شده و امکانات متنوعی از جمله مدیریت مشتریان، فروشگاه آنلاین، هوش مصنوعی، مدیریت وظایف، تقویم شمسی و اپلیکیشن پیش‌رونده (PWA) را در اختیار مدیران و کارکنان قرار می‌دهد.

---

# ویژگی‌های کلیدی

<table>
<tr>
<td width="50%">

### 🤖 هوش مصنوعی

- گفتگوی هوشمند با Llama 4
- تولید تصویر با ۳ مدل مختلف
- دستیار فروش صوتی
- پاسخگویی خودکار

</td>

<td width="50%">

### 🛍️ خدمات و فروش

- بیش از ۱۰۰ خدمت آنلاین
- فروشگاه اینترنتی
- ثبت سفارش
- مدیریت پرداخت

</td>
</tr>

<tr>
<td>

### 👥 مدیریت مشتریان

- CRM حرفه‌ای
- رتبه‌بندی مشتریان
- سوابق خدمات
- مدیریت پرونده‌ها

</td>

<td>

### ⚙️ امکانات مدیریتی

- ۹ تم رنگی
- مدیریت وظایف Kanban
- تقویم شمسی
- نصب به‌صورت PWA

</td>
</tr>
</table>

---

# تکنولوژی‌ها

<div align="center">

<img src="https://img.shields.io/badge/PHP-8%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP">

<img src="https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL">

<img src="https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black" alt="JavaScript">

<img src="https://img.shields.io/badge/Cloudflare-Workers_AI-F38020?style=for-the-badge&logo=cloudflare&logoColor=white" alt="Cloudflare Workers AI">

<img src="https://img.shields.io/badge/Phosphor-Icons-000000?style=for-the-badge" alt="Phosphor Icons">

<img src="https://img.shields.io/badge/PWA-Ready-5A0FC8?style=for-the-badge&logo=pwa&logoColor=white" alt="PWA">

</div>

---

# نصب و راه‌اندازی

## مرحله ۱: ایجاد پایگاه داده

ابتدا یک پایگاه داده جدید در MySQL ایجاد کرده و فایل SQL پروژه را در آن Import نمایید.

---

## مرحله ۲: ویرایش فایل `config/database.php`

```php
<?php

return [
    'host' => 'localhost',
    'database' => 'golestan_platform',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4'
];
```

---

## مرحله ۳: ثبت توکن API

```sql
UPDATE settings
SET value = 'YOUR_API_TOKEN'
WHERE name = 'workers_ai_token';
```

---

## مرحله ۴: بارگذاری روی Apache

کل پروژه را در مسیر DocumentRoot وب‌سرور Apache قرار دهید و سپس سرویس Apache و MySQL را راه‌اندازی کنید.

---

# ساختار پروژه

```text
golestan-platform/
├── assets/
│   ├── css/
│   ├── js/
│   ├── icons/
│   └── images/
├── config/
│   └── database.php
├── modules/
│   ├── ai/
│   ├── crm/
│   ├── shop/
│   ├── kanban/
│   ├── calendar/
│   └── voice/
├── api/
├── uploads/
├── vendor/
├── index.php
├── manifest.json
├── service-worker.js
└── README.md
```

---

# امنیت

| بخش                      | وضعیت                  |
| ------------------------ | ---------------------- |
| اعتبارسنجی ورودی‌ها      | ✅                     |
| جلوگیری از SQL Injection | ✅ Prepared Statements |
| جلوگیری از XSS           | ✅ Escape Output       |
| احراز هویت کاربران       | ✅                     |
| مدیریت نشست‌ها           | ✅ Secure Session      |
| محافظت از API Token      | ✅                     |
| HTTPS پیشنهادی           | ✅                     |
| سطح دسترسی کاربران       | ✅ Role-Based Access   |

---

<div align="center">

### ساخته شده در یاسوج، ایران 🇮🇷

</div>
