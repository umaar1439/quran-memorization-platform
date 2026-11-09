/**
 * عقد وحدة إعدادات المنظمة (Settings) — صفحة Admin/Settings/Index.
 *
 * `admin.settings.page` يمرّر نتيجة SettingsQuery::allForOrganization كمجموعة
 * Eloquent (لا Resource)، و`admin.settings.update` يستقبل مصفوفة `settings`
 * من عناصر {key, value, group} ويُرجع RedirectResponse (Inertia).
 */

/** ما تُسلسِله Eloquent داخل settings (مصفوفة غير مُرقَّمة الصفحات). */
export type SettingPageModel = {
    id: number;
    organization_id: number | null;
    key: string;
    value: string | null;
    group: string | null;
};

export type SettingIndexPageProps = {
    settings: SettingPageModel[];
};

/** عنصر واحد في حمولة الحفظ — نفس مفاتيح UpdateSettingRequest. */
export type SettingEntryPayload = {
    key: string;
    value: string | null;
    group: string | null;
};

/** إعدادات مجمَّعة للعرض: مجموعة واحدة وبنودها. */
export type SettingGroup = {
    name: string;
    items: SettingPageModel[];
};
