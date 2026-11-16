<?php

namespace App\Enums;

enum SurveyStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Closed = 'closed';

    /**
     * Human readable label, in the interface language.
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => __('Draft'),
            self::Active => __('Active'),
            self::Closed => __('Closed'),
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
