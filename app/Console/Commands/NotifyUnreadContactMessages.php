<?php

namespace App\Console\Commands;

use App\Mail\UnreadContactDigestMail;
use App\Models\ContactMessage;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

#[Signature('contact:notify-unread')]
#[Description('Wysyła adminowi digest nieprzeczytanych wiadomości kontaktowych.')]
class NotifyUnreadContactMessages extends Command
{
    public function handle(): int
    {
        $unread = ContactMessage::unread()->orderByDesc('created_at')->get();

        if ($unread->isEmpty()) {
            $this->info('Brak nieprzeczytanych wiadomości — digest nie wysłany.');
            return self::SUCCESS;
        }

        $settings  = SiteSetting::current();
        $recipient = $settings->contact_email;

        // Wyślij też do wszystkich adminów jeśli mają inny adres.
        $adminEmails = User::where('role', User::ROLE_ADMIN)
            ->whereNotNull('email')
            ->pluck('email')
            ->prepend($recipient)
            ->filter()
            ->unique()
            ->values();

        $sent = 0;
        foreach ($adminEmails as $email) {
            try {
                Mail::to($email)->send(new UnreadContactDigestMail($unread, $settings->site_name));
                $sent++;
            } catch (\Throwable $e) {
                $this->error("Błąd wysyłki na {$email}: {$e->getMessage()}");
            }
        }

        $this->info("Digest o {$unread->count()} wiadomościach wysłany na {$sent} adresów.");

        return self::SUCCESS;
    }
}
