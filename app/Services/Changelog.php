<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * What changed in each version, as CHANGELOG.md tells it: the dialog that the version in the footer opens (as in MTI
 * Radio Management). Only the changes are shown, by the kind of change each heading of a version names
 * ("### Ditambahkan"). The opening of the file, the lines for whoever runs the server ("**Deploy:** ...") and the links
 * of the headings stay in the file; a link in an entry keeps its words. The file is written in Indonesian; the same
 * notes in English are in CHANGELOG.en.md beside it, which a language without notes of its own (Chinese) reads too.
 */
final class Changelog
{
    /**
     * The language of the file. Its notes in another language are in CHANGELOG.<language>.md beside it.
     */
    public const LANGUAGE = 'id';

    /**
     * The language whose notes are shown when a language has none of its own.
     */
    public const FALLBACK_LANGUAGE = 'en';

    /**
     * The kind of change a heading names (Keep a Changelog, in Indonesian as the file writes it, or in English).
     *
     * @var array<string, string>
     */
    public const KINDS = [
        'ditambahkan' => 'added',
        'added' => 'added',
        'diubah' => 'changed',
        'changed' => 'changed',
        'diperbaiki' => 'fixed',
        'fixed' => 'fixed',
        'dihapus' => 'removed',
        'removed' => 'removed',
        'keamanan' => 'security',
        'security' => 'security',
        'ditinggalkan' => 'deprecated',
        'usang' => 'deprecated',
        'deprecated' => 'deprecated',
    ];

    /**
     * Create a new class instance.
     */
    public function __construct(private readonly string $file) {}

    /**
     * The changelog of this application.
     */
    public static function ofApp(): self
    {
        return new self(base_path('CHANGELOG.md'));
    }

    /**
     * The versions, newest first (the order of the file). "Unreleased" and anything else that is no version number is
     * left out: it has not reached a server yet. The sections are the changes by their heading, in the order of the
     * file; their kind is added, changed, fixed, removed, security, deprecated, or null for a heading of another kind
     * (and for notes before the first heading); their html is the notes as markup, any HTML in the file stripped.
     *
     * @return list<array{version: string, date: ?string, sections: list<array{heading: ?string, kind: ?string, html: string}>}>
     */
    public function releases(): array
    {
        if (! is_file($this->file)) {
            return [];
        }

        $text = str_replace("\r\n", "\n", (string) file_get_contents($this->file));
        preg_match_all('/^## \[([^\]\n]+)\][^\n]*$/m', $text, $headings, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        $releases = [];

        foreach ($headings as $index => [$line, $version]) {
            $start = $line[1] + strlen($line[0]);
            $end = isset($headings[$index + 1]) ? $headings[$index + 1][0][1] : strlen($text);

            if (! preg_match('/^\d+\.\d+\.\d+$/', $version[0])) {
                continue;
            }

            $releases[] = [
                'version' => $version[0],
                'date' => preg_match('/\b(\d{4}-\d{2}-\d{2})\b/', $line[0], $date) ? $date[1] : null,
                'sections' => $this->sections(substr($text, $start, $end - $start)),
            ];
        }

        return $releases;
    }

    /**
     * The versions with their notes in a language: for each version, the notes of the file in that language when it
     * has the version, else those in English, else those of this file. The versions and their dates are always this
     * file's; "language" is the language the notes of a version are written in.
     *
     * @return list<array{version: string, date: ?string, sections: list<array{heading: ?string, kind: ?string, html: string}>, language: string}>
     */
    public function releasesIn(string $language): array
    {
        $translations = [];

        // The language of the file reads the file; another reads its own notes, else the English ones.
        foreach ($language === self::LANGUAGE ? [] : array_unique([$language, self::FALLBACK_LANGUAGE]) as $candidate) {
            if ($candidate !== self::LANGUAGE && preg_match('/^[a-z]{2}(_[A-Z]{2})?$/', $candidate)) {
                $translations[$candidate] = array_column($this->translation($candidate)->releases(), 'sections', 'version');
            }
        }

        return array_map(function (array $release) use ($translations): array {
            foreach ($translations as $language => $sections) {
                if (isset($sections[$release['version']])) {
                    return array_replace($release, ['sections' => $sections[$release['version']]]) + ['language' => $language];
                }
            }

            return $release + ['language' => self::LANGUAGE];
        }, $this->releases());
    }

    /**
     * The file beside this one with the notes in a language: CHANGELOG.en.md.
     */
    private function translation(string $language): self
    {
        return new self(preg_replace('/\.md$/', '', $this->file).".{$language}.md");
    }

    /**
     * The changes of a version by their heading ("### Ditambahkan"): the kind of change, the notes as markup. A heading
     * without notes is left out.
     *
     * @return list<array{heading: ?string, kind: ?string, html: string}>
     */
    private function sections(string $body): array
    {
        $parts = preg_split('/^###[ \t]+(.+?)[ \t]*$/m', $this->changes($body), -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];

        $sections = [];
        $lead = $this->html((string) array_shift($parts));

        if ($lead !== '') {
            $sections[] = ['heading' => null, 'kind' => null, 'html' => $lead];
        }

        foreach (array_chunk($parts, 2) as $pair) {
            [$heading, $notes] = $pair + [1 => ''];
            $heading = trim(strip_tags((string) $heading));
            $html = $this->html((string) $notes);

            if ($heading !== '' && $html !== '') {
                $sections[] = ['heading' => $heading, 'kind' => self::KINDS[Str::lower($heading)] ?? null, 'html' => $html];
            }
        }

        return $sections;
    }

    /**
     * The body of a version without what is not a change: the links of the headings, the note for the server, the links.
     */
    private function changes(string $body): string
    {
        // The links of the headings ("[1.10.13]: https://...") are defined at the end of the file, after the last version.
        $body = preg_replace('/^\[[^\]\n]+\]:[ \t]*\S+[ \t]*$/m', '', $body);
        // The note for the server: from "**Deploy:**" to the end of its paragraph.
        $body = preg_replace('/^\*\*Deploy:\*\*.*?(?=\n[ \t]*\n|\z)/ms', '', (string) $body);

        return (string) preg_replace('/\[([^\]\n]+)\]\([^)\n]*\)/', '$1', (string) $body);
    }

    /**
     * Markdown as markup, any HTML in it stripped and no unsafe link kept.
     */
    private function html(string $markdown): string
    {
        return trim(Str::markdown(trim($markdown), ['html_input' => 'strip', 'allow_unsafe_links' => false]));
    }
}
