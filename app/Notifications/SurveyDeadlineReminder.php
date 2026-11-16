<?php

namespace App\Notifications;

use App\Models\Survey\Survey;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Reminds an employee who has not completed an open survey that its end
 * date is near (in-app notification and email). The texts are written in the
 * recipient's language (User::preferredLocale()).
 */
class SurveyDeadlineReminder extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Survey $survey, public int $daysLeft) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $replace = $this->replacements();

        return (new MailMessage)
            ->subject($this->forDeadline(
                __('Reminder: :survey closes today', $replace),
                __('Reminder: :survey closes tomorrow', $replace),
                __('Reminder: :survey closes in :days days', $replace),
            ))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line($this->forDeadline(
                __('The :survey closes today (:date) and you have not completed it yet.', $replace),
                __('The :survey closes tomorrow (:date) and you have not completed it yet.', $replace),
                __('The :survey closes in :days days (:date) and you have not completed it yet.', $replace),
            ))
            ->line(__('It takes about :minutes minutes and your answers are anonymous. If you saved a draft, you can continue where you left off.', ['minutes' => $this->survey->estimated_minutes]))
            ->action(__('Complete the Survey'), route('survey-form.show', $this->survey))
            ->line(__('Thank you for helping us improve our support services.'));
    }

    /**
     * Get the array representation of the notification (in-app notification).
     *
     * @return array{title: string, message: string, url: string, survey_id: int, days_left: int}
     */
    public function toArray(object $notifiable): array
    {
        $replace = $this->replacements();

        return [
            'title' => __('Survey deadline approaching'),
            'message' => $this->forDeadline(
                __(':survey closes today (:date). Please complete it before the deadline.', $replace),
                __(':survey closes tomorrow (:date). Please complete it before the deadline.', $replace),
                __(':survey closes in :days days (:date). Please complete it before the deadline.', $replace),
            ),
            'url' => route('survey-form.show', $this->survey, false),
            'survey_id' => $this->survey->id,
            'days_left' => $this->daysLeft,
        ];
    }

    /**
     * Values of the :survey, :days and :date placeholders.
     *
     * @return array{survey: string, days: int, date: string}
     */
    protected function replacements(): array
    {
        return [
            'survey' => $this->survey->title,
            'days' => $this->daysLeft,
            'date' => $this->survey->end_date->translatedFormat(__('d M Y')),
        ];
    }

    /**
     * The text matching the time left: the survey closes today (or already
     * ended), tomorrow or in a number of days (see Survey::deadlineText()).
     */
    protected function forDeadline(string $today, string $tomorrow, string $inDays): string
    {
        return match (true) {
            $this->daysLeft <= 0 => $today,
            $this->daysLeft === 1 => $tomorrow,
            default => $inDays,
        };
    }
}
