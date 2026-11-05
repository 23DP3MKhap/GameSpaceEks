<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class BrevoMailer
{
    public function send(string $email, string $username, string $subject, string $html, array $attachments = [])
    {
        $payload = [
            'sender' => [
                'name' => 'GameSpace',
                'email' => config('mail.from.address'),
            ],
            'to' => [
                ['email' => $email, 'name' => $username],
            ],
            'subject' => $subject,
            'htmlContent' => $html,
        ];

        if ($attachments) {
            $payload['attachment'] = $attachments;
        }

        return Http::timeout(10)
            ->withHeaders(['api-key' => config('services.brevo.key')])
            ->post('https://api.brevo.com/v3/smtp/email', $payload)
            ->throw()
            ->json();
    }
}
