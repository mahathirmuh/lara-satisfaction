<?php

namespace App\Enums;

enum QuestionType: string
{
    /**
     * Service quality rating (1-5), the satisfaction measure.
     */
    case Rating = 'rating';

    /**
     * How critical the service is for the respondent's department (1-5).
     */
    case Criticality = 'criticality';

    case Text = 'text';

    /**
     * Human readable label, in the interface language.
     */
    public function label(): string
    {
        return match ($this) {
            self::Rating => __('Service Quality (1-5)'),
            self::Criticality => __('Criticality (1-5)'),
            self::Text => __('Open Text'),
        };
    }

    /**
     * Whether the answer is a 1-5 rating.
     */
    public function isRating(): bool
    {
        return $this !== self::Text;
    }

    /**
     * Whether ratings count towards the satisfaction scores (average, CSAT).
     */
    public function measuresSatisfaction(): bool
    {
        return $this === self::Rating;
    }

    /**
     * The rating scale (title and the label and description of every level),
     * in the interface language.
     *
     * @return array{title: string, levels: array<int, array{label: string, description: string}>}|null
     */
    public function scale(): ?array
    {
        $scale = match ($this) {
            self::Rating => config('survey.scales.quality'),
            self::Criticality => config('survey.scales.criticality'),
            self::Text => null,
        };

        if ($scale === null) {
            return null;
        }

        return [
            ...$scale,
            'title' => __($scale['title']),
            'levels' => array_map(fn (array $level): array => [
                ...$level,
                'label' => __($level['label']),
                'description' => __($level['description']),
            ], $scale['levels']),
        ];
    }

    /**
     * Label of every rating level, e.g. [1 => 'Very Poor', ..., 5 => 'Excellent'],
     * in the interface language.
     *
     * @return array<int, string>
     */
    public function scaleLabels(): array
    {
        return array_map(fn (array $level): string => $level['label'], $this->scale()['levels'] ?? []);
    }

    /**
     * Options for select inputs.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type): array => [$type->value => $type->label()])
            ->all();
    }
}
