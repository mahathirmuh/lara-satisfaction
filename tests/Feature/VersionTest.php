<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The version of the application (README, "Versions"): one number in Semantic Versioning, kept in the file VERSION,
 * with its part on top of CHANGELOG.md, and the same wherever it is shown.
 */
class VersionTest extends TestCase
{
    /**
     * The regular expression semver.org suggests for Semantic Versioning 2.0.0.
     */
    private const SEMVER = '/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)'
        .'(?:-((?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?'
        .'(?:\+([0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$/';

    public function test_the_version_is_one_semantic_version_in_the_file_version(): void
    {
        $this->assertFileExists(base_path('VERSION'));
        $this->assertMatchesRegularExpression(self::SEMVER, $this->version());
        // The number and a line break, nothing else: a script may read it as it is.
        $this->assertSame($this->version()."\n", str_replace("\r\n", "\n", (string) file_get_contents(base_path('VERSION'))));
        $this->assertSame($this->version(), config('merdeka.app_version'));
    }

    public function test_the_changelog_starts_with_the_version_and_has_every_version_once_newest_first(): void
    {
        $heading = '/^## \[([^\]]+)\] - (\d{4}-\d{2}-\d{2})$/m';
        preg_match_all($heading, $this->changelog(), $parts, PREG_SET_ORDER);
        $bodies = array_slice(preg_split($heading, $this->changelog()), 1);

        $this->assertNotEmpty($parts);
        $this->assertSame($this->version(), $parts[0][1], 'CHANGELOG.md starts with the part of the version in VERSION');

        $versions = array_column($parts, 1);
        $this->assertSame(array_values(array_unique($versions)), $versions, 'each version has one part');

        foreach ($parts as $index => [, $version, $date]) {
            $this->assertMatchesRegularExpression(self::SEMVER, $version);
            $this->assertSame($date, date('Y-m-d', strtotime($date)), "the day of {$version}");
            // Each part says what to do on the server.
            $this->assertStringContainsString('**Deploy:**', $bodies[$index], "the part of {$version}");

            if ($index > 0) {
                $this->assertTrue(version_compare($versions[$index - 1], $version, '>'), "{$versions[$index - 1]} comes above {$version}");
                $this->assertGreaterThanOrEqual($date, $parts[$index - 1][2], "{$versions[$index - 1]} is not older than {$version}");
            }
        }
    }

    public function test_the_version_is_shown_in_the_footer(): void
    {
        $this->assertStringContainsString('>v'.$this->version().'</button>', view('main.footer.footer')->render());

        // No file VERSION and no setting: no button at all rather than a "v" alone.
        config(['merdeka.app_version' => null]);

        $this->assertStringNotContainsString('rmVersion', view('main.footer.footer')->render());
    }

    public function test_the_docker_image_gets_the_version_and_the_changelog(): void
    {
        $ignored = array_filter(array_map('trim', file(base_path('.dockerignore'))), fn (string $line): bool => $line !== '' && ! str_starts_with($line, '#'));

        foreach (['VERSION', 'CHANGELOG.md', 'CHANGELOG.en.md'] as $file) {
            foreach ($ignored as $pattern) {
                $this->assertFalse(fnmatch($pattern, $file), "{$pattern} in .dockerignore keeps {$file} out of the image");
            }
        }
    }

    private function version(): string
    {
        return trim((string) file_get_contents(base_path('VERSION')));
    }

    private function changelog(): string
    {
        return str_replace("\r\n", "\n", (string) file_get_contents(base_path('CHANGELOG.md')));
    }
}
