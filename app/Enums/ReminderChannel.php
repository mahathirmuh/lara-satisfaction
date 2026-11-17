<?php

namespace App\Enums;

/**
 * How a survey reminder reaches people: the notification in the application (the bell in the top bar) and the e-mail
 * (from MAIL_FROM_ADDRESS). A reminder sent by hand goes by the ways chosen; the automatic one goes both ways.
 */
enum ReminderChannel: string
{
    case App = 'app';
    case Email = 'email';

    /**
     * Human readable label, in the interface language.
     */
    public function label(): string
    {
        return match ($this) {
            self::App => __('Notification in the application'),
            self::Email => __('E-mail'),
        };
    }

    /**
     * The name the audit trail records, in English like its other values.
     */
    public function auditLabel(): string
    {
        return match ($this) {
            self::App => 'Notification in the application',
            self::Email => 'E-mail',
        };
    }

    /**
     * The Laravel notification channel that delivers it.
     */
    public function notificationChannel(): string
    {
        return match ($this) {
            self::App => 'database',
            self::Email => 'mail',
        };
    }

    /**
     * The notification channels of the given ways, in the order of the cases (the notification first, then the
     * e-mail).
     *
     * @param  array<int, self>  $channels
     * @return array<int, string>
     */
    public static function notificationChannels(array $channels): array
    {
        return array_values(array_map(
            fn (self $channel): string => $channel->notificationChannel(),
            array_filter(self::cases(), fn (self $channel): bool => in_array($channel, $channels, true)),
        ));
    }

    /**
     * Options for check boxes.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $channel): array => [$channel->value => $channel->label()])
            ->all();
    }
}
