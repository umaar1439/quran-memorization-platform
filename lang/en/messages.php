<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| User-facing messages (English)
|--------------------------------------------------------------------------
| Mirror of lang/ar/messages.php. Keep keys in sync; values may differ.
*/

return [
    'common' => [
        'unauthorized' => 'You are not allowed to perform this action.',
        'unexpected_error' => 'An unexpected error occurred, please try again later.',
    ],

    'teachers' => [
        'created' => 'Teacher added successfully.',
        'updated' => 'Teacher updated successfully.',
        'deleted' => 'Teacher deleted successfully.',
        'missing_account' => 'No account is linked to this teacher.',
        'cross_organization_link' => 'You cannot link an account from another organization.',
    ],

    'students' => [
        'created' => 'Student added successfully.',
        'updated' => 'Student updated successfully.',
        'deleted' => 'Student deleted successfully.',
        'missing_profile' => 'No student profile is linked to this user.',
        'missing_guardian_profile' => 'No guardian profile is linked to this user.',
    ],

    'users' => [
        'created' => 'User added successfully.',
        'updated' => 'User updated successfully.',
        'deleted' => 'User deleted successfully.',
    ],

    'mosques' => [
        'created' => 'Mosque added successfully.',
        'updated' => 'Mosque updated successfully.',
        'deleted' => 'Mosque deleted successfully.',
    ],

    'circles' => [
        'created' => 'Circle added successfully.',
        'updated' => 'Circle updated successfully.',
        'deleted' => 'Circle deleted successfully.',
    ],

    'settings' => [
        'saved' => 'Settings saved successfully.',
    ],

    'feature_flags' => [
        'updated' => 'Feature flag updated successfully.',
    ],

    'features' => [
        'disabled' => 'This feature is not enabled for your organization.',
    ],

    'goals' => [
        'completed' => 'Goal completed successfully.',
        'scope_required' => 'A goal scope is required: a surah or a juz.',
    ],

    'reports' => [
        'expired' => 'This report link has expired.',
        'file_missing' => 'The report file does not exist.',
        'forbidden' => 'You are not allowed to generate reports.',
        'organization_required' => 'The user must belong to an organization to generate a report.',
    ],

    'certificates' => [
        'queued' => 'Your certificate is being generated in the background and you will be notified once ready.',
        'forbidden_role' => 'Issuing certificates is restricted to organization admins.',
        'forbidden_permission' => 'You are not allowed to issue certificates.',
        'feature_disabled' => 'The certificates feature is not enabled for your organization.',
        'student_other_org' => 'The student does not belong to your organization.',
    ],

    'recitations' => [
        'unauthenticated' => 'The teacher is not authenticated.',
        'forbidden' => 'You are not allowed to record recitations.',
        'student_missing' => 'The student does not exist.',
        'student_other_org' => 'The student does not belong to your organization.',
        'not_your_circle' => 'This student is not in a circle you teach.',
        'idempotency_required' => 'The idempotency key is required.',
    ],

    'transfers' => [
        'student_missing' => 'The student does not exist.',
        'student_other_org' => 'The student does not belong to your organization.',
        'mosque_other_org' => 'The target mosque does not belong to the student organization.',
        'circle_mismatch' => 'The target circle does not belong to the same organization/mosque.',
    ],

    'exam_results' => [
        'only_approved' => 'Corrections are only allowed on approved results.',
        'approve_permission' => 'Correcting a result requires the exams.approve permission.',
        'section_mismatch' => 'The selected section does not belong to this exam.',
    ],

    'quran' => [
        'invalid_range' => 'The ayah range is invalid.',
        'range_not_positive' => 'The ayah range must be positive numbers.',
        'range_order' => 'The range start (ayah_from) must be less than or equal to its end (ayah_to).',
        'surah_required' => 'The surah number is required to validate the ayah range.',
        'surah_missing' => 'The selected surah number does not exist in the Quran reference.',
        'surah_between' => 'The surah number must be between 1 and 114.',
    ],

    'notifications' => [
        'goal_completed' => [
            'title' => 'Goal completed',
        ],
        'exam_result' => [
            'title' => 'Exam result approved',
            'body' => 'Exam result approved: :grade.',
        ],
        'achievement' => [
            'title' => 'New achievement',
        ],
        'certificate_issued' => [
            'title' => 'Your certificate has been issued',
            'body' => 'A certificate has been issued: :type.',
            'verification_code' => 'Verification code: :code.',
        ],
        'missed_attendance' => [
            'title' => 'Attendance alert',
            'body' => 'The student was marked absent on :date.',
        ],
    ],
];
