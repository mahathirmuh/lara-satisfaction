<?php

namespace Tests\Feature;

use App\Services\Changelog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The version in the footer opens what changed in each version (CHANGELOG.md): for everyone signed in, whatever their
 * role, every version of the file newest first, without the notes for the server, in the language of the page (the
 * English notes are in CHANGELOG.en.md, which Chinese reads too).
 */
class ChangelogDialogTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_who_is_signed_in_reads_it(): void
    {
        $this->getJson(route('changelog'))->assertUnauthorized();
        $this->get(route('changelog'))->assertRedirect(route('login'));
    }

    public function test_every_version_of_the_file_newest_first_and_the_running_one_marked(): void
    {
        $answer = $this->actingAs($this->employee(['locale' => 'id']))->getJson(route('changelog'))->assertOk()->json();

        preg_match_all('/^## \[(\d+\.\d+\.\d+)\]/m', (string) file_get_contents(base_path('CHANGELOG.md')), $versions);
        $this->assertSame($versions[1], array_column($answer['releases'], 'version'));
        $this->assertSame(config('merdeka.app_version'), $answer['version']);
        $this->assertSame(config('merdeka.app_version'), $answer['releases'][0]['version']);
        $this->assertSame([0], array_keys(array_filter(array_column($answer['releases'], 'current'))));
        $this->assertSame(['id'], array_values(array_unique(array_column($answer['releases'], 'language'))));

        foreach ($answer['releases'] as $release) {
            $this->assertMatchesRegularExpression('/^\d{2} \p{L}{3} \d{4}$/u', (string) $release['date_label'], $release['version']);
            $this->assertNotSame([], $release['sections'], $release['version']);

            foreach ($release['sections'] as $section) {
                $this->assertSame(['heading', 'kind', 'html'], array_keys($section), $release['version']);
                $this->assertContains($section['kind'], ['added', 'changed', 'fixed', 'removed', 'security', 'deprecated'], $release['version']);
                $this->assertNotSame('', $section['html'], $release['version']);
                $this->assertStringNotContainsString('Deploy:', $section['html'], $release['version']);
                $this->assertStringNotContainsString('<a ', $section['html'], $release['version']);
            }
        }

        // Every heading of the file is a kind the dialog colours: a new heading would come out grey.
        $headings = array_merge(...array_map(fn (array $release): array => array_column($release['sections'], 'heading'), $answer['releases']));
        $this->assertSame([], array_values(array_filter($headings, fn (string $heading): bool => ! isset(Changelog::KINDS[mb_strtolower($heading)]))));
    }

    public function test_english_and_chinese_read_the_english_notes_of_every_version(): void
    {
        $indonesian = $this->actingAs($this->employee(['locale' => 'id']))->getJson(route('changelog'))->assertOk()->json('releases');

        foreach (['en', 'zh_CN'] as $locale) {
            $releases = $this->actingAs($this->employee(['locale' => $locale]))->getJson(route('changelog'))->assertOk()->json('releases');

            // CHANGELOG.en.md has every version of CHANGELOG.md with the same kinds of change: a release written in one file fails here.
            $this->assertSame(array_column($indonesian, 'version'), array_column($releases, 'version'));

            foreach ($releases as $index => $release) {
                $this->assertSame('en', $release['language'], $release['version'].' has no notes in CHANGELOG.en.md');
                $this->assertSame(array_column($indonesian[$index]['sections'], 'kind'), array_column($release['sections'], 'kind'), $release['version']);
                $this->assertSame($indonesian[$index]['date'], $release['date'], $release['version']);

                foreach ($release['sections'] as $section) {
                    $this->assertStringNotContainsString('Deploy:', $section['html'], $release['version']);
                    $this->assertStringNotContainsString('<a ', $section['html'], $release['version']);
                }
            }
        }

        // Nor does CHANGELOG.en.md have a version that CHANGELOG.md has not (a typo in a number would go unseen).
        preg_match_all('/^## \[([^\]\n]+)\]/m', (string) file_get_contents(base_path('CHANGELOG.en.md')), $versions);
        $this->assertSame(array_column($indonesian, 'version'), $versions[1]);
    }

    public function test_the_notes_leave_out_the_lines_for_the_server_and_keep_the_words_of_links(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'changelog').'.md';
        file_put_contents($file, implode("\n", [
            '# Changelog', '', 'Intro.', '',
            '## [Unreleased]', '', '### Ditambahkan', '', '- Not yet.', '',
            '## [1.1.0] - 2026-11-16', '', '### Ditambahkan', '', '- A [page](https://example.com) <b>here</b>.', '',
            '### Lainnya', '', '- Something else.', '',
            '**Deploy:** `git pull`.', '',
            '## [1.0.0] - 2026-11-14', '', '### Diperbaiki', '', '- First.', '',
            '[1.1.0]: https://example.com/compare',
        ]));

        $releases = (new Changelog($file))->releases();
        unlink($file);

        $this->assertSame(['1.1.0', '1.0.0'], array_column($releases, 'version'));
        $this->assertSame([
            ['heading' => 'Ditambahkan', 'kind' => 'added', 'html' => "<ul>\n<li>A page here.</li>\n</ul>"],
            ['heading' => 'Lainnya', 'kind' => null, 'html' => "<ul>\n<li>Something else.</li>\n</ul>"],
        ], $releases[0]['sections']);
        $this->assertSame('fixed', $releases[1]['sections'][0]['kind']);
    }

    public function test_the_footer_has_the_version_as_a_button_and_the_dialog_it_opens(): void
    {
        $page = (string) $this->actingAs($this->employee(['locale' => 'id']))->get(route('dashboard.index'))->assertOk()->getContent();

        $this->assertStringContainsString('<button type="button" class="rm-version" id="rmVersion"', $page);
        $this->assertStringContainsString('>v'.config('merdeka.app_version').'</button>', $page);
        $this->assertSame(1, substr_count($page, 'id="rmChangelog"'));
        $this->assertStringContainsString('<p class="rm-dialog-sub">Versi yang berjalan: v'.config('merdeka.app_version').'</p>', $page);
        $this->assertStringContainsString("url: '".str_replace('/', '\/', route('changelog'))."'", $page);
    }
}
