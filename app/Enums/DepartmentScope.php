<?php

namespace App\Enums;

/**
 * Which departments' survey results a role sees (the `department_scope`
 * column).
 */
enum DepartmentScope: string
{
    case All = 'ALL';
    case Managed = 'MANAGED';

    /**
     * Human readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::All => 'All departments',
            self::Managed => 'Departments managed by the job title',
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
