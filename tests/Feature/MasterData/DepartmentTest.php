<?php

namespace Tests\Feature\MasterData;

use App\Models\Organization\Department;
use App\Models\Organization\DepartmentSection;
use App\Models\Survey\SurveyQuestion;
use App\Models\UserManagement\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DepartmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->superAdmin();
    }

    public function test_admin_creates_a_department_with_a_normalised_code(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('master-data.department.store'), $this->payload(['code' => ' hse ']))
            ->assertCreated();

        $this->assertDatabaseHas('departments', [
            'code' => 'HSE',
            'name' => 'Health, Safety & Environment',
            'image_path' => null,
            'is_active' => true,
        ]);
    }

    public function test_manager_job_titles_are_saved_one_per_line(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('master-data.department.store'), $this->payload([
                'manager_job_titles' => "OHS Manager\r\n  Occupational Health & Safety   Manager \n\n",
            ]))
            ->assertCreated();

        $department = Department::query()->where('code', 'HSE')->sole();
        $this->assertSame(['OHS Manager', 'Occupational Health & Safety Manager'], $department->manager_job_titles);

        $this->actingAs($this->admin)
            ->getJson(route('master-data.department.show', $department))
            ->assertOk()
            ->assertJsonPath('data.manager_job_titles', "OHS Manager\nOccupational Health & Safety Manager");
    }

    public function test_department_code_must_be_unique(): void
    {
        $existing = Department::factory()->create(['code' => 'HR']);

        $this->actingAs($this->admin)
            ->postJson(route('master-data.department.store'), $this->payload(['code' => 'hr']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['code' => 'The code has already been taken.']);

        $this->actingAs($this->admin)
            ->putJson(route('master-data.department.update', $existing), $this->payload(['code' => 'HR']))
            ->assertOk();
    }

    public function test_icon_and_colour_are_restricted_to_safe_values(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('master-data.department.store'), $this->payload([
                'icon' => 'users" onmouseover="alert(1)',
                'color' => 'red;background:url(evil)',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'icon' => 'The icon must be a Tabler icon name, e.g. "leaf" or "users".',
                'color' => 'The selected color is invalid.',
            ]);
    }

    public function test_uploaded_image_is_re_encoded_as_webp_on_the_public_disk(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin)
            ->withHeader('Accept', 'application/json')
            ->post(route('master-data.department.store'), $this->payload([
                'image' => UploadedFile::fake()->image('site.jpg', 1200, 800),
            ]))
            ->assertCreated();

        $imagePath = Department::query()->where('code', 'HSE')->sole()->image_path;

        $this->assertMatchesRegularExpression('#^departments/[A-Za-z0-9]{40}\.webp$#', $imagePath);
        Storage::disk('public')->assertExists($imagePath);
        $this->assertSame('image/webp', mime_content_type(Storage::disk('public')->path($imagePath)));
    }

    public function test_large_photos_are_downscaled_to_web_size(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin)
            ->withHeader('Accept', 'application/json')
            ->post(route('master-data.department.store'), $this->payload([
                'image' => UploadedFile::fake()->image('phone-photo.jpg', 2400, 1200),
            ]))
            ->assertCreated();

        $imagePath = Department::query()->where('code', 'HSE')->sole()->image_path;
        [$width, $height] = getimagesize(Storage::disk('public')->path($imagePath));

        $this->assertSame([1920, 960], [$width, $height]);
    }

    public function test_image_too_large_to_decode_in_memory_is_rejected_with_a_clear_message(): void
    {
        Storage::fake('public');
        $image = UploadedFile::fake()->image('huge.jpg', 3000, 3000);
        $originalLimit = ini_get('memory_limit');
        ini_set('memory_limit', (string) (memory_get_usage() + 32 * 1024 * 1024));

        try {
            $response = $this->actingAs($this->admin)
                ->withHeader('Accept', 'application/json')
                ->post(route('master-data.department.store'), $this->payload(['image' => $image]));
        } finally {
            ini_set('memory_limit', $originalLimit);
        }

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'image' => 'The image is too large to process. Please resize it to at most 1920 pixels and try again.',
        ]);
        $this->assertDatabaseMissing('departments', ['code' => 'HSE']);
    }

    public function test_svg_uploads_are_rejected(): void
    {
        Storage::fake('public');
        $svg = UploadedFile::fake()->createWithContent(
            'logo.svg',
            '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"></svg>'
        );

        $this->actingAs($this->admin)
            ->withHeader('Accept', 'application/json')
            ->post(route('master-data.department.store'), $this->payload(['image' => $svg]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['image' => 'The image field must be an image.']);

        $this->assertDatabaseMissing('departments', ['code' => 'HSE']);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_updating_without_an_upload_keeps_the_image(): void
    {
        Storage::fake('public');
        $department = $this->departmentWithImage('departments/current.webp');

        $this->actingAs($this->admin)
            ->putJson(route('master-data.department.update', $department), $this->payload([
                'code' => $department->code,
                'name' => 'Renamed Department',
            ]))
            ->assertOk();

        $this->assertSame('departments/current.webp', $department->fresh()->image_path);
        Storage::disk('public')->assertExists('departments/current.webp');
    }

    public function test_uploading_a_new_image_deletes_the_previous_file(): void
    {
        Storage::fake('public');
        $department = $this->departmentWithImage('departments/previous.webp');

        $this->actingAs($this->admin)
            ->withHeader('Accept', 'application/json')
            ->post(route('master-data.department.update', $department), $this->payload([
                '_method' => 'PUT',
                'code' => $department->code,
                'image' => UploadedFile::fake()->image('new.png', 400, 300),
            ]))
            ->assertOk();

        $newPath = $department->fresh()->image_path;

        $this->assertNotSame('departments/previous.webp', $newPath);
        Storage::disk('public')->assertExists($newPath);
        Storage::disk('public')->assertMissing('departments/previous.webp');
    }

    public function test_removing_the_image_deletes_the_file(): void
    {
        Storage::fake('public');
        $department = $this->departmentWithImage('departments/obsolete.webp');

        $this->actingAs($this->admin)
            ->putJson(route('master-data.department.update', $department), $this->payload([
                'code' => $department->code,
                'remove_image' => 1,
            ]))
            ->assertOk();

        $this->assertNull($department->fresh()->image_path);
        Storage::disk('public')->assertMissing('departments/obsolete.webp');
    }

    public function test_list_shows_the_image_and_sections_and_searches_section_names(): void
    {
        $department = Department::factory()->create(['name' => 'Human Resources', 'image_path' => 'departments/hr.webp']);
        DepartmentSection::factory()->for($department)->create(['name' => 'Culture', 'sort_order' => 1]);
        DepartmentSection::factory()->for($department)->inactive()->create(['name' => 'Payroll', 'sort_order' => 2]);
        Department::factory()->create(['name' => 'Supply Chain']);

        $this->actingAs($this->admin)
            ->getJson(route('master-data.department.list', ['search' => 'payroll']))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.name', 'Human Resources')
            ->assertJsonPath('data.0.image_url', asset('storage/departments/hr.webp'))
            ->assertJsonPath('data.0.sections', [
                ['name' => 'Culture', 'is_active' => true],
                ['name' => 'Payroll', 'is_active' => false],
            ]);
    }

    public function test_department_used_by_questions_cannot_be_deleted(): void
    {
        $department = Department::factory()->create();
        SurveyQuestion::factory()->for($department)->create();

        $this->actingAs($this->admin)
            ->deleteJson(route('master-data.department.destroy', $department))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'This department is used by survey questions. Deactivate it instead of deleting it.');

        $this->assertModelExists($department);
    }

    public function test_deleting_an_unused_department_deletes_its_image(): void
    {
        Storage::fake('public');
        $department = $this->departmentWithImage('departments/unused.webp');

        $this->actingAs($this->admin)
            ->deleteJson(route('master-data.department.destroy', $department))
            ->assertOk();

        $this->assertModelMissing($department);
        Storage::disk('public')->assertMissing('departments/unused.webp');
    }

    public function test_deleting_a_department_deletes_the_pictures_of_its_sections(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('sections/team.webp', 'image');
        Storage::disk('public')->put('sections/work-summary.webp', 'image');
        $section = DepartmentSection::factory()->withOverview()->create();

        $this->actingAs($this->admin)
            ->deleteJson(route('master-data.department.destroy', $section->department_id))
            ->assertOk();

        $this->assertModelMissing($section);
        Storage::disk('public')->assertMissing(['sections/team.webp', 'sections/work-summary.webp']);
    }

    public function test_employee_cannot_manage_departments(): void
    {
        $this->actingAs($this->employee())
            ->postJson(route('master-data.department.store'), $this->payload())
            ->assertForbidden();

        $this->assertDatabaseMissing('departments', ['code' => 'HSE']);
    }

    /**
     * A valid department payload.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function payload(array $overrides = []): array
    {
        return [
            'code' => 'HSE',
            'name' => 'Health, Safety & Environment',
            'description' => 'Safety inductions and permits.',
            'icon' => 'shield-check',
            'color' => 'green',
            'sort_order' => 5,
            'is_active' => 1,
            ...$overrides,
        ];
    }

    /**
     * A department whose image exists on the (faked) public disk.
     */
    protected function departmentWithImage(string $path): Department
    {
        Storage::disk('public')->put($path, 'image');

        return Department::factory()->create(['image_path' => $path]);
    }
}
