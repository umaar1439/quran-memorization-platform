<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * حالة المنظمة (المستأجر).
 */
enum OrganizationStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Trial = 'trial';
}
