<?php

namespace Tests\Feature;

use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_switches_the_language_for_the_session(): void
    {
        $this->from(route('login'))
            ->post(route('locale.update'), ['locale' => 'id'])
            ->assertRedirect(route('login'))
            ->assertSessionHas(SetLocale::SESSION_KEY, 'id');

        $this->get(route('login'))->assertOk()->assertSee('<html lang="id">', false);
    }

    public function test_signed_in_user_keeps_the_chosen_language_on_their_account(): void
    {
        $employee = $this->employee();

        $this->actingAs($employee)
            ->from(route('survey-form.index'))
            ->post(route('locale.update'), ['locale' => 'zh_CN'])
            ->assertRedirect(route('survey-form.index'));

        $this->assertSame('zh_CN', $employee->fresh()->locale);
        $this->assertSame('zh_CN', $employee->fresh()->preferredLocale());

        $this->flushSession();

        $this->actingAs($employee->fresh())
            ->get(route('survey-form.index'))
            ->assertOk()
            ->assertSee('<html lang="zh-CN">', false);
    }

    public function test_language_stays_after_signing_out(): void
    {
        $employee = $this->employee(['locale' => 'zh_CN']);

        $this->actingAs($employee)->post(route('logout'))->assertRedirect(route('login'));

        $this->assertGuest();
        $this->get(route('login'))->assertSee('<html lang="zh-CN">', false);
    }

    public function test_unavailable_language_is_refused(): void
    {
        $this->from(route('login'))
            ->post(route('locale.update'), ['locale' => 'fr'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('locale')
            ->assertSessionMissing(SetLocale::SESSION_KEY);
    }

    /**
     * Browser Accept-Language header => interface language.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function browserLanguages(): array
    {
        return [
            'simplified chinese' => ['zh-CN,zh;q=0.9,en;q=0.8', 'zh-CN'],
            'indonesian' => ['id-ID,id;q=0.9', 'id'],
            'unavailable language' => ['fr-FR,fr;q=0.9', 'en'],
        ];
    }

    #[DataProvider('browserLanguages')]
    public function test_browser_language_is_used_until_one_is_chosen(string $acceptLanguage, string $htmlLang): void
    {
        $this->withHeader('Accept-Language', $acceptLanguage)
            ->get(route('login'))
            ->assertOk()
            ->assertSee('<html lang="'.$htmlLang.'">', false);
    }

    public function test_chosen_language_wins_over_the_browser_language(): void
    {
        $this->post(route('locale.update'), ['locale' => 'id']);

        $this->withHeader('Accept-Language', 'zh-CN,zh;q=0.9')
            ->get(route('login'))
            ->assertSee('<html lang="id">', false);
    }
}
