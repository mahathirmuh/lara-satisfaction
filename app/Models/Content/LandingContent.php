<?php

namespace App\Models\Content;

use App\Helpers\FileApps;
use App\Models\Survey\Survey;
use Database\Factories\Content\LandingContentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

/**
 * Editable content of the public landing page (a single row).
 */
#[Fillable([
    'hero_title', 'hero_intro', 'hero_image_path', 'hero_image_alt',
    'feedback_title', 'feedback_body', 'feedback_note',
    'closing_title', 'closing_note', 'updated_by',
])]
class LandingContent extends Model
{
    /** @use HasFactory<LandingContentFactory> */
    use HasFactory;

    /**
     * Placeholder replaced by the title of the open survey.
     */
    public const SURVEY_PLACEHOLDER = '{survey}';

    /**
     * Folder of the hero images on the public disk.
     */
    public const IMAGE_DIRECTORY = 'landing';

    /**
     * Image shown until a hero image is uploaded.
     */
    public const DEFAULT_IMAGE = 'assets/img/bg-auth/mdka-bg-1.jpg';

    /**
     * Initial content (from the original satisfaction landing page).
     *
     * @var array<string, string>
     */
    public const DEFAULTS = [
        'hero_title' => 'Your Voice Shapes Our Collective Future',
        'hero_intro' => 'Welcome to the {survey}. Your honest feedback across all departments helps us build a more effective, supportive, and efficient workplace.',
        'hero_image_alt' => 'Merdeka operations site',
        'feedback_title' => 'Why Your Feedback Matters',
        'feedback_body' => 'We believe in radical transparency as a tool for evolution. Every submission is rigorously anonymized to ensure your safety. Data collected is used exclusively for organizational improvement, identifying friction points, and celebrating operational triumphs. Your perspective is the blueprint for our next chapter.',
        'feedback_note' => 'Confidentiality Guaranteed',
        'closing_title' => 'Ready to contribute to the collective audit?',
        'closing_note' => 'Authentication required via employee credentials.',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'updated_by' => 'integer',
        ];
    }

    /**
     * The stored content, or the defaults when nothing was saved yet.
     */
    public static function current(): self
    {
        return static::query()->orderBy('id')->first() ?? new static(self::DEFAULTS);
    }

    /**
     * URL of the hero image (uploaded or default).
     */
    public function heroImageUrl(): string
    {
        return FileApps::url($this->hero_image_path) ?? asset(self::DEFAULT_IMAGE);
    }

    /**
     * Hero introduction with the open survey title in bold; every part is escaped.
     * The default introduction is translated (its translations keep "{survey}"),
     * a saved one has no translation key and is shown as typed.
     */
    public function heroIntroHtml(?Survey $survey): HtmlString
    {
        if ($survey === null) {
            return new HtmlString(e(__('There is no survey open at the moment. Sign in to view your survey history or check back when the next operational audit opens.')));
        }

        $parts = explode(self::SURVEY_PLACEHOLDER, (string) __((string) $this->hero_intro));
        $title = '<strong class="text-reset">'.e($survey->title).'</strong>';

        return new HtmlString(implode($title, array_map(fn (string $part): string => e($part), $parts)));
    }
}
