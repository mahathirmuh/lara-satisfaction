<?php

namespace App\Models\Survey;

use App\Models\UserManagement\User;
use Database\Factories\Survey\SurveyManualReminderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A reminder sent by hand (Survey Management > Reminders) to everyone who has
 * not completed a survey, or to chosen people.
 */
#[Fillable(['survey_id', 'sent_by', 'audience', 'recipients', 'skipped', 'sent_at'])]
#[WithoutTimestamps]
class SurveyManualReminder extends Model
{
    /** @use HasFactory<SurveyManualReminderFactory> */
    use HasFactory;

    /**
     * Everyone who may fill in surveys and has not completed this one.
     */
    public const AUDIENCE_ALL = 'all';

    /**
     * People chosen one by one.
     */
    public const AUDIENCE_CUSTOM = 'custom';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'survey_id' => 'integer',
            'sent_by' => 'integer',
            'recipients' => 'integer',
            'skipped' => 'integer',
            'sent_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Survey, $this>
     */
    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by')->withTrashed();
    }
}
