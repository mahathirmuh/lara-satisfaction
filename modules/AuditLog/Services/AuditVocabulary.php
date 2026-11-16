<?php

namespace Modules\AuditLog\Services;

use App\Services\Audit\AuditLogger;
use Illuminate\Support\Str;

/**
 * The words the page "Audit Log" describes an entry with, in the language of the page: the part of the application an
 * action belongs to, its name in the filter, the words that go between the actor and the record ("Budi updated survey
 * Operational Audit 2026") and the badge it gets. What was written (the action key, the field names and values, the
 * label) is shown as it is.
 */
final class AuditVocabulary
{
    /**
     * The parts of the application, by the first part of an action key, in the order of the filter.
     *
     * @var array<string, string>
     */
    public const AREAS = [
        'auth' => 'Sign-in',
        'user' => 'Accounts',
        'role' => 'Roles',
        'survey' => 'Surveys',
        'question' => 'Questions',
        'reminder' => 'Reminders',
        'master_data' => 'Master Data',
        'other' => 'Other',
    ];

    /**
     * Each action by a name of its own (its key is the value of the filter), in the order of the filter.
     *
     * @var array<string, string>
     */
    public const NAMES = [
        AuditLogger::LOGIN => 'Signed in',
        AuditLogger::LOGIN_FAILED => 'Sign-in failed',
        AuditLogger::LOGOUT => 'Signed out',
        AuditLogger::USER_CREATED => 'Account created',
        AuditLogger::USER_UPDATED => 'Account updated',
        AuditLogger::USER_DELETED => 'Account deleted',
        AuditLogger::USERS_SYNCED => 'Employees synced from the directory',
        AuditLogger::ROLE_CREATED => 'Role created',
        AuditLogger::ROLE_UPDATED => 'Role updated',
        AuditLogger::ROLE_DELETED => 'Role deleted',
        AuditLogger::SURVEY_CREATED => 'Survey created',
        AuditLogger::SURVEY_UPDATED => 'Survey updated',
        AuditLogger::SURVEY_STATUS_CHANGED => 'Survey status changed',
        AuditLogger::SURVEY_DUPLICATED => 'Survey duplicated',
        AuditLogger::SURVEY_DELETED => 'Survey deleted',
        AuditLogger::QUESTION_CREATED => 'Question created',
        AuditLogger::QUESTION_UPDATED => 'Question updated',
        AuditLogger::QUESTION_DELETED => 'Question deleted',
        AuditLogger::REMINDER_SENT => 'Reminder sent',
        AuditLogger::MASTER_CREATED => 'Master data added',
        AuditLogger::MASTER_UPDATED => 'Master data updated',
        AuditLogger::MASTER_DELETED => 'Master data deleted',
    ];

    /**
     * What was done, as words that go between the actor and the record.
     *
     * @var array<string, string>
     */
    public const ACTIVITIES = [
        AuditLogger::LOGIN => 'signed in',
        AuditLogger::LOGIN_FAILED => 'failed to sign in',
        AuditLogger::LOGOUT => 'signed out',
        AuditLogger::USER_CREATED => 'created account',
        AuditLogger::USER_UPDATED => 'updated account',
        AuditLogger::USER_DELETED => 'deleted account',
        AuditLogger::USERS_SYNCED => 'synced the employees from the directory',
        AuditLogger::ROLE_CREATED => 'created role',
        AuditLogger::ROLE_UPDATED => 'updated role',
        AuditLogger::ROLE_DELETED => 'deleted role',
        AuditLogger::SURVEY_CREATED => 'created survey',
        AuditLogger::SURVEY_UPDATED => 'updated survey',
        AuditLogger::SURVEY_STATUS_CHANGED => 'changed the status of survey',
        AuditLogger::SURVEY_DUPLICATED => 'duplicated survey',
        AuditLogger::SURVEY_DELETED => 'deleted survey',
        AuditLogger::QUESTION_CREATED => 'created question',
        AuditLogger::QUESTION_UPDATED => 'updated question',
        AuditLogger::QUESTION_DELETED => 'deleted question',
        AuditLogger::REMINDER_SENT => 'sent a reminder for',
        AuditLogger::MASTER_CREATED => 'added',
        AuditLogger::MASTER_UPDATED => 'updated',
        AuditLogger::MASTER_DELETED => 'deleted',
    ];

    /**
     * An action for the eye: [colour, icon, word]. Any other action that ends in "created", "updated" or "deleted" gets
     * the badge of its kind (badge()).
     *
     * @var array<string, array{0: string, 1: string, 2: string}>
     */
    public const BADGES = [
        AuditLogger::LOGIN => ['blue', 'login', 'Signed in'],
        AuditLogger::LOGIN_FAILED => ['red', 'lock', 'Sign-in failed'],
        AuditLogger::LOGOUT => ['gray', 'logout', 'Signed out'],
        AuditLogger::USERS_SYNCED => ['purple', 'refresh', 'Synced'],
        AuditLogger::SURVEY_STATUS_CHANGED => ['orange', 'toggle-right', 'Status changed'],
        AuditLogger::SURVEY_DUPLICATED => ['blue', 'copy', 'Duplicated'],
        AuditLogger::REMINDER_SENT => ['blue', 'send', 'Sent'],
    ];

    /**
     * The actions for the filter, each by its name (one without a name by its key), under the part of the application
     * it belongs to ("Other" for a part without a name). The parts and the actions in each come in the order of the
     * names above, actions without a name last.
     *
     * @param  array<int, string>  $actions
     * @return array<string, array<string, string>> name of the part => [action => name]
     */
    public static function actionGroups(array $actions): array
    {
        $rank = array_flip(array_keys(self::NAMES));
        $parts = [];

        foreach ($actions as $action) {
            $area = Str::before($action, '.');
            $parts[isset(self::AREAS[$area]) ? $area : 'other'][$action] = isset(self::NAMES[$action]) ? __(self::NAMES[$action]) : $action;
        }

        $groups = [];

        foreach (array_keys(self::AREAS) as $area) {
            if (isset($parts[$area])) {
                uksort($parts[$area], fn (string $a, string $b): int => [$rank[$a] ?? PHP_INT_MAX, $a] <=> [$rank[$b] ?? PHP_INT_MAX, $b]);
                $groups[__(self::AREAS[$area])] = $parts[$area];
            }
        }

        return $groups;
    }

    /**
     * What was done in words; an action without words here shows its key, so a new one is not quietly read as something
     * else.
     */
    public static function activity(string $action): string
    {
        return isset(self::ACTIVITIES[$action]) ? __(self::ACTIVITIES[$action]) : $action;
    }

    /**
     * The badge of an action: its colour, icon and word in the language of the page.
     *
     * @return array{tone: string, icon: string, label: string}
     */
    public static function badge(string $action): array
    {
        [$tone, $icon, $label] = self::BADGES[$action] ?? match (true) {
            str_ends_with($action, 'created') => ['green', 'plus', 'Created'],
            str_ends_with($action, 'updated') => ['orange', 'edit', 'Updated'],
            str_ends_with($action, 'deleted') => ['red', 'trash', 'Deleted'],
            default => ['gray', 'activity', $action],
        };

        return ['tone' => $tone, 'icon' => $icon, 'label' => $label === $action ? $action : __($label)];
    }
}
