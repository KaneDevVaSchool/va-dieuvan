<?php

namespace App\Console\Commands;

use App\Services\P2pPolicy\P2pPolicyDepartReminderService;
use Illuminate\Console\Command;

class SendP2pPolicyDepartRemindersCommand extends Command
{
    protected $signature = 'policy:send-depart-reminders';

    protected $description = 'Send P2P policy trip depart reminders to drivers, activators, and dispatchers.';

    public function handle(P2pPolicyDepartReminderService $service): int
    {
        $count = $service->sendDueReminders();
        $this->info('P2P depart reminders sent for '.$count.' slot(s).');

        return self::SUCCESS;
    }
}
