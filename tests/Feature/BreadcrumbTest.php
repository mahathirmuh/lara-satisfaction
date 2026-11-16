<?php

namespace Tests\Feature;

use App\Models\Organization\Department;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyParticipation;
use App\Models\UserManagement\Menu;
use App\Models\UserManagement\Role;
use DOMDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Every page shows the path to it under the top bar: the dashboard, its place in the sidebar and the page itself.
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
            ['Dashboard', route('dashboard.index'), false],
            ['Survey Management', null, false],
            ['Reminders', null, true],
        ], $this->breadcrumbOf($response));
    }

    public function test_a_detail_page_links_back_to_its_menu_page(): void
    {
        $survey = Survey::factory()->create(['title' => 'Operational Audit 2026']);

        $response = $this->actingAs($this->superAdmin())->get(route('survey-report.show', $survey));

        $this->assertSame([
            ['Dashboard', route('dashboard.index'), false],
            ['Survey Report', route('survey-report.index'), false],
            ['Operational Audit 2026', null, true],
        ], $this->breadcrumbOf($response));
        $this->assertSame('Survey Report', $this->headingOf($response));
    }

    public function test_the_survey_pages_follow_the_survey(): void
    {
        [$survey] = $this->openSurveyFor([Department::factory()->create()], ['title' => 'Operational Audit 2026']);
        $employee = $this->employee();
        $path = [
            ['Dashboard', route('dashboard.index'), false],
            ['My Survey', route('survey-form.index'), false],
            ['Operational Audit 2026', null, true],
        ];

        $this->assertSame($path, $this->breadcrumbOf($this->actingAs($employee)->get(route('survey-form.show', $survey))));

        SurveyParticipation::create(['survey_id' => $survey->id, 'user_id' => $employee->id, 'submitted_at' => now()]);
        $thanks = $this->actingAs($employee)->get(route('survey-form.thanks', $survey));

        $this->assertSame($path, $this->breadcrumbOf($thanks));
        $this->assertSame('Thank You', $this->headingOf($thanks));
    }

    public function test_the_dashboard_is_the_whole_path_of_the_dashboard(): void
    {
        $response = $this->actingAs($this->employee())->get(route('dashboard.index'));

        $this->assertSame([['Dashboard', null, true]], $this->breadcrumbOf($response));
    }

    public function test_a_page_outside_the_menu_ends_with_its_title(): void
    {
        $response = $this->actingAs($this->employee())->get(route('profile.show'));

        $this->assertSame([
            ['Dashboard', route('dashboard.index'), false],
            ['My Profile', null, true],
        ], $this->breadcrumbOf($response));
    }

    public function test_the_path_starts_at_the_menu_for_whoever_may_not_open_the_dashboard(): void
    {
        Role::query()->where('name', 'Employee')->firstOrFail()
            ->menus()->detach(Menu::query()->where('route', 'dashboard.index')->value('id'));

        $response = $this->actingAs($this->employee())->get(route('survey-form.index'));

        $this->assertSame([['My Survey', null, true]], $this->breadcrumbOf($response));
    }

    public function test_the_path_follows_the_interface_language(): void
    {
        $response = $this->actingAs($this->superAdmin(['locale' => 'zh_CN']))->get(route('survey-management.reminder.index'));

        $this->assertSame(['仪表板', '调查管理', '提醒'], array_column($this->breadcrumbOf($response), 0));
    }

    /**
     * The path of a page as [label, link, current].
     *
     * @return array<int, array{0: string, 1: string|null, 2: bool}>
     */
    protected function breadcrumbOf(TestResponse $response): array
    {
        $response->assertOk();

        preg_match('#<nav class="rm-crumbbar"[^>]*>.*?</nav>#s', (string) $response->getContent(), $match);
        $this->assertNotEmpty($match, 'The page has no path.');

        $document = new DOMDocument;
        $document->loadHTML('<?xml encoding="utf-8" ?>'.$match[0], LIBXML_NOERROR);
        $items = [];

        foreach ($document->getElementsByTagName('li') as $item) {
            $link = $item->getElementsByTagName('a')->item(0);
            $current = $item->getElementsByTagName('span')->item(0)?->getAttribute('aria-current') === 'page';

            $items[] = [trim($item->textContent), $link?->getAttribute('href') ?: null, $current];
        }

        return $items;
    }

    /**
     * The heading of a page in the top bar.
     */
    protected function headingOf(TestResponse $response): string
    {
        preg_match('#<h1 class="rm-topbar-title">(.*?)</h1>#s', (string) $response->getContent(), $match);
        $this->assertNotEmpty($match, 'The page has no heading.');

        return html_entity_decode(trim($match[1]), ENT_QUOTES | ENT_HTML5);
    }
}
