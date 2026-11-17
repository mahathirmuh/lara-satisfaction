<?php

namespace Tests\Feature;

use App\Models\Organization\Department;
use App\Models\Survey\Survey;
use App\Models\UserManagement\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SurveyForm\Services\SurveySubmissionService;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_sees_open_surveys_but_no_insights(): void
    {
        [$survey] = $this->openSurveyFor([Department::factory()->create()], ['title' => 'Operational Audit 2026']);

        $this->actingAs($this->employee())
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('Operational Audit 2026')
            ->assertSee(route('survey-form.show', $survey))
            ->assertDontSee('Survey Insights');
    }

    public function test_super_admin_sees_insights_compared_with_the_previous_survey(): void
    {
        $department = Department::factory()->create(['name' => 'Human Resources']);
        [$previous, $previousQuestions] = $this->openSurveyFor([$department], [
            'title' => 'Operational Audit 2025',
            'start_date' => today()->subYear(),
        ]);
        $this->respond($previous, $previousQuestions['HR'] ?? $previousQuestions[$department->code], 3, 5);
        $previous->update(['status' => 'closed']);

        [$current, $currentQuestions] = $this->openSurveyFor([$department], ['title' => 'Operational Audit 2026']);
        $this->respond($current, $currentQuestions[$department->code], 4, 6);

        $this->actingAs($this->superAdmin())
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('Survey Insights')
            ->assertSee('Operational Audit 2026')
            ->assertSee('compared with Operational Audit 2025')
            ->assertViewHas('summary', fn (array $summary): bool => $summary['responses'] === 6 && $summary['average'] === 4.0)
            ->assertViewHas('previousSummary', fn (array $summary): bool => $summary['average'] === 3.0);
    }

    public function test_each_insight_explains_how_it_is_counted_when_pointed_at(): void
    {
        $this->openSurveyFor([Department::factory()->create()], ['title' => 'Operational Audit 2026']);

        $this->actingAs($this->superAdmin(['locale' => 'id']))
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('data-bs-toggle="tooltip"', false)
            ->assertSeeInOrder([
                'Jumlah orang yang sudah mengirim survei.',
                'Persentase karyawan departemen yang dinilai yang sudah mengirim survei.',
                'Rata-rata semua penilaian Kualitas Layanan, dari 1 (Sangat Buruk) sampai 5 (Sangat Baik).',
                'Kepuasan pelanggan: persentase penilaian Kualitas Layanan bernilai 4 atau lebih, dari skala 5.',
            ]);
    }

    public function test_comment_highlights_are_escaped(): void
    {
        $department = Department::factory()->create();
        [$survey, $questions] = $this->openSurveyFor([$department]);
        $respondents = User::factory()->count(5)->create();

        foreach ($respondents as $respondent) {
            app(SurveySubmissionService::class)->submit($survey, $respondent, [
                $questions[$department->code]->id => 4,
                $questions['comment']->id => '<img src=x onerror=alert(1)>',
            ]);
        }

        $this->actingAs($this->superAdmin())
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('&lt;img src=x onerror=alert(1)&gt;', false)
            ->assertDontSee('<img src=x onerror=alert(1)>', false);
    }

    /**
     * Submit the same rating for a number of new respondents.
     */
    protected function respond(Survey $survey, $question, int $rating, int $count): void
    {
        foreach (User::factory()->count($count)->create() as $respondent) {
            app(SurveySubmissionService::class)->submit($survey, $respondent, [$question->id => $rating]);
        }
    }
}
