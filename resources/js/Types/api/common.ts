/**
 * عقود مشتركة بين وحدات الـ API: أخطاء التحقق وخيارات القوائم المنسدلة.
 * تُستورد من هنا بدل تكرارها في كل وحدة (Rule of Three).
 */

/** أخطاء التحقق القادمة من FormRequest (مفتاح الحقل => أول رسالة). */
export type FormErrors = Partial<Record<string, string>>;

/** خيار واحد في FormSelect — يوافق Option داخل المكوّن. */
export type SelectOption = {
    value: string | number;
    label: string;
};

/**
 * فشل استدعاء JSON بعد التطبيع: خريطة أخطاء تحقق (قد تكون فارغة عند فشل
 * غير تحقّقي كخطأ شبكة/500) ورسالة عامة صالحة للعرض.
 */
export type ApiFailure = {
    errors: FormErrors;
    message: string;
};
