<?php

namespace App\Enums;

enum UserRole: string
{
    case Student = 'student';
    case AlumniAdmin = 'alumni_admin';
    case SuperAdmin = 'super_admin';
    case Cashier = 'cashier';

    public function label(): string
    {
        return match ($this) {
            self::Student => 'Student',
            self::AlumniAdmin => 'Alumni Admin',
            self::SuperAdmin => 'Super Admin',
            self::Cashier => 'Cashier',
        };
    }
}
