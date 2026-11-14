<?php

namespace App\Notifications;

use App\Models\Survey\Survey;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Reminds an employee who has not completed an open survey that its end
 * date is near (in-app notification and email).
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
        $deadline = Survey::deadlineText($this->daysLeft);

        return (new MailMessage)
            ->subject("Reminder: {$this->survey->title} closes {$deadline}")
            ->greeting("Hello {$notifiable->name},")
            ->line("The {$this->survey->title} closes {$deadline} ({$this->survey->end_date->format('d M Y')}) and you have not completed it yet.")
            ->line("It takes about {$this->survey->estimated_minutes} minutes and your answers are anonymous. If you saved a draft, you can continue where you left off.")
            ->action('Complete the Survey', route('survey-form.show', $this->survey))
            ->line('Thank you for helping us improve our support services.');
    }

    /**
     * Get the array representation of the notification (in-app notification).
     *
     * @return array{title: string, message: string, url: string, survey_id: int, days_left: int}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Survey deadline approaching',
            'message' => "{$this->survey->title} closes ".Survey::deadlineText($this->daysLeft)
                ." ({$this->survey->end_date->format('d M Y')}). Please complete it before the deadline.",
            'url' => route('survey-form.show', $this->survey, false),
            'survey_id' => $this->survey->id,
            'days_left' => $this->daysLeft,
        ];
    }
}
