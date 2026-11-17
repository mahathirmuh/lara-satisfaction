<?php

namespace App\Models\Log;

use App\Models\UserManagement\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use LogicException;

/**
 * One entry of the audit trail (menu "Audit Log"), written by App\Services\Audit\AuditLogger.
 *
 * Written once and never touched again: there is no updated_at, and changing or deleting an entry is refused. A trail
 * that can be edited from the application is no trail.
 */
#[Table('audit_logs')]
#[Fillable([
    'user_id', 'user_email', 'action', 'entity_type', 'entity_id', 'entity_label',
    'old_values', 'new_values', 'ip_address', 'user_agent', 'via',
])]
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    /**
     * Whether the table is there is asked at most once a minute: the code may be live before its migration runs.
     */
    public const READY_CACHE_KEY = 'audit-logs-table';

    /**
     * What the page calls the records of a kind (the entity type as it is stored => its name, translated).
     *
     * @var array<string, string>
     */
    public const ENTITY_NAMES = [
        'User' => 'Account',
        'Role' => 'Role',
        'Survey' => 'Survey',
        'SurveyQuestion' => 'Question',
        'SurveyManualReminder' => 'Reminder',
        'SurveyReminderSetting' => 'Automatic Reminders',
        'Department' => 'Department',
        'DepartmentSection' => 'Section',
        'LandingContent' => 'Landing Page',
    ];

    /**
     * Bootstrap the model and its traits.
     */
    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('An audit entry is not changed.'));
        static::deleting(fn () => throw new LogicException('An audit entry is not deleted.'));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'entity_id' => 'integer',
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Whether the table of the trail exists (its migration has run). Until then nothing is written and the page says so.
     */
    public static function ready(): bool
    {
        return (bool) Cache::remember(self::READY_CACHE_KEY, 60, fn (): bool => Schema::hasTable('audit_logs'));
    }

    /**
     * Who did it. Users are soft-deleted: the entry keeps the name of who did it after the account is removed.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * Who did it: the account, else the address it had; nobody signed in is a guest (a failed sign-in), or the system.
     */
    public function actorName(): string
    {
        return $this->user?->name
            ?? $this->user_email
            ?? __($this->user_agent === AuditLogger::CONSOLE ? 'System' : 'Guest');
    }

    /**
     * "Survey #12" in the language of the page, or null for an action that is about no record.
     */
    public function entityName(): ?string
    {
        return $this->entity_type === null ? null : self::entityTypeName($this->entity_type).' #'.$this->entity_id;
    }

    /**
     * The name of a kind of record in the language of the page; a kind without a name here as it is stored.
     */
    public static function entityTypeName(string $type): string
    {
        return isset(self::ENTITY_NAMES[$type]) ? __(self::ENTITY_NAMES[$type]) : $type;
    }

    /**
     * A value as one line of text: a list is summed up rather than dumped, a yes or a no in the words of the page.
     */
    public static function shown(mixed $value): string
    {
        return match (true) {
            $value === null || $value === '' => '-',
            is_bool($value) => $value ? __('yes') : __('no'),
            is_scalar($value) => (string) $value,
            default => '…',
        };
    }
}
