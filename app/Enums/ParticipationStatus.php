<?php

namespace App\Enums;

/**
 * Where a person stands with a survey: Not Started -> In Progress (opened the
 * form, possibly with a saved draft) -> Completed. Only the filling status:
 * answers stay anonymous and are never linked to a person.
 */
enum ParticipationStatus: string
{
    case NotStarted = 'not_started';
    case InProgress = 'in_progress';
    case Completed = 'completed';

    /**
     * The status from what is recorded for the person.
     */
    public static function resolve(bool $completed, bool $savedDraft, bool $started): self
    {
        return match (true) {
            $completed => self::Completed,
            $savedDraft, $started => self::InProgress,
            default => self::NotStarted,
        };
    }

    /**
     * Human readable label, in the interface language.
     */
    public function label(): string
    {
        return match ($this) {
            self::NotStarted => __('Not Started'),
            self::InProgress => __('In Progress'),
            self::Completed => __('Completed'),
        };
    }

    /**
     * Badge colour.
     */
    public function color(): string
    {
        return match ($this) {
            self::NotStarted => 'yellow',
            self::InProgress => 'azure',
            self::Completed => 'green',
        };
    }

    /**
     * Position in the flow, from 1.
     */
    public function step(): int
    {
        return array_search($this, self::cases(), true) + 1;
    }

    /**
     * Options for select inputs.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status): array => [$status->value => $status->label()])
            ->all();
    }
}
