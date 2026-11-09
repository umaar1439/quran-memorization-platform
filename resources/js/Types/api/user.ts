/**
 * عقد وحدة المستخدمين والأدوار.
 *
 * لا يوجد هنا `*Response` كما في exam.ts: مسارات users.* / roles.* تُرجع
 * إعادة توجيه Inertia (RedirectResponse + flash)، لا JsonResource — أي لا
 * يوجد wire contract بصيغة JSON. لذلك نُعرِّف نموذج صف الصفحة (*PageModel)
 * كما تُسلسِله Eloquent فعلياً، وحمولة النموذج (*Payload) كما تُرسَل.
 */

import type { PaginatedResponse } from '@/Types';

/** يطابق App\Enums\UserStatus. */
export type UserStatusValue = 'active' | 'inactive' | 'suspended';

/** يطابق App\Queries\UserDirectoryQuery::ROLES (الأدوار القابلة للإسناد). */
export type AssignableRoleValue = 'org-admin' | 'supervisor' | 'teacher' | 'student' | 'parent';

export type UserRoleRef = {
    id: number;
    name: string;
};

/** ما تُسلسِله Eloquent داخل users.data (UserDirectoryQuery::with('roles')). */
export type UserPageModel = {
    id: number;
    organization_id: number | null;
    name: string;
    email: string;
    phone: string | null;
    status: UserStatusValue;
    created_at: string | null;
    roles: UserRoleRef[];
};

export type UserIndexPageProps = {
    users: PaginatedResponse<UserPageModel>;
    filters: { search?: string; role?: string };
    availableRoles: AssignableRoleValue[];
};

/**
 * حقول نموذج الإنشاء/التعديل — نفس مفاتيح Store/UpdateUserRequest.
 * role/status نصّان لأن FormSelect يُصدر string؛ القيم المسموح بها تأتي من
 * availableRoles و UserStatusValue.
 */
export type UserFormPayload = {
    name: string;
    email: string;
    password: string;
    phone: string;
    role: string;
    status: string;
};

/** أخطاء التحقق القادمة من FormRequest (مفتاح الحقل => أول رسالة). */
export type { FormErrors } from '@/Types/api/common';

/** يطابق ما يُسلسِله RoleController::page داخل roles. */
export type RoleMatrixRow = {
    id: number;
    name: string;
    editable: boolean;
    permissions: string[];
};

export type RoleIndexPageProps = {
    roles: RoleMatrixRow[];
    permissions: string[];
};

export type PermissionOption = {
    name: string;
    label: string;
};

export type PermissionGroup = {
    key: string;
    label: string;
    permissions: PermissionOption[];
};
