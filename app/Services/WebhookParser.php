<?php

namespace App\Services;

/**
 * ════════════════════════════════════════════════════════════════
 *  كلاس تحليل إشعار محفظة جيب (Webhook Parser)
 * ════════════════════════════════════════════════════════════════
 *
 *  ⚠️ هذا الكلاس مسؤول فقط عن تحليل النصوص (Regex).
 *  لا يتصل بالراوتر ولا بقاعدة البيانات ولا بأي شبكة.
 *  يستقبل نص الإشعار الخام ويُرجع مصفوفة مهيكلة.
 *
 *  سبب الفصل:
 *  - سهولة اختبار الـ Parser بدون محاكاة شبكة
 *  - سهولة تعديل الأنماط بدون لمس منطق الاتصال
 *  - الوضوح المعماري (SoC - Separation of Concerns)
 *
 *  أنماط الـ Regex مُعرّفة في config/jeeb.php
 */
class WebhookParser
{
    /**
     * تحليل نص الإشعار واستخراج البيانات المالية
     *
     * @param string $rawText النص الخام للإشعار
     * @return array {
     *     @var bool   $success        هل نجح التحليل
     *     @var string $error          رسالة الخطأ في حالة الفشل
     *     @var float  $amount         المبلغ المستخرج
     *     @var string $phone          رقم الهاتف (مُطبّع بصيغة 967xxxxxxxxx)
     *     @var string $full_name      الاسم الرباعي
     *     @var string $reference      رقم المرجع من جيب
     * }
     */
    public function parse(string $rawText): array
    {
        $result = [
            'success' => false,
            'error' => null,
            'amount' => null,
            'phone' => null,
            'full_name' => null,
            'reference' => null,
        ];

        // تنظيف النص (إزالة الـ emojis و Unicode المختلط)
        $clean = $this->normalizeText($rawText);

        if (empty(trim($clean))) {
            $result['error'] = 'النص فارغ بعد التنظيف';
            return $result;
        }

        // استخراج الحقول واحداً تلو الآخر
        $phone = $this->extractPhone($clean);
        $amount = $this->extractAmount($clean);
        $name = $this->extractName($clean);
        $reference = $this->extractReference($clean);

        // ⚠️ الهاتف والاسم حقلان إجباريان للمطابقة
        // المبلغ والمرجع اختياريان لكنهما مهمان للتدقيق
        if (empty($phone) || empty($name)) {
            $result['error'] = 'بيانات ناقصة: ' .
                (empty($phone) ? 'الهاتف ' : '') .
                (empty($name) ? 'الاسم' : '');
            $result['amount'] = $amount;
            $result['reference'] = $reference;
            return $result;
        }

        $result['success'] = true;
        $result['amount'] = $amount;
        $result['phone'] = $phone;
        $result['full_name'] = $name;
        $result['reference'] = $reference;

        return $result;
    }

    /**
     * تنظيف النص وتوحيد الـ Unicode
     */
    private function normalizeText(string $text): string
    {
        // توحيد الحروف العربية (تطبيع الألف والياء والتاء المربوطة)
        $text = preg_replace('/[إأآا]/u', 'ا', $text);
        $text = preg_replace('/[ىي]/u', 'ي', $text);
        $text = preg_replace('/ة/u', 'ه', $text);

        // إزالة الإيموجي والرموز الغريبة
        $text = preg_replace('/[\x{1F300}-\x{1FAFF}]/u', '', $text);
        $text = preg_replace('/[^\p{L}\p{N}\s\.,:\-+]/u', ' ', $text);

        // تقليص المسافات المتعددة
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim($text);
    }

    /**
     * استخراج رقم الهاتف وتطبيع بصيغة 967xxxxxxxxx
     */
    private function extractPhone(string $text): ?string
    {
        $pattern = config('jeeb.parser.phone_pattern', '/(?:\+?967|0)?7\d{8}/u');

        if (!preg_match($pattern, $text, $matches)) {
            return null;
        }

        $phone = $matches[0];

        // إزالة +
        $phone = ltrim($phone, '+');

        // إذا بدأ بـ 0، أزله وأضف 967
        if (preg_match('/^07\d{8}$/', $phone)) {
            $phone = '967' . substr($phone, 1);
        }

        // إذا بدأ بـ 7 بدون رمز دولة، أضف 967
        if (preg_match('/^7\d{8}$/', $phone)) {
            $phone = '967' . $phone;
        }

        return $phone;
    }

    /**
     * استخراج المبلغ كرقم عشري
     */
    private function extractAmount(?string $text): ?float
    {
        if (empty($text)) {
            return null;
        }

        $pattern = config('jeeb.parser.amount_pattern', '/(\d{1,3}(?:[,\s]\d{3})*|\d+)/u');

        if (!preg_match($pattern, $text, $matches)) {
            return null;
        }

        $raw = $matches[1];

        // إزالة الفواصل والمسافات بين الأرقام
        $clean = preg_replace('/[,\s]/', '', $raw);

        return (float) $clean;
    }

    /**
     * استخراج الاسم (بعد كلمة "إلى" أو "لـ")
     */
    private function extractName(string $text): ?string
    {
        $pattern = config('jeeb.parser.name_pattern', '/(?:إلى|لـ|الى|from|to)\s+([\p{L}\s]{4,})/u');

        if (!preg_match($pattern, $text, $matches)) {
            // محاولة أخيرة: أول 4 كلمات عربية متتالية في النص
            if (preg_match('/([\p{L}]{2,}\s+[\p{L}]{2,}\s+[\p{L}]{2,}\s+[\p{L}]{2,})/u', $text, $fallback)) {
                return trim($fallback[1]);
            }
            return null;
        }

        $name = trim($matches[1]);

        // أخذ أول 4 كلمات فقط (الاسم الرباعي)
        $words = preg_split('/\s+/u', $name, 5);
        if (count($words) > 4) {
            $words = array_slice($words, 0, 4);
        }

        return implode(' ', $words);
    }

    /**
     * استخراج رقم المرجع (Transaction Reference)
     */
    private function extractReference(string $text): ?string
    {
        $pattern = config('jeeb.parser.reference_pattern', '/(?:Ref|ref|مرجع|REF)[:#\s]*([A-Z0-9\-]{4,})/u');

        if (preg_match($pattern, $text, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }
}
