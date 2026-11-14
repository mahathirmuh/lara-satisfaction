<?php

namespace App\Enums;

/**
 * CRUD permissions; the values match the ids seeded in the `permissions` table.
 */
enum PermissionAction: int
{
    case Create = 1;
    case Read = 2;
    case Update = 3;
    case Delete = 4;

    /**
     * Resolve the permission a mutating HTTP verb requires.
     */
    public static function forHttpMethod(string $method): ?self
    {
        return match (strtoupper($method)) {
            'GET', 'HEAD' => self::Read,
            'POST' => self::Create,
            'PUT', 'PATCH' => self::Update,
            'DELETE' => self::Delete,
            default => null,
        };
    }

    /**
     * Resolve a permission from its name (Create, Read, Update, Delete).
     */
    public static function fromName(string $name): ?self
    {
        foreach (self::cases() as $case) {
            if (strcasecmp($case->name, $name) === 0) {
                return $case;
            }
        }

        return null;
    }
}
