<?php

namespace App\Models\Survey;

use App\Models\UserManagement\User;
use Database\Factories\Survey\SurveyStartFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A person opened a survey's form (first time): the survey is In Progress for
 * them until they complete it. Holds no answers.
 */
#[Fillable(['survey_id', 'user_id', 'started_at'])]
#[WithoutTimestamps]
class SurveyStart extends Model
{
    /** @use HasFactory<SurveyStartFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'survey_id' => 'integer',
            'user_id' => 'integer',
            'started_at' => 'datetime',
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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
