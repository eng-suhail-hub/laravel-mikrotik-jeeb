# MikroTik Cards System (Laravel Backend)

نظام Laravel لإدارة وتوليد كروت الإنترنت تلقائياً عبر راوتر **MikroTik RB1100AHx2** (RouterOS v6.49.19) مع تكامل محفظة **Jeeb**.

---

## 🎯 مميزات النظام

- ✅ إدارة بيانات الراوتر + اختبار الاتصال من لوحة الأدمن
- ✅ توليد كروت في **User Manager** (ليس Hotspot) مع `username == password`
- ✅ معالجة الطلبات عبر **Laravel Queue** (تتابعية، لا overload على الراوتر)
- ✅ استقبال إشعارات الدفع من **Emulator** عبر Webhook محمي (localhost + API key)
- ✅ لوحة تحكم خفيفة (Blade + Bootstrap 5) — بدون Nova/Filament
- ✅ سجل مالي غير قابل للتعديل (`raw_webhooks` - Append-Only)
- ✅ تطابق آلي بين العميل المسجّل وإشعار الدفع (full_name + phone)
- ✅ تفعيل يدوي للعمليات غير المطابقة
- ✅ Failover Logic: الطلب يُقبل حتى لو الراوتر مُعطّل

---

## 📋 المتطلبات

- PHP **8.2+**
- MySQL 5.7+ / MariaDB 10.3+ (أو SQLite للاختبار المحلي)
- Composer
- MikroTik RouterOS v6.49.19 مع تفعيل API على منفذ `8728`
- Android Emulator على نفس الجهاز الفيزيائي

---

## 🚀 خطوات التثبيت

### 1. تثبيت Dependencies

```bash
cd /path/to/project
composer install
```

### 2. إعداد ملف البيئة

```bash
cp .env.example .env
php artisan key:generate
```

### 3. **مهم جداً:** عدّل المفتاح السري في `.env`

```env
JEEB_WEBHOOK_SECRET=<مفتاح-سري-قوي-32-حرف>
```

### 4. إنشاء قاعدة البيانات

```bash
# في MySQL
mysql -u root -p -e "CREATE DATABASE mikrotik_cards CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# تشغيل الـ migrations
php artisan migrate
```

### 5. إنشاء أول حساب أدمن

```bash
php artisan db:seed --class=InitialAdminSeeder
```

الافتراضي:
- **Username:** `admin`
- **Password:** `admin123`

⚠️ غيّر كلمة المرور فوراً بعد الدخول الأول.

### 6. صلاحيات المجلدات

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

---

## ⚙️ الإعداد في MikroTik الراوتر

قبل استخدام النظام، يجب إعداد الراوتر عبر **WinBox**:

### تفعيل خدمة API:

```
IP → Services → api
✅ Enabled
✅ Port: 8728
```

### إنشاء باقات في User Manager:

```
Tool → User Manager → Profiles
```

أنشئ Profiles بأسماء واضحة (مثلاً `um-5k-daily`، `um-10k-weekly`).

### إنشاء مستخدم Customer (مرة واحدة):

```
Tool → User Manager → Customers → Add
Name: admin
Password: <password>
```

⚠️ نفس الـ `customer = admin` يجب أن يُمرّر للـ API في `MikroTikService::createUserManagerUser`.

---

## 🖥️ تشغيل النظام

### تشغيل خادم Laravel:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

### **مهم:** تشغيل Queue Worker (في طرفية منفصلة):

```bash
php artisan queue:work --queue=cards --tries=3 --timeout=60 --sleep=3
```

أو باستخدام Supervisor (موصى به في Production):

```ini
[program:mikrotik-cards-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/project/artisan queue:work --queue=cards --tries=3 --timeout=60 --sleep=3
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/project/storage/logs/worker.log
```

---

## 📡 API Endpoints

### لتطبيق Flutter

| Method | URL | الوصف |
|--------|-----|------|
| `POST` | `/api/auth/register` | تسجيل العميل (لمرة واحدة) |
| `GET`  | `/api/profiles` | عرض الباقات النشطة |
| `POST` | `/api/purchase` | طلب شراء كرت |

