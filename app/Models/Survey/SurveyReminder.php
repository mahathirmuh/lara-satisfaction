<?php

namespace App\Models\Survey;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A deadline reminder that was sent for a survey (one per reminder day).
 */
#[Fillable(['survey_id', 'days_before', 'recipients', 'sent_at'])]
#[WithoutTimestamps]
class SurveyReminder extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'survey_id' => 'integer',
            'days_before' => 'integer',
            'recipients' => 'integer',
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
}
