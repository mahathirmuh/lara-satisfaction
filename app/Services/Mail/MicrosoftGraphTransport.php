<?php

namespace App\Services\Mail;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;

/**
 * Sends the e-mail of the application through Microsoft Graph (POST /users/{mailbox}/sendMail) from one Exchange
 * Online mailbox, e.g. mti.communication@merdekabattery.com, with the app-only token of an Entra app registration that
 * Exchange allows to send from that mailbox (RBAC for Applications, "Application Mail.Send" in a scope of that mailbox;
 * see README). No mailbox password is kept, and it keeps working where Exchange Online no longer accepts passwords over
 * SMTP. The mailer "microsoft-graph" of config/mail.php, registered by
 * AppServiceProvider.
 */
class MicrosoftGraphTransport extends AbstractTransport
{
    /**
     * Microsoft Graph, version 1.0.
     */
    protected const GRAPH_URL = 'https://graph.microsoft.com/v1.0';

    /**
     * A token is asked for again this many seconds before it expires.
     */
    protected const TOKEN_MARGIN = 300;

    /**
     * The app-only token, kept in memory only: the queue worker sends many e-mails with one.
     */
    protected ?string $accessToken = null;

    /**
     * When the token stops being used (Unix time).
     */
    protected int $accessTokenExpiresAt = 0;

    /**
     * Create a new transport instance.
     *
     * @param  string|null  $sender  the mailbox that sends; by default the sender of each message (MAIL_FROM_ADDRESS)
     * @param  bool  $saveToSentItems  whether a copy goes to the Sent Items of the mailbox
     */
    public function __construct(
        protected string $tenantId,
        protected string $clientId,
        protected string $clientSecret,
        protected ?string $sender = null,
        protected bool $saveToSentItems = false,
    ) {
        parent::__construct();
    }

    /**
     * {@inheritDoc}
     *
     * @throws TransportException when Microsoft refuses the token or the e-mail, or cannot be reached
     */
    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $envelope = $message->getEnvelope();
        $mailbox = filled($this->sender) ? (string) $this->sender : $envelope->getSender()->getAddress();

        try {
            $response = $this->client()
                ->withToken($this->accessToken())
                ->post(self::GRAPH_URL.'/users/'.rawurlencode($mailbox).'/sendMail', [
                    'message' => $this->graphMessage($email, $envelope),
                    'saveToSentItems' => $this->saveToSentItems,
                ]);
        } catch (ConnectionException $exception) {
            throw new TransportException('Microsoft Graph could not be reached: '.$exception->getMessage(), 0, $exception);
        }