### Webhook من Emulator

| Method | URL | الوصف |
|--------|-----|------|
| `POST` | `/api/webhook/jeeb` | استقبال إشعار الدفع |

⚠️ يجب أن يكون الـ Request من `127.0.0.1` ومع الـ Header:
```
X-Jeeb-Secret: <JEEB_WEBHOOK_SECRET من .env>
Content-Type: application/json
```

مثال على جسم الطلب:
```json
{
  "text": "تم استلام 5000 ر.ي من محمد أحمد علي الحضرمي إلى 967712345678. Ref: TXN123ABC"
}
```

أو كنص خام:
```json
"تم استلام 5000 ر.ي من محمد أحمد علي الحضرمي إلى 967712345678. Ref: TXN123ABC"
```

---

## 🛡️ الأمان

### 1. Webhook محمي بـ:

- **LocalhostOnly:** الطلب يجب أن يكون من `127.0.0.1` فقط
- **API Key:** Header `X-Jeeb-Secret` يطابق المفتاح في `.env`

### 2. كلمة مرور الراوتر:

- مُشفّرة في قاعدة البيانات عبر Laravel `encrypted` cast
- لا تظهر في الواجهة بعد الحفظ

### 3. سجل الـ Webhook (Append-Only):

- الـ Model `RawWebhook` يمنع UPDATE/DELETE عبر `booted()`
- يُسجّل النص الخام + الوقت بدقة **ملي ثانية**

### 4. حساب الأدمن:

- Session Guard منفصل (`admin`)
- كلمة المرور مُجزأة (bcrypt)

---

## 🏗️ البنية المعمارية

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/         # لوحة التحكم
│   │   └── Api/           # Flutter + Webhook
│   └── Middleware/        # AdminAuth + LocalhostOnly
├── Jobs/
│   └── GenerateMikrotikCardJob.php  # الطابور
├── Models/
│   ├── Admin.php
│   ├── User.php           # العملاء (Flutter)
│   ├── RouterSetting.php
│   ├── Profile.php
│   ├── Transaction.php
│   └── RawWebhook.php     # ⚠️ غير قابل للتعديل
└── Services/
    ├── WebhookParser.php  # تحليل النصوص (Regex)
    ├── MikroTikService.php # الاتصال بالراوتر
    └── CardGeneratorService.php # توليد الكرت
```

### ⚠️ الفصل المعماري الصارم:

- **WebhookParser** → منطق نصوص فقط (لا شبكة)
- **MikroTikService** → شبكة فقط (لا regex)
- **CardGeneratorService** → تنسيق + قواعد بيانات

---

## 🔧 أوامر مفيدة

```bash
# عرض الـ Jobs الفاشلة
php artisan queue:failed

# إعادة تشغيل job فاشل
php artisan queue:retry <id>

# إعادة كل الـ Jobs الفاشلة
php artisan queue:retry all

# مسح الـ Jobs الفاشلة
php artisan queue:flush

# اختبار الاتصال بالراوتر (من CLI)
php artisan tinker
>>> app(\App\Services\MikroTikService::class)->testConnection('192.168.88.1', 8728, 'admin', 'password');
```

---

## 🐛 استكشاف الأخطاء

### "Router not connected"

1. تحقق من الـ IP في إعدادات الراوتر
2. تحقق من تفعيل API service على منفذ 8728 في الراوتر
3. تأكد أن كلمة المرور صحيحة

### "فشل تحليل Webhook"

1. افتح جدول `raw_webhooks` لرؤية النص الخام
2. عدّل أنماط الـ Regex في `config/jeeb.php` إذا تغيّر تنسيق إشعار جيب

### "الكروت لا تُولّد"

1. تأكد أن `queue:work` يعمل:
   ```bash
   php artisan queue:work --queue=cards
   ```
2. تحقق من `storage/logs/laravel.log`

---

## 📜 الترخيص

MIT License — استخدم بحرية.
