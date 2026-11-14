<?php

namespace App\Enums;

/**
 * Data scope of a role (the `func_role` column).
 */
enum RoleScope: string
{
    case All = 'ALL';
    case Company = 'COMPANY';

    /**
     * Human readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::All => 'ALL - All companies',
            self::Company => 'COMPANY - Selected companies',
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
            ->mapWithKeys(fn (self $scope): array => [$scope->value => $scope->label()])
            ->all();
    }
}
