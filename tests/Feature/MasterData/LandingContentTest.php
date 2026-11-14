<?php

namespace Tests\Feature\MasterData;

use App\Models\Content\LandingContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LandingContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_form_shows_the_original_texts_until_content_is_saved(): void
    {
        $this->actingAs($this->surveyAdmin())
            ->get(route('master-data.landing-content.index'))
            ->assertOk()
            ->assertSee('Your Voice Shapes Our Collective Future')
            ->assertSee('Ready to contribute to the collective audit?');

        $this->assertDatabaseCount('landing_contents', 0);
    }

    public function test_admin_saves_the_landing_texts(): void
    {
        $admin = $this->surveyAdmin();

        $this->actingAs($admin)
            ->put(route('master-data.landing-content.update'), $this->payload())
            ->assertRedirect(route('master-data.landing-content.index'))
            ->assertSessionHas('alert.title', 'Landing page updated');

        $this->assertDatabaseHas('landing_contents', [
            'hero_title' => 'Tell Us How We Are Doing',
            'feedback_title' => 'Your Opinion Counts',
            'closing_title' => 'Start now',
            'hero_image_path' => null,
            'updated_by' => $admin->id,
        ]);
    }

    public function test_main_texts_are_required(): void
    {
        $this->actingAs($this->surveyAdmin())
            ->put(route('master-data.landing-content.update'), [])
            ->assertSessionHasErrors([
                'hero_title' => 'The headline field is required.',
                'hero_intro' => 'The introduction field is required.',
                'feedback_title' => 'The feedback title field is required.',
                'feedback_body' => 'The feedback text field is required.',
                'closing_title' => 'The closing title field is required.',
            ]);

        $this->assertDatabaseCount('landing_contents', 0);
    }

    public function test_uploaded_hero_image_replaces_the_previous_one(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('landing/previous.webp', 'image');
        LandingContent::factory()->withHeroImage('landing/previous.webp')->create();

        $this->actingAs($this->surveyAdmin())
            ->put(route('master-data.landing-content.update'), $this->payload([
                'hero_image' => UploadedFile::fake()->image('site.jpg', 1600, 900),
            ]))
            ->assertRedirect(route('master-data.landing-content.index'));

        $imagePath = LandingContent::query()->sole()->hero_image_path;

        $this->assertMatchesRegularExpression('#^landing/[A-Za-z0-9]{40}\.webp$#', $imagePath);
        Storage::disk('public')->assertExists($imagePath);
        Storage::disk('public')->assertMissing('landing/previous.webp');
    }

    public function test_removing_the_hero_image_restores_the_default_image(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('landing/custom.webp', 'image');
        LandingContent::factory()->withHeroImage('landing/custom.webp')->create();

        $this->actingAs($this->surveyAdmin())
            ->put(route('master-data.landing-content.update'), $this->payload(['remove_hero_image' => 1]))
            ->assertRedirect(route('master-data.landing-content.index'));

        $this->assertNull(LandingContent::query()->sole()->hero_image_path);
        Storage::disk('public')->assertMissing('landing/custom.webp');

        $this->get(route('landing'))->assertSee(asset(LandingContent::DEFAULT_IMAGE));
    }

    public function test_hero_image_must_be_a_raster_image(): void
    {
        Storage::fake('public');

        $this->actingAs($this->surveyAdmin())
            ->put(route('master-data.landing-content.update'), $this->payload([
                'hero_image' => UploadedFile::fake()->create('brochure.pdf', 100, 'application/pdf'),
            ]))
            ->assertSessionHasErrors(['hero_image' => 'The hero image field must be an image.']);

        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_employee_cannot_change_the_landing_page(): void
    {
        $this->actingAs($this->employee())
            ->put(route('master-data.landing-content.update'), $this->payload())
            ->assertForbidden();

        $this->assertDatabaseCount('landing_contents', 0);
    }

    /**
     * A valid landing content payload.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function payload(array $overrides = []): array
    {
        return [
            'hero_title' => 'Tell Us How We Are Doing',
            'hero_intro' => 'Welcome to the {survey}.',
            'hero_image_alt' => 'Processing plant at dawn',
            'feedback_title' => 'Your Opinion Counts',
            'feedback_body' => 'Every answer is anonymous.',
            'feedback_note' => 'Anonymous by design',
            'closing_title' => 'Start now',
            'closing_note' => 'Sign in with your employee account.',
            ...$overrides,
        ];
    }
}
