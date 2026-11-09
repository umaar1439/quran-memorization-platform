/**
 * عقد وحدة أعلام الميزات (Feature Flags) — صفحة Admin/FeatureFlags/Index.
 *
 * `admin.feature-flags.page` يمرّر نتيجة FeatureFlagQuery::forUser (مجموعة
 * Eloquent، بلا Resource) + `isSuperAdmin`، و`update` يستقبل {enabled} ويُرجع
 * RedirectResponse (Inertia).
 */

export type FeatureFlagPageModel = {
    id: number;
    /** null = علم عالمي افتراضي يخص super-admin فقط. */
    organization_id: number | null;
    key: string;
    enabled: boolean;
    updated_by: number | null;
};

export type FeatureFlagIndexPageProps = {
    flags: FeatureFlagPageModel[];
    isSuperAdmin: boolean;
};

/** حمولة التبديل — نفس مفتاح UpdateFeatureFlagRequest. */
export type FeatureFlagTogglePayload = {
    enabled: boolean;
};
