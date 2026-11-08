<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * حالة الشهادة.
 */
enum CertificateStatus: string
{
    case Pending = 'pending';
    case Issued = 'issued';
    case Failed = 'failed';
    case Revoked = 'revoked';
}
