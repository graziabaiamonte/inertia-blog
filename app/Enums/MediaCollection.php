<?php

namespace App\Enums;

enum MediaCollection: string
{
    case Featured = 'featured';
    case Content = 'content';

    /**
     * Human-readable label for the UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::Featured => 'Featured image',
            self::Content => 'Content images',
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
