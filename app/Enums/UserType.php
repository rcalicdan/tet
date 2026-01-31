<?php

namespace App\Enums;

enum UserType: string
{
    case CLIENT = 'client';
    case CONTRACTOR = 'contractor';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match($this) {
            self::CLIENT => 'Klient',
            self::CONTRACTOR => 'Wykonawca',
        };
    }

    public function isClient(): bool
    {
        return $this === self::CLIENT;
    }

    public function isContractor(): bool
    {
        return $this === self::CONTRACTOR;
    }
}