<?php

namespace Database\Seeders;

use App\Enums\QuestionType;
use App\Enums\SurveyStatus;
use App\Models\Organization\Company;
use App\Models\Organization\DepartmentSection;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyParticipation;
use App\Models\Survey\SurveyResponse;
use App\Models\UserManagement\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\SurveyForm\Services\SurveySubmissionService;

/**
 * Local demo data: section pictures, employees and anonymous responses for
 * the current and the previous operational audit, so the survey, dashboards
 * and reports have content. Never runs in production (see DatabaseSeeder).
 */
class DemoSeeder extends Seeder
{
    /**
     * Domain of the generated demo accounts.
     */
    public const DEMO_DOMAIN = 'demo.merdeka.test';

    /**
     * Folder of the demo section pictures.
     */
    protected const IMAGE_SOURCE = __DIR__.'/demo-images';

    /**
     * Average service quality rating per department code for the current survey.
     *
     * @var array<string, float>
     */
    protected const CURRENT_MEANS = ['ENV' => 3.8, 'EXT' => 3.4, 'HR' => 4.1, 'SCM' => 3.0];

    /**
     * Average service quality rating per department code for the previous survey.
     *
     * @var array<string, float>
     */
    protected const PREVIOUS_MEANS = ['ENV' => 3.5, 'EXT' => 3.3, 'HR' => 3.8, 'SCM' => 2.8];

    /**
     * Average criticality rating per department code.
     *
     * @var array<string, float>
     */
    protected const CRITICALITY_MEANS = ['ENV' => 3.6, 'EXT' => 4.0, 'HR' => 4.2, 'SCM' => 4.5];

    /**
     * Quality rating shift per section name, so section scores differ within a department.
     *
     * @var array<string, float>
     */
    protected const SECTION_SHIFTS = [
        'Environmental Monitoring' => 0.3, 'Waste Management' => -0.3, 'Rehabilitation' => 0.1,
        'Asset Protection' => 0.4, 'Community Relations' => -0.2, 'Government Relations' => -0.3,
        'Recruitment' => -0.2, 'Training' => 0.3, 'Employee Services' => 0.1,
        'Procurement' => -0.3, 'Warehouse' => 0.2, 'Logistics' => -0.4,
    ];

