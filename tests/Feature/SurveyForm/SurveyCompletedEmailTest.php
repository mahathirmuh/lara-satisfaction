<?php

namespace Tests\Feature\SurveyForm;

use App\Models\Survey\Survey;
use App\Notifications\SurveyCompleted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * The thank-you e-mail a respondent receives after completing a survey: a receipt that never holds the answers.
 */
class SurveyCompletedEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_thank_you_email_names_the_survey_and_when_it_was_submitted(): void
    {
        config(['mail.from.name' => 'MTI Employee Communication']);
        $survey = Survey::factory()->create(['title' => 'Operational Audit 2026']);
        $employee = $this->departmentEmployee(['name' => 'Budi Santoso [MTI]']);

        $mail = (new SurveyCompleted($survey, Carbon::parse('2026-11-17 14:30:00', 'Asia/Jakarta')))->toMail($employee);
        $html = (string) $mail->render();

        $this->assertSame('Thank you for completing Operational Audit 2026', $mail->subject);
        $this->assertSame('Open My Survey', $mail->actionText);
        $this->assertSame(route('survey-form.index'), $mail->actionUrl);
        $this->assertStringContainsString('Hello Budi Santoso,', $html);
        $this->assertStringContainsString('We have received your answers to the Operational Audit 2026.', $html);
        $this->assertStringContainsString('Submitted: 17 Nov 2026 14:30 WIB', $html);
        $this->assertStringContainsString('Your answers are anonymous: they are never linked to your name.', $html);
        $this->assertStringContainsString('MTI Employee Communication', $html);
    }

    public function test_the_thank_you_email_is_written_in_the_language_of_the_respondent(): void
    {
        $survey = Survey::factory()->create(['title' => 'Operational Audit 2026']);

        Notification::sendNow($this->departmentEmployee(['locale' => 'id']), new SurveyCompleted($survey, now()));

        $email = app('mail.manager')->mailer('array')->getSymfonyTransport()->messages()->sole()->getOriginalMessage();
        $this->assertSame('Terima kasih telah mengisi Operational Audit 2026', $email->getSubject());
        $this->assertStringContainsString('Jawaban Anda untuk Operational Audit 2026 sudah kami terima.', (string) $email->getHtmlBody());
    }
}
