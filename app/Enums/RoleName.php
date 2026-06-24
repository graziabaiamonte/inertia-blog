<?php

namespace App\Enums;

enum RoleName: string
{
    case Admin = 'admin';
    case Author = 'author';

    /**
     * Human-readable label for the UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Author => 'Author',
        };
    }

    /**
     * All backing values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
