<?php

namespace App\Mail\Transport;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;

/**
 * Transport poczty wysyłający przez Microsoft Graph (`POST /users/{skrzynka}/sendMail`)
 * z uwierzytelnieniem aplikacji (client credentials). Nie wymaga SMTP AUTH,
 * który w tenantach Microsoft 365 jest coraz częściej wyłączony.
 *
 * Wymagania po stronie Azure: rejestracja aplikacji z uprawnieniem aplikacyjnym
 * Microsoft Graph → Mail.Send (ze zgodą administratora). Nadawcą jest zawsze
 * skrzynka wskazana w konfiguracji ($sender) — nagłówek From z wiadomości jest
 * ignorowany, bo wysłanie „jako” inna skrzynka wymaga dodatkowo uprawnienia SendAs.
 *
 * Zalecane: ograniczyć aplikację do jednej skrzynki zasadą
 * `New-ApplicationAccessPolicy` (Exchange Online PowerShell).
 */
class MicrosoftGraphTransport extends AbstractTransport
{
    public const TOKEN_URL = 'https://login.microsoftonline.com/%s/oauth2/v2.0/token';

    public const GRAPH_URL = 'https://graph.microsoft.com/v1.0';

    public const SCOPE = 'https://graph.microsoft.com/.default';

    public function __construct(
        private readonly string $tenantId,
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $sender,
        private readonly bool $saveToSentItems = true,
        private readonly int $timeout = 20,
    ) {
        parent::__construct();
    }

    /** Buduje transport z tablicy konfiguracji mailera (config('mail.mailers.msgraph')). */
    public static function fromConfig(array $config): self
    {
        foreach (['tenant_id', 'client_id', 'client_secret', 'sender'] as $key) {
            if (blank($config[$key] ?? null)) {
                throw new TransportException("Microsoft Graph: brak wartości „{$key}” w konfiguracji poczty.");
            }
        }

        return new self(
            (string) $config['tenant_id'],
            (string) $config['client_id'],
            (string) $config['client_secret'],
            (string) $config['sender'],
            (bool) ($config['save_to_sent_items'] ?? true),
            (int) ($config['timeout'] ?? 20),
        );
    }

    /**
     * Diagnostyka bez wysyłania wiadomości: pobiera token aplikacji i sprawdza
     * w jego treści (claim `roles`), czy aplikacja ma uprawnienie Mail.Send.
     * Nie korzysta z pamięci podręcznej, więc odzwierciedla bieżącą konfigurację.
     *
     * @param  array{tenant_id: ?string, client_id: ?string, client_secret: ?string, sender: ?string}  $config
     * @return array{ok: bool, level: 'success'|'warning'|'error', message: string}
     */
    public static function diagnose(array $config): array
    {
        foreach (['tenant_id' => 'ID tenanta', 'client_id' => 'ID aplikacji', 'client_secret' => 'sekret klienta', 'sender' => 'skrzynka nadawcza'] as $key => $label) {
            if (blank($config[$key] ?? null)) {
                return ['ok' => false, 'level' => 'error', 'message' => "Uzupełnij pole: {$label}."];
            }
        }
        if (strtolower((string) $config['tenant_id']) === 'common') {
            return ['ok' => false, 'level' => 'error', 'message' => 'ID tenanta nie może mieć wartości „common” — wpisz identyfikator katalogu (Directory ID) z Entra ID.'];
        }

        try {
            $response = \Illuminate\Support\Facades\Http::asForm()->acceptJson()->timeout(15)
                ->post(sprintf(self::TOKEN_URL, rawurlencode((string) $config['tenant_id'])), [
                    'grant_type' => 'client_credentials',
                    'client_id' => $config['client_id'],
                    'client_secret' => $config['client_secret'],
                    'scope' => self::SCOPE,
                ]);
        } catch (\Throwable $e) {
            return ['ok' => false, 'level' => 'error', 'message' => 'Brak połączenia z login.microsoftonline.com: '.$e->getMessage()];
        }

        if ($response->failed()) {
            $detail = (string) ($response->json('error_description') ?: $response->body());

            return ['ok' => false, 'level' => 'error', 'message' => 'Microsoft odrzucił dane aplikacji: '.trim(strtok($detail, "\r\n"))];
        }

        $parts = explode('.', (string) $response->json('access_token'));
        $claims = count($parts) === 3 ? json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true) : null;
        $roles = is_array($claims) ? (array) ($claims['roles'] ?? []) : [];

