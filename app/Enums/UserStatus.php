<?php

namespace App\Enums;

/**
 * حالة المستخدم.
 */
enum UserStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Suspended = 'suspended';
}
