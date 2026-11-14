<?php

namespace Database\Seeders;

use App\Enums\QuestionType;
use App\Enums\SurveyStatus;
use App\Models\Organization\Department;
use App\Models\Survey\Survey;
use App\Models\UserManagement\User;
use Illuminate\Database\Seeder;

/**
 * The current operational audit with its question set.
 */
class SurveySeeder extends Seeder
{
    /**
     * Questions asked in every department about the section the employee
     * chooses to evaluate ("{section}" is replaced by its name). Each question
     * is [type, text, required].
     *
     * @var array<int, array{0: QuestionType, 1: string, 2: bool}>
     */
    public const SECTION_QUESTIONS = [
        [QuestionType::Criticality, 'Please rate the level of criticality of {section} towards your department.', true],
        [QuestionType::Rating, 'How would you rate the service quality of {section} towards your department?', true],
        [QuestionType::Text, 'Additional Feedback', true],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $title = 'Operational Audit '.now()->year;

        if (Survey::query()->where('title', $title)->exists()) {
            return;
        }

        $survey = Survey::create([
            'title' => $title,
            'description' => 'Your honest feedback across all departments helps us build a more effective, supportive, and efficient workplace. Every submission is anonymous.',
            'start_date' => today()->subDays(10),
            'end_date' => today()->addDays(20),
            'status' => SurveyStatus::Active,
            'estimated_minutes' => 15,
            'created_by' => User::query()->orderBy('id')->value('id'),
        ]);

        static::createQuestions($survey);
    }

    /**
     * Create the standard question set for every active department.
     */
    public static function createQuestions(Survey $survey): void
    {
        $order = 0;

        foreach (Department::query()->active()->ordered()->get() as $department) {
            foreach (self::SECTION_QUESTIONS as [$type, $text, $isRequired]) {
                $survey->questions()->create([
                    'department_id' => $department->id,
                    'type' => $type,
                    'question' => $text,
                    'is_required' => $isRequired,
                    'sort_order' => ++$order,
                ]);
            }
        }
    }
}
