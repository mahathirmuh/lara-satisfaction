<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

/**
 * The user menu opens the user guide (the PDF in docs/) in the language of the page: English, Bahasa Indonesia or
 * Simplified Chinese, each at its own address.
 */
class UserGuideTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['id', 'User-Guide-Employee-Satisfaction-Survey-ID.pdf'])]
    #[TestWith(['en', 'User-Guide-Employee-Satisfaction-Survey-EN.pdf'])]
    #[TestWith(['zh_CN', 'User-Guide-Employee-Satisfaction-Survey-ZH.pdf'])]
    public function test_the_menu_opens_the_guide_in_the_language_of_the_page(string $locale, string $guide): void
    {
        $employee = $this->employee(['locale' => $locale]);
        $link = route('user-guide', ['language' => $locale]);

        $this->actingAs($employee)->get(route('profile.show'))->assertSee('href="'.$link.'"', false);

        $response = $this->actingAs($employee)->get($link);

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'inline; filename="'.$guide.'"');
        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);
        $this->assertSame(realpath(base_path('docs/'.$guide)), $response->baseResponse->getFile()->getRealPath());
    }

    public function test_without_a_language_in_the_address_the_guide_follows_the_page(): void
    {
        $this->actingAs($this->employee(['locale' => 'zh_CN']))
            ->get(route('user-guide'))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'inline; filename="User-Guide-Employee-Satisfaction-Survey-ZH.pdf"');
    }

    public function test_the_browser_checks_the_guide_with_the_server_every_time(): void
    {
        $cacheControl = (string) $this->actingAs($this->employee())
            ->get(route('user-guide', ['language' => 'en']))
            ->headers->get('Cache-Control');

        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('private', $cacheControl);
    }

    public function test_a_language_without_a_guide_is_not_found(): void
    {
        $this->actingAs($this->employee())->get('/user-guide/fr')->assertNotFound();
    }

    public function test_the_user_menu_and_the_phone_menu_link_to_the_guide(): void
    {
        $page = $this->actingAs($this->employee(['locale' => 'en']))->get(route('profile.show'))->assertOk();

        $this->assertSame(2, substr_count($page->getContent(), 'href="'.route('user-guide', ['language' => 'en']).'"'));
        $page->assertSeeInOrder(['My Profile', 'User Guide', 'Logout']);
    }

    public function test_guests_sign_in_first(): void
    {
        $this->get(route('user-guide', ['language' => 'en']))->assertRedirect(route('login'));
    }
}
