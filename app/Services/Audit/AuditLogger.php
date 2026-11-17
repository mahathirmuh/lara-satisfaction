<?php

namespace App\Services\Audit;

use App\Models\Log\AuditLog;
use App\Models\UserManagement\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Throwable;

/**
 * Writes the audit trail (menu "Audit Log", as in MTI Radio Management): sign-ins, failed sign-ins and sign-outs, and
 * every change made in the administration of the application, after it has been made, with the values before and
 * after. Survey answers and drafts are never recorded: they stay anonymous.
 *
 * Every value passes through redact(): a password or a token never reaches the table, which is read by people other
 * than the ones it is about. Writing an entry never stops the action it records: a failure is reported and the action
 * goes on. Before the migration of the table has run, nothing is written.
 */
class AuditLogger
{
    /* Actions. Kept as keys: the page filters on them, and they are what is searched for. */

    public const LOGIN = 'auth.login';

    public const LOGIN_FAILED = 'auth.login_failed';

    public const LOGOUT = 'auth.logout';

    public const USER_CREATED = 'user.created';

    public const USER_UPDATED = 'user.updated';

    public const USER_DELETED = 'user.deleted';

    /**
     * The employees of the assessed departments taken from Active Directory (users:sync-directory, by hand or daily).
     */
    public const USERS_SYNCED = 'user.directory_synced';

    public const ROLE_CREATED = 'role.created';

    public const ROLE_UPDATED = 'role.updated';

    public const ROLE_DELETED = 'role.deleted';

    public const SURVEY_CREATED = 'survey.created';

    public const SURVEY_UPDATED = 'survey.updated';

    /**
     * Published, closed or moved back to draft.
     */
    public const SURVEY_STATUS_CHANGED = 'survey.status_changed';

    public const SURVEY_DUPLICATED = 'survey.duplicated';

    public const SURVEY_DELETED = 'survey.deleted';

    public const QUESTION_CREATED = 'question.created';

    public const QUESTION_UPDATED = 'question.updated';

    public const QUESTION_DELETED = 'question.deleted';

    /**
     * Survey reminders sent by hand (Survey Management > Reminders).
     */
    public const REMINDER_SENT = 'reminder.sent';

    /**
     * The automatic deadline reminders turned on or off (Survey Management > Reminders).
     */
    public const AUTOMATIC_REMINDERS_ON = 'reminder.automatic_on';

    public const AUTOMATIC_REMINDERS_OFF = 'reminder.automatic_off';

    /**
     * Departments, sections and the landing page (Master Data): the entry names which.
     */
    public const MASTER_CREATED = 'master_data.created';

    public const MASTER_UPDATED = 'master_data.updated';

    public const MASTER_DELETED = 'master_data.deleted';

    /**
     * The user agent of an entry written by an artisan command.
     */
    public const CONSOLE = 'console';

    /**
     * How a sign-in came in with the network account (the SSO ones are "<Provider> SSO").
     */
    public const VIA_LDAP = 'LDAP';

    /**
     * What is written instead of a sensitive value.
     */
    public const REDACTED = '[redacted]';

    /**
     * Names of fields whose value is replaced by REDACTED: matched as parts of the name, in any case, so "Password",
     * "remember_token" and "client_secret" are all caught.
     */
    private const REDACTED_KEYS = [
        'password', 'secret', 'token', 'credential', 'certificate', 'api_key', 'apikey', 'private_key', 'authorization',
    ];

    /**
     * A value longer than this is cut: the trail says what changed, it is no copy of the data.
     */
    private const MAX_VALUE = 500;

