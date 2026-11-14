<?php

namespace App\Models\Survey;

use App\Models\Concerns\HasRandomUuids;
use App\Models\Organization\Department;
use App\Models\Organization\DepartmentSection;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Single anonymous answer (rating or comment) of a response.
 */
#[WithoutTimestamps]
#[Fillable(['survey_response_id', 'survey_question_id', 'department_id', 'department_section_id', 'rating', 'comment'])]
class SurveyAnswer extends Model
{
    use HasRandomUuids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'survey_question_id' => 'integer',
            'department_id' => 'integer',
            'department_section_id' => 'integer',
            'rating' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<SurveyResponse, $this>
     */
    public function response(): BelongsTo
    {
        return $this->belongsTo(SurveyResponse::class, 'survey_response_id');
    }

    /**
     * @return BelongsTo<SurveyQuestion, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(SurveyQuestion::class, 'survey_question_id');
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return BelongsTo<DepartmentSection, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(DepartmentSection::class, 'department_section_id');
    }
}
