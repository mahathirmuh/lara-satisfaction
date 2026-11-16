<?php

namespace Tests\Unit;

use App\Helpers\JobTitles;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class JobTitlesTest extends TestCase
{
    /**
     * The Manager rules applied to job titles found in the directory.
     *
     * @return array<string, array{0: string|null, 1: bool}>
     */
    public static function managerTitles(): array
    {
        return [
            'department manager' => ['OHS Manager', true],
            'other letter case' => ['Procurement manager', true],
            'deputy and senior managers' => ['Pyrite Plant Deputy Manager', true],
            'general manager' => ['AIM Operation General Manager', true],
            'deputy GM without the word manager' => ['Deputy GM', true],
            'assistant of the general manager' => ['Assistant of General Manager', false],
            'general manager assistant' => ['General Manager Assistant', false],
            'superintendent' => ['OHS Superintendent', false],
            'officer' => ['IT Support Officer', false],
            'extra spaces' => ['  Finance   Manager ', true],
            'no job title' => [null, false],
        ];
    }

    #[DataProvider('managerTitles')]
    public function test_manager_rules_cover_manager_titles_only(?string $jobTitle, bool $matches): void
    {
        $this->assertSame($matches, JobTitles::matches(['*Manager', 'Deputy GM', '!Assistant of *'], $jobTitle));
    }

    public function test_an_exclusion_wins_whatever_its_position(): void
    {
        $this->assertFalse(JobTitles::matches(['!Assistant of *', '*Manager'], 'Assistant of General Manager'));
    }

    public function test_a_rule_without_wildcard_matches_the_whole_title_only(): void
    {
        $this->assertTrue(JobTitles::matches(['Support Services Deputy General Manager'], 'support services deputy general manager'));
        $this->assertFalse(JobTitles::matches(['Finance Manager'], 'Finance Manager Assistant'));
    }

    public function test_rules_are_parsed_one_per_line_without_blanks_or_duplicates(): void
    {
        $this->assertSame(
            ['*Manager', 'Deputy GM', '!Assistant of *'],
            JobTitles::parse("*Manager\r\n\n  Deputy   GM \n!Assistant of *\n*Manager\n!\n"),
        );
    }
}
