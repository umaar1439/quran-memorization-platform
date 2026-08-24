export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string | null;
    phone?: string | null;
    locale?: string | null;
    status?: string;
    organization_id?: number | null;
}

export interface Organization {
    id: number;
    name: string;
    slug: string;
    status: string;
}

export interface Mosque {
    id: number;
    organization_id: number;
    name: string;
    address?: string | null;
    city?: string | null;
    phone?: string | null;
    status: string;
    circles_count?: number;
}

export interface Circle {
    id: number;
    organization_id: number;
    mosque_id: number;
    teacher_id?: number | null;
    name: string;
    level?: string | null;
    capacity?: number | null;
    status: string;
    mosque?: Mosque;
    teacher?: User | null;
    student_profiles_count?: number;
    studentProfiles?: StudentProfile[];
}

export interface StudentProfile {
    id: number;
    user_id: number;
    organization_id: number;
    mosque_id?: number | null;
    circle_id?: number | null;
    enrollment_number?: string | null;
    birth_date?: string | null;
    current_juz?: number | null;
    status: string;
    user?: User;
    mosque?: Mosque;
    circle?: Circle;
}

export interface TeacherProfile {
    id: number;
    user_id: number;
    organization_id: number;
    specialization?: string | null;
    ijazah_details?: string | null;
    hired_at?: string | null;
    user?: User;
}

export interface Guardian {
    id: number;
    organization_id: number;
    user_id?: number | null;
    name: string;
    phone?: string | null;
    email?: string | null;
    relationship_type?: string | null;
    students?: StudentProfile[];
}

export interface Surah {
    id: number;
    number: number;
    name_ar: string;
    name_en?: string;
    ayah_count: number;
    revelation_type?: string;
}

export interface RecitationError {
    id: number;
    recitation_id: number;
    error_type: string;
    count: number;
    notes?: string | null;
}

export interface Recitation {
    id: number;
    organization_id: number;
    student_id: number;
    teacher_id: number;
    mosque_id?: number | null;
    circle_id?: number | null;
    type: string;
    surah_number: number;
    ayah_from: number;
    ayah_to: number;
    rating?: string | null;
    notes?: string | null;
    performed_at: string;
    idempotency_key: string;
    errors?: RecitationError[];
    student?: StudentProfile;
    teacher?: User;
}

export interface StudentGoal {
    id: number;
    organization_id: number;
    student_id: number;
    period_type: string;
    target_type: string;
    surah_id?: number | null;
    juz_number?: number | null;
    target_amount: number;
    achieved_amount: number;
    completion_percentage: number;
    period_start: string;
    period_end: string;
    status: string;
    surah?: Surah;
}

export interface ExamSection {
    id: number;
    exam_id: number;
    surah_number?: number | null;
    ayah_from?: number | null;
    ayah_to?: number | null;
    section_type: string;
    max_score: number;
}

export interface Exam {
    id: number;
    organization_id: number;
    mosque_id?: number | null;
    circle_id?: number | null;
    title: string;
    exam_type: string;
    scheduled_at?: string | null;
    status: string;
    created_by?: number;
    sections?: ExamSection[];
    results?: ExamResult[];
    mosque?: Mosque;
    circle?: Circle;
}

export interface ExamResult {
    id: number;
    exam_id: number;
    exam_section_id: number;
    student_id: number;
    organization_id: number;
    score: number;
    grade?: string | null;
    examiner_id: number;
    status: string;
    approved_by?: number | null;
    approved_at?: string | null;
    exam?: Exam;
    student?: StudentProfile;
}

export interface Certificate {
    id: string;
    organization_id: number;
    student_id: number;
    exam_id?: number | null;
    certificate_type: string;
    issued_at: string;
    issued_by: number;
    verification_code: string;
    status: string;
    student?: StudentProfile;
}

export interface Report {
    id: number;
    organization_id: number;
    requested_by: number;
    report_type: string;
    parameters?: Record<string, unknown>;
    status: string;
    expires_at?: string | null;
    created_at: string;
}

export interface AuditLog {
    id: number;
    organization_id: number;
    user_id?: number | null;
    event: string;
    auditable_type?: string | null;
    auditable_id?: number | null;
    old_values?: Record<string, unknown> | null;
    new_values?: Record<string, unknown> | null;
    ip_address?: string | null;
    created_at: string;
    user?: User;
}

export interface Setting {
    id: number;
    organization_id: number | null;
    key: string;
    value: string | null;
    group?: string | null;
}

export interface FeatureFlag {
    id: number;
    organization_id: number | null;
    key: string;
    enabled: boolean;
    updated_by?: number | null;
}

export interface StudentTransfer {
    id: number;
    organization_id: number;
    student_id: number;
    from_mosque_id?: number | null;
    to_mosque_id: number;
    from_circle_id?: number | null;
    to_circle_id?: number | null;
    transferred_by_user_id: number;
    reason?: string | null;
    transferred_at: string;
    student?: StudentProfile;
    from_mosque?: Mosque | null;
    to_mosque?: Mosque;
}

export interface TeacherAttendance {
    id: number;
    organization_id: number;
    teacher_id: number;
    mosque_id?: number | null;
    date: string;
    first_activity_at?: string | null;
    last_activity_at?: string | null;
    actions_count: number;
    teacher?: User;
}

export interface PaginatedResponse<T> {
    data: T[];
    total: number;
    current_page: number;
    last_page: number;
    per_page: number;
}

export interface FlashMessages {
    success?: string | null;
    error?: string | null;
}

export interface StudentAnalytics {
    overall_progress: {
        memorized_ayahs: number;
        total_ayahs: number;
        percentage: number;
    };
    progress_by_juz: {
        juz_number: number;
        recitations_count: number;
        ayahs_covered: number;
    }[];
    progress_by_surah: {
        surah_number: number;
        surah_name: string | null;
        ayahs_covered: number;
        total_ayahs: number;
        percentage: number;
    }[];
    attendance_rate: {
        attendance_days: number;
        study_days: number;
        percentage: number;
    };
    streak: number;
    strengths: {
        surah_number: number;
        excellent_ratio: number;
        error_rate: number;
        samples: number;
    }[];
    weaknesses: {
        surah_number: number;
        needs_work_ratio: number;
        error_rate: number;
        samples: number;
    }[];
    error_distribution: Record<string, { count: number; percentage: number }>;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
        roles: string[];
        permissions: string[];
    };
    settings: Record<string, string | null>;
    feature_flags: Record<string, boolean>;
    flash: FlashMessages;
};
