<?php

namespace App\Notifications;

use App\Models\Survey\Survey;
use App\Models\UserManagement\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

/**
 * Thanks an employee by e-mail for completing a survey, as a receipt: which survey, when it was submitted (with the time
 * zone of the application, config app.timezone, which may not be the reader's), and that the answers stay anonymous. It
 * never holds the answers. Written in the recipient's language (User::preferredLocale()),
 * from the same mailbox as the reminders and in the same layout (mail.survey-reminder); queued like them
 * (SurveyDeadlineReminder), so submitting does not wait for the mail server.
 */
class SurveyCompleted extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Survey $survey, public Carbon $submittedAt) {}

    /**
     * Get the notification's delivery channels: e-mail only, the thank-you page says the rest in the application.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $replace = ['survey' => $this->survey->title];

        return (new MailMessage)
            ->subject(__('Thank you for completing :survey', $replace))
            ->greeting(__('Hello :name,', ['name' => $notifiable instanceof User ? $notifiable->plainName() : $notifiable->name]))
            ->line(__('We have received your answers to the :survey. Thank you for taking the time to complete it.', $replace))
            ->action(__('Open My Survey'), route('survey-form.index'))
            ->line(__('Thank you for helping us improve our support services.'))
            ->markdown('mail.survey-reminder', [
                'surveyTitle' => $this->survey->title,
                'details' => [
                    __('Submitted: :date', ['date' => $this->submittedAt->translatedFormat(__('d M Y H:i').' T')]),
                    __('Your answers are anonymous: they are never linked to your name.'),
                ],
                'signature' => config('mail.from.name'),
            ]);
    }
}
