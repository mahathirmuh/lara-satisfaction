<?php

namespace Tests\Feature\AuditLog;

use App\Models\Log\AuditLog;
use App\Models\Survey\Survey;
use App\Models\UserManagement\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Tests\TestCase;

/**
 * The page "Audit Log": who signed in and who changed what, read only, for whoever is given the menu.
 */
class AuditLogPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAccessControl();
    }

    public function test_only_who_is_given_the_menu_reads_the_trail(): void
    {
        foreach ([$this->employee(), $this->superAdmin()] as $user) {
            $this->actingAs($user)->get(route('audit-log.index'))->assertForbidden();
            $this->actingAs($user)->getJson(route('audit-log.list'))->assertForbidden();
        }

        $admin = $this->itAdmin();
        $this->actingAs($admin)->get(route('audit-log.index'))->assertOk()->assertSee('rm-crumbbar', false);
        $this->actingAs($admin)->getJson(route('audit-log.list'))->assertOk();
    }

    public function test_an_entry_is_described_in_the_language_of_the_page_and_its_values_as_written(): void
    {
        $admin = $this->itAdmin(['locale' => 'id']);
        $survey = Survey::factory()->create(['title' => 'Operational Audit 2026']);
        $this->actingAs($admin);
        request()->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36');
        app(AuditLogger::class)->logChanges(AuditLogger::SURVEY_UPDATED, $survey, ['Title' => 'Draft title', 'Status' => 'Draft'], ['Title' => 'Draft title', 'Status' => 'Active'], $survey->title);

        $row = $this->getJson(route('audit-log.list'))->assertOk()->json('data.0');

        $this->assertSame($admin->name, $row['user']);
        $this->assertSame('survey.updated', $row['action']);
        $this->assertSame('mengubah survei', $row['activity']);
        $this->assertSame(['tone' => 'orange', 'icon' => 'edit', 'label' => 'Diubah'], $row['badge']);
        $this->assertSame('Survei #'.$survey->id, $row['entity']);
        $this->assertSame('Operational Audit 2026', $row['label']);
        $this->assertSame([['field' => 'Status', 'old' => 'Draft', 'new' => 'Active']], $row['changes']);
        $this->assertSame('Chrome 141 di Windows', $row['browser']);
    }

    public function test_the_list_finds_entries_by_person_action_kind_and_days(): void
    {
        $budi = User::factory()->create(['name' => 'Budi Santoso', 'email' => 'budi@merdekabattery.com']);
        $siti = User::factory()->create(['name' => 'Siti Rahma', 'email' => 'siti@merdekabattery.com']);
        $survey = Survey::factory()->create();
        $logger = app(AuditLogger::class);

        $this->travelTo('2026-11-01 09:00:00');
        $logger->log(AuditLogger::LOGIN, user: $budi, via: 'LDAP');
        $this->travelTo('2026-11-10 09:00:00');
        $logger->log(AuditLogger::SURVEY_DELETED, $survey, old: ['Title' => 'Old survey'], user: $siti, label: 'Old survey');
        $logger->log(AuditLogger::LOGIN_FAILED, new: ['Account' => 'agus.typed', 'Reason' => 'Account inactive'], via: 'LDAP');
        $this->travelBack();

        $find = fn (array $query): array => array_column($this->actingAs($this->itAdmin())->getJson(route('audit-log.list', $query))->assertOk()->json('data'), 'action');

        $this->assertSame(['auth.login'], $find(['search' => 'budi']));
        $this->assertSame(['survey.deleted'], $find(['search' => 'Siti Rahma']));
        // A failed sign-in has no account: what was typed into the form finds it.
        $this->assertSame(['auth.login_failed'], $find(['search' => 'agus.typed']));
        $this->assertSame(['survey.deleted'], $find(['action' => 'survey.deleted']));
        $this->assertSame(['survey.deleted'], $find(['entity' => 'Survey']));
        $this->assertSame(['auth.login_failed', 'survey.deleted'], $find(['from' => '2026-11-10', 'to' => '2026-11-10']));
        // Days given the wrong way round are turned; a day that does not exist is none.
        $this->assertSame(['auth.login'], $find(['from' => '2026-11-05', 'to' => '2026-11-01']));
        $this->assertCount(3, $find(['from' => '2026-02-31']));
    }

    public function test_the_page_offers_the_recorded_actions_by_part_of_the_application(): void
    {
        app(AuditLogger::class)->log(AuditLogger::LOGIN, user: $this->employee());
        app(AuditLogger::class)->log(AuditLogger::MASTER_UPDATED, user: $this->employee());

        $this->actingAs($this->itAdmin())->get(route('audit-log.index', ['action' => 'role.deleted']))
            ->assertOk()
            ->assertSeeInOrder(['<optgroup label="Sign-in">', 'value="auth.login"', 'Signed in', '<optgroup label="Roles">', 'value="role.deleted" selected', 'Role deleted', '<optgroup label="Master Data">', 'value="master_data.updated"'], false);
    }

    public function test_an_entry_is_never_changed_or_deleted(): void
    {
        $entry = app(AuditLogger::class)->log(AuditLogger::LOGIN, user: $this->employee());

        $this->assertInstanceOf(AuditLog::class, $entry);
        $this->assertThrows(fn () => $entry->update(['action' => 'auth.logout']), LogicException::class);
        $this->assertThrows(fn () => $entry->delete(), LogicException::class);
        $this->assertDatabaseHas('audit_logs', ['id' => $entry->id, 'action' => 'auth.login']);
    }

    public function test_secrets_never_reach_the_trail_and_long_values_are_cut(): void
    {
        $entry = app(AuditLogger::class)->log(AuditLogger::USER_UPDATED, new: [
            'Password' => 'Secret-123',
            'Client secret' => 'abc',
            'remember_token' => 'xyz',
            'Notes' => str_repeat('a', 600),
            'Name' => 'Budi',
        ], user: $this->employee());

        $this->assertSame(AuditLogger::REDACTED, $entry->new_values['Password']);
        $this->assertSame(AuditLogger::REDACTED, $entry->new_values['Client secret']);
        $this->assertSame(AuditLogger::REDACTED, $entry->new_values['remember_token']);
        $this->assertSame(str_repeat('a', 500).'…', $entry->new_values['Notes']);
        $this->assertSame('Budi', $entry->new_values['Name']);
    }

    public function test_without_its_table_nothing_is_written_and_the_page_says_so(): void
    {
        Schema::drop('audit_logs');
        Cache::forget(AuditLog::READY_CACHE_KEY);

        $this->assertNull(app(AuditLogger::class)->log(AuditLogger::LOGIN, user: $this->employee()));

        $admin = $this->itAdmin();
        $this->actingAs($admin)->get(route('audit-log.index'))->assertOk()->assertSee('id="auditNotReady"', false);
        $this->actingAs($admin)->getJson(route('audit-log.list'))->assertServiceUnavailable();
    }
}