        if (! in_array('Mail.Send', $roles, true)) {
            return ['ok' => false, 'level' => 'warning', 'message' => 'Dane aplikacji są poprawne, ale brakuje uprawnienia aplikacyjnego Mail.Send albo zgody administratora (Entra ID → API permissions → Grant admin consent).'];
        }

        return ['ok' => true, 'level' => 'success', 'message' => 'Połączenie działa: aplikacja ma uprawnienie Mail.Send. Skrzynkę nadawczą '.$config['sender'].' sprawdzisz testową wysyłką.'];
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());

        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->timeout($this->timeout)
            ->post(self::GRAPH_URL.'/users/'.rawurlencode($this->sender).'/sendMail', [
                'message' => $this->graphMessage($email),
                'saveToSentItems' => $this->saveToSentItems,
            ]);

        if ($response->failed()) {
            $detail = $response->json('error.message') ?: $response->body();

            throw new TransportException(
                'Microsoft Graph odrzucił wiadomość (HTTP '.$response->status().'): '.$detail
            );
        }
    }

    public function __toString(): string
    {
        return 'msgraph://'.$this->sender;
    }

    /** Token aplikacji (client credentials), buforowany do minuty przed wygaśnięciem. */
    protected function accessToken(): string
    {
        $cacheKey = 'msgraph-mail-token:'.sha1($this->tenantId.'|'.$this->clientId.'|'.$this->clientSecret);

        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->timeout($this->timeout)
                ->post(sprintf(self::TOKEN_URL, rawurlencode($this->tenantId)), [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'scope' => self::SCOPE,
                ])
                ->throw();
        } catch (RequestException $e) {
            $detail = $e->response?->json('error_description') ?: $e->getMessage();

            throw new TransportException('Microsoft Graph: nie udało się pobrać tokenu: '.$detail, 0, $e);
        }

        $token = (string) $response->json('access_token');
        if ($token === '') {
            throw new TransportException('Microsoft Graph: odpowiedź tokenu bez access_token.');
        }

        $ttl = max(60, (int) $response->json('expires_in', 3600) - 60);
        Cache::put($cacheKey, $token, $ttl);

        return $token;
    }

    /**
     * Mapuje wiadomość MIME na obiekt `message` Graph API.
     *
     * @return array<string, mixed>
     */
    protected function graphMessage(Email $email): array
    {
        $html = $email->getHtmlBody();
        $text = $email->getTextBody();

        $payload = [
            'subject' => (string) $email->getSubject(),
            'body' => [
                'contentType' => $html !== null ? 'HTML' : 'Text',
                'content' => (string) ($html ?? $text ?? ''),
            ],
            'toRecipients' => $this->recipients($email->getTo()),
        ];

        if ($email->getCc() !== []) {
            $payload['ccRecipients'] = $this->recipients($email->getCc());
        }
        if ($email->getBcc() !== []) {
            $payload['bccRecipients'] = $this->recipients($email->getBcc());
        }
        if ($email->getReplyTo() !== []) {
            $payload['replyTo'] = $this->recipients($email->getReplyTo());
        }

        $attachments = [];
        foreach ($email->getAttachments() as $part) {
            $attachments[] = [
                '@odata.type' => '#microsoft.graph.fileAttachment',
                'name' => $part->getFilename() ?: 'zalacznik',
                'contentType' => $part->getMediaType().'/'.$part->getMediaSubtype(),
                'contentBytes' => base64_encode($part->getBody()),
            ];
        }
        if ($attachments !== []) {
            $payload['attachments'] = $attachments;
        }

        return $payload;
    }

    /**
     * @param  Address[]  $addresses
     * @return array<int, array{emailAddress: array{address: string, name?: string}}>
     */
    protected function recipients(array $addresses): array
    {
        return array_map(function (Address $address) {
            $entry = ['address' => $address->getAddress()];
            if ($address->getName() !== '') {
                $entry['name'] = $address->getName();
            }

            return ['emailAddress' => $entry];
        }, array_values($addresses));
    }
}
