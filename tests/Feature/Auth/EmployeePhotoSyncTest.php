<?php

namespace Tests\Feature\Auth;

use App\Services\Auth\EmployeePhotoSync;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Employee photos come from the access card system (CardDB, StaffNo =
 * employee ID), as small thumbnails only signed-in people may see.
 */
class EmployeePhotoSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        config([
            'database.connections.card' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'merdeka.employee_photos.connection' => 'card',
            'merdeka.employee_photos.table' => 'CardDB',
        ]);
        DB::purge('card');

        Schema::connection('card')->create('CardDB', function (Blueprint $table) {
            $table->string('ID')->primary();
            $table->string('StaffNo')->nullable();
            $table->binary('Photo')->nullable();
            $table->boolean('Del_State')->default(false);
            $table->dateTime('LastUpdate')->nullable();
        });
    }

    public function test_the_photos_of_the_department_employees_are_taken_from_their_card(): void
    {
        $twoCards = $this->departmentEmployee(['employee_id' => 'MTI240001']);
        $this->card('MTI240001', $this->jpeg(), '2026-01-01 08:00:00');
        $this->card('MTI240001', $this->jpeg(), '2026-11-10 08:00:00', deleted: true);
        $paddedStaffNo = $this->departmentEmployee(['employee_id' => 'MTI240002']);
        $this->card(' MTI240002 ', $this->jpeg(), '2026-05-01 08:00:00');
        $withoutCard = $this->departmentEmployee(['employee_id' => 'MTI240003']);
        $outsider = $this->employee(['employee_id' => 'MTI240004']);
        $this->card('MTI240004', $this->jpeg(), '2026-05-01 08:00:00');

        $this->assertSame(['updated' => 2, 'unchanged' => 0, 'missing' => 1], app(EmployeePhotoSync::class)->sync());

        $twoCards->refresh();
        $this->assertSame('user-photos/'.$twoCards->id.'.webp', $twoCards->photo_path);
        $this->assertSame('2026-01-01 08:00:00', $twoCards->photo_updated_at->format('Y-m-d H:i:s'), 'The card not deleted wins.');
        $this->assertSame([160, 160, IMAGETYPE_WEBP], array_slice((array) getimagesizefromstring(Storage::disk('local')->get($twoCards->photo_path)), 0, 3));
        $this->assertNotNull($paddedStaffNo->fresh()->photo_path);
        $this->assertNull($withoutCard->fresh()->photo_path);
        $this->assertNull($outsider->fresh()->photo_path);
    }

    public function test_a_photo_is_taken_again_only_after_its_card_changed(): void
    {
        $employee = $this->departmentEmployee(['employee_id' => 'MTI240001']);
        $cardId = $this->card('MTI240001', $this->jpeg(), '2026-01-01 08:00:00');
        $sync = app(EmployeePhotoSync::class);

        $this->assertSame(1, $sync->sync()['updated']);
        $this->assertSame(['updated' => 0, 'unchanged' => 1, 'missing' => 0], $sync->sync());

        DB::connection('card')->table('CardDB')->where('ID', $cardId)->update(['LastUpdate' => '2026-11-16 08:00:00']);

        $this->assertSame(1, $sync->sync()['updated']);
        $this->assertSame('2026-11-16', $employee->fresh()->photo_updated_at->toDateString());
    }

    public function test_a_photo_taken_by_another_server_sharing_the_database_is_taken_here_too(): void
    {
        $employee = $this->departmentEmployee(['employee_id' => 'MTI240001']);
        $this->card('MTI240001', $this->jpeg(), '2026-01-01 08:00:00');
        $sync = app(EmployeePhotoSync::class);
        $sync->sync();
        Storage::disk('local')->delete($employee->fresh()->photo_path);

        $this->assertNull($employee->fresh()->photoUrl(), 'The initials show instead of a broken picture.');
        $this->assertSame(['updated' => 1, 'unchanged' => 0, 'missing' => 0], $sync->sync());
        Storage::disk('local')->assertExists($employee->fresh()->photo_path);
        $this->assertNotNull($employee->fresh()->photoUrl());
    }

    public function test_photos_are_shown_to_the_person_and_to_those_who_follow_people(): void
    {
        $employee = $this->departmentEmployee(['employee_id' => 'MTI240001']);
        $this->card('MTI240001', $this->jpeg(), '2026-01-01 08:00:00');
        app(EmployeePhotoSync::class)->sync();
        $photo = route('users.photo', $employee);

        $this->actingAs($employee)->get($photo)->assertOk()->assertHeader('Content-Type', 'image/webp');
        $this->actingAs($this->superAdmin())->get($photo)->assertOk();
        $this->actingAs($this->departmentEmployee())->get($photo)->assertForbidden();
        $this->actingAs($this->superAdmin())->get(route('users.photo', $this->departmentEmployee()))->assertNotFound();
        auth()->logout();
        $this->get($photo)->assertRedirect(route('login'));
    }

    public function test_the_user_list_shows_each_photo_or_initials(): void
    {
        $withPhoto = $this->departmentEmployee(['employee_id' => 'MTI240001', 'name' => 'Budi Santoso [MTI]']);
        $this->card('MTI240001', $this->jpeg(), '2026-01-01 08:00:00');
        app(EmployeePhotoSync::class)->sync();
        $this->departmentEmployee(['name' => 'Abdul Ransep [MTI]']);

        $rows = collect($this->actingAs($this->superAdmin())
            ->getJson(route('user-management.user-registered.list', ['search' => '[MTI]']))
            ->assertOk()
            ->json('data'))->keyBy('name');

        $this->assertSame($withPhoto->fresh()->photoUrl(), $rows['Budi Santoso [MTI]']['photo_url']);
        $this->assertStringContainsString('?v=', (string) $rows['Budi Santoso [MTI]']['photo_url']);
        $this->assertNull($rows['Abdul Ransep [MTI]']['photo_url']);
        $this->assertSame('AR', $rows['Abdul Ransep [MTI]']['initials']);
    }

    /**
     * A card record with a photo; returns its ID.
     */
    protected function card(string $staffNo, string $photo, string $lastUpdate, bool $deleted = false): string
    {
        $id = (string) Str::uuid();

        DB::connection('card')->table('CardDB')->insert([
            'ID' => $id,
            'StaffNo' => $staffNo,
            'Photo' => $photo,
            'Del_State' => $deleted,
            'LastUpdate' => $lastUpdate,
        ]);

        return $id;
    }

    /**
     * A small portrait JPEG.
     */
    protected function jpeg(): string
    {
        $image = imagecreatetruecolor(120, 160);
        ob_start();
        imagejpeg($image);

        return (string) ob_get_clean();
    }
}
