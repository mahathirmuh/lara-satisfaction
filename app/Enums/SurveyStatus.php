<?php

namespace App\Enums;

enum SurveyStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Closed = 'closed';

    /**
     * Human readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Active => 'Active',
            self::Closed => 'Closed',
        };
    }

    /**
     * Merdeka palette colour used by badges.
     */
    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Active => 'green',
            self::Closed => 'red',
        };
    }

    /**
     * Options for select inputs.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status): array => [$status->value => $status->label()])
            ->all();
    }
}
