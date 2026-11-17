<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

/**
 * The user menu opens the user guide (the PDF in docs/) in the language of the page.
 */
class UserGuideTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['id', 'User-Guide-Employee-Satisfaction-Survey-ID.pdf'])]
    #[TestWith(['en', 'User-Guide-Employee-Satisfaction-Survey-EN.pdf'])]
    #[TestWith(['zh_CN', 'User-Guide-Employee-Satisfaction-Survey-EN.pdf'])]
    public function test_the_guide_opens_in_the_language_of_the_page(string $locale, string $guide): void
    {
        $response = $this->actingAs($this->employee(['locale' => $locale]))->get(route('user-guide'));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'inline; filename="'.$guide.'"');
        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);
        $this->assertSame(realpath(base_path('docs/'.$guide)), $response->baseResponse->getFile()->getRealPath());
    }

    public function test_the_user_menu_and_the_phone_menu_link_to_the_guide(): void
    {
        $page = $this->actingAs($this->employee())->get(route('profile.show'))->assertOk();

        $this->assertSame(2, substr_count($page->getContent(), 'href="'.route('user-guide').'"'));
        $page->assertSeeInOrder(['My Profile', 'User Guide', 'Logout']);
    }

    public function test_guests_sign_in_first(): void
    {
        $this->get(route('user-guide'))->assertRedirect(route('login'));
    }
}