    /**
     * Records an action. $old and $new name fields the way the page that made the change does ("Status", "Title").
     *
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     * @param  User|null  $user  who did it; by default whoever is signed in
     * @param  string|null  $label  what the record is, in words that stay readable after it is gone
     * @param  string|null  $via  how it came in, e.g. the way a person signed in ("Microsoft SSO")
     */
    public function log(
        string $action,
        ?Model $entity = null,
        ?array $old = null,
        ?array $new = null,
        ?User $user = null,
        ?string $label = null,
        ?string $via = null,
    ): ?AuditLog {
        try {
            if (! AuditLog::ready()) {
                return null;
            }

            // A record as it was made, or as it was when it went: only the fields it had. (Next to a value on the other
            // side, an empty one does say something: "Status: Active → -".)
            $filled = fn (array $values): array => array_filter($values, fn ($value): bool => $value !== null && $value !== '' && $value !== []);

            if (! $old && $new) {
                $new = $filled($new);
            } elseif ($old && ! $new) {
                $old = $filled($old);
            }

            $actor = $user ?? Auth::user();
            $console = $this->console();

            return AuditLog::query()->create([
                'user_id' => $actor?->getKey(),
                'user_email' => $actor === null ? null : mb_substr((string) $actor->email, 0, 255),
                'action' => $action,
                'entity_type' => $entity === null ? null : class_basename($entity),
                'entity_id' => $entity?->getKey(),
                'entity_label' => blank($label) ? null : mb_substr((string) $label, 0, 255),
                'old_values' => $old ? $this->redact($old) : null,
                'new_values' => $new ? $this->redact($new) : null,
                'ip_address' => $console ? null : Request::ip(),
                'user_agent' => $console ? self::CONSOLE : mb_substr((string) Request::userAgent(), 0, 512),
                'via' => blank($via) ? null : mb_substr((string) $via, 0, 150),
            ]);
        } catch (Throwable $exception) {
            // The action itself has been done: an entry that could not be written must not undo it, or answer an error.
            report($exception);

            return null;
        }
    }

    /**
     * Records a change: only the fields whose value moved between $before and $after. Nothing is written when none did
     * (a form saved as it was).
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public function logChanges(string $action, ?Model $entity, array $before, array $after, ?string $label = null): ?AuditLog
    {
        [$old, $new] = self::diff($before, $after);

        return $new === [] && $old === [] ? null : $this->log($action, $entity, $old, $new, label: $label);
    }

    /**
     * The fields of $after whose value differs from $before, as [old values, new values]. A field that is only in
     * $before is taken to be empty now.
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    public static function diff(array $before, array $after): array
    {
        $old = [];
        $new = [];

        foreach ($after + array_fill_keys(array_keys(array_diff_key($before, $after)), null) as $field => $value) {
            $was = $before[$field] ?? null;

            if (AuditLog::shown($was) === AuditLog::shown($value)) {
                continue;
            }

            $old[$field] = $was;
            $new[$field] = $value;
        }

        return [$old, $new];
    }

    /**
     * Replaces the values of sensitive fields, walking nested lists, and cuts long ones.
     *
     * @param  array<string|int, mixed>  $values
     * @return array<string|int, mixed>
     */
    public function redact(array $values): array
    {
        $kept = [];

        foreach ($values as $key => $value) {
            $kept[$key] = match (true) {
                $this->sensitive((string) $key) => $value === null ? null : self::REDACTED,
                is_array($value) => $this->redact($value),
                is_string($value) && mb_strlen($value) > self::MAX_VALUE => mb_substr($value, 0, self::MAX_VALUE).'…',
                is_scalar($value) || $value === null => $value,
                default => (string) json_encode($value),
            };
        }

        return $kept;
    }

    /**
     * Whether a field holds a secret.
     */
    private function sensitive(string $key): bool
    {
        $key = mb_strtolower($key);

        foreach (self::REDACTED_KEYS as $part) {
            if (str_contains($key, $part)) {
                return true;
            }
        }

        return false;
    }

    /**
     * An artisan command: there is no visitor, and no address. (The tests run in the console too, with requests.)
     */
    private function console(): bool
    {
        return app()->runningInConsole() && ! app()->runningUnitTests();
    }
}
