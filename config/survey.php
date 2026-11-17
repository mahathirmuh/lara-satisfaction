<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Anonymity Threshold
    |--------------------------------------------------------------------------
    |
    | Reports never show scores, comments or segment breakdowns for a group
    | with fewer responses than this value, so individual answers cannot be
    | inferred from small groups.
    |
    */

    'min_group_size' => (int) env('SURVEY_MIN_GROUP_SIZE', 5),

    /*
    |--------------------------------------------------------------------------
    | Respondents
    |--------------------------------------------------------------------------
    |
    | Every employee of these companies (codes, comma separated) fills in the
    | surveys, besides the employees of the assessed departments of any
    | company. All of them are imported every day from the directory
    | (users:sync-directory), reminded, and counted in Survey Progress and in
    | the participation rate.
    |
    */

    'respondent_companies' => array_values(array_filter(array_map(
        fn (string $code): string => strtoupper(trim($code)),
        explode(',', (string) env('SURVEY_RESPONDENT_COMPANIES', 'MTI')),
    ))),

    /*
    |--------------------------------------------------------------------------
    | Rating Scales
    |--------------------------------------------------------------------------
    |
    | Every rating question uses one of these 1-5 scales. The description of
    | each level is shown to employees under the question. Only "quality"
    | ratings count towards the satisfaction scores (average and CSAT);
    | "criticality" ratings measure how much a department depends on a
    | service and are reported separately.
    |
    */

    'scales' => [
        'quality' => [
            'title' => 'Service Quality Level',
            'levels' => [
                1 => ['label' => 'Very Poor', 'description' => 'Service is consistently below acceptable standards'],
                2 => ['label' => 'Poor', 'description' => 'Service frequently falls short of expectations'],
                3 => ['label' => 'Average', 'description' => 'Service meets expectations but lacks in some areas'],
                4 => ['label' => 'Good', 'description' => 'Service meets expectations and occasionally exceeds them'],
                5 => ['label' => 'Excellent', 'description' => 'Service exceeds expectations consistently'],
            ],
        ],
        'criticality' => [
            'title' => 'Critical Level',
            'levels' => [
                1 => ['label' => 'Not Critical', 'description' => 'The service has little to no impact on my department'],
                2 => ['label' => 'Somewhat Critical', 'description' => 'The service has a minor impact on my department'],
                3 => ['label' => 'Moderately Critical', 'description' => 'The service has a noticeable impact on my department'],
                4 => ['label' => 'Very Critical', 'description' => 'The service significantly impacts my department performance'],
                5 => ['label' => 'Extremely Critical', 'description' => 'My department cannot function effectively without this service'],
            ],
        ],
    ],

    /*
    | Quality ratings greater than or equal to this value count as satisfied
    | for the CSAT percentage.
    */

    'satisfied_threshold' => 4,

    /*
    |--------------------------------------------------------------------------
    | Deadline Reminders
    |--------------------------------------------------------------------------
    |
    | Days before the end date on which employees who have not completed an
    | open survey are reminded (in-app notification and email), e.g. "14" or
    | "14,7,1". Each reminder is sent once per survey by the daily
    | "survey:send-deadline-reminders" command; the survey cards show a
    | countdown from the first reminder day.
    |
    */

    'reminder_days' => array_values(array_unique(array_map('intval', array_filter(
        explode(',', (string) env('SURVEY_REMINDER_DAYS', '14')),
        fn (string $day): bool => is_numeric(trim($day)) && (int) $day >= 0,
    )))),

    /*
    |--------------------------------------------------------------------------
    | Open Answers
    |--------------------------------------------------------------------------
    */

    'comment_max_length' => 500,

    'feedback_guidelines' => [
        'Share specific examples of good or poor service',
        'Suggest improvements or new features',
        'Mention any issues or challenges you faced',
        'Highlight what worked well for you',
    ],

];
