<?php

namespace Modules\SurveyReport\Support;

use App\Models\Survey\Survey;

/**
 * The slice of a survey's results a report covers: the survey, the
 * respondent companies and the evaluated departments (null means all).
 */
final class ReportScope
{
    /**
     * Create a new report scope.
     *
     * @param  array<int, int>|null  $companyIds
     * @param  array<int, int>|null  $departmentIds
     */
    public function __construct(
        public readonly Survey $survey,
        public readonly ?array $companyIds = null,
        public readonly ?array $departmentIds = null,
    ) {}

    /**
     * Whether the report is limited to specific companies.
     */
    public function isRestricted(): bool
    {
        return $this->companyIds !== null;
    }

    /**
     * Whether the report is limited to specific departments (e.g. a manager's).
     */
    public function isDepartmentRestricted(): bool
    {
        return $this->departmentIds !== null;
    }

    /**
     * Whether results about a department (null: general questions) are covered.
     */
    public function coversDepartment(?int $departmentId): bool
    {
        return $this->departmentIds === null || ($departmentId !== null && in_array($departmentId, $this->departmentIds, true));
    }
}
