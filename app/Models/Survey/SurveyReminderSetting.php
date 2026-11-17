<?php

namespace App\Models\Survey;

use App\Models\UserManagement\User;
use Database\Factories\Survey\SurveyReminderSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The switch of the automatic deadline reminders (Survey Management >
 * Reminders), a single row: they are on until someone turns them off. The
 * reminders sent by hand do not depend on it.
 */
#[Fillable(['automatic', 'updated_by'])]
class SurveyReminderSetting extends Model
{
    /** @use HasFactory<SurveyReminderSettingFactory> */
    use HasFactory;

    /**
     * The default of the table, for the setting that was never saved.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'automatic' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'automatic' => 'boolean',
            'updated_by' => 'integer',
        ];
    }

    /**
     * The stored setting, or the default (on) when it was never changed.
     */
    public static function current(): self
    {
        return static::query()->orderBy('id')->first() ?? new static;
    }

    /**
     * Whether the daily survey:send-deadline-reminders sends the reminders.
     */
    public static function automaticRemindersOn(): bool
    {
        return static::current()->automatic;
    }

    /**
     * Who changed it last.
     *
     * @return BelongsTo<User, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by')->withTrashed();
    }
}