    /**
     * Sample feedback per department code.
     *
     * @var array<string, array<int, string>>
     */
    protected const COMMENTS = [
        'ENV' => [
            'Please share the monitoring results with site teams more regularly.',
            'Permit requirements are clear, but approvals still take quite long.',
            'More hands-on training on waste segregation would help the field crew.',
            'The team is responsive when we raise an issue during audits.',
        ],
        'EXT' => [
            'Information about community activities often reaches us late.',
            'Coordination with the local government went well during the last project.',
            'Security escorts are always on time, thank you.',
            'A single point of contact for stakeholder requests would be useful.',
        ],
        'HR' => [
            'Benefit claims are processed quickly, thank you.',
            'Training schedules should be planned around shift rotations.',
            'The new onboarding programme is a big improvement.',
            'Clearer information on career paths would motivate the team.',
        ],
        'SCM' => [
            'Material delivery to site is frequently delayed.',
            'Please provide real-time status for purchase requests.',
            'Warehouse staff are helpful, but stock data is not always accurate.',
            'Urgent requests need a faster approval route.',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(SurveySubmissionService $submission): void
    {
        if (User::query()->where('email', 'like', '%@'.self::DEMO_DOMAIN)->exists()) {
            $this->command?->info('Demo data already present, skipping.');

            return;
        }

        $this->attachSectionPictures();

        $current = Survey::query()->open()->orderByDesc('start_date')->first();

        if ($current === null) {
            $this->command?->warn('No open survey found; demo responses were not created.');

            return;
        }

        $employees = $this->createEmployees(60);
        $previous = $this->createPreviousSurvey($current);

        foreach ($employees->random(38) as $employee) {
            $this->storeDirectly($previous, $employee, $this->evaluationsFor($previous, self::PREVIOUS_MEANS));
        }

        foreach ($employees->random(46) as $employee) {
            $submission->submit($current, $employee, [], $this->evaluationsFor($current, self::CURRENT_MEANS));
        }

        $this->spreadSubmissionDates($current);
        $this->createTryOutEmployee();
    }

    /**
     * Team picture and work summary (demo images) with captions for every section.
     */
    protected function attachSectionPictures(): void
    {
        $faker = fake('id_ID');
        $disk = Storage::disk('public');

        foreach (DepartmentSection::query()->whereNull('team_photo_path')->get() as $section) {
            $teamPhoto = DepartmentSection::IMAGE_DIRECTORY.'/'.Str::random(40).'.webp';
            $workSummary = DepartmentSection::IMAGE_DIRECTORY.'/'.Str::random(40).'.webp';

            $disk->put($teamPhoto, (string) file_get_contents(self::IMAGE_SOURCE.'/team-picture.webp'));
            $disk->put($workSummary, (string) file_get_contents(self::IMAGE_SOURCE.'/work-summary.webp'));

            $section->update([
                'team_photo_path' => $teamPhoto,
                'team_caption' => sprintf('%s Team (Left to Right): %s, %s, %s.', $section->name, $faker->name(), $faker->name(), $faker->name()),
                'work_summary_path' => $workSummary,
                'work_summary_caption' => sprintf('Work Summary Matrix %s Semester 1 - %d', $section->name, now()->year),
            ]);
        }
    }

    /**
     * Demo employees spread over a few companies.
     *
     * @return Collection<int, User>
     */
    protected function createEmployees(int $count): Collection
    {
        $companies = Company::query()->whereIn('code', ['MBM', 'MTI', 'SCM', 'MCG', 'BSI'])->pluck('id', 'code');
        $weights = ['MBM' => 22, 'MTI' => 18, 'SCM' => 10, 'MCG' => 7, 'BSI' => 3];
        $faker = fake('id_ID');
        $employees = new Collection;
        $number = 0;
        $unusablePassword = Hash::make(Str::password(32));

        foreach ($weights as $code => $weight) {
            for ($index = 0; $index < (int) round($count * $weight / array_sum($weights)); $index++) {
                $number++;
                $name = $faker->name();

                $employee = User::create([
                    'name' => $name,
                    'email' => Str::slug($name, '.').'.'.$number.'@'.self::DEMO_DOMAIN,
                    'employee_id' => sprintf('DEMO%04d', $number),
                    'company_id' => $companies[$code] ?? null,
                    'password' => $unusablePassword,
                    'is_active' => true,
                ]);

                $employee->roles()->attach((int) config('merdeka.default_role_id'));
                $employees->push($employee);
            }
        }

        return $employees;
    }

    /**
     * Closed survey of the previous year with the same question set.
     */
    protected function createPreviousSurvey(Survey $current): Survey
    {
        $start = $current->start_date->copy()->subYear();

        $previous = Survey::create([
            'title' => 'Operational Audit '.$start->year,
            'description' => 'Previous operational audit (demo data).',
            'start_date' => $start,
            'end_date' => $start->copy()->addDays(30),
            'status' => SurveyStatus::Closed,
            'estimated_minutes' => 15,
            'created_by' => $current->created_by,
        ]);

        SurveySeeder::createQuestions($previous);

        return $previous;
    }

    /**
     * Answers for every section of every department, around the given averages.
     *
     * @param  array<string, float>  $means
     * @return array<int, array<int, int|string>> Section id => (question id => answer)
     */
    protected function evaluationsFor(Survey $survey, array $means): array
    {
        $questions = $survey->questions()->with('department.sections')->get();
        $evaluations = [];

        foreach ($questions as $question) {
            $code = $question->department?->code;

            foreach ($question->department?->sections->where('is_active', true) ?? [] as $section) {
                if (! $question->appliesTo($section->id)) {
                    continue;
                }

                $evaluations[$section->id][$question->id] = match ($question->type) {
                    QuestionType::Rating => $this->sampleRating(($means[$code] ?? 3.5) + (self::SECTION_SHIFTS[$section->name] ?? 0.0)),
                    QuestionType::Criticality => $this->sampleRating(self::CRITICALITY_MEANS[$code] ?? 3.8),
                    QuestionType::Text => isset(self::COMMENTS[$code]) ? Arr::random(self::COMMENTS[$code]) : 'Keep up the good work.',
                };
            }
        }

        return $evaluations;
    }

    /**
     * Rating from a normal distribution around the mean, clamped to 1-5.
     */
    protected function sampleRating(float $mean): int
    {
        $uniform1 = random_int(1, PHP_INT_MAX) / PHP_INT_MAX;
        $uniform2 = random_int(1, PHP_INT_MAX) / PHP_INT_MAX;
        $gaussian = sqrt(-2 * log($uniform1)) * cos(2 * M_PI * $uniform2);

        return (int) max(1, min(5, round($mean + $gaussian * 0.9)));
    }

    /**
     * Store a response of a closed survey (the submission service only accepts open surveys).
     *
     * @param  array<int, array<int, int|string>>  $evaluations  Section id => (question id => answer)
     */
    protected function storeDirectly(Survey $survey, User $employee, array $evaluations): void
    {
        SurveyParticipation::create([
            'survey_id' => $survey->id,
            'user_id' => $employee->id,
            'submitted_at' => Carbon::parse($survey->start_date)->addDays(random_int(0, 29))->setTime(random_int(7, 17), random_int(0, 59)),
        ]);

        $response = SurveyResponse::create([
            'survey_id' => $survey->id,
            'company_id' => $employee->company_id,
        ]);

        $questions = $survey->questions()->get()->keyBy('id');
        $rows = [];

        foreach ($evaluations as $sectionId => $answers) {
            foreach ($answers as $questionId => $value) {
                $rows[] = [
                    'survey_question_id' => $questionId,
                    'department_id' => $questions[$questionId]->department_id,
                    'department_section_id' => $sectionId,
                    'rating' => is_int($value) ? $value : null,
                    'comment' => is_string($value) ? $value : null,
                ];
            }
        }

        $response->answers()->createMany($rows);
    }

    /**
     * Spread the demo completions over the open period for a realistic trend.
     */
    protected function spreadSubmissionDates(Survey $survey): void
    {
        $days = max(0, (int) $survey->start_date->diffInDays(today()));

        $survey->participations()->each(function (SurveyParticipation $participation) use ($survey, $days): void {
            $participation->update([
                'submitted_at' => $survey->start_date->copy()->addDays(random_int(0, $days))->setTime(random_int(7, 17), random_int(0, 59)),
            ]);
        });
    }

    /**
     * An employee who has not answered yet, to try the survey flow.
     */
    protected function createTryOutEmployee(): void
    {
        $password = (string) (config('merdeka.seed.admin_password') ?: Str::password(16, symbols: false));

        $employee = User::create([
            'name' => 'Demo Employee',
            'email' => 'employee@'.self::DEMO_DOMAIN,
            'employee_id' => 'DEMO9999',
            'company_id' => Company::query()->where('code', 'MBM')->value('id'),
            'password' => $password,
            'is_active' => true,
        ]);

        $employee->roles()->attach((int) config('merdeka.default_role_id'));

        $this->command?->warn('Demo employee account created (has not answered the survey yet):');
        $this->command?->line('  Email    : employee@'.self::DEMO_DOMAIN.' (or Employee ID DEMO9999)');
        $this->command?->line(config('merdeka.seed.admin_password') ? '  Password : (from SEED_ADMIN_PASSWORD)' : "  Password : {$password}");
    }
}
