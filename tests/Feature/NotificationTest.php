<?php

namespace Tests\Feature;

use App\Models\Survey\Survey;
use App\Notifications\SurveyDeadlineReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_navbar_lists_the_unread_notifications(): void
    {
        $employee = $this->employee();
        $employee->notify(new SurveyDeadlineReminder(Survey::factory()->create(['title' => 'Operational Audit 2026']), 14));

        $this->actingAs($employee)
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('Survey deadline approaching')
            ->assertSee('Operational Audit 2026 closes in 14 days')
            ->assertSee('Mark all as read');
    }

    public function test_opening_a_notification_marks_it_read_and_opens_the_survey(): void
    {
        $employee = $this->employee();
        $survey = Survey::factory()->create();
        $employee->notify(new SurveyDeadlineReminder($survey, 14));
        $notification = $employee->notifications()->sole();

        $this->actingAs($employee)
            ->get(route('notifications.show', $notification->id))
            ->assertRedirect(route('survey-form.show', $survey));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_notifications_of_other_users_cannot_be_opened(): void
    {
        $owner = $this->employee();
        $owner->notify(new SurveyDeadlineReminder(Survey::factory()->create(), 14));
        $notification = $owner->notifications()->sole();

        $this->actingAs($this->employee())
            ->get(route('notifications.show', $notification->id))
            ->assertNotFound();

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_links_outside_the_application_are_not_followed(): void
    {
        $employee = $this->employee();
        $notification = $employee->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => SurveyDeadlineReminder::class,
            'data' => ['title' => 'Update your password', 'message' => 'Click here', 'url' => '//evil.test/login'],
        ]);

        $this->actingAs($employee)
            ->get(route('notifications.show', $notification->id))
            ->assertRedirect($employee->homeUrl());
    }

    public function test_all_notifications_can_be_marked_as_read(): void
    {
        $employee = $this->employee();
        $survey = Survey::factory()->create();
        $employee->notify(new SurveyDeadlineReminder($survey, 14));
        $employee->notify(new SurveyDeadlineReminder($survey, 7));

        $this->actingAs($employee)
            ->from(route('dashboard.index'))
            ->post(route('notifications.read-all'))
            ->assertRedirect(route('dashboard.index'));

        $this->assertSame(0, $employee->unreadNotifications()->count());
    }

    public function test_notification_text_is_escaped(): void
    {
        $employee = $this->employee();
        $employee->notify(new SurveyDeadlineReminder(Survey::factory()->create(['title' => '<script>alert(1)</script>']), 14));

        $this->actingAs($employee)
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }
}