        if ($response->failed()) {
            throw new TransportException(sprintf('Microsoft Graph did not send the e-mail from %s: %s', $mailbox, $this->reason($response)), $response->status());
        }
    }

    /**
     * The message as Microsoft Graph takes it.
     *
     * @return array<string, mixed>
     */
    protected function graphMessage(Email $email, Envelope $envelope): array
    {
        $html = $email->getHtmlBody();
        $from = $email->getFrom()[0] ?? null;

        return array_filter([
            'subject' => (string) $email->getSubject(),
            'body' => $html !== null
                ? ['contentType' => 'HTML', 'content' => $this->contents($html)]
                : ['contentType' => 'Text', 'content' => $this->contents($email->getTextBody())],
            'from' => $from === null ? null : $this->recipient($from),
            'toRecipients' => array_map($this->recipient(...), $this->toRecipients($email, $envelope)),
            'ccRecipients' => array_map($this->recipient(...), $email->getCc()),
            'bccRecipients' => array_map($this->recipient(...), $email->getBcc()),
            'replyTo' => array_map($this->recipient(...), $email->getReplyTo()),
            'attachments' => $this->attachments($email),
        ], fn (mixed $value): bool => $value !== null && $value !== []);
    }

    /**
     * Whoever the message goes to, except the people in copy: the envelope may hold more than the To header.
     *
     * @return array<int, Address>
     */
    protected function toRecipients(Email $email, Envelope $envelope): array
    {
        $copied = array_map(fn (Address $address): string => strtolower($address->getAddress()), [...$email->getCc(), ...$email->getBcc()]);

        return array_values(array_filter(
            $envelope->getRecipients(),
            fn (Address $address): bool => ! in_array(strtolower($address->getAddress()), $copied, true),
        ));
    }

    /**
     * An address as Microsoft Graph takes it.
     *
     * @return array{emailAddress: array{address: string, name?: string}}
     */
    protected function recipient(Address $address): array
    {
        return ['emailAddress' => array_filter(['address' => $address->getAddress(), 'name' => $address->getName()])];
    }

    /**
     * The attached files; an inline one (an image in the text) keeps its content ID.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function attachments(Email $email): array
    {
        $attachments = [];

        foreach ($email->getAttachments() as $attachment) {
            $headers = $attachment->getPreparedHeaders();
            $filename = $headers->getHeaderParameter('Content-Disposition', 'filename') ?? 'attachment';
            $file = [
                '@odata.type' => '#microsoft.graph.fileAttachment',
                'name' => $filename,
                'contentType' => $headers->get('Content-Type')?->getBody() ?? 'application/octet-stream',
                'contentBytes' => base64_encode($attachment->getBody()),
            ];

            if ($headers->getHeaderBody('Content-Disposition') === 'inline') {
                $file['isInline'] = true;
                $file['contentId'] = $attachment->hasContentId() ? $attachment->getContentId() : $filename;
            }

            $attachments[] = $file;
        }

        return $attachments;
    }

    /**
     * An app-only token for Microsoft Graph (client credentials), asked for again shortly before it expires.
     *
     * @throws TransportException when the settings are missing or Microsoft Entra ID refuses them
     */
    protected function accessToken(): string
    {
        if ($this->accessToken !== null && now()->getTimestamp() < $this->accessTokenExpiresAt) {
            return $this->accessToken;
        }

        if (blank($this->tenantId) || blank($this->clientId) || blank($this->clientSecret)) {
            throw new TransportException('The microsoft-graph mailer needs MAIL_GRAPH_TENANT_ID, MAIL_GRAPH_CLIENT_ID and MAIL_GRAPH_CLIENT_SECRET (or MS_GRAPH_*).');
        }

        $response = $this->client()->asForm()->post('https://login.microsoftonline.com/'.rawurlencode($this->tenantId).'/oauth2/v2.0/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'scope' => 'https://graph.microsoft.com/.default',
        ]);
        $token = $response->json('access_token');

        if ($response->failed() || ! is_string($token) || $token === '') {
            throw new TransportException('Microsoft Entra ID gave no token for sending e-mail: '.$this->reason($response), $response->status());
        }

        $this->accessToken = $token;
        $this->accessTokenExpiresAt = now()->getTimestamp() + max(60, (int) $response->json('expires_in', 3600) - self::TOKEN_MARGIN);

        return $token;
    }

    /**
     * Why Microsoft refused, from its answer: the error code and message of Graph, or the error of Entra ID with the
     * first line of its description (the rest is trace and correlation IDs).
     */
    protected function reason(Response $response): string
    {
        $error = $response->json('error');
        $reason = match (true) {
            is_array($error) => trim(($error['code'] ?? '').': '.($error['message'] ?? ''), ': '),
            is_string($error) => trim($error.': '.strtok((string) $response->json('error_description', ''), "\r\n"), ': '),
            default => mb_substr(trim($response->body()), 0, 200),
        };

        return trim('HTTP '.$response->status().' '.$reason);
    }

    /**
     * The text of a body part, which may also be a stream.
     *
     * @param  resource|string|null  $body
     */
    protected function contents(mixed $body): string
    {
        return is_resource($body) ? (string) stream_get_contents($body) : (string) $body;
    }

    /**
     * The HTTP client for Entra ID and Graph.
     */
    protected function client(): PendingRequest
    {
        return Http::acceptJson()->connectTimeout(10)->timeout(30);
    }

    /**
     * Get the string representation of the transport.
     */
    public function __toString(): string
    {
        return 'microsoft-graph';
    }
}
