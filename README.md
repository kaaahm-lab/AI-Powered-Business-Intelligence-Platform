# AI-Powered Business Intelligence Platform

واجهة خلفية مبنية باستخدام Laravel 9 لإدارة وتحليل أفكار المشاريع بالاعتماد على عدة خدمات ذكاء اصطناعي محلية. يوفّر النظام تسجيل المستخدمين وتوثيقهم، إنشاء الأفكار وتحليلها، استخراج المنافسين، التوصيات، التقدير المالي، تحليل المنطقة المناسبة، وتقدير أسعار العقارات المرتبطة بفكرة المشروع.

## فكرة المشروع

يستقبل النظام فكرة المشروع من المستخدم، ثم يمررها إلى خدمات تحليل خارجية تعمل محليًا على منافذ مختلفة. بعد ذلك يتم حفظ نتائج التحليل داخل قاعدة البيانات وإتاحتها عبر API محمي باستخدام Laravel Sanctum.

هذا المستودع يمثل طبقة الـ Backend فقط، بينما تعتمد بعض وظائفه على خدمات AI خارجية يجب تشغيلها محليًا حتى تعمل جميع المزايا بشكل كامل.

## المزايا الرئيسية

- تسجيل مستخدم جديد وتسجيل الدخول والخروج باستخدام `Sanctum`.
- إدارة حساب المستخدم: عرض البيانات، تعديلها، حذف الحساب.
- إنشاء فكرة مشروع جديدة وتحليلها تلقائيًا.
- إعادة تحليل فكرة موجودة بعد تعديلها.
- حفظ واسترجاع أفكار المستخدم الخاصة.
- تحليل المنافسين وربطهم بكل فكرة.
- استرجاع التوصيات والتقدير المالي وتقارير التحليل.
- تحليل المنطقة الأنسب للفكرة.
- تقدير أسعار العقارات بناءً على المنطقة ونوع العقار والحجم.
- حفظ `FCM token` للمستخدم من أجل الإشعارات.
- توفير عينات من سجل تجاري مخزّن داخل قاعدة البيانات.

## التقنيات المستخدمة

- `PHP 8`
- `Laravel 9`
- `Laravel Sanctum`
- `MySQL`
- `Laravel Mix`
- `Guzzle HTTP`
- `Firebase PHP SDK`
- `DOMPDF`

## بنية النظام

المشروع يعمل كـ REST API، ويتكامل مع خدمات خارجية محلية مثل:

- `http://127.0.0.1:8009/predict`
  لتحليل الفكرة وتصنيفها.
- `http://127.0.0.1:8002/competition/analyze`
  لتحليل المنافسين ونتائج SWOT.
- `http://127.0.0.1:8005/predict`
  لتحليل المنطقة المناسبة.
- `http://127.0.0.1:8007/predict`
  لتقدير أسعار العقارات.
- `http://127.0.0.1:8001/api/mock-ai`
  مستخدمة في مسار إعادة التحليل القديم.

## قاعدة البيانات

أهم الجداول الموجودة في المشروع:

- `users`
- `ideas`
- `analyses`
- `competitors`
- `recommendations`
- `financial_estimates`
- `swot_analyses`
- `region_analyses`
- `property_price_analyses`
- `commerce_registers`
- `personal_access_tokens`

## أهم المسارات

### Auth

- `POST /api/registration`
- `POST /api/login`
- `POST /api/logout`
- `GET /api/user/me`
- `PUT /api/user/update`
- `DELETE /api/user/delete`
- `POST /api/user/fcm-token`

### Ideas

- `POST /api/ideas`
- `GET /api/ideas/my`
- `GET /api/ideas/{id}`
- `POST /api/idea/update`
- `POST /api/idea/delete`
- `POST /api/idea/reanalyze`
- `POST /api/ideas/region-analysis`
- `POST /api/ideas/property-price`

### Analysis Results

- `POST /api/competitors/get`
- `POST /api/recommendations/get`
- `POST /api/financial/get`
- `POST /api/report/get`

### Commerce Register

- `GET /api/commerce-registers/samples`

## تشغيل المشروع محليًا

### 1. استنساخ المشروع

```bash
git clone <your-repo-url>
cd senior_project
```

### 2. تثبيت الاعتمادات

```bash
composer install
npm install
```

### 3. إعداد ملف البيئة

```bash
cp .env.example .env
php artisan key:generate
```

ثم عدّل بيانات قاعدة البيانات داخل ملف `.env` مثل:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password
```

### 4. تنفيذ الـ migrations

```bash
php artisan migrate
```

### 5. تشغيل المشروع

```bash
php artisan serve
```

ولتجميع الملفات الأمامية إن لزم:

```bash
npm run dev
```

## متطلبات التشغيل الكاملة

حتى تعمل جميع وظائف التحليل، يجب أن تكون خدمات الذكاء الاصطناعي الخارجية شغالة محليًا على المنافذ المذكورة سابقًا. إذا لم تكن هذه الخدمات متوفرة، فسيعمل الـ API جزئيًا فقط، بينما ستفشل المسارات التي تعتمد على التحليل الخارجي.

## المصادقة

يعتمد المشروع على `Laravel Sanctum`. بعد تسجيل الدخول أو إنشاء حساب جديد، يعيد النظام `token` يجب تمريره في الهيدر:

```http
Authorization: Bearer YOUR_TOKEN
```

