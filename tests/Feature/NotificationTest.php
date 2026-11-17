<?php

namespace Tests\Feature;

use App\Models\Survey\Survey;
use App\Models\UserManagement\User;
use App\Notifications\SurveyDeadlineReminder;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
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

    public function test_the_inbox_lists_every_notification_of_the_user_newest_first(): void
    {
        $employee = $this->employee();
        $this->notification($employee, 'Operational Audit 2025 closes in 7 days', now()->subDays(3));
        $this->notification($employee, 'Operational Audit 2026 closes in 14 days', now());
        $this->notification($this->employee(), 'A reminder of someone else', now());

        $this->actingAs($employee)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertViewHas('total', 2)
            ->assertSeeInOrder(['Operational Audit 2026 closes in 14 days', 'Operational Audit 2025 closes in 7 days'])
            ->assertDontSee('A reminder of someone else');
    }

    public function test_the_inbox_shows_only_the_unread_ones_on_request(): void
    {
        $employee = $this->employee();
        $this->notification($employee, 'Still to read', now());
        $this->notification($employee, 'Already read', now()->subDay(), read: true);

        // (The bell of the top bar lists the latest ones, read or not: the list of the page is what is checked.)
        $this->actingAs($employee)
            ->get(route('notifications.index', ['filter' => 'unread']))
            ->assertOk()
            ->assertViewHas('unreadCount', 1)
            ->assertViewHas('notifications', fn ($page): bool => $page->pluck('data.message')->all() === ['Still to read']);
    }

    public function test_a_notification_is_marked_read_or_deleted_from_the_inbox(): void
    {
        $employee = $this->employee();
        $toRead = $this->notification($employee, 'To read', now());
        $toDelete = $this->notification($employee, 'To delete', now());

        $this->actingAs($employee)
            ->from(route('notifications.index'))
            ->post(route('notifications.read', $toRead->id))
            ->assertRedirect(route('notifications.index'));
        $this->actingAs($employee)
            ->from(route('notifications.index'))
            ->delete(route('notifications.destroy', $toDelete->id))
            ->assertRedirect(route('notifications.index'));

        $this->assertNotNull($toRead->fresh()->read_at);
        $this->assertNull($toDelete->fresh());
    }

    public function test_nobody_reads_or_deletes_the_notifications_of_someone_else(): void
    {
        $notification = $this->notification($this->employee(), 'Mine', now());
        $stranger = $this->employee();

        $this->actingAs($stranger)->post(route('notifications.read', $notification->id))->assertNotFound();
        $this->actingAs($stranger)->delete(route('notifications.destroy', $notification->id))->assertNotFound();

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_the_bell_leads_to_the_inbox_and_guests_sign_in_first(): void
    {
        $this->actingAs($this->employee())
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertSeeInOrder(['Open Inbox'])
            ->assertSee('href="'.route('notifications.index').'"', false);

        auth()->logout();
        $this->get(route('notifications.index'))->assertRedirect(route('login'));
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

    /**
     * A survey reminder of the user with the given message, sent at the given time.
     */
    protected function notification(User $user, string $message, CarbonInterface $at, bool $read = false): DatabaseNotification
    {
        return $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => SurveyDeadlineReminder::class,
            'data' => ['title' => 'Survey deadline approaching', 'message' => $message, 'url' => '/survey-form'],
            'read_at' => $read ? $at : null,
            'created_at' => $at,
            'updated_at' => $at,
        ]);
    }
}
