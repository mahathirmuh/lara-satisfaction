<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The interface translations (lang/{locale}.json, English text as key) stay
 * complete and usable: every language has every text, none is empty, no
 * :placeholder is lost, and the interface actually switches.
 */
class TranslationFilesTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_language_translates_the_same_texts(): void
    {
        $keys = array_map(fn (string $locale): array => array_keys($this->translations($locale)), $this->translatedLocales());

        $this->assertNotEmpty($keys[0]);

        foreach (array_slice($keys, 1) as $otherKeys) {
            $this->assertSame([], array_values(array_diff($keys[0], $otherKeys)), 'Texts missing in a language.');
            $this->assertSame([], array_values(array_diff($otherKeys, $keys[0])), 'Texts missing in a language.');
        }
    }

    public function test_translations_are_filled_and_keep_their_placeholders(): void
    {
        foreach ($this->translatedLocales() as $locale) {
            foreach ($this->translations($locale) as $english => $translation) {
                $this->assertNotSame('', trim($translation), "[{$locale}] empty translation of \"{$english}\".");

                preg_match_all('/:([A-Za-z_]+)/', $english, $expected);
                preg_match_all('/:([A-Za-z_]+)/', $translation, $actual);

                $this->assertSame([], array_values(array_diff($expected[1], $actual[1])), "[{$locale}] placeholder lost in \"{$english}\".");
            }
        }
    }

    public function test_validation_messages_exist_in_every_language(): void
    {
        foreach ($this->translatedLocales() as $locale) {
            app()->setLocale($locale);

            $this->assertNotSame('validation.required', __('validation.required', ['attribute' => 'email']));
            $this->assertStringContainsString('email', __('validation.required', ['attribute' => 'email']));
        }
    }

    public function test_the_login_page_switches_language(): void
    {
        $this->post(route('locale.update'), ['locale' => 'id']);

        $this->get(route('login'))->assertOk()
            ->assertSee($this->translations('id')['Sign in with Network Account (LDAP)'])
            ->assertSee('aria-pressed="true">ID</button>', false);
    }

    /**
     * Languages other than English (the source language).
     *
     * @return array<int, string>
     */
    protected function translatedLocales(): array
    {
        return array_values(array_diff(array_keys(config('app.available_locales')), [config('app.fallback_locale')]));
    }

    /**
     * @return array<string, string>
     */
    protected function translations(string $locale): array
    {
        return json_decode((string) file_get_contents(lang_path($locale.'.json')), true, flags: JSON_THROW_ON_ERROR);
    }
}
