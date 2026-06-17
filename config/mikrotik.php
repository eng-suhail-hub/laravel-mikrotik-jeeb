<?php

/**
 * ════════════════════════════════════════════════════════════════
 *  إعدادات المايكروتك (RouterOS v6)
 * ════════════════════════════════════════════════════════════════
 *
 *  هذه القيم هي "افتراضات النظام"، وليست بيانات الاتصال الفعلية.
 *  بيانات الاتصال الحقيقية (IP, Username, Password) تُخزن في جدول
 *  router_settings ويُديرها الأدمن حصرياً عبر لوحة التحكم.
 *
 *  سبب الفصل: بيانات الاتصال سرية وتُغيّر بين بيئة وأخرى،
 *  ولا يجب أن تكون مكتوبة في الكود أو متاحة في الـ env بشكل دائم.
 */

return [

    // منفذ الـ API التقليدي في RouterOS v6 (مكتبة routeros-api)
    'default_port' => (int) env('MIKROTIK_DEFAULT_PORT', 8728),

    // مهلة الاتصال بالثواني (يجب أن تكون قصيرة لتقليل استهلاك المعالج)
    'connection_timeout' => (int) env('MIKROTIK_CONNECTION_TIMEOUT', 10),

    // مفتاح القفل (Lock) لمنع فتح أكثر من اتصال في نفس الوقت
    // (Laravel Cache Lock — يمنع الـ race condition)
    'queue_lock_key' => env('MIKROTIK_QUEUE_LOCK_KEY', 'mikrotik_connection_lock'),

    // مدة قفل الاتصال بالثواني (لتجنّب التعليق الطويل عند انقطاع الراوتر)
    'queue_lock_ttl' => 30,

    // المسار داخل الراوتر لإضافة مستخدم جديد (User Manager)
    // ⚠️ تم اختيار User Manager وليس Hotspot حسب المواصفات
    'user_manager_add_path' => '/tool/user-manager/user/add',

    // المسار لتعديل كلمة مرور مستخدم في User Manager
    'user_manager_set_path' => '/tool/user-manager/user/set',

    // المسار لعرض قائمة المستخدمين (للتحقق)
    'user_manager_print_path' => '/tool/user-manager/user/print',

    // إعدادات الـ Hotspot (غير مستخدمة حالياً — محجوزة للمستقبل)
    'hotspot_enabled' => false,
];
