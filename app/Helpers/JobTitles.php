<?php

namespace App\Helpers;

/**
 * Job title rules, used to give roles automatically and to find the managers
 * of a department. One rule per job title, compared without letter case:
 *  - "OHS Manager" matches that title exactly;
 *  - "*" stands for any text: "*Manager" matches every title ending in Manager;
 *  - a leading "!" excludes: "!Assistant of *" wins over any other rule.
 */
class JobTitles
{
    /**
     * Whether a job title is matched by the rules (and excluded by none).
     *
     * @param  array<int, string>  $rules
     */
    public static function matches(array $rules, ?string $jobTitle): bool
    {
        $title = self::normalize($jobTitle);

        if ($title === '') {
            return false;
        }

        $isIncluded = false;

        foreach ($rules as $rule) {
            $rule = self::normalize($rule);
            $isExclusion = str_starts_with($rule, '!');
            $pattern = $isExclusion ? ltrim(substr($rule, 1)) : $rule;

            if ($pattern === '' || ! self::fits($pattern, $title)) {
                continue;
            }

            if ($isExclusion) {
                return false;
            }

            $isIncluded = true;
        }

        return $isIncluded;
    }

    /**
     * Rules from a text with one rule per line (blank lines and duplicates dropped).
     *
     * @return array<int, string>
     */
    public static function parse(?string $text): array
    {
        $rules = array_map(self::normalize(...), preg_split('/\R/', (string) $text) ?: []);

        return array_values(array_unique(array_filter($rules, fn (string $rule): bool => $rule !== '' && $rule !== '!')));
    }

    /**
     * A job title or rule without surrounding or repeated spaces.
     */
    public static function normalize(?string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', (string) $value));
    }

    /**
     * Whether a pattern ("*" = any text) covers the whole title, ignoring case.
     */
    protected static function fits(string $pattern, string $title): bool
    {
        $regex = '/^'.str_replace('\*', '.*', preg_quote($pattern, '/')).'$/iu';

        return preg_match($regex, $title) === 1;
    }
}
