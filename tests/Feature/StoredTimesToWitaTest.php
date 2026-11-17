<?php

namespace Tests\Feature;

use App\Models\Survey\Survey;
use App\Models\Survey\SurveyParticipation;
use App\Models\UserManagement\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

/**
 * The move of the application from WIB (Asia/Jakarta) to WITA (Asia/Makassar): the stored times keep meaning the same
 * moment, an hour later on the clock.
 */
class StoredTimesToWitaTest extends TestCase
{
    use RefreshDatabase;

    protected User $employee;

    protected Survey $survey;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employee = $this->employee();
        $this->survey = Survey::factory()->create(['start_date' => '2026-11-06', 'end_date' => '2026-12-06']);
        SurveyParticipation::create(['survey_id' => $this->survey->id, 'user_id' => $this->employee->id, 'submitted_at' => '2026-11-17 09:30:00']);
        DB::table('users')->where('id', $this->employee->id)->update([
            'last_login_at' => '2026-11-17 10:00:00',
            'photo_updated_at' => '2026-11-01 08:00:00',
        ]);
    }

    public function test_the_stored_times_move_an_hour_later_and_the_dates_stay(): void
    {
        config(['app.timezone' => 'Asia/Makassar']);

        $this->migration()->up();

        $this->assertSame('2026-11-17 11:00:00', $this->userValue('last_login_at'));
        $this->assertSame('2026-11-17 10:30:00', (string) DB::table('survey_participations')->where('user_id', $this->employee->id)->value('submitted_at'));
        $this->assertSame('2026-11-01 08:00:00', $this->userValue('photo_updated_at'), 'The time of the access card system stays.');
        $this->assertSame(['start_date' => '2026-11-06', 'end_date' => '2026-12-06'], array_map(
            fn (mixed $date): string => substr((string) $date, 0, 10),
            (array) DB::table('surveys')->where('id', $this->survey->id)->first(['start_date', 'end_date']),
        ));
    }

    public function test_the_times_move_only_together_with_the_clock_of_the_application(): void
    {
        config(['app.timezone' => 'Asia/Jakarta']);

        try {
            $this->migration()->up();
            $this->fail('The stored times moved while the application still keeps WIB.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('APP_TIMEZONE=Asia/Makassar', $exception->getMessage());
        }

        $this->assertSame('2026-11-17 10:00:00', $this->userValue('last_login_at'));
    }

    /**
     * The migration that moves the stored times.
     */
    protected function migration(): object
    {
        return require database_path('migrations/2026_11_17_140115_move_stored_times_from_wib_to_wita.php');
    }

    /**
     * A stored value of the employee, as the database holds it.
     */
    protected function userValue(string $column): string
    {
        return (string) DB::table('users')->where('id', $this->employee->id)->value($column);
    }
}
