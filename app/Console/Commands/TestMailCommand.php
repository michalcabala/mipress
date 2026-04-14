<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

#[Signature('mail:test {to=test@example.com}')]
#[Description('Odešle testovací e-mail do Mailpitu')]
class TestMailCommand extends Command
{
    public function handle(): int
    {
        $to = $this->argument('to');

        $this->info("Odesílám testovací mail na {$to}...");

        Mail::raw('Testovací e-mail z miPress. Pokud to vidíte v Mailpitu, vše funguje správně.', function ($message) use ($to) {
            $message->to($to)->subject('miPress — Test Mailpit');
        });

        $this->info('Mail odeslán! Zkontrolujte Mailpit na http://localhost:8025');

        return self::SUCCESS;
    }
}
