<?php

namespace App\Enums;

enum PaymentRequestStatus: string
{
    case Pending = 'pending';
    case Successful = 'successful';
    case Failed = 'failed';
    case Abandoned = 'abandoned';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Successful => 'Successful',
            self::Failed => 'Failed',
            self::Abandoned => 'Abandoned',
        };
    }

    public function canInitialize(): bool
    {
        return $this === self::Pending;
    }

    public function isSuccessful(): bool
    {
        return $this === self::Successful;
    }
}
