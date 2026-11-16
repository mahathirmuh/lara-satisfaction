<?php

namespace Tests\Feature;

use App\Models\Organization\Department;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyParticipation;
use DOMDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Every page shows where it sits: home, its menu path and the page itself.
 */
class BreadcrumbTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAccessControl();
    }

    public function test_a_menu_page_shows_its_place_in_the_menu(): void
    {
        $response = $this->actingAs($this->superAdmin())->get(route('survey-management.reminder.index'));

        $this->assertSame([
            ['Home', route('dashboard.index'), false],
            ['Survey Management', null, false],
            ['Reminders', null, true],
        ], $this->breadcrumbOf($response));
    }

    public function test_a_detail_page_links_back_to_its_menu_page(): void
    {
        $survey = Survey::factory()->create(['title' => 'Operational Audit 2026']);

        $response = $this->actingAs($this->superAdmin())->get(route('survey-report.show', $survey));

        $this->assertSame([
            ['Home', route('dashboard.index'), false],
            ['Survey Report', route('survey-report.index'), false],
            ['Operational Audit 2026', null, true],
        ], $this->breadcrumbOf($response));
    }

    public function test_the_survey_pages_follow_the_survey(): void
    {
        [$survey] = $this->openSurveyFor([Department::factory()->create()], ['title' => 'Operational Audit 2026']);
        $employee = $this->employee();

        $this->assertSame([
            ['Home', route('survey-form.index'), false],
            ['My Survey', route('survey-form.index'), false],
            ['Operational Audit 2026', null, true],
        ], $this->breadcrumbOf($this->actingAs($employee)->get(route('survey-form.show', $survey))));

        SurveyParticipation::create(['survey_id' => $survey->id, 'user_id' => $employee->id, 'submitted_at' => now()]);

        $this->assertSame([
            ['Home', route('survey-form.index'), false],
            ['My Survey', route('survey-form.index'), false],
            ['Operational Audit 2026', null, false],
            ['Thank You', null, true],
        ], $this->breadcrumbOf($this->actingAs($employee)->get(route('survey-form.thanks', $survey))));
    }

    public function test_a_page_outside_the_menu_ends_with_its_title(): void
    {
        $response = $this->actingAs($this->employee())->get(route('profile.show'));

        $this->assertSame([
            ['Home', route('survey-form.index'), false],
            ['My Profile', null, true],
        ], $this->breadcrumbOf($response));
    }

    public function test_the_breadcrumb_follows_the_interface_language(): void
    {
        $response = $this->actingAs($this->superAdmin(['locale' => 'zh_CN']))->get(route('survey-management.reminder.index'));

        $this->assertSame(['首页', '调查管理', '提醒'], array_column($this->breadcrumbOf($response), 0));
    }

    /**
     * Breadcrumb items of a page as [label, link, current].
     *
     * @return array<int, array{0: string, 1: string|null, 2: bool}>
     */
    protected function breadcrumbOf(TestResponse $response): array
    {
        $response->assertOk();

        preg_match('#<nav aria-label="[^"]*" class="mb-2">.*?</nav>#s', (string) $response->getContent(), $match);
        $this->assertNotEmpty($match, 'The page has no breadcrumb.');

        $document = new DOMDocument;
        $document->loadHTML('<?xml encoding="utf-8" ?>'.$match[0], LIBXML_NOERROR);
        $items = [];

        foreach ($document->getElementsByTagName('li') as $item) {
            $link = $item->getElementsByTagName('a')->item(0);

            $items[] = [
                trim($item->textContent) ?: (string) $link?->getAttribute('aria-label'),
                $link?->getAttribute('href') ?: null,
                $item->getAttribute('aria-current') === 'page',
            ];
        }

        return $items;
    }
}
