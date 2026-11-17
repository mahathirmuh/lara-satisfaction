<?php

namespace Tests\Feature\Mail;

use App\Models\Survey\Survey;
use App\Models\UserManagement\User;
use App\Notifications\SurveyDeadlineReminder;
use Illuminate\Http\Client\Request;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

/**
 * The "microsoft-graph" mailer sends the e-mail of the application through Microsoft Graph from one mailbox
 * (MAIL_FROM_ADDRESS), with an app-only token of the Entra app registration.
 */
class MicrosoftGraphTransportTest extends TestCase
{
    protected const SENDER = 'mti.communication@merdekabattery.com';

    protected const TOKEN_URL = 'https://login.microsoftonline.com/tenant-id/oauth2/v2.0/token';

    protected const SEND_URL = 'https://graph.microsoft.com/v1.0/users/mti.communication%40merdekabattery.com/sendMail';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'mail.default' => 'microsoft-graph',
            'mail.from' => ['address' => self::SENDER, 'name' => 'Employee Satisfaction Survey'],
            'mail.mailers.microsoft-graph' => [
                'transport' => 'microsoft-graph',
                'tenant_id' => 'tenant-id',
                'client_id' => 'client-id',
                'client_secret' => 'client-secret',
                'sender' => self::SENDER,
                'save_to_sent_items' => false,
            ],
        ]);
        Http::preventStrayRequests();
    }

    public function test_a_survey_reminder_is_sent_from_the_communication_mailbox(): void
    {
        $this->fakeMicrosoft();
        $survey = Survey::factory()->make(['id' => 7, 'title' => 'Operational Audit 2026', 'end_date' => '2026-11-27']);
        $employee = User::factory()->make(['name' => 'Ani', 'email' => 'ani@merdekabattery.com']);

        Notification::sendNow($employee, new SurveyDeadlineReminder($survey, 14), ['mail']);

        Http::assertSent(fn (Request $request): bool => $request->url() === self::TOKEN_URL
            && $request['grant_type'] === 'client_credentials'
            && $request['client_id'] === 'client-id'
            && $request['client_secret'] === 'client-secret'
            && $request['scope'] === 'https://graph.microsoft.com/.default');
        Http::assertSent(function (Request $request): bool {
            if ($request->url() !== self::SEND_URL) {
                return false;
            }

            $message = $request['message'];

            return $request->hasHeader('Authorization', 'Bearer graph-token')
                && $request['saveToSentItems'] === false
                && $message['subject'] === 'Reminder: Operational Audit 2026 closes in 14 days'
                && $message['from'] === ['emailAddress' => ['address' => self::SENDER, 'name' => 'Employee Satisfaction Survey']]
                && $message['toRecipients'] === [['emailAddress' => ['address' => 'ani@merdekabattery.com']]]
                && $message['body']['contentType'] === 'HTML'
                && str_contains($message['body']['content'], route('survey-form.show', 7))
                // The company logos go inside the e-mail, as inline pictures of its text.
                && count($message['attachments']) === 3
                && collect($message['attachments'])->every(fn (array $logo): bool => $logo['isInline'] === true
                    && str_contains($message['body']['content'], 'cid:'.$logo['contentId']));
        });
    }

    public function test_copies_reply_to_and_attachments_go_with_the_e_mail(): void
    {
        $this->fakeMicrosoft();

        Mail::raw('Hello', fn (Message $message) => $message
            ->to('budi@merdekabattery.com', 'Budi')
            ->cc('ani@merdekabattery.com')
            ->bcc('audit@merdekabattery.com')
            ->replyTo('hr@merdekabattery.com', 'HR')
            ->subject('Note')
            ->attachData('hello', 'note.txt', ['mime' => 'text/plain']));

        Http::assertSent(fn (Request $request): bool => $request->url() === self::SEND_URL && $request['message'] === [
            'subject' => 'Note',
            'body' => ['contentType' => 'Text', 'content' => 'Hello'],
            'from' => ['emailAddress' => ['address' => self::SENDER, 'name' => 'Employee Satisfaction Survey']],
            'toRecipients' => [['emailAddress' => ['address' => 'budi@merdekabattery.com', 'name' => 'Budi']]],
            'ccRecipients' => [['emailAddress' => ['address' => 'ani@merdekabattery.com']]],
            'bccRecipients' => [['emailAddress' => ['address' => 'audit@merdekabattery.com']]],
            'replyTo' => [['emailAddress' => ['address' => 'hr@merdekabattery.com', 'name' => 'HR']]],
            'attachments' => [[
                '@odata.type' => '#microsoft.graph.fileAttachment',
                'name' => 'note.txt',
                'contentType' => 'text/plain',
                'contentBytes' => base64_encode('hello'),
            ]],
        ]);
    }

    public function test_one_token_serves_many_e_mails(): void
    {
        $this->fakeMicrosoft();

        Mail::raw('First', fn (Message $message) => $message->to('budi@merdekabattery.com')->subject('First'));
        Mail::raw('Second', fn (Message $message) => $message->to('ani@merdekabattery.com')->subject('Second'));

        $this->assertCount(1, Http::recorded(fn (Request $request): bool => $request->url() === self::TOKEN_URL));
        $this->assertCount(2, Http::recorded(fn (Request $request): bool => $request->url() === self::SEND_URL));
    }

    /**
     * @return array<string, array{0: int, 1: int, 2: array<string, mixed>, 3: string}>
     */
    public static function refusals(): array
    {
        return [
            'the app registration' => [
                401, 202,
                ['error' => 'invalid_client', 'error_description' => "AADSTS7000215: Invalid client secret provided.\r\nTrace ID: 9f1c"],
                'Microsoft Entra ID gave no token for sending e-mail: HTTP 401 invalid_client: AADSTS7000215: Invalid client secret provided.',
            ],
            'the mailbox (no Mail.Send permission)' => [
                200, 403,
                ['error' => ['code' => 'ErrorAccessDenied', 'message' => 'Access is denied. Check credentials and try again.']],
                'Microsoft Graph did not send the e-mail from mti.communication@merdekabattery.com: HTTP 403 ErrorAccessDenied: Access is denied. Check credentials and try again.',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $error
     */
    #[DataProvider('refusals')]
    public function test_a_refusal_from_microsoft_fails_the_e_mail_with_its_reason(int $tokenStatus, int $sendStatus, array $error, string $reason): void
    {
        Http::fake([
            self::TOKEN_URL => $tokenStatus === 200
                ? Http::response(['token_type' => 'Bearer', 'expires_in' => 3599, 'access_token' => 'graph-token'])
                : Http::response($error, $tokenStatus),
            self::SEND_URL => Http::response($sendStatus === 202 ? null : $error, $sendStatus),
        ]);

        try {
            Mail::raw('Hello', fn (Message $message) => $message->to('budi@merdekabattery.com')->subject('Note'));
            $this->fail('The e-mail was taken as sent.');
        } catch (TransportException $exception) {
            $this->assertSame($reason, $exception->getMessage());
            $this->assertStringNotContainsString('client-secret', $exception->getMessage());
        }
    }

    public function test_the_mailer_without_an_app_registration_asks_for_its_settings(): void
    {
        config(['mail.mailers.microsoft-graph.client_secret' => '']);

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('The microsoft-graph mailer needs MAIL_GRAPH_TENANT_ID, MAIL_GRAPH_CLIENT_ID and MAIL_GRAPH_CLIENT_SECRET (or MS_GRAPH_*).');

        Mail::raw('Hello', fn (Message $message) => $message->to('budi@merdekabattery.com')->subject('Note'));
    }

    /**
     * Microsoft Entra ID gives a token and Graph accepts every e-mail.
     */
    protected function fakeMicrosoft(): void
    {
        Http::fake([
            self::TOKEN_URL => Http::response(['token_type' => 'Bearer', 'expires_in' => 3599, 'access_token' => 'graph-token']),
            self::SEND_URL => Http::response(status: 202),
        ]);
    }
}
