<?php

namespace Modules\SurveyReport\Support;

use App\Models\Survey\Survey;

/**
 * The slice of a survey's results a report covers: the survey and the
 * respondent companies (null means every company).
 */
final class ReportScope
{
    /**
     * Create a new report scope.
     *
     * @param  array<int, int>|null  $companyIds
     */
    public function __construct(
        public readonly Survey $survey,
        public readonly ?array $companyIds = null,
    ) {}

    /**
     * Whether the report is limited to specific companies.
     */
    public function isRestricted(): bool
    {
        return $this->companyIds !== null;
    }
}
