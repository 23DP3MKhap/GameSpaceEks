<?php

namespace App\Jobs;

use App\Services\BrevoMailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendEmail implements ShouldQueue
{
    use Queueable;

    public $tries = 4;

    public $backoff = 60;

    public function __construct(
        public string $email,
        public string $username,
        public string $subject,
        public string $html,
        public array $attachments = [],
    ) {}

    public function handle(BrevoMailer $mailer)
    {
        $mailer->send($this->email, $this->username, $this->subject, $this->html, $this->attachments);
    }

    public function failed(Throwable $e)
    {
        Log::error("Email \"{$this->subject}\" to {$this->email} failed after {$this->tries} attempts: {$e->getMessage()}");
    }
}
